#!/usr/bin/env php
<?php
/**
 * Batch content improvement using Anthropic Batches API.
 *
 * Usage:
 *   php batch-improve-content.php submit          # Submit batch
 *   php batch-improve-content.php poll BATCH_ID    # Check status
 *   php batch-improve-content.php apply BATCH_ID   # Download results & apply
 */

if (php_sapi_name() !== 'cli') die("CLI only.\n");

require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

$apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY');
if (!$apiKey) die("ANTHROPIC_API_KEY not set.\n");

$action = $argv[1] ?? 'help';
$batchId = $argv[2] ?? null;

switch ($action) {
    case 'submit': submitBatch($apiKey); break;
    case 'poll':   pollBatch($apiKey, $batchId); break;
    case 'apply':  applyBatch($apiKey, $batchId); break;
    default:
        echo "Usage:\n";
        echo "  php batch-improve-content.php submit\n";
        echo "  php batch-improve-content.php poll BATCH_ID\n";
        echo "  php batch-improve-content.php apply BATCH_ID\n";
}

function submitBatch(string $apiKey): void {
    // Fetch eligible beaches
    $beaches = query(
        "SELECT id, slug, name, municipality, description, lat, lng
         FROM beaches WHERE publish_status = 'published' AND description_es IS NULL
         ORDER BY name ASC"
    ) ?: [];

    $count = count($beaches);
    echo "Building batch for {$count} beaches...\n";

    $requests = [];
    foreach ($beaches as $beach) {
        $sections = query(
            "SELECT id, section_type, heading, content FROM beach_content_sections
             WHERE beach_id = :bid AND status = 'published' ORDER BY display_order ASC",
            [':bid' => $beach['id']]
        ) ?: [];

        $tags = array_column(
            query('SELECT tag FROM beach_tags WHERE beach_id = :bid', [':bid' => $beach['id']]) ?: [],
            'tag'
        );

        if (empty($sections)) continue;

        $prompt = buildPromptForBatch($beach, $tags, $sections);
        $requests[] = [
            'custom_id' => 'b-' . $beach['id'],
            'params' => [
                'model' => 'claude-haiku-4-5-20251001',
                'max_tokens' => 8000,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ],
        ];
    }

    echo "Submitting " . count($requests) . " requests to Batches API...\n";

    $response = curlPost($apiKey, 'https://api.anthropic.com/v1/messages/batches', [
        'requests' => $requests,
    ]);

    if (isset($response['id'])) {
        echo "\nBatch submitted successfully!\n";
        echo "  Batch ID: {$response['id']}\n";
        echo "  Status:   {$response['processing_status']}\n";
        echo "\nNext: php batch-improve-content.php poll {$response['id']}\n";
    } else {
        echo "Error submitting batch:\n";
        print_r($response);
    }
}

function pollBatch(string $apiKey, ?string $batchId): void {
    if (!$batchId) die("Usage: php batch-improve-content.php poll BATCH_ID\n");

    $response = curlGet($apiKey, "https://api.anthropic.com/v1/messages/batches/{$batchId}");

    echo "Batch: {$batchId}\n";
    echo "Status: {$response['processing_status']}\n";

    $counts = $response['request_counts'] ?? [];
    $total = ($counts['processing'] ?? 0) + ($counts['succeeded'] ?? 0)
           + ($counts['errored'] ?? 0) + ($counts['canceled'] ?? 0) + ($counts['expired'] ?? 0);
    echo "Succeeded: " . ($counts['succeeded'] ?? 0) . "/{$total}\n";
    echo "Errored:   " . ($counts['errored'] ?? 0) . "\n";
    echo "Processing: " . ($counts['processing'] ?? 0) . "\n";

    if ($response['processing_status'] === 'ended') {
        echo "\nBatch complete! Run: php batch-improve-content.php apply {$batchId}\n";
    }
}

