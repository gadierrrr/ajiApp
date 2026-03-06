<?php
/**
 * Restore the latest or a specific SQLite backup into a temporary database
 * and verify that it can be opened and queried.
 *
 * Usage:
 *   php scripts/restore-smoke-test.php
 *   php scripts/restore-smoke-test.php --backup=/path/to/backup.sqlite
 *   php scripts/restore-smoke-test.php --latest --keep-restored
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';

function restoreCliOption(array $argv, string $prefix): ?string
{
    foreach ($argv as $arg) {
        if (str_starts_with($arg, $prefix)) {
            return substr($arg, strlen($prefix));
        }
    }

    return null;
}

function restoreCliFlag(array $argv, string $flag): bool
{
    return in_array($flag, $argv, true);
}

function restoreNormalizeDirectory(string $path): string
{
    $path = trim($path);
    if ($path === '') {
        return normalizePathFromAppRoot('./backups/db');
    }

    return pathIsAbsolute($path) ? $path : normalizePathFromAppRoot($path);
}

function restoreJsonExit(array $payload, int $exitCode = 0): void
{
    $stream = $exitCode === 0 ? STDOUT : STDERR;
    fwrite($stream, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    exit($exitCode);
}

function restoreEnsureDirectory(string $directory): void
{
    if (is_dir($directory)) {
        return;
    }

    if (!@mkdir($directory, 0750, true) && !is_dir($directory)) {
        throw new RuntimeException('Could not create restore directory: ' . $directory);
    }
}

function restoreLatestBackup(string $backupDir, string $sourceName): ?string
{
    $pattern = rtrim($backupDir, '/') . '/' . $sourceName . '.backup-*.sqlite';
    $paths = glob($pattern) ?: [];
    if ($paths === []) {
        return null;
    }

    usort($paths, static function (string $a, string $b): int {
        return (int) (filemtime($b) <=> filemtime($a));
    });

    return $paths[0] ?? null;
}

function restoreTableList(SQLite3 $db): array
{
    $result = $db->query("SELECT name FROM sqlite_master WHERE type = 'table' ORDER BY name");
    $tables = [];
    if ($result === false) {
        return $tables;
    }

    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name !== '') {
            $tables[] = $name;
        }
    }

    return $tables;
}

function restoreOptionalTableCounts(SQLite3 $db, array $tables): array
{
    $counts = [];
    foreach (['schema_migrations', 'users', 'beaches', 'email_messages'] as $table) {
        if (!in_array($table, $tables, true)) {
            continue;
        }

        $counts[$table] = (int) ($db->querySingle('SELECT COUNT(*) FROM "' . $table . '"') ?? 0);
    }

    return $counts;
}

if (restoreCliFlag($argv, '--help')) {
    restoreJsonExit([
        'usage' => [
            'php scripts/restore-smoke-test.php',
            'php scripts/restore-smoke-test.php --backup=/path/to/file.sqlite',
            'php scripts/restore-smoke-test.php --latest --keep-restored',
        ],
    ]);
}

$sourceName = basename(normalizePathFromAppRoot(envRequire('DB_PATH')));
$backupDir = restoreNormalizeDirectory((string) env('BACKUP_DIR', './backups/db'));
$restoreDir = restoreNormalizeDirectory((string) (restoreCliOption($argv, '--restore-dir=') ?? './backups/restore-smoke'));
$keepRestored = restoreCliFlag($argv, '--keep-restored');
$explicitBackup = trim((string) restoreCliOption($argv, '--backup='));

if ($explicitBackup !== '') {
    $backupPath = pathIsAbsolute($explicitBackup) ? $explicitBackup : normalizePathFromAppRoot($explicitBackup);
} else {
    $backupPath = restoreLatestBackup($backupDir, $sourceName);
}

if (!is_string($backupPath) || $backupPath === '' || !is_file($backupPath)) {
    restoreJsonExit([
        'ok' => false,
        'error' => 'Backup file not found',
        'backup_dir' => $backupDir,
    ], 1);
}

$restoreFileName = 'restore-smoke-' . basename($backupPath) . '-' . gmdate('Ymd_His') . '.sqlite';
$restorePath = rtrim($restoreDir, '/') . '/' . $restoreFileName;
$sourceDb = null;
$restoredDb = null;

try {
    restoreEnsureDirectory($restoreDir);

    if (is_file($restorePath) && !@unlink($restorePath)) {
        throw new RuntimeException('Could not remove stale restore target: ' . $restorePath);
    }

    $sourceDb = new SQLite3($backupPath, SQLITE3_OPEN_READONLY);
    $sourceDb->busyTimeout(5000);
    $restoredDb = new SQLite3($restorePath, SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);
    $restoredDb->busyTimeout(5000);

    if (!method_exists($sourceDb, 'backup')) {
        throw new RuntimeException('SQLite3 backup API is not available in this PHP runtime');
    }

    if (!$sourceDb->backup($restoredDb)) {
        throw new RuntimeException('Restore backup operation failed');
    }

    $integrity = strtolower(trim((string) $restoredDb->querySingle('PRAGMA integrity_check;')));
    if ($integrity !== 'ok') {
        throw new RuntimeException('Restored database integrity check failed: ' . $integrity);
    }

    $tables = restoreTableList($restoredDb);
    if ($tables === []) {
        throw new RuntimeException('Restored database contains no tables');
    }

    $tableCount = count($tables);
    $sampleCounts = restoreOptionalTableCounts($restoredDb, $tables);
    $sha256 = hash_file('sha256', $backupPath);

    $restoredDb->close();
    $restoredDb = null;
    $sourceDb->close();
    $sourceDb = null;

    $result = [
        'ok' => true,
        'backup_path' => $backupPath,
        'backup_sha256' => $sha256,
        'restore_path' => $restorePath,
        'integrity_check' => 'ok',
        'table_count' => $tableCount,
        'sample_counts' => $sampleCounts,
    ];

    if (!$keepRestored) {
        @unlink($restorePath);
        $result['restore_path'] = '';
        $result['restored_copy_removed'] = true;
    }

    restoreJsonExit($result);
} catch (Throwable $e) {
    if ($restoredDb instanceof SQLite3) {
        $restoredDb->close();
    }
    if ($sourceDb instanceof SQLite3) {
        $sourceDb->close();
    }

    restoreJsonExit([
        'ok' => false,
        'error' => $e->getMessage(),
        'backup_path' => $backupPath,
        'restore_path' => is_file($restorePath) ? $restorePath : '',
    ], 1);
}
