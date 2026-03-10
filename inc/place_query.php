<?php
/**
 * Generic place query builder for multi-type discovery.
 *
 * Modeled after collection_query.php but parameterized by place type.
 * Supports filtering, sorting, pagination for any registered place type.
 */

if (defined('PLACE_QUERY_INCLUDED')) {
    return;
}
define('PLACE_QUERY_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/place_types.php';
require_once __DIR__ . '/place_helpers.php';

/**
 * Normalize filters from request params for a given place type.
 */
function placeFiltersFromRequest(string $type, array $input, int $maxLimit = 120): array {
    $config = getPlaceTypeConfig($type);
    $defaultSort = 'name';
    $defaultLimit = 15;
    $defaultView = 'cards';
    $maxLimit = max(1, $maxLimit);

    $rawTags = [];
    if (isset($input['tags'])) {
        $rawTags = (array) $input['tags'];
    } elseif (isset($input['tags[]'])) {
        $rawTags = (array) $input['tags[]'];
    }
    $tags = [];
    foreach ($rawTags as $tag) {
        if (is_string($tag) && isValidPlaceTag($type, $tag)) {
            $tags[] = $tag;
        }
    }
    $tags = array_values(array_unique($tags));

    $municipality = '';
    if (isset($input['municipality']) && is_string($input['municipality']) && isValidMunicipality($input['municipality'])) {
        $municipality = $input['municipality'];
    }

    $sortOptions = $config['sort_options'] ?? ['name', 'rating', 'distance'];
    $sort = isset($input['sort']) && is_string($input['sort']) ? $input['sort'] : $defaultSort;
    if (!in_array($sort, $sortOptions, true)) {
        $sort = $defaultSort;
    }

    $view = isset($input['view']) && is_string($input['view']) ? $input['view'] : $defaultView;
    if (!in_array($view, ['cards', 'list', 'grid', 'map'], true)) {
        $view = $defaultView;
    }

    $page = max(1, intval($input['page'] ?? 1));
    $limit = intval($input['limit'] ?? $defaultLimit);
    $limit = min($maxLimit, max(1, $limit));

    $searchQuery = trim((string) ($input['q'] ?? ''));

    return [
        'q' => $searchQuery,
        'tags' => $tags,
        'municipality' => $municipality,
        'sort' => $sort,
        'view' => $view,
        'page' => $page,
        'limit' => $limit,
    ];
}

/**
 * Count places matching filters for a given type.
 */
function countPlaces(string $type, array $filters): int {
    $config = getPlaceTypeConfig($type);
    if (!$config) return 0;

    $table = $config['table'];
    $normalized = placeFiltersFromRequest($type, $filters);
    $params = [];
    $where = placeBuildWhereClause($type, $normalized, $params);
    $whereClause = ' WHERE ' . implode(' AND ', $where);

    $sql = "SELECT COUNT(*) AS total FROM {$table} p" . $whereClause;
    $row = queryOne($sql, $params);
    return intval($row['total'] ?? 0);
}

/**
 * Fetch places with metadata for a given type.
 */
function fetchPlaces(string $type, array $filters, int $maxLimit = 120): array {
    $config = getPlaceTypeConfig($type);
    if (!$config) {
        return ['places' => [], 'total' => 0, 'page' => 1, 'limit' => 15, 'pages' => 1];
    }

    $table = $config['table'];
    $normalized = placeFiltersFromRequest($type, $filters, $maxLimit);
    $params = [];
    $distanceExpr = null;
    $where = placeBuildWhereClause($type, $normalized, $params, $distanceExpr);
    $whereClause = ' WHERE ' . implode(' AND ', $where);

    // Count
    $countSql = "SELECT COUNT(*) AS total FROM {$table} p" . $whereClause;
    $countRow = queryOne($countSql, $params);
    $total = intval($countRow['total'] ?? 0);

    // Order
    $orderBy = placeOrderByClause($type, $normalized['sort'], $distanceExpr !== null);
    $offset = ($normalized['page'] - 1) * $normalized['limit'];

    $selectDistance = $distanceExpr ? ', ' . $distanceExpr . ' AS distance_km' : '';

    // Build SELECT columns — common columns for all types
    $sql = "SELECT p.id, p.slug, p.name, p.municipality, p.lat, p.lng, p.cover_image,
                   p.description, p.description_es,
                   p.google_rating, p.google_review_count, p.place_id,
                   p.publish_status" . $selectDistance . "
            FROM {$table} p" . $whereClause . "
            ORDER BY " . $orderBy . "
            LIMIT :limit OFFSET :offset";

    $queryParams = $params;
    $queryParams[':limit'] = intval($normalized['limit']);
    $queryParams[':offset'] = intval($offset);
    $places = query($sql, $queryParams) ?: [];

    // Attach type discriminator and metadata
    foreach ($places as &$place) {
        $place['place_type'] = $type;
    }
    unset($place);

    if (!empty($places)) {
        attachPlaceMetadata($type, $places);
    }

    $pages = max(1, intval(ceil($total / max(1, intval($normalized['limit'])))));

    return [
        'places' => $places,
        'total' => $total,
        'page' => intval($normalized['page']),
        'limit' => intval($normalized['limit']),
        'pages' => $pages,
        'effective_filters' => $normalized,
    ];
}

/**
 * Fetch places across ALL types (cross-category search).
 * Uses the places VIEW.
 */
function fetchAllPlaces(array $filters, int $maxLimit = 60): array {
    $normalized = placeFiltersFromRequest('beach', $filters, $maxLimit); // beach for generic validation
    $params = [];

    $where = ['p.publish_status = "published"'];

    if (!empty($normalized['municipality'])) {
        $params[':municipality'] = $normalized['municipality'];
        $where[] = 'p.municipality = :municipality';
    }

    if (!empty($normalized['q'])) {
        $search = '%' . $normalized['q'] . '%';
        $params[':search_name'] = $search;
        $params[':search_municipality'] = $search;
        $params[':search_description'] = $search;
        $where[] = '(p.name LIKE :search_name
            OR p.municipality LIKE :search_municipality
            OR p.description LIKE :search_description)';
    }

    // Optional type filter
    if (!empty($filters['type']) && isValidPlaceType($filters['type'])) {
        $params[':place_type'] = $filters['type'];
        $where[] = 'p.place_type = :place_type';
    }

    $whereClause = ' WHERE ' . implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*) AS total FROM places p' . $whereClause;
    $countRow = queryOne($countSql, $params);
    $total = intval($countRow['total'] ?? 0);

    $offset = ($normalized['page'] - 1) * $normalized['limit'];
    $orderBy = 'COALESCE(p.google_rating, 0) DESC, p.name ASC';

    if ($normalized['sort'] === 'name') {
        $orderBy = 'p.name ASC';
    }

    $sql = "SELECT p.id, p.slug, p.name, p.municipality, p.lat, p.lng, p.cover_image,
                   p.description, p.description_es,
                   p.google_rating, p.google_review_count, p.place_id,
                   p.publish_status, p.place_type
            FROM places p" . $whereClause . "
            ORDER BY " . $orderBy . "
            LIMIT :limit OFFSET :offset";

    $queryParams = $params;
    $queryParams[':limit'] = intval($normalized['limit']);
    $queryParams[':offset'] = intval($offset);
    $places = query($sql, $queryParams) ?: [];

    $pages = max(1, intval(ceil($total / max(1, intval($normalized['limit'])))));

    return [
        'places' => $places,
        'total' => $total,
        'page' => intval($normalized['page']),
        'limit' => intval($normalized['limit']),
        'pages' => $pages,
        'effective_filters' => $normalized,
    ];
}

