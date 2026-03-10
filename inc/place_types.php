<?php
/**
 * Category registry for multi-type place discovery.
 *
 * Central config that drives routing, cards, filters, SEO, and admin
 * for all place types (beaches, rivers, waterfalls, trails, restaurants, photo spots).
 */

if (defined('PLACE_TYPES_INCLUDED')) {
    return;
}
define('PLACE_TYPES_INCLUDED', true);

require_once __DIR__ . '/constants.php';

const PLACE_TYPES = [
    'beach' => [
        'table'           => 'beaches',
        'slug_prefix'     => 'beach',
        'slug_prefix_es'  => 'playa',
        'icon'            => 'umbrella-beach',
        'emoji'           => "\xF0\x9F\x8F\x96\xEF\xB8\x8F",
        'label'           => 'Beach',
        'label_plural'    => 'Beaches',
        'label_es'        => 'Playa',
        'label_plural_es' => 'Playas',
        'tags_key'        => 'TAGS',
        'amenities_key'   => 'AMENITIES',
        'tag_table'       => 'beach_tags',
        'amenity_table'   => 'beach_amenities',
        'tag_fk'          => 'beach_id',
        'amenity_fk'      => 'beach_id',
        'card_extras'     => 'beach-card-extras.php',
        'schema_type'     => ['LocalBusiness', 'Beach'],
        'sort_options'    => ['name', 'rating', 'distance'],
        'has_conditions'  => true,
        'detail_script'   => '/beach.php',
    ],
    'river' => [
        'table'           => 'rivers',
        'slug_prefix'     => 'river',
        'slug_prefix_es'  => 'rio',
        'icon'            => 'waves',
        'emoji'           => "\xF0\x9F\x8F\x9E\xEF\xB8\x8F",
        'label'           => 'River',
        'label_plural'    => 'Rivers',
        'label_es'        => 'R\xC3\xADo',
        'label_plural_es' => 'R\xC3\xADos',
        'tags_key'        => 'RIVER_TAGS',
        'amenities_key'   => 'RIVER_AMENITIES',
        'tag_table'       => 'place_tags',
        'amenity_table'   => 'place_amenities',
        'tag_fk'          => 'place_id',
        'amenity_fk'      => 'place_id',
        'card_extras'     => 'river-card-extras.php',
        'schema_type'     => ['TouristAttraction', 'BodyOfWater'],
        'sort_options'    => ['name', 'rating', 'distance'],
        'has_conditions'  => false,
        'detail_script'   => '/place.php',
    ],
    'waterfall' => [
        'table'           => 'waterfalls',
        'slug_prefix'     => 'waterfall',
        'slug_prefix_es'  => 'cascada',
        'icon'            => 'droplets',
        'emoji'           => "\xF0\x9F\x92\xA7",
        'label'           => 'Waterfall',
        'label_plural'    => 'Waterfalls',
        'label_es'        => 'Cascada',
        'label_plural_es' => 'Cascadas',
        'tags_key'        => 'WATERFALL_TAGS',
        'amenities_key'   => 'WATERFALL_AMENITIES',
        'tag_table'       => 'place_tags',
        'amenity_table'   => 'place_amenities',
        'tag_fk'          => 'place_id',
        'amenity_fk'      => 'place_id',
        'card_extras'     => 'waterfall-card-extras.php',
        'schema_type'     => ['TouristAttraction', 'Waterfall'],
        'sort_options'    => ['name', 'rating', 'distance'],
        'has_conditions'  => false,
        'detail_script'   => '/place.php',
    ],
    'trail' => [
        'table'           => 'trails',
        'slug_prefix'     => 'trail',
        'slug_prefix_es'  => 'sendero',
        'icon'            => 'mountain',
        'emoji'           => "\xF0\x9F\xA5\xBE",
        'label'           => 'Trail',
        'label_plural'    => 'Trails',
        'label_es'        => 'Sendero',
        'label_plural_es' => 'Senderos',
        'tags_key'        => 'TRAIL_TAGS',
        'amenities_key'   => 'TRAIL_AMENITIES',
        'tag_table'       => 'place_tags',
        'amenity_table'   => 'place_amenities',
        'tag_fk'          => 'place_id',
        'amenity_fk'      => 'place_id',
        'card_extras'     => 'trail-card-extras.php',
        'schema_type'     => ['TouristAttraction'],
        'sort_options'    => ['name', 'rating', 'distance', 'difficulty'],
        'has_conditions'  => false,
        'detail_script'   => '/place.php',
    ],
    'restaurant' => [
        'table'           => 'restaurants',
        'slug_prefix'     => 'restaurant',
        'slug_prefix_es'  => 'restaurante',
        'icon'            => 'utensils',
        'emoji'           => "\xF0\x9F\x8D\xBD\xEF\xB8\x8F",
        'label'           => 'Restaurant',
        'label_plural'    => 'Restaurants',
        'label_es'        => 'Restaurante',
        'label_plural_es' => 'Restaurantes',
        'tags_key'        => 'RESTAURANT_TAGS',
        'amenities_key'   => 'RESTAURANT_AMENITIES',
        'tag_table'       => 'place_tags',
        'amenity_table'   => 'place_amenities',
        'tag_fk'          => 'place_id',
        'amenity_fk'      => 'place_id',
        'card_extras'     => 'restaurant-card-extras.php',
        'schema_type'     => ['Restaurant', 'FoodEstablishment'],
        'sort_options'    => ['name', 'rating', 'distance', 'price'],
        'has_conditions'  => false,
        'detail_script'   => '/place.php',
    ],
    'photo_spot' => [
        'table'           => 'photo_spots',
        'slug_prefix'     => 'photo-spot',
        'slug_prefix_es'  => 'punto-foto',
        'icon'            => 'camera',
        'emoji'           => "\xF0\x9F\x93\xB8",
        'label'           => 'Photo Spot',
        'label_plural'    => 'Photo Spots',
        'label_es'        => 'Punto Fotogr\xC3\xA1fico',
        'label_plural_es' => 'Puntos Fotogr\xC3\xA1ficos',
        'tags_key'        => 'PHOTO_SPOT_TAGS',
        'amenities_key'   => 'PHOTO_SPOT_AMENITIES',
        'tag_table'       => 'place_tags',
        'amenity_table'   => 'place_amenities',
        'tag_fk'          => 'place_id',
        'amenity_fk'      => 'place_id',
        'card_extras'     => 'photo-spot-card-extras.php',
        'schema_type'     => ['TouristAttraction'],
        'sort_options'    => ['name', 'rating', 'distance'],
        'has_conditions'  => false,
        'detail_script'   => '/place.php',
    ],
];

