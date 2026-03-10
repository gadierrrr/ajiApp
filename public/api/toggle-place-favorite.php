<?php
/**
 * API: Toggle favorite for any place type.
 *
 * POST /api/toggle-place-favorite.php
 * Body: type=river&place_id=abc123
 *
 * For beaches, also updates the legacy user_favorites table.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/session.php';
require_once APP_ROOT . '/inc/security_headers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    jsonResponse(['error' => 'Method not allowed']);
    exit;
}

if (!isAuthenticated()) {
    http_response_code(401);
    jsonResponse(['error' => 'Authentication required']);
    exit;
}

$type = trim($_POST['type'] ?? '');
$placeId = trim($_POST['place_id'] ?? '');

if (!isValidPlaceType($type) || $placeId === '') {
    http_response_code(400);
    jsonResponse(['error' => 'Invalid type or place_id']);
    exit;
}

$userId = currentUser()['id'];

if ($type === 'beach') {
    // Use legacy user_favorites for backward compatibility
    $existing = queryOne(
        'SELECT id FROM user_favorites WHERE user_id = ? AND beach_id = ?',
        [$userId, $placeId]
    );

    if ($existing) {
        execute('DELETE FROM user_favorites WHERE user_id = ? AND beach_id = ?', [$userId, $placeId]);
        $isFavorite = false;
    } else {
        execute(
            'INSERT INTO user_favorites (id, user_id, beach_id, place_type, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)',
            [uuid(), $userId, $placeId, 'beach']
        );
        $isFavorite = true;
    }
} else {
    // Use place_favorites for non-beach types
    $existing = queryOne(
        'SELECT id FROM place_favorites WHERE user_id = ? AND place_type = ? AND place_id = ?',
        [$userId, $type, $placeId]
    );

    if ($existing) {
        execute('DELETE FROM place_favorites WHERE user_id = ? AND place_type = ? AND place_id = ?', [$userId, $type, $placeId]);
        $isFavorite = false;
    } else {
        execute(
            'INSERT INTO place_favorites (id, user_id, place_type, place_id, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)',
            [uuid(), $userId, $type, $placeId]
        );
        $isFavorite = true;
    }
}

if (isHtmx()) {
    header('Content-Type: text/html; charset=UTF-8');
    $heartClass = $isFavorite ? 'text-red-500 fill-current' : 'text-white';
    echo '<button class="favorite-btn ' . $heartClass . '" data-action="togglePlaceFavorite" data-place-type="' . h($type) . '" data-place-id="' . h($placeId) . '">';
    echo '<i data-lucide="heart" class="w-5 h-5"></i>';
    echo '</button>';
    exit;
}

jsonResponse([
    'success' => true,
    'is_favorite' => $isFavorite,
    'place_type' => $type,
    'place_id' => $placeId,
]);
