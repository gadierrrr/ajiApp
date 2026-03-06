<?php
/**
 * API: Bulk add favorites from a quiz results token.
 *
 * POST /api/favorites/bulk-add.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_cache_limiter('');
session_start();

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!isAuthenticated()) {
    jsonResponse(['success' => false, 'error' => 'Authentication required'], 401);
}

// CSRF is required because this endpoint mutates authenticated user state.
$csrfToken = trim((string) ($_POST['csrf_token'] ?? ''));
if ($csrfToken === '' || !validateCsrf($csrfToken)) {
    jsonResponse(['success' => false, 'error' => 'Invalid request'], 403);
}

$token = trim((string)($_POST['results_token'] ?? ''));
if ($token === '') {
    jsonResponse(['success' => false, 'error' => 'Missing results token'], 400);
}

$row = queryOne('SELECT matched_beaches FROM quiz_results WHERE token = :token', [':token' => $token]);
if (!$row) {
    jsonResponse(['success' => false, 'error' => 'Quiz results not found'], 404);
}

$matches = json_decode((string)($row['matched_beaches'] ?? '[]'), true);
if (!is_array($matches) || empty($matches)) {
    jsonResponse(['success' => false, 'error' => 'No matches found'], 404);
}

$userId = (string)($_SESSION['user_id'] ?? '');
$added = 0;
$db = getDB();
$stmt = $db->prepare('INSERT OR IGNORE INTO user_favorites (id, user_id, beach_id, created_at) VALUES (:id, :user_id, :beach_id, datetime("now"))');
if (!$stmt) {
    jsonResponse(['success' => false, 'error' => 'Database error'], 500);
}

$db->exec('BEGIN');
try {
    foreach (array_slice($matches, 0, 12) as $m) {
        if (!is_array($m)) continue;
        $beachId = (string)($m['id'] ?? '');
        if ($beachId === '') continue;

        $stmt->reset();
        $stmt->bindValue(':id', uuid(), SQLITE3_TEXT);
        $stmt->bindValue(':user_id', $userId, SQLITE3_TEXT);
        $stmt->bindValue(':beach_id', $beachId, SQLITE3_TEXT);
        $res = $stmt->execute();
        if ($res !== false) {
            // 1 when inserted, 0 when ignored.
            $added += (int)$db->changes();
        }
    }
    $db->exec('COMMIT');
} catch (Throwable $e) {
    $db->exec('ROLLBACK');
    jsonResponse(['success' => false, 'error' => 'Database error'], 500);
}

jsonResponse(['success' => true, 'added' => $added]);
