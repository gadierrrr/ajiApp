<?php
// inc/db.php - Database connection and helpers

require_once __DIR__ . '/bootstrap.php';

function getDB() {
    static $db = null;

    if ($db === null) {
        $dbPath = envRequire('DB_PATH');
        $dbPath = normalizePathFromAppRoot($dbPath);
        $dbDir = dirname($dbPath);

        if (!is_dir($dbDir) && !@mkdir($dbDir, 0755, true) && !is_dir($dbDir)) {
            throw new RuntimeException("Database directory is not writable or cannot be created: {$dbDir}");
        }

        try {
            $db = new SQLite3($dbPath);
        } catch (Throwable $e) {
            throw new RuntimeException("Unable to open database at {$dbPath}: " . $e->getMessage(), 0, $e);
        }

        // Apply the busy timeout before any follow-up pragmas so concurrent requests
        // wait for transient locks instead of failing during connection setup.
        $db->busyTimeout(5000);
        $db->exec('PRAGMA busy_timeout=5000;');
        $db->exec('PRAGMA foreign_keys=ON;');

        // WAL mode persists on the database file, so avoid rewriting it on every
        // request. Re-applying it conditionally prevents unnecessary lock churn.
        $journalMode = strtolower((string) $db->querySingle('PRAGMA journal_mode;'));
        if ($journalMode !== 'wal') {
            $db->exec('PRAGMA journal_mode=WAL;');
        }
    }

    return $db;
}

function normalizePathFromAppRoot(string $path): string {
    if ($path === '') {
        return $path;
    }

    if (pathIsAbsolute($path)) {
        return $path;
    }

    $trimmed = $path;
    if (str_starts_with($trimmed, './')) {
        $trimmed = substr($trimmed, 2);
    }
    $trimmed = ltrim($trimmed, '/');

    return APP_ROOT . '/' . $trimmed;
}

function pathIsAbsolute(string $path): bool {
    if ($path === '') {
        return false;
    }

    if ($path[0] === '/' || $path[0] === '\\') {
        return true;
    }

    return preg_match('/^[A-Za-z]:[\\\\\\/]/', $path) === 1;
}

function uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

function query($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        error_log('SQL Error: ' . $db->lastErrorMsg());
        return false;
    }

    foreach ($params as $key => $value) {
        $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
    }

    $result = $stmt->execute();
    if (!$result) {
        error_log('Query Error: ' . $db->lastErrorMsg());
        return false;
    }

    $rows = [];
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $rows[] = $row;
    }

    return $rows;
}

function queryOne($sql, $params = []) {
    $rows = query($sql, $params);
    return $rows ? ($rows[0] ?? null) : null;
}

function execute($sql, $params = []) {
    $db = getDB();
    $stmt = $db->prepare($sql);

    if (!$stmt) {
        error_log('SQL Error: ' . $db->lastErrorMsg());
        return false;
    }

    foreach ($params as $key => $value) {
        $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value);
    }

    $result = $stmt->execute();
    return $result !== false;
}

/**
 * Batch fetch tags for multiple beaches
 * @param array $beachIds Array of beach IDs
 * @return array Keyed by beach_id => [tags]
 */
function batchGetTags($beachIds) {
    if (empty($beachIds)) return [];
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $rows = query("SELECT beach_id, tag FROM beach_tags WHERE beach_id IN ($placeholders)", array_values($beachIds));
    $result = array_fill_keys($beachIds, []);
    foreach ($rows as $row) {
        $result[$row['beach_id']][] = $row['tag'];
    }
    return $result;
}

/**
 * Batch fetch amenities for multiple beaches
 * @param array $beachIds Array of beach IDs
 * @return array Keyed by beach_id => [amenities]
 */
function batchGetAmenities($beachIds) {
    if (empty($beachIds)) return [];
    $placeholders = implode(',', array_fill(0, count($beachIds), '?'));
    $rows = query("SELECT beach_id, amenity FROM beach_amenities WHERE beach_id IN ($placeholders)", array_values($beachIds));
    $result = array_fill_keys($beachIds, []);
    foreach ($rows as $row) {
        $result[$row['beach_id']][] = $row['amenity'];
    }
    return $result;
}

/**
 * Attach tags and amenities to beaches array efficiently
 * @param array &$beaches Array of beach records (modified in place)
 */
function attachBeachMetadata(&$beaches) {
    if (empty($beaches)) return;
    $beachIds = array_column($beaches, 'id');
    $allTags = batchGetTags($beachIds);
    $allAmenities = batchGetAmenities($beachIds);
    foreach ($beaches as &$beach) {
        $beach['tags'] = $allTags[$beach['id']] ?? [];
        $beach['amenities'] = $allAmenities[$beach['id']] ?? [];
    }
}