/**
 * Get configuration for a place type.
 */
function getPlaceTypeConfig(string $type): ?array {
    return PLACE_TYPES[$type] ?? null;
}

/**
 * Get all valid place type keys.
 */
function getPlaceTypeKeys(): array {
    return array_keys(PLACE_TYPES);
}

/**
 * Check if a string is a valid place type key.
 */
function isValidPlaceType(string $type): bool {
    return isset(PLACE_TYPES[$type]);
}

/**
 * Get the database table name for a place type.
 */
function getPlaceTable(string $type): ?string {
    return PLACE_TYPES[$type]['table'] ?? null;
}

/**
 * Get the tag vocabulary array for a place type.
 */
function getPlaceTypeTags(string $type): array {
    $config = PLACE_TYPES[$type] ?? null;
    if (!$config) return [];
    $key = $config['tags_key'] ?? '';
    return defined($key) ? constant($key) : [];
}

/**
 * Get the amenity vocabulary array for a place type.
 */
function getPlaceTypeAmenities(string $type): array {
    $config = PLACE_TYPES[$type] ?? null;
    if (!$config) return [];
    $key = $config['amenities_key'] ?? '';
    return defined($key) ? constant($key) : [];
}

/**
 * Validate a tag for a specific place type.
 */
function isValidPlaceTag(string $type, string $tag): bool {
    $tags = getPlaceTypeTags($type);
    return in_array($tag, $tags, true);
}

/**
 * Validate an amenity for a specific place type.
 */
function isValidPlaceAmenity(string $type, string $amenity): bool {
    $amenities = getPlaceTypeAmenities($type);
    return in_array($amenity, $amenities, true);
}
