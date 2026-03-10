<?php
/**
 * API: Fetch full detail for a place by type + slug or ID.
 *
 * GET /api/place-detail.php?type=river&slug=rio-tanama
 * GET /api/place-detail.php?type=trail&id=abc123
 *
 * Returns HTML (drawer) for HTMX requests, JSON otherwise.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/session.php';

$type = trim($_GET['type'] ?? '');
$slug = trim($_GET['slug'] ?? '');
$id = trim($_GET['id'] ?? '');
$format = $_GET['format'] ?? (isHtmx() ? 'html' : 'json');

header('Cache-Control: public, max-age=86400, stale-while-revalidate=3600');

if (!isValidPlaceType($type)) {
    http_response_code(400);
    if ($format === 'html') {
        echo '<div class="p-8 text-center text-red-400">Invalid place type.</div>';
    } else {
        jsonResponse(['error' => 'Invalid place type']);
    }
    exit;
}

$config = getPlaceTypeConfig($type);
$table = $config['table'];

// Fetch by slug or ID
$place = null;
if ($slug !== '') {
    $place = fetchPlaceBySlug($type, $slug);
} elseif ($id !== '') {
    $place = queryOne("SELECT * FROM {$table} WHERE id = :id AND publish_status = 'published'", [':id' => $id]);
    if ($place) {
        $place['place_type'] = $type;
        $places = [$place];
        attachPlaceMetadata($type, $places);
        $place = $places[0];
    }
}

if (!$place) {
    http_response_code(404);
    if ($format === 'html') {
        $label = getPlaceLabel($type);
        echo '<div class="p-8 text-center text-gray-400">' . h($label) . ' not found.</div>';
    } else {
        jsonResponse(['error' => 'Place not found']);
    }
    exit;
}

// Load reviews
$reviews = [];
if ($type === 'beach') {
    $reviews = query(
        'SELECT r.*, u.name AS user_name FROM beach_reviews r
         LEFT JOIN users u ON u.id = r.user_id
         WHERE r.beach_id = :id AND r.status = "published"
         ORDER BY r.created_at DESC LIMIT 5',
        [':id' => $place['id']]
    ) ?: [];
} else {
    $reviews = query(
        'SELECT r.*, u.name AS user_name FROM place_reviews r
         LEFT JOIN users u ON u.id = r.user_id
         WHERE r.place_type = :type AND r.place_id = :id AND r.status = "published"
         ORDER BY r.created_at DESC LIMIT 5',
        [':type' => $type, ':id' => $place['id']]
    ) ?: [];
}
$place['reviews'] = $reviews;

// Check favorite status
$isFavorite = false;
if (function_exists('isAuthenticated') && isAuthenticated()) {
    $userId = currentUser()['id'] ?? null;
    if ($userId) {
        $isFavorite = isPlaceFavorited($userId, $type, $place['id']);
    }
}
$place['is_favorite'] = $isFavorite;

// Get type-specific extras
$place['extras'] = getPlaceTypeExtras($place);

if ($format === 'html') {
    header('Content-Type: text/html; charset=UTF-8');
    include APP_ROOT . '/components/place-drawer.php';
    exit;
}

// JSON response
jsonResponse([
    'data' => $place,
    'type' => $type,
    'type_config' => [
        'label' => $config['label'],
        'icon' => $config['icon'],
        'schema_type' => $config['schema_type'],
    ],
]);
