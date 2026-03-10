<?php
/**
 * Type-aware helper functions for multi-category places.
 */

if (defined('PLACE_HELPERS_INCLUDED')) {
    return;
}
define('PLACE_HELPERS_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/place_types.php';

/**
 * Build the public URL for a place.
 */
function getPlaceUrl(array $place, string $locale = 'en'): string {
    $type = $place['place_type'] ?? 'beach';
    $slug = $place['slug'] ?? '';
    $config = getPlaceTypeConfig($type);
    if (!$config || $slug === '') return '/';

    $prefix = ($locale === 'es')
        ? ($config['slug_prefix_es'] ?? $config['slug_prefix'])
        : $config['slug_prefix'];

    return ($locale === 'es')
        ? '/es/' . $prefix . '/' . $slug
        : '/' . $prefix . '/' . $slug;
}

/**
 * Get the detail page URL for an API/HTMX call.
 */
function getPlaceDetailUrl(array $place): string {
    $type = $place['place_type'] ?? 'beach';
    $slug = $place['slug'] ?? '';
    if ($type === 'beach') {
        return '/api/beach-detail.php?slug=' . urlencode($slug);
    }
    return '/api/place-detail.php?type=' . urlencode($type) . '&slug=' . urlencode($slug);
}

/**
 * Get the Lucide icon name for a place type.
 */
function getPlaceIcon(string $type): string {
    return PLACE_TYPES[$type]['icon'] ?? 'map-pin';
}

/**
 * Get the emoji for a place type.
 */
function getPlaceEmoji(string $type): string {
    return PLACE_TYPES[$type]['emoji'] ?? "\xF0\x9F\x93\x8D";
}

/**
 * Get the display label for a place type.
 */
function getPlaceLabel(string $type, string $locale = 'en'): string {
    $config = PLACE_TYPES[$type] ?? null;
    if (!$config) return ucfirst($type);
    return ($locale === 'es')
        ? ($config['label_es'] ?? $config['label'])
        : $config['label'];
}

/**
 * Get the plural display label for a place type.
 */
function getPlaceLabelPlural(string $type, string $locale = 'en'): string {
    $config = PLACE_TYPES[$type] ?? null;
    if (!$config) return ucfirst($type) . 's';
    return ($locale === 'es')
        ? ($config['label_plural_es'] ?? $config['label_plural'])
        : $config['label_plural'];
}

/**
 * Batch fetch tags for places of a given type.
 * For beaches, uses beach_tags table. For others, uses place_tags.
 */
function batchGetPlaceTags(string $type, array $placeIds): array {
    if (empty($placeIds)) return [];

    $config = getPlaceTypeConfig($type);
    if (!$config) return [];

    $tagTable = $config['tag_table'];
    $tagFk = $config['tag_fk'];
    $placeholders = implode(',', array_fill(0, count($placeIds), '?'));

    if ($tagTable === 'place_tags') {
        // place_tags has place_type column
        $params = array_merge([$type], array_values($placeIds));
        $rows = query(
            "SELECT place_id, tag FROM place_tags WHERE place_type = ? AND place_id IN ($placeholders)",
            $params
        );
        $result = array_fill_keys($placeIds, []);
        foreach ($rows ?: [] as $row) {
            $result[$row['place_id']][] = $row['tag'];
        }
        return $result;
    }

    // beach_tags table
    $rows = query(
        "SELECT {$tagFk} AS place_id, tag FROM {$tagTable} WHERE {$tagFk} IN ($placeholders)",
        array_values($placeIds)
    );
    $result = array_fill_keys($placeIds, []);
    foreach ($rows ?: [] as $row) {
        $result[$row['place_id']][] = $row['tag'];
    }
    return $result;
}

/**
 * Batch fetch amenities for places of a given type.
 */
function batchGetPlaceAmenities(string $type, array $placeIds): array {
    if (empty($placeIds)) return [];

    $config = getPlaceTypeConfig($type);
    if (!$config) return [];

    $amenityTable = $config['amenity_table'];
    $amenityFk = $config['amenity_fk'];
    $placeholders = implode(',', array_fill(0, count($placeIds), '?'));

    if ($amenityTable === 'place_amenities') {
        $params = array_merge([$type], array_values($placeIds));
        $rows = query(
            "SELECT place_id, amenity FROM place_amenities WHERE place_type = ? AND place_id IN ($placeholders)",
            $params
        );
        $result = array_fill_keys($placeIds, []);
        foreach ($rows ?: [] as $row) {
            $result[$row['place_id']][] = $row['amenity'];
        }
        return $result;
    }

    // beach_amenities table
    $rows = query(
        "SELECT {$amenityFk} AS place_id, amenity FROM {$amenityTable} WHERE {$amenityFk} IN ($placeholders)",
        array_values($placeIds)
    );
    $result = array_fill_keys($placeIds, []);
    foreach ($rows ?: [] as $row) {
        $result[$row['place_id']][] = $row['amenity'];
    }
    return $result;
}

/**
 * Attach tags and amenities to a places array efficiently.
 */
function attachPlaceMetadata(string $type, array &$places): void {
    if (empty($places)) return;

    $placeIds = array_column($places, 'id');
    $allTags = batchGetPlaceTags($type, $placeIds);
    $allAmenities = batchGetPlaceAmenities($type, $placeIds);

    foreach ($places as &$place) {
        $place['tags'] = $allTags[$place['id']] ?? [];
        $place['amenities'] = $allAmenities[$place['id']] ?? [];
    }
}

/**
 * Check if a place is favorited by the current user.
 */
function isPlaceFavorited(string $userId, string $type, string $placeId): bool {
    if ($type === 'beach') {
        $row = queryOne(
            'SELECT 1 FROM user_favorites WHERE user_id = ? AND beach_id = ? LIMIT 1',
            [$userId, $placeId]
        );
    } else {
        $row = queryOne(
            'SELECT 1 FROM place_favorites WHERE user_id = ? AND place_type = ? AND place_id = ? LIMIT 1',
            [$userId, $type, $placeId]
        );
    }
    return $row !== null;
}

/**
 * Batch check favorites for multiple places of a given type.
 */
function batchGetPlaceFavorites(string $userId, string $type, array $placeIds): array {
    if (empty($placeIds) || empty($userId)) return [];

    $placeholders = implode(',', array_fill(0, count($placeIds), '?'));

    if ($type === 'beach') {
        $params = array_merge([$userId], array_values($placeIds));
        $rows = query(
            "SELECT beach_id AS place_id FROM user_favorites WHERE user_id = ? AND beach_id IN ($placeholders)",
            $params
        );
    } else {
        $params = array_merge([$userId, $type], array_values($placeIds));
        $rows = query(
            "SELECT place_id FROM place_favorites WHERE user_id = ? AND place_type = ? AND place_id IN ($placeholders)",
            $params
        );
    }

    $favored = [];
    foreach ($rows ?: [] as $row) {
        $favored[$row['place_id']] = true;
    }
    return $favored;
}

/**
 * Get directions URL for any place.
 */
function getPlaceDirectionsUrl(array $place): string {
    $placeId = $place['place_id'] ?? '';
    if ($placeId !== '') {
        return 'https://www.google.com/maps/search/?api=1&query=Google&query_place_id=' . urlencode($placeId);
    }
    $lat = $place['lat'] ?? '';
    $lng = $place['lng'] ?? '';
    if ($lat !== '' && $lng !== '') {
        return 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng;
    }
    $name = $place['name'] ?? '';
    return 'https://www.google.com/maps/search/?api=1&query=' . urlencode($name . ' Puerto Rico');
}

/**
 * Fetch a single place by type and slug.
 */
function fetchPlaceBySlug(string $type, string $slug): ?array {
    $config = getPlaceTypeConfig($type);
    if (!$config) return null;

    $table = $config['table'];
    $place = queryOne("SELECT * FROM {$table} WHERE slug = :slug AND publish_status = 'published'", [':slug' => $slug]);
    if (!$place) return null;

    $place['place_type'] = $type;

    // Attach tags/amenities
    $places = [$place];
    attachPlaceMetadata($type, $places);
    $place = $places[0];

    // Attach gallery
    if ($type === 'beach') {
        $place['gallery'] = query(
            'SELECT * FROM beach_gallery WHERE beach_id = ? ORDER BY position',
            [$place['id']]
        ) ?: [];
    } else {
        $place['gallery'] = query(
            'SELECT * FROM place_gallery WHERE place_type = ? AND place_id = ? ORDER BY position',
            [$type, $place['id']]
        ) ?: [];
    }

    return $place;
}

/**
 * Get type-specific extra data for a place (e.g., conditions for beaches).
 */
function getPlaceTypeExtras(array $place): array {
    $type = $place['place_type'] ?? 'beach';
    $extras = [];

    switch ($type) {
        case 'beach':
            $extras['sargassum'] = $place['sargassum'] ?? null;
            $extras['surf'] = $place['surf'] ?? null;
            $extras['wind'] = $place['wind'] ?? null;
            $extras['has_lifeguard'] = $place['has_lifeguard'] ?? null;
            break;
        case 'river':
            $extras['water_clarity'] = $place['water_clarity'] ?? null;
            $extras['current_strength'] = $place['current_strength'] ?? null;
            $extras['swimmable'] = $place['swimmable'] ?? null;
            break;
        case 'waterfall':
            $extras['height_meters'] = $place['height_meters'] ?? null;
            $extras['num_tiers'] = $place['num_tiers'] ?? null;
            $extras['hike_difficulty'] = $place['hike_difficulty'] ?? null;
            $extras['hike_distance_km'] = $place['hike_distance_km'] ?? null;
            break;
        case 'trail':
            $extras['difficulty'] = $place['difficulty'] ?? null;
            $extras['distance_km'] = $place['distance_km'] ?? null;
            $extras['elevation_gain_m'] = $place['elevation_gain_m'] ?? null;
            $extras['estimated_time_minutes'] = $place['estimated_time_minutes'] ?? null;
            break;
        case 'restaurant':
            $extras['cuisine_type'] = $place['cuisine_type'] ?? null;
            $extras['price_range'] = $place['price_range'] ?? null;
            $extras['phone'] = $place['phone'] ?? null;
            $extras['hours_json'] = $place['hours_json'] ?? null;
            break;
        case 'photo_spot':
            $extras['best_light'] = $place['best_light'] ?? null;
            $extras['best_time_of_day'] = $place['best_time_of_day'] ?? null;
            $extras['tripod_recommended'] = $place['tripod_recommended'] ?? null;
            $extras['drone_allowed'] = $place['drone_allowed'] ?? null;
            break;
    }

    return $extras;
}
