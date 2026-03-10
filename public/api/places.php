<?php
/**
 * API: Search/filter places by type.
 *
 * GET /api/places.php?type=river&tags[]=swimming&municipality=Jayuya&sort=rating&page=1
 *
 * If `type` is omitted, searches across all types via the places VIEW.
 * Returns HTML (HTMX) or JSON based on request headers.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_query.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/session.php';

$type = trim($_GET['type'] ?? '');
$isHtmxReq = isHtmx();
$format = $_GET['format'] ?? ($isHtmxReq ? 'html' : 'json');
$filters = $_GET;

header('Cache-Control: public, max-age=86400, stale-while-revalidate=3600');

// Cross-type search (no type specified or type=all)
if ($type === '' || $type === 'all') {
    $result = fetchAllPlaces($filters);
    $places = $result['places'];

    if ($format === 'html') {
        header('Content-Type: text/html; charset=UTF-8');
        if (empty($places)) {
            echo '<div class="text-center py-12 text-gray-400">';
            echo '<p>' . h(__('places.no_results', [], 'No places found matching your filters.')) . '</p>';
            echo '</div>';
            exit;
        }
        foreach ($places as $place) {
            include APP_ROOT . '/components/place-card.php';
        }
        exit;
    }

    jsonResponse([
        'data' => $places,
        'meta' => [
            'total' => $result['total'],
            'page' => $result['page'],
            'limit' => $result['limit'],
            'pages' => $result['pages'],
        ],
    ]);
    exit;
}

// Single-type search
if (!isValidPlaceType($type)) {
    if ($format === 'html') {
        header('Content-Type: text/html; charset=UTF-8');
        echo '<div class="text-center py-12 text-red-400">Invalid place type.</div>';
        exit;
    }
    http_response_code(400);
    jsonResponse(['error' => 'Invalid place type', 'valid_types' => getPlaceTypeKeys()]);
    exit;
}

$result = fetchPlaces($type, $filters);
$places = $result['places'];

// Load favorites if authenticated
$userId = null;
$favorites = [];
if (function_exists('isAuthenticated') && isAuthenticated()) {
    $userId = currentUser()['id'] ?? null;
    if ($userId && !empty($places)) {
        $placeIds = array_column($places, 'id');
        $favorites = batchGetPlaceFavorites($userId, $type, $placeIds);
    }
}

if ($format === 'html') {
    header('Content-Type: text/html; charset=UTF-8');
    if (empty($places)) {
        $typeLabel = getPlaceLabelPlural($type);
        echo '<div class="text-center py-12 text-gray-400">';
        echo '<p>No ' . h($typeLabel) . ' found matching your filters.</p>';
        echo '</div>';
        exit;
    }
    foreach ($places as $place) {
        $isFavorite = isset($favorites[$place['id']]);
        include APP_ROOT . '/components/place-card.php';
    }

    // Pagination OOB swap
    if ($result['pages'] > 1 && $result['page'] < $result['pages']) {
        $nextPage = $result['page'] + 1;
        $qs = http_build_query(array_merge($filters, ['page' => $nextPage]));
        echo '<div id="place-pagination" hx-swap-oob="true">';
        echo '<button hx-get="/api/places.php?' . h($qs) . '" hx-target="#place-results" hx-swap="beforeend"';
        echo ' class="mx-auto block mt-6 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">';
        echo 'Load More</button></div>';
    } else {
        echo '<div id="place-pagination" hx-swap-oob="true"></div>';
    }
    exit;
}

// JSON response
jsonResponse([
    'data' => $places,
    'meta' => [
        'type' => $type,
        'total' => $result['total'],
        'page' => $result['page'],
        'limit' => $result['limit'],
        'pages' => $result['pages'],
        'filters' => $result['effective_filters'],
    ],
]);
