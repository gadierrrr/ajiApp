<?php
/**
 * API: Toggle Beach Favorite
 *
 * POST /api/toggle-favorite.php
 * Body: beach_id, csrf_token
 * Query:
 * - format=json (optional)
 *
 * Returns updated favorite button HTML (default) or JSON response.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';

$format = isset($_GET['format']) ? (string)$_GET['format'] : 'html';
$wantsJson = $format === 'json';

// Require authentication
if (!isAuthenticated()) {
    if ($wantsJson) {
        jsonResponse([
            'success' => false,
            'error' => 'Authentication required.',
        ], 401);
    }
    http_response_code(401);
    echo '<button class="favorite-btn w-9 h-9 flex items-center justify-center rounded-full bg-black/40 backdrop-blur-sm border border-white/20 hover:bg-black/60 transition-colors" data-action-stop data-action="showSignupPrompt" data-action-args=\'["favorites"]\' aria-label="Sign in to save this beach" title="Sign in to save favorites"><i data-lucide="heart" class="w-4 h-4 text-white/50"></i></button>';
    exit;
}

// Validate CSRF token
$csrfToken = $_POST['csrf_token'] ?? '';
if (!validateCsrf($csrfToken)) {
    if ($wantsJson) {
        jsonResponse([
            'success' => false,
            'error' => 'Invalid CSRF token.',
        ], 403);
    }
    http_response_code(403);
    echo '<button class="favorite-btn">⚠️</button>';
    exit;
}

// Get beach ID
$beachId = $_POST['beach_id'] ?? '';
if (!$beachId) {
    if ($wantsJson) {
        jsonResponse([
            'success' => false,
            'error' => 'Missing beach_id.',
        ], 400);
    }
    http_response_code(400);
    echo '<button class="favorite-btn">⚠️</button>';
    exit;
}

// Verify beach exists
$beach = queryOne('SELECT id FROM beaches WHERE id = :id', [':id' => $beachId]);
if (!$beach) {
    if ($wantsJson) {
        jsonResponse([
            'success' => false,
            'error' => 'Beach not found.',
        ], 404);
    }
    http_response_code(404);
    echo '<button class="favorite-btn">⚠️</button>';
    exit;
}

$userId = $_SESSION['user_id'];

// Check if already favorited
$existing = queryOne(
    'SELECT id FROM user_favorites WHERE user_id = :user_id AND beach_id = :beach_id',
    [':user_id' => $userId, ':beach_id' => $beachId]
);

if ($existing) {
    // Remove from favorites
    execute('DELETE FROM user_favorites WHERE id = :id', [':id' => $existing['id']]);
    $isFavorite = false;
} else {
    // Add to favorites
    $favoriteId = uuid();
    execute(
        'INSERT INTO user_favorites (id, user_id, beach_id, created_at) VALUES (:id, :user_id, :beach_id, datetime("now"))',
        [':id' => $favoriteId, ':user_id' => $userId, ':beach_id' => $beachId]
    );
    $isFavorite = true;
}

if ($wantsJson) {
    jsonResponse([
        'success' => true,
        'beach_id' => $beachId,
        'is_favorite' => $isFavorite,
    ]);
}

// Return updated button
?>
<button class="favorite-btn absolute top-3 left-3 w-8 h-8 flex items-center justify-center rounded-full bg-white/90 shadow hover:bg-white transition-colors"
        hx-post="/api/toggle-favorite.php"
        hx-target="this"
        hx-swap="outerHTML"
        hx-vals='{"beach_id": "<?= h($beachId) ?>", "csrf_token": "<?= h(csrfToken()) ?>"}'
        title="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
    <span class="text-lg <?= $isFavorite ? 'text-red-500 a11y-error-text' : 'text-gray-400' ?>">
        <?= $isFavorite ? '❤️' : '🤍' ?>
    </span>
</button>