/**
 * Build WHERE clause for a single place type query.
 */
function placeBuildWhereClause(string $type, array $filters, array &$params, ?string &$distanceExpr = null): array {
    $config = getPlaceTypeConfig($type);
    $where = ['p.publish_status = "published"'];
    $distanceExpr = null;

    // Tag filter (polymorphic — beach uses beach_tags, others use place_tags)
    if (!empty($filters['tags']) && is_array($filters['tags'])) {
        $tagTable = $config['tag_table'] ?? 'place_tags';
        $tagFk = $config['tag_fk'] ?? 'place_id';

        $tagPlaceholders = [];
        foreach ($filters['tags'] as $idx => $tag) {
            $placeholder = ':filter_tag_' . $idx;
            $tagPlaceholders[] = $placeholder;
            $params[$placeholder] = $tag;
        }
        if (!empty($tagPlaceholders)) {
            if ($tagTable === 'place_tags') {
                $params[':tag_place_type'] = $type;
                $where[] = "EXISTS (
                    SELECT 1 FROM {$tagTable} pt
                    WHERE pt.{$tagFk} = p.id
                    AND pt.place_type = :tag_place_type
                    AND pt.tag IN (" . implode(', ', $tagPlaceholders) . ")
                )";
            } else {
                // beach_tags doesn't have place_type column
                $where[] = "EXISTS (
                    SELECT 1 FROM {$tagTable} pt
                    WHERE pt.{$tagFk} = p.id
                    AND pt.tag IN (" . implode(', ', $tagPlaceholders) . ")
                )";
            }
        }
    }

    // Municipality filter
    if (!empty($filters['municipality'])) {
        $params[':municipality'] = $filters['municipality'];
        $where[] = 'p.municipality = :municipality';
    }

    // Search query
    if (!empty($filters['q'])) {
        $search = '%' . $filters['q'] . '%';
        $params[':search_name'] = $search;
        $params[':search_municipality'] = $search;
        $params[':search_description'] = $search;
        $where[] = '(p.name LIKE :search_name
            OR p.municipality LIKE :search_municipality
            OR p.description LIKE :search_description)';
    }

    // Distance filter (if user lat/lng provided)
    if (!empty($filters['user_lat']) && !empty($filters['user_lng'])) {
        $params[':user_lat'] = floatval($filters['user_lat']);
        $params[':user_lng'] = floatval($filters['user_lng']);
        $distanceExpr = '(6371 * acos(
            cos(radians(:user_lat)) * cos(radians(p.lat)) *
            cos(radians(p.lng) - radians(:user_lng)) +
            sin(radians(:user_lat)) * sin(radians(p.lat))
        ))';
        $where[] = 'p.lat IS NOT NULL';
        $where[] = 'p.lng IS NOT NULL';

        if (!empty($filters['radius_km'])) {
            $params[':radius_km'] = floatval($filters['radius_km']);
            $where[] = $distanceExpr . ' <= :radius_km';
        }
    }

    // Type-specific filters
    if ($type === 'trail' && !empty($filters['difficulty'])) {
        $params[':difficulty'] = $filters['difficulty'];
        $where[] = 'p.difficulty = :difficulty';
    }

    if ($type === 'restaurant' && !empty($filters['price_range'])) {
        $params[':price_range'] = $filters['price_range'];
        $where[] = 'p.price_range = :price_range';
    }

    return $where;
}

/**
 * Build ORDER BY clause.
 */
function placeOrderByClause(string $type, string $sort, bool $hasDistance): string {
    switch ($sort) {
        case 'rating':
            return 'COALESCE(p.google_rating, 0) DESC, COALESCE(p.google_review_count, 0) DESC, p.name ASC';
        case 'distance':
            if ($hasDistance) {
                return 'distance_km ASC, p.name ASC';
            }
            return 'p.name ASC';
        case 'difficulty':
            if ($type === 'trail') {
                return "CASE p.difficulty
                    WHEN 'easy' THEN 1
                    WHEN 'moderate' THEN 2
                    WHEN 'difficult' THEN 3
                    WHEN 'expert' THEN 4
                    ELSE 5 END ASC, p.name ASC";
            }
            return 'p.name ASC';
        case 'price':
            if ($type === 'restaurant') {
                return "CASE p.price_range
                    WHEN '$' THEN 1
                    WHEN '$$' THEN 2
                    WHEN '$$$' THEN 3
                    WHEN '$$$$' THEN 4
                    ELSE 5 END ASC, p.name ASC";
            }
            return 'p.name ASC';
        case 'name':
        default:
            return 'p.name ASC';
    }
}
