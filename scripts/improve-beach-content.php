#!/usr/bin/env php
<?php
/**
 * Improve Beach Content CLI
 *
 * Uses the Claude API to:
 *   A) Expand short English descriptions (< 300 chars) to 400-500 chars
 *   B) Translate descriptions to Spanish (beaches.description_es)
 *   C) Reformat content sections from plain text to structured HTML
 *   D) Translate content sections to Spanish (heading_es, content_es)
 *
 * Usage:
 *   php improve-beach-content.php [options]
 *
 * Options:
 *   --dry-run          Preview changes without writing to DB
 *   --offset=N         Skip first N beaches
 *   --limit=N          Process only N beaches
 *   --beach=SLUG       Process a single beach by slug
 *   --force            Re-process beaches even if description_es is set
 *   --help             Show this help message
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

// Load project dependencies
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/helpers.php';

// ========================================================================
// CLI helpers
// ========================================================================

function cli_color(string $text, string $color): string {
    $codes = [
        'red'    => "\033[31m",
        'green'  => "\033[32m",
        'yellow' => "\033[33m",
        'blue'   => "\033[34m",
        'cyan'   => "\033[36m",
        'dim'    => "\033[2m",
        'reset'  => "\033[0m",
    ];
    return ($codes[$color] ?? '') . $text . $codes['reset'];
}

function cli_log(string $msg): void {
    echo $msg . "\n";
}

function cli_ok(string $msg): void {
    echo cli_color("  OK ", 'green') . $msg . "\n";
}

function cli_skip(string $msg): void {
    echo cli_color("SKIP ", 'yellow') . $msg . "\n";
}

function cli_err(string $msg): void {
    echo cli_color(" ERR ", 'red') . $msg . "\n";
}

function cli_info(string $msg): void {
    echo cli_color("INFO ", 'cyan') . $msg . "\n";
}

// ========================================================================
// Argument parsing
// ========================================================================

function parseCliArgs(array $argv): array {
    $opts = [];
    foreach ($argv as $arg) {
        if (strpos($arg, '--') === 0) {
            $parts = explode('=', substr($arg, 2), 2);
            $opts[$parts[0]] = $parts[1] ?? true;
        }
    }
    return $opts;
}

function showHelp(): void {
    global $argv;
    $name = basename($argv[0]);
    echo <<<HELP

\033[34mImprove Beach Content\033[0m
======================================================================
Expand descriptions, reformat sections to HTML, translate to Spanish.

Usage:
  php {$name} [options]

Options:
  --dry-run          Preview changes without writing to DB
  --offset=N         Skip first N beaches
  --limit=N          Process only N beaches
  --beach=SLUG       Process a single beach by slug
  --force            Re-process even if description_es already set
  --help             Show this help

Examples:
  php {$name} --dry-run --limit=5
  php {$name} --beach=condado-beach
  php {$name} --offset=100 --limit=50
  php {$name} --force --beach=flamenco-beach

HELP;
}

// ========================================================================
// Claude API caller with retry
// ========================================================================

function callClaudeAPI(string $apiKey, string $prompt, int $attempt = 1): array {
    $url = 'https://api.anthropic.com/v1/messages';
    $maxAttempts = 3;

    $payload = json_encode([
        'model'      => 'claude-haiku-4-5-20251001',
        'max_tokens' => 8000,
        'messages'   => [
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    $headers = [
        'Content-Type: application/json',
        'x-api-key: ' . $apiKey,
        'anthropic-version: 2023-06-01',
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => $headers,
        CURLOPT_TIMEOUT        => 120,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    // cURL-level failure
    if ($curlErr) {
        if ($attempt < $maxAttempts) {
            $wait = pow(2, $attempt); // 2s, 4s
            cli_err("cURL error (attempt {$attempt}/{$maxAttempts}): {$curlErr} -- retrying in {$wait}s");
            sleep($wait);
            return callClaudeAPI($apiKey, $prompt, $attempt + 1);
        }
        throw new RuntimeException("cURL error after {$maxAttempts} attempts: {$curlErr}");
    }

    // HTTP error
    if ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMsg  = $errorData['error']['message'] ?? "HTTP {$httpCode}";
        $isRateLimit = ($httpCode === 429 || stripos($errorMsg, 'rate limit') !== false);
        $maxRetries = $isRateLimit ? 8 : $maxAttempts;
        if ($attempt < $maxRetries) {
            $wait = $isRateLimit ? 60 : pow(2, $attempt);
            cli_err("API error (attempt {$attempt}/{$maxRetries}): {$errorMsg} -- retrying in {$wait}s");
            sleep($wait);
            return callClaudeAPI($apiKey, $prompt, $attempt + 1);
        }
        throw new RuntimeException("API error after {$attempt} attempts: {$errorMsg}");
    }

    $data = json_decode($response, true);
    if (!isset($data['content'][0]['text'])) {
        throw new RuntimeException('Unexpected API response structure');
    }

    // Parse JSON from the response text
    $text = $data['content'][0]['text'];

    // Strip markdown code fences if present
    $cleaned = preg_replace('/^```(?:json)?\s*/m', '', $text);
    $cleaned = preg_replace('/```\s*$/m', '', $cleaned);
    $cleaned = trim($cleaned);

    $parsed = json_decode($cleaned, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if ($attempt < $maxAttempts) {
            $wait = pow(2, $attempt);
            cli_err("JSON parse error (attempt {$attempt}/{$maxAttempts}): " . json_last_error_msg() . " -- retrying in {$wait}s");
            sleep($wait);
            return callClaudeAPI($apiKey, $prompt, $attempt + 1);
        }
        throw new RuntimeException(
            'JSON parse failed after ' . $maxAttempts . ' attempts: '
            . json_last_error_msg() . "\nRaw (first 500 chars): " . substr($text, 0, 500)
        );
    }

    return $parsed;
}

