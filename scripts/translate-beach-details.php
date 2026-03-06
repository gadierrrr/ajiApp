#!/usr/bin/env php
<?php
/**
 * Batch-translate beach detail fields (safety_info, best_time, parking_details,
 * features, tips) to Spanish using Claude API.
 *
 * Usage:
 *   php scripts/translate-beach-details.php [--limit=50] [--dry-run]
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

$limit = 50;
$dryRun = false;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--limit=')) $limit = (int) substr($arg, 8);
    if ($arg === '--dry-run') $dryRun = true;
}

echo "=== Beach Detail Translation Script ===\n";
echo "Limit: {$limit} beaches per run" . ($dryRun ? " (DRY RUN)\n" : "\n");

// Find beaches needing translation (any field missing _es)
$beaches = query("
    SELECT b.id, b.slug, b.name, b.municipality,
           b.safety_info, b.safety_info_es,
           b.best_time, b.best_time_es,
           b.parking_details, b.parking_details_es
    FROM beaches b
    WHERE b.publish_status = 'published'
      AND (
        (b.safety_info IS NOT NULL AND b.safety_info <> '' AND (b.safety_info_es IS NULL OR b.safety_info_es = ''))
        OR (b.best_time IS NOT NULL AND b.best_time <> '' AND (b.best_time_es IS NULL OR b.best_time_es = ''))
        OR (b.parking_details IS NOT NULL AND b.parking_details <> '' AND (b.parking_details_es IS NULL OR b.parking_details_es = ''))
        OR EXISTS (SELECT 1 FROM beach_features f WHERE f.beach_id = b.id AND (f.title_es IS NULL OR f.title_es = ''))
        OR EXISTS (SELECT 1 FROM beach_tips t WHERE t.beach_id = b.id AND (t.tip_es IS NULL OR t.tip_es = ''))
      )
    ORDER BY b.google_review_count DESC
    LIMIT {$limit}
") ?: [];

$total = count($beaches);
echo "Found {$total} beaches needing translation.\n\n";

$translated = 0;
$errors = 0;

foreach ($beaches as $i => $beach) {
    $n = $i + 1;
    echo "[{$n}/{$total}] {$beach['name']} ({$beach['municipality']})... ";

    // Gather all English content for this beach
    $features = query(
        "SELECT id, title, description FROM beach_features WHERE beach_id = :id AND (title_es IS NULL OR title_es = '') ORDER BY position",
        [':id' => $beach['id']]
    ) ?: [];

    $tips = query(
        "SELECT id, category, tip FROM beach_tips WHERE beach_id = :id AND (tip_es IS NULL OR tip_es = '') ORDER BY position",
        [':id' => $beach['id']]
    ) ?: [];

    $fieldsToTranslate = [];

    if (!empty($beach['safety_info']) && empty($beach['safety_info_es'])) {
        $fieldsToTranslate['safety_info'] = $beach['safety_info'];
    }
    if (!empty($beach['best_time']) && empty($beach['best_time_es'])) {
        $fieldsToTranslate['best_time'] = $beach['best_time'];
    }
    if (!empty($beach['parking_details']) && empty($beach['parking_details_es'])) {
        $fieldsToTranslate['parking_details'] = $beach['parking_details'];
    }
    foreach ($features as $f) {
        $fieldsToTranslate["feature_{$f['id']}_title"] = $f['title'];
        $fieldsToTranslate["feature_{$f['id']}_desc"] = $f['description'];
    }
    foreach ($tips as $t) {
        $fieldsToTranslate["tip_{$t['id']}"] = $t['tip'];
    }

    if (empty($fieldsToTranslate)) {
        echo "nothing to translate.\n";
        continue;
    }

    $fieldCount = count($fieldsToTranslate);
    echo "{$fieldCount} fields... ";

    if ($dryRun) {
        echo "SKIP (dry run)\n";
        continue;
    }

    // Build prompt
    $json = json_encode($fieldsToTranslate, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $prompt = <<<PROMPT
Translate the following beach information from English to Spanish (Puerto Rico / Latin American Spanish).

Rules:
- Keep proper nouns (beach names, place names, road numbers like PR-115) unchanged
- Keep numbers, units (mph, °F, km), and dollar amounts unchanged
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

    // Parse JSON from response
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

    // Apply translations
    $applied = 0;

    if (isset($translations['safety_info'])) {
        execute("UPDATE beaches SET safety_info_es = :val WHERE id = :id",
            [':val' => $translations['safety_info'], ':id' => $beach['id']]);
        $applied++;
    }
    if (isset($translations['best_time'])) {
        execute("UPDATE beaches SET best_time_es = :val WHERE id = :id",
            [':val' => $translations['best_time'], ':id' => $beach['id']]);
        $applied++;
    }
    if (isset($translations['parking_details'])) {
        execute("UPDATE beaches SET parking_details_es = :val WHERE id = :id",
            [':val' => $translations['parking_details'], ':id' => $beach['id']]);
        $applied++;
    }

    foreach ($features as $f) {
        $titleKey = "feature_{$f['id']}_title";
        $descKey = "feature_{$f['id']}_desc";
        $titleEs = $translations[$titleKey] ?? null;
        $descEs = $translations[$descKey] ?? null;
        if ($titleEs || $descEs) {
            execute("UPDATE beach_features SET title_es = :t, description_es = :d WHERE id = :id", [
                ':t' => $titleEs ?? $f['title'],
                ':d' => $descEs ?? $f['description'],
                ':id' => $f['id'],
            ]);
            $applied++;
        }
    }

    foreach ($tips as $t) {
        $tipKey = "tip_{$t['id']}";
        if (isset($translations[$tipKey])) {
            execute("UPDATE beach_tips SET tip_es = :val WHERE id = :id",
                [':val' => $translations[$tipKey], ':id' => $t['id']]);
            $applied++;
        }
    }

    echo "OK ({$applied} applied)\n";
    $translated++;

    // Rate limiting
    usleep(200000); // 200ms between requests
}

echo "\n=== Done ===\n";
echo "Translated: {$translated}, Errors: {$errors}, Remaining: " . ($total - $translated - $errors) . "\n";

// Check how many beaches still need translation
$remaining = queryOne("
    SELECT COUNT(*) as cnt FROM beaches b
    WHERE b.publish_status = 'published'
      AND (
        (b.safety_info IS NOT NULL AND b.safety_info <> '' AND (b.safety_info_es IS NULL OR b.safety_info_es = ''))
        OR (b.best_time IS NOT NULL AND b.best_time <> '' AND (b.best_time_es IS NULL OR b.best_time_es = ''))
        OR (b.parking_details IS NOT NULL AND b.parking_details <> '' AND (b.parking_details_es IS NULL OR b.parking_details_es = ''))
        OR EXISTS (SELECT 1 FROM beach_features f WHERE f.beach_id = b.id AND (f.title_es IS NULL OR f.title_es = ''))
        OR EXISTS (SELECT 1 FROM beach_tips t WHERE t.beach_id = b.id AND (t.tip_es IS NULL OR t.tip_es = ''))
      )
");
echo "Beaches still needing translation: {$remaining['cnt']}\n";

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
    // Try to find JSON object in response
    if (preg_match('/\{[\s\S]*\}/', $text, $m)) {
        return $m[0];
    }
    return null;
}