function applyBatch(string $apiKey, ?string $batchId): void {
    if (!$batchId) die("Usage: php batch-improve-content.php apply BATCH_ID\n");

    echo "Fetching batch results...\n";

    // Stream results from the batch
    $url = "https://api.anthropic.com/v1/messages/batches/{$batchId}/results";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 300,
    ]);
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Error fetching results (HTTP {$httpCode}):\n{$body}\n";
        return;
    }

    // Results are JSONL (one JSON object per line)
    $lines = explode("\n", trim($body));
    echo "Got " . count($lines) . " results.\n\n";

    $succeeded = 0;
    $failed = 0;

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') continue;

        $item = json_decode($line, true);
        if (!$item) continue;

        $customId = $item['custom_id'] ?? 'unknown';
        $beachId = preg_replace('/^b-/', '', $customId);
        $resultType = $item['result']['type'] ?? 'unknown';

        if ($resultType !== 'succeeded') {
            echo "  FAIL {$customId}: {$resultType}\n";
            $failed++;
            continue;
        }

        // Extract the text content from the message response
        $text = $item['result']['message']['content'][0]['text'] ?? '';
        if ($text === '') {
            echo "  FAIL {$customId}: empty response\n";
            $failed++;
            continue;
        }

        // Parse JSON (strip code fences)
        $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $text);
        $cleaned = preg_replace('/```\s*$/m', '', $cleaned);
        $parsed = json_decode(trim($cleaned), true);

        if (!$parsed || !isset($parsed['description'], $parsed['description_es'], $parsed['sections'])) {
            echo "  FAIL {$customId}: invalid JSON structure\n";
            $failed++;
            continue;
        }

        // Look up beach
        $beach = queryOne('SELECT id, slug, description FROM beaches WHERE id = :id', [':id' => $beachId]);
        if (!$beach) {
            echo "  SKIP {$customId}: beach not found\n";
            continue;
        }
        $slug = $beach['slug'];

        $sections = query(
            "SELECT id, section_type FROM beach_content_sections
             WHERE beach_id = :bid AND status = 'published'",
            [':bid' => $beach['id']]
        ) ?: [];

        // Apply updates in transaction
        $db = getDb();
        $db->exec('BEGIN IMMEDIATE');
        try {
            $newDesc = trim($parsed['description']);
            $newDescEs = trim($parsed['description_es']);

            if ($newDesc === '' || $newDescEs === '') {
                throw new RuntimeException('Empty description');
            }

            execute(
                'UPDATE beaches SET description = :desc, description_es = :desc_es WHERE id = :id',
                [':desc' => $newDesc, ':desc_es' => $newDescEs, ':id' => $beach['id']]
            );

            $secUpdated = 0;
            foreach ($sections as $sec) {
                $type = $sec['section_type'];
                if (!isset($parsed['sections'][$type])) continue;

                $s = $parsed['sections'][$type];
                $hes = trim($s['heading_es'] ?? '');
                $cen = trim($s['content'] ?? '');
                $ces = trim($s['content_es'] ?? '');

                if ($hes === '' || $cen === '' || $ces === '') continue;

                execute(
                    'UPDATE beach_content_sections SET content = :c, heading_es = :hes, content_es = :ces WHERE id = :id',
                    [':c' => sanitizeContentHtml($cen), ':hes' => $hes, ':ces' => sanitizeContentHtml($ces), ':id' => $sec['id']]
                );
                $secUpdated++;
            }

            $db->exec('COMMIT');
            $descBefore = mb_strlen($beach['description'] ?? '');
            $descAfter = mb_strlen($newDesc);
            echo "    OK {$slug}: desc {$descBefore}->{$descAfter}, {$secUpdated} sections\n";
            $succeeded++;
        } catch (\Throwable $e) {
            $db->exec('ROLLBACK');
            echo "  FAIL {$slug}: {$e->getMessage()}\n";
            $failed++;
        }
    }

    echo "\nDone! Succeeded: {$succeeded}, Failed: {$failed}\n";
    $remaining = queryOne('SELECT COUNT(*) as c FROM beaches WHERE publish_status="published" AND description_es IS NULL');
    echo "Remaining: {$remaining['c']}\n";
}

function buildPromptForBatch(array $beach, array $tags, array $sections): string {
    $descLen = mb_strlen($beach['description'] ?? '');
    $needsExpand = $descLen < 300;
    $sectionJson = json_encode(array_map(fn($s) => [
        'section_type' => $s['section_type'],
        'heading' => $s['heading'],
        'content' => $s['content'],
    ], $sections), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $tagsStr = implode(', ', $tags);
    $descInstruction = $needsExpand
        ? "The current English description is only {$descLen} characters. Expand it to 400-500 characters while keeping factual accuracy. Do NOT invent amenities or features not implied by the tags/sections."
        : "The current English description is {$descLen} characters, which is adequate. Return it unchanged in the \"description\" field.";

    return <<<PROMPT
You are a bilingual (English/Spanish) travel content editor for Puerto Rico beaches.

Beach: {$beach['name']}
Municipality: {$beach['municipality']}
Coordinates: {$beach['lat']}, {$beach['lng']}
Tags: {$tagsStr}
Current description ({$descLen} chars):
{$beach['description']}

Current content sections (plain text):
{$sectionJson}

TASKS:
1. DESCRIPTION
   {$descInstruction}

2. DESCRIPTION_ES
   Translate the final English description to natural Latin American Spanish. Keep it the same length range.

3. REFORMAT SECTIONS
   Convert each section's plain-text content into structured HTML following these rules:
Formatting rules per section_type (use ONLY these HTML tags: <p>, <ul>, <ol>, <li>, <strong>, <em>, <h3>, <br>):
- history: 2-3 <p> paragraphs. Use <strong> for key facts (dates, locations, notable features).
- best_time: Opening <p>, then <ul> with seasonal bullet points. <strong> for month ranges.
- getting_there: Overview <p>, then <ol> numbered directions. <strong> for drive times/distances. Separate <p> for parking info.
- what_to_bring: <ul> bullet lists grouped by purpose. <strong> for item names.
- nearby: Intro <p>, then <ul> list of attractions. <strong> for place names.
- local_tips: <ul> bullet list. <strong> for the key takeaway per tip.
   Keep the factual content intact. Improve readability with proper HTML structure. Do NOT add new facts.

4. TRANSLATE SECTIONS
   For each section, translate:
   - The heading to Spanish (heading_es)
   - The HTML content to Spanish (content_es) preserving identical HTML structure

Return ONLY valid JSON (no markdown fences, no explanation) matching this exact schema:
{
  "description": "expanded or unchanged English description (string, 300-500 chars)",
  "description_es": "Spanish translation of description (string)",
  "sections": {
    "<section_type>": {
      "heading_es": "Spanish heading",
      "content": "reformatted HTML English content",
      "content_es": "HTML Spanish content"
    }
  }
}

The "sections" object must have a key for every section_type present in the input. Do not omit any.
PROMPT;
}

function curlPost(string $apiKey, string $url, array $data): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 300,
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return json_decode($response, true) ?: ['error' => "HTTP {$httpCode}", 'body' => $response];
}

function curlGet(string $apiKey, string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 120,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true) ?: ['error' => 'parse failed'];
}