// ========================================================================
// Prompt builder
// ========================================================================

function buildPrompt(array $beach, array $tags, array $sections): string {
    $descLen       = mb_strlen($beach['description'] ?? '');
    $needsExpand   = $descLen < 300;
    $sectionJson   = json_encode(array_map(function ($s) {
        return [
            'section_type' => $s['section_type'],
            'heading'      => $s['heading'],
            'content'      => $s['content'],
        ];
    }, $sections), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

    $tagsStr = implode(', ', $tags);

    // Build formatting rules for the prompt
    $formattingRules = <<<'RULES'
Formatting rules per section_type (use ONLY these HTML tags: <p>, <ul>, <ol>, <li>, <strong>, <em>, <h3>, <br>):
- history: 2-3 <p> paragraphs. Use <strong> for key facts (dates, locations, notable features).
- best_time: Opening <p>, then <ul> with seasonal bullet points. <strong> for month ranges.
- getting_there: Overview <p>, then <ol> numbered directions. <strong> for drive times/distances. Separate <p> for parking info.
- what_to_bring: <ul> bullet lists grouped by purpose. <strong> for item names.
- nearby: Intro <p>, then <ul> list of attractions. <strong> for place names.
- local_tips: <ul> bullet list. <strong> for the key takeaway per tip.
RULES;

    $descInstruction = $needsExpand
        ? "The current English description is only {$descLen} characters. Expand it to 400-500 characters while keeping factual accuracy. Do NOT invent amenities or features not implied by the tags/sections."
        : "The current English description is {$descLen} characters, which is adequate. Return it unchanged in the \"description\" field.";

    $prompt = <<<PROMPT
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
{$formattingRules}
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

    return $prompt;
}

// ========================================================================
// Database operations
// ========================================================================

function fetchBeaches(array $opts): array {
    $where  = "WHERE b.publish_status = 'published'";
    $params = [];

    if (!empty($opts['beach'])) {
        $where .= ' AND b.slug = :slug';
        $params[':slug'] = $opts['beach'];
    }

    if (empty($opts['force'])) {
        $where .= ' AND b.description_es IS NULL';
    }

    $orderLimit = 'ORDER BY b.name ASC';

    if (!empty($opts['limit'])) {
        $orderLimit .= ' LIMIT ' . (int)$opts['limit'];
    }

    if (!empty($opts['offset'])) {
        $orderLimit .= ' OFFSET ' . (int)$opts['offset'];
    }

    $sql = "SELECT b.id, b.slug, b.name, b.municipality, b.description, b.lat, b.lng
            FROM beaches b {$where} {$orderLimit}";

    return query($sql, $params) ?: [];
}

function fetchTotalEligible(array $opts): int {
    $where  = "WHERE b.publish_status = 'published'";
    $params = [];

    if (!empty($opts['beach'])) {
        $where .= ' AND b.slug = :slug';
        $params[':slug'] = $opts['beach'];
    }

    if (empty($opts['force'])) {
        $where .= ' AND b.description_es IS NULL';
    }

    $row = queryOne("SELECT COUNT(*) as cnt FROM beaches b {$where}", $params);
    return (int)($row['cnt'] ?? 0);
}

function fetchSections(string $beachId): array {
    return query(
        "SELECT id, section_type, heading, content FROM beach_content_sections
         WHERE beach_id = :bid AND status = 'published'
         ORDER BY display_order ASC",
        [':bid' => $beachId]
    ) ?: [];
}

function fetchTagsForBeach(string $beachId): array {
    $rows = query(
        'SELECT tag FROM beach_tags WHERE beach_id = :bid',
        [':bid' => $beachId]
    ) ?: [];
    return array_column($rows, 'tag');
}

function applyUpdates(array $beach, array $sections, array $result, bool $dryRun): array {
    $db = getDb();
    $stats = ['desc_before' => mb_strlen($beach['description'] ?? ''), 'desc_after' => 0, 'sections_updated' => 0];

    // Validate top-level fields
    $newDesc   = trim($result['description'] ?? '');
    $newDescEs = trim($result['description_es'] ?? '');

    if ($newDesc === '' || $newDescEs === '') {
        throw new RuntimeException('API returned empty description or description_es');
    }

    $stats['desc_after'] = mb_strlen($newDesc);

    if ($dryRun) {
        // Count sections that would be updated
        foreach ($sections as $sec) {
            $type = $sec['section_type'];
            if (isset($result['sections'][$type])) {
                $stats['sections_updated']++;
            }
        }
        return $stats;
    }

    // Wrap everything in a transaction
    $db->exec('BEGIN IMMEDIATE');

    try {
        // Update beach description + Spanish translation
        execute(
            'UPDATE beaches SET description = :desc, description_es = :desc_es WHERE id = :id',
            [':desc' => $newDesc, ':desc_es' => $newDescEs, ':id' => $beach['id']]
        );

        // Update each section
        foreach ($sections as $sec) {
            $type = $sec['section_type'];
            if (!isset($result['sections'][$type])) {
                continue;
            }

            $sData     = $result['sections'][$type];
            $headingEs = trim($sData['heading_es'] ?? '');
            $contentEn = trim($sData['content'] ?? '');
            $contentEs = trim($sData['content_es'] ?? '');

            // Never overwrite with empty content
            if ($contentEn === '' || $contentEs === '' || $headingEs === '') {
                cli_err("  Skipping section '{$type}': API returned empty field");
                continue;
            }

            // Sanitize HTML to allowed tags only
            $contentEn = sanitizeContentHtml($contentEn);
            $contentEs = sanitizeContentHtml($contentEs);

            execute(
                'UPDATE beach_content_sections
                 SET content = :content, heading_es = :hes, content_es = :ces
                 WHERE id = :id',
                [
                    ':content' => $contentEn,
                    ':hes'     => $headingEs,
                    ':ces'     => $contentEs,
                    ':id'      => $sec['id'],
                ]
            );

            $stats['sections_updated']++;
        }

        $db->exec('COMMIT');
    } catch (\Throwable $e) {
        $db->exec('ROLLBACK');
        throw $e;
    }

    return $stats;
}

// ========================================================================
// Main
// ========================================================================

function main(array $argv): int {
    $opts = parseCliArgs($argv);

    if (isset($opts['help'])) {
        showHelp();
        return 0;
    }

    $dryRun = isset($opts['dry-run']);
    $force  = isset($opts['force']);

    // Resolve API key (bootstrap already loaded .env into $_ENV)
    $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY');
    if (!$apiKey) {
        cli_err('ANTHROPIC_API_KEY is not set. Add it to .env or export it.');
        return 1;
    }

    // Build options for query
    $queryOpts = [
        'beach'  => $opts['beach'] ?? null,
        'offset' => $opts['offset'] ?? null,
        'limit'  => $opts['limit'] ?? null,
        'force'  => $force,
    ];

    $totalEligible = fetchTotalEligible($queryOpts);
    $beaches       = fetchBeaches($queryOpts);
    $count         = count($beaches);

    echo "\n";
    echo cli_color("Improve Beach Content", 'blue') . "\n";
    echo str_repeat('=', 60) . "\n";
    cli_info("Eligible beaches: {$totalEligible}");
    cli_info("Processing:       {$count}");
    cli_info("Mode:             " . ($dryRun ? 'DRY RUN (no writes)' : 'LIVE'));
    if ($force) {
        cli_info("Force:            yes (re-processing all)");
    }
    echo str_repeat('=', 60) . "\n\n";

    if ($count === 0) {
        cli_info('Nothing to process. All beaches already have Spanish content (use --force to redo).');
        return 0;
    }

    $succeeded = 0;
    $failed    = 0;
    $skipped   = 0;
    $lastRequestTime = 0;

    foreach ($beaches as $i => $beach) {
        $num  = $i + 1;
        $slug = $beach['slug'];

        echo cli_color("[{$num}/{$count}]", 'dim') . " {$slug}: ";

        // Fetch sections and tags
        $sections = fetchSections($beach['id']);
        $tags     = fetchTagsForBeach($beach['id']);

        if (empty($sections)) {
            cli_skip("no content sections found");
            $skipped++;
            continue;
        }

        // Rate limiting: enforce minimum delay between requests
        // 10K output tokens/min limit, ~5K tokens/response → ~30s between requests
        $minDelay = floatval(getenv('BEACH_RATE_DELAY') ?: 25);
        if ($lastRequestTime > 0) {
            $elapsed   = microtime(true) - $lastRequestTime;
            $remaining = $minDelay - $elapsed;
            if ($remaining > 0) {
                usleep((int)($remaining * 1_000_000));
            }
        }

        try {
            $prompt = buildPrompt($beach, $tags, $sections);
            $lastRequestTime = microtime(true);
            $result = callClaudeAPI($apiKey, $prompt);

            // Validate required structure
            if (!isset($result['description']) || !isset($result['description_es']) || !isset($result['sections'])) {
                throw new RuntimeException('Response missing required keys (description, description_es, sections)');
            }

            $stats = applyUpdates($beach, $sections, $result, $dryRun);

            $descBefore = $stats['desc_before'];
            $descAfter  = $stats['desc_after'];
            $secCount   = $stats['sections_updated'];

            cli_ok("desc {$descBefore}->{$descAfter} chars, {$secCount} sections formatted+translated"
                . ($dryRun ? ' (dry run)' : ''));
            $succeeded++;

        } catch (\Throwable $e) {
            cli_err($e->getMessage());
            $failed++;
        }
    }

    // Summary
    echo "\n" . str_repeat('=', 60) . "\n";
    echo cli_color("Results", 'blue') . "\n";
    echo str_repeat('-', 60) . "\n";
    echo "  Processed: {$count}\n";
    echo "  " . cli_color("Succeeded: {$succeeded}", 'green') . "\n";
    if ($skipped > 0) {
        echo "  " . cli_color("Skipped:   {$skipped}", 'yellow') . "\n";
    }
    if ($failed > 0) {
        echo "  " . cli_color("Failed:    {$failed}", 'red') . "\n";
    }
    echo str_repeat('=', 60) . "\n\n";

    return $failed > 0 ? 1 : 0;
}

exit(main($argv));
