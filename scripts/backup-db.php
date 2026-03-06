<?php
/**
 * Create a verified SQLite backup using SQLite's backup API.
 *
 * Usage:
 *   php scripts/backup-db.php
 *   php scripts/backup-db.php --output-dir=./backups/db --keep-days=30
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';

function backupCliOption(array $argv, string $prefix): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }

    return null;
}

function backupCliFlag(array $argv, string $flag): bool
{
    return in_array($flag, $argv, true);
}

function backupNormalizeDirectory(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return normalizePathFromAppRoot('./backups/db');
    }

    return pathIsAbsolute($path) ? $path : normalizePathFromAppRoot($path);
}

function backupIntValue(?string $value, int $default): int
{
    $value = trim((string) $value);
    if ($value === '') {
        return $default;
    }

    if (!preg_match('/^\d+$/', $value)) {
        throw new RuntimeException('Expected a non-negative integer, got: ' . $value);
    }

    return (int) $value;
}

function backupJsonExit(array $payload, int $exitCode = 0): void
{
    $stream = $exitCode === 0 ? STDOUT : STDERR;
    fwrite($stream, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit($exitCode);
}

function backupEnsureDirectory(string $directory): void
{
    if (is_dir($directory)) {
        return;
    }

    if (!@mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create backup directory: ' . $directory);
    }
}

function backupPruneOldFiles(string $directory, string $sourceName, int $keepDays, array $exclude = []): array
{
    if ($keepDays <= 0) {
        return [];
    }

    $cutoff = time() - ($keepDays * 86400);
    $pattern = rtrim($directory, '/') . '/' . $sourceName . '.backup-*';
    $paths = glob($pattern) ?: [];
    $removed = [];

    foreach ($paths as $path) {
        if (in_array($path, $exclude, true)) {
            continue;
        }

        if (!is_file($path) || filemtime($path) === false || filemtime($path) >= $cutoff) {
            continue;
        }

        if (@unlink($path)) {
            $removed[] = $path;
        }
    }

    return $removed;
}

function backupMetadataForDb(SQLite3 $db): array
{
    $tableCount = (int) ($db->querySingle("SELECT COUNT(*) FROM sqlite_master WHERE type = 'table'") ?? 0);
    $pageCount = (int) ($db->querySingle('PRAGMA page_count') ?? 0);
    $journalMode = (string) ($db->querySingle('PRAGMA journal_mode') ?? '');

    return [
        'table_count' => $tableCount,
        'page_count' => $pageCount,
        'journal_mode' => $journalMode,
    ];
}

if (backupCliFlag($argv, '--help')) {
    backupJsonExit([
        'usage' => [
            'php scripts/backup-db.php',
            'php scripts/backup-db.php --output-dir=./backups/db --keep-days=30',
        ],
    ]);
}

$sourcePath = normalizePathFromAppRoot(envRequire('DB_PATH'));
$outputDir = backupNormalizeDirectory((string) (backupCliOption($argv, '--output-dir=') ?? env('BACKUP_DIR', './backups/db')));
$keepDays = backupIntValue(backupCliOption($argv, '--keep-days=') ?? env('BACKUP_KEEP_DAYS', '30'), 30);
$sourceName = basename($sourcePath);
$timestamp = gmdate('Ymd_His');
$backupPath = rtrim($outputDir, '/') . '/' . $sourceName . '.backup-' . $timestamp . '.sqlite';
$tempPath = $backupPath . '.tmp';
$metadataPath = $backupPath . '.json';

if (!is_file($sourcePath)) {
    backupJsonExit([
        'ok' => false,
        'error' => 'Source database file not found',
        'source_path' => $sourcePath,
    ], 1);
}

$sourceDb = null;
$backupDb = null;

try {
    backupEnsureDirectory($outputDir);

    if (is_file($tempPath) && !@unlink($tempPath)) {
        throw new RuntimeException('Could not remove stale temporary backup file: ' . $tempPath);
    }

    $sourceDb = new SQLite3($sourcePath, SQLITE3_OPEN_READWRITE);
    $sourceDb->busyTimeout(5000);
    $sourceDb->exec('PRAGMA busy_timeout=5000;');
    $sourceDb->exec('PRAGMA wal_checkpoint(PASSIVE);');

    $backupDb = new SQLite3($tempPath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $backupDb->busyTimeout(5000);

    if (!method_exists($sourceDb, 'backup')) {
        throw new RuntimeException('SQLite3 backup API is not available in this PHP runtime');
    }

    if (!$sourceDb->backup($backupDb)) {
        throw new RuntimeException('SQLite backup operation failed');
    }

    $integrity = strtolower(trim((string) $backupDb->querySingle('PRAGMA integrity_check;')));
    if ($integrity !== 'ok') {
        throw new RuntimeException('Backup integrity check failed: ' . $integrity);
    }

    $metadata = backupMetadataForDb($backupDb);
    if (($metadata['table_count'] ?? 0) < 1) {
        throw new RuntimeException('Backup contains no tables');
    }

    $backupDb->close();
    $backupDb = null;
    $sourceDb->close();
    $sourceDb = null;

    if (!@rename($tempPath, $backupPath)) {
        throw new RuntimeException('Could not finalize backup file');
    }

    @chmod($backupPath, 0600);

    $metadataPayload = [
        'created_at' => gmdate('c'),
        'app_env' => appEnv(),
        'source_path' => $sourcePath,
        'backup_path' => $backupPath,
        'sha256' => hash_file('sha256', $backupPath),
        'bytes' => filesize($backupPath) ?: 0,
    ] + $metadata;

    file_put_contents($metadataPath, json_encode($metadataPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    @chmod($metadataPath, 0600);

    $pruned = backupPruneOldFiles($outputDir, $sourceName, $keepDays, [$backupPath, $metadataPath]);

    backupJsonExit([
        'ok' => true,
        'backup_path' => $backupPath,
        'metadata_path' => $metadataPath,
        'integrity_check' => 'ok',
        'table_count' => $metadata['table_count'],
        'page_count' => $metadata['page_count'],
        'journal_mode' => $metadata['journal_mode'],
        'pruned_files' => $pruned,
    ]);
} catch (Throwable $e) {
    if ($backupDb instanceof SQLite3) {
        $backupDb->close();
    }
    if ($sourceDb instanceof SQLite3) {
        $sourceDb->close();
    }
    if (is_file($tempPath)) {
        @unlink($tempPath);
    }

    backupJsonExit([
        'ok' => false,
        'error' => $e->getMessage(),
        'source_path' => $sourcePath,
        'output_dir' => $outputDir,
    ], 1);
}
