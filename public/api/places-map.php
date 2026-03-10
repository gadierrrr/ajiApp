<?php
/**
 * API: GeoJSON for map display (all types or filtered).
 *
 * GET /api/places-map.php              → all published places
 * GET /api/places-map.php?type=river   → only rivers
 * GET /api/places-map.php?type=all     → all types
 *
 * Returns GeoJSON FeatureCollection.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_helpers.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=86400, stale-while-revalidate=3600');

$type = trim($_GET['type'] ?? 'all');

if ($type !== 'all' && !isValidPlaceType($type)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid place type']);
    exit;
}

$params = [];
$where = ["p.publish_status = 'published'", 'p.lat IS NOT NULL', 'p.lng IS NOT NULL'];

if ($type !== 'all') {
    $params[':place_type'] = $type;
    $where[] = 'p.place_type = :place_type';
}

if (!empty($_GET['municipality']) && isValidMunicipality($_GET['municipality'])) {
    $params[':municipality'] = $_GET['municipality'];
    $where[] = 'p.municipality = :municipality';
}

$whereClause = ' WHERE ' . implode(' AND ', $where);

$sql = "SELECT p.id, p.slug, p.name, p.municipality, p.lat, p.lng,
               p.google_rating, p.cover_image, p.place_type
        FROM places p" . $whereClause . "
        ORDER BY p.name ASC";

$places = query($sql, $params) ?: [];

$features = [];
foreach ($places as $place) {
    $placeType = $place['place_type'];
    $config = getPlaceTypeConfig($placeType);

    $features[] = [
        'type' => 'Feature',
        'geometry' => [
            'type' => 'Point',
            'coordinates' => [floatval($place['lng']), floatval($place['lat'])],
        ],
        'properties' => [
            'id' => $place['id'],
            'slug' => $place['slug'],
            'name' => $place['name'],
            'municipality' => $place['municipality'],
            'rating' => $place['google_rating'] ? floatval($place['google_rating']) : null,
            'cover_image' => $place['cover_image'],
            'place_type' => $placeType,
            'icon' => $config['icon'] ?? 'map-pin',
            'emoji' => $config['emoji'] ?? '',
            'url' => getPlaceUrl($place),
        ],
    ];
}

echo json_encode([
    'type' => 'FeatureCollection',
    'features' => $features,
], JSON_UNESCAPED_UNICODE);
