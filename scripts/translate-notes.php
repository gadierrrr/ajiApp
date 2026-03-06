#!/usr/bin/env php
<?php
/**
 * Batch-translate beach notes to Spanish using Claude API.
 *
 * Usage:
 *   php scripts/translate-notes.php [--limit=250] [--dry-run]
 */

if (php_sapi_name() !== 'cli') die("CLI only.\n");

require_once __DIR__ . '/../inc/db.php';

// Load .env
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with($line, '#')) continue;
        putenv($line);
    }
}

$apiKey = getenv('ANTHROPIC_API_KEY');
if (!$apiKey) die("ANTHROPIC_API_KEY not set.\n");

$limit = 250;
$dryRun = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) $limit = (int) substr($arg, 8);
    if ($arg === '--dry-run') $dryRun = true;
}

echo "=== Beach Notes Translation Script ===\n";
echo "Limit: {$limit} beaches per run" . ($dryRun ? " (DRY RUN)\n" : "\n");

// Find beaches with notes but no notes_es
$beaches = query("
    SELECT id, slug, name, municipality, notes
    FROM beaches
    WHERE publish_status = 'published'
      AND notes IS NOT NULL AND notes <> ''
      AND (notes_es IS NULL OR notes_es = '')
    ORDER BY google_review_count DESC
    LIMIT {$limit}
") ?: [];

$total = count($beaches);
echo "Found {$total} beaches needing notes translation.\n\n";

// Batch them in groups of 10 for efficiency
$batches = array_chunk($beaches, 10);
$translated = 0;
$errors = 0;

foreach ($batches as $batchIdx => $batch) {
    $batchNum = $batchIdx + 1;
    $batchSize = count($batch);
    echo "Batch {$batchNum}/" . count($batches) . " ({$batchSize} beaches)... ";

    if ($dryRun) {
        echo "SKIP (dry run)\n";
        continue;
    }

    // Build a map of id => notes for translation
    $fieldsToTranslate = [];
    foreach ($batch as $b) {
        $fieldsToTranslate["note_{$b['id']}"] = $b['notes'];
    }

    $json = json_encode($fieldsToTranslate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $prompt = <<<PROMPT
Translate the following beach notes from English to Spanish (Puerto Rico / Latin American Spanish).

Rules:
- Keep proper nouns (beach names, place names, road numbers like PR-115) unchanged
- Keep numbers, units (mph, °F, km), and dollar amounts unchanged
- Keep source references in parentheses unchanged (e.g., "(OSM; Google)")
- Use informal "tú" form
- Keep translations concise and natural
- Output ONLY a valid JSON object with the same keys, Spanish values

Input:
{$json}
PROMPT;

    $result = callClaude($apiKey, $prompt);
    if ($result === null) {
        echo "API ERROR\n";
        $errors++;
        continue;
    }

    $jsonStr = extractJson($result);
    if (!$jsonStr) {
        echo "PARSE ERROR\n";
        $errors++;
        continue;
    }

    $translations = json_decode($jsonStr, true);
    if (!is_array($translations)) {
        echo "JSON ERROR\n";
        $errors++;
        continue;
    }

    $applied = 0;
    foreach ($batch as $b) {
        $key = "note_{$b['id']}";
        if (isset($translations[$key])) {
            execute("UPDATE beaches SET notes_es = :val WHERE id = :id",
                [':val' => $translations[$key], ':id' => $b['id']]);
            $applied++;
        }
    }

    echo "OK ({$applied} applied)\n";
    $translated += $applied;

    usleep(200000); // 200ms between requests
}

echo "\n=== Done ===\n";
echo "Translated: {$translated}, Errors: {$errors}\n";

$remaining = queryOne("
    SELECT COUNT(*) as cnt FROM beaches
    WHERE publish_status = 'published'
      AND notes IS NOT NULL AND notes <> ''
      AND (notes_es IS NULL OR notes_es = '')
");
echo "Notes still needing translation: {$remaining['cnt']}\n";

function callClaude(string $apiKey, string $prompt): ?string {
    $payload = json_encode([
        'model' => 'claude-haiku-4-5-20251001',
        'max_tokens' => 4096,
        'messages' => [['role' => 'user', 'content' => $prompt]],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . $apiKey,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 60,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        $err = json_decode($response, true);
        $msg = $err['error']['message'] ?? 'Unknown error';
        fwrite(STDERR, "  API {$httpCode}: {$msg}\n");
        return null;
    }

    $data = json_decode($response, true);
    return $data['content'][0]['text'] ?? null;
}

function extractJson(string $text): ?string {
    if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
        return $m[0];
    }
    return null;
}
