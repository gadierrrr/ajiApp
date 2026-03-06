<?php
/**
 * Shared collection query and filter helpers.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/collection_contexts.php';

if (defined('COLLECTION_QUERY_INCLUDED')) {
    return;
}
define('COLLECTION_QUERY_INCLUDED', true);

/**
 * @return array<string>
 */
function getCollectionKeys(): array {
    return array_keys(collectionContextRegistry());
}

function isValidCollectionKey(string $key): bool {
    $registry = collectionContextRegistry();
    return isset($registry[$key]);
}

function getCollectionContext(string $key): array {
    $registry = collectionContextRegistry();
    if (isset($registry[$key])) {
        return $registry[$key];
    }
    return $registry['best-beaches'];
}

/**
 * Normalize collection filters from request/query params.
 */
function collectionFiltersFromRequest(string $key, array $input, int $maxLimit = 120): array {
    $context = getCollectionContext($key);
    $defaultSort = $context['default_sort'] ?? 'rating';
    $defaultLimit = intval($context['default_limit'] ?? 15);
    $defaultView = 'cards';
    $maxLimit = max(1, $maxLimit);

    $rawTags = [];
    if (isset($input['tags'])) {
        $rawTags = (array)$input['tags'];
    } elseif (isset($input['tags[]'])) {
        $rawTags = (array)$input['tags[]'];
    }
    $tags = [];
    foreach ($rawTags as $tag) {
        if (is_string($tag) && isValidTag($tag)) {
            $tags[] = $tag;
        }
    }
    $tags = array_values(array_unique($tags));

    $municipality = '';
    if (isset($input['municipality']) && is_string($input['municipality']) && isValidMunicipality($input['municipality'])) {
        $municipality = $input['municipality'];
    }

    $sort = isset($input['sort']) && is_string($input['sort']) ? $input['sort'] : $defaultSort;
    if (!in_array($sort, ['name', 'rating', 'reviews', 'distance'], true)) {
        $sort = $defaultSort;
    }

    $view = isset($input['view']) && is_string($input['view']) ? $input['view'] : $defaultView;
    if (!in_array($view, ['cards', 'list', 'grid', 'map'], true)) {
        $view = $defaultView;
    }

    $page = max(1, intval($input['page'] ?? 1));
    $limit = intval($input['limit'] ?? $defaultLimit);
    $limit = min($maxLimit, max(1, $limit));

    $includeAllRaw = $input['include_all'] ?? '0';
    $includeAll = in_array((string)$includeAllRaw, ['1', 'true', 'yes', 'on'], true);

    $searchQuery = trim((string)($input['q'] ?? ''));

    return [
        'q' => $searchQuery,
        'tags' => $tags,
        'municipality' => $municipality,
        'sort' => $sort,
        'view' => $view,
        'page' => $page,
        'limit' => $limit,
        'include_all' => $includeAll,
    ];
}

function countCollectionBeaches(string $key, array $filters, int $maxLimit = 120): int {
    $context = getCollectionContext($key);
    $normalized = collectionFiltersFromRequest($key, $filters, $maxLimit);

    $params = [];
    $distanceExpr = null;
    $where = collectionBuildWhereClause($context, $normalized, $params, $distanceExpr);
    $whereClause = ' WHERE ' . implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*) AS total FROM beaches b' . $whereClause;
    $countRow = queryOne($countSql, $params);
    return intval($countRow['total'] ?? 0);
}

/**
 * Fetch beaches and metadata for a collection context.
 *
 * @return array<string,mixed>
 */
function fetchCollectionBeaches(string $key, array $filters, int $maxLimit = 120): array {
    $context = getCollectionContext($key);
    $normalized = collectionFiltersFromRequest($key, $filters, $maxLimit);

    $contextFallback = false;
    $result = collectionRunQuery($context, $normalized);

    $hasUserFilters = collectionHasUserFilters($normalized);
    if (!$normalized['include_all'] && !$hasUserFilters && ($result['total'] ?? 0) === 0) {
        $fallbackFilters = $normalized;
        $fallbackFilters['include_all'] = true;
        $result = collectionRunQuery($context, $fallbackFilters);
        $normalized = $fallbackFilters;
        $contextFallback = true;
    }

    $result['collection'] = $context;
    $result['effective_filters'] = $normalized;
    $result['context_fallback'] = $contextFallback;
    return $result;
}

function collectionHasUserFilters(array $filters): bool {
    return ($filters['q'] ?? '') !== ''
        || !empty($filters['tags'])
        || ($filters['municipality'] ?? '') !== '';
}

/**
 * @param array<string,mixed> $context
 * @param array<string,mixed> $filters
 * @return array<string,mixed>
 */
function collectionRunQuery(array $context, array $filters): array {
    $params = [];
    $distanceExpr = null;
    $where = collectionBuildWhereClause($context, $filters, $params, $distanceExpr);
    $whereClause = ' WHERE ' . implode(' AND ', $where);

    $countSql = 'SELECT COUNT(*) AS total FROM beaches b' . $whereClause;
    $countRow = queryOne($countSql, $params);
    $total = intval($countRow['total'] ?? 0);

    $orderBy = collectionOrderByClause($filters['sort'], $distanceExpr !== null);
    $offset = ($filters['page'] - 1) * $filters['limit'];

    $selectDistance = $distanceExpr ? ', ' . $distanceExpr . ' AS distance_km' : '';
    $sql = 'SELECT b.id, b.slug, b.name, b.municipality, b.lat, b.lng, b.cover_image, b.description, b.description_es,
                   b.google_rating, b.google_review_count, b.access_label, b.place_id, b.sargassum, b.surf, b.wind,
                   b.has_lifeguard, b.safe_for_children' . $selectDistance . '
            FROM beaches b' . $whereClause . '
            ORDER BY ' . $orderBy . '
            LIMIT :limit OFFSET :offset';

    $queryParams = $params;
    $queryParams[':limit'] = intval($filters['limit']);
    $queryParams[':offset'] = intval($offset);
    $beaches = query($sql, $queryParams) ?: [];

    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    $pages = max(1, intval(ceil($total / max(1, intval($filters['limit'])))));

    return [
        'beaches' => $beaches,
        'total' => $total,
        'page' => intval($filters['page']),
        'limit' => intval($filters['limit']),
        'pages' => $pages,
    ];
}

/**
 * @param array<string,mixed> $context
 * @param array<string,mixed> $filters
 * @param array<string,mixed> $params
 * @return array<int,string>
 */
function collectionBuildWhereClause(array $context, array $filters, array &$params, ?string &$distanceExpr): array {
    $where = ['b.publish_status = "published"'];
    $distanceExpr = null;
    $isIncludeAll = !empty($filters['include_all']);

    if (!$isIncludeAll) {
        $mode = $context['mode'] ?? 'best';

        if ($mode === 'best') {
            $where[] = 'b.google_rating IS NOT NULL';
            if (!empty($context['municipalities']) && is_array($context['municipalities'])) {
                $munPlaceholders = [];
                foreach ($context['municipalities'] as $idx => $municipality) {
                    $placeholder = ':ctx_municipality_' . $idx;
                    $munPlaceholders[] = $placeholder;
                    $params[$placeholder] = $municipality;
                }
                if (!empty($munPlaceholders)) {
                    $where[] = 'b.municipality IN (' . implode(', ', $munPlaceholders) . ')';
                }
            }
        } elseif ($mode === 'tag') {
            $contextTag = (string)($context['context_tag'] ?? '');
            if ($contextTag !== '') {
                $params[':context_tag'] = $contextTag;
                $where[] = 'EXISTS (
                    SELECT 1
                    FROM beach_tags bt_context
                    WHERE bt_context.beach_id = b.id
                    AND bt_context.tag = :context_tag
                )';
            }
        } elseif ($mode === 'radius') {
            $params[':ctx_lat'] = floatval($context['center_lat'] ?? 0);
            $params[':ctx_lng'] = floatval($context['center_lng'] ?? 0);
            $params[':ctx_radius'] = floatval($context['radius_km'] ?? 25);
            $distanceExpr = '(6371 * acos(
                cos(radians(:ctx_lat)) * cos(radians(b.lat)) *
                cos(radians(b.lng) - radians(:ctx_lng)) +
                sin(radians(:ctx_lat)) * sin(radians(b.lat))
            ))';
            $where[] = 'b.lat IS NOT NULL';
            $where[] = 'b.lng IS NOT NULL';
            $where[] = $distanceExpr . ' <= :ctx_radius';
        } elseif ($mode === 'hidden') {
            $hiddenTags = (array)($context['hidden_tags'] ?? ['secluded']);
            $tagPlaceholders = [];
            foreach ($hiddenTags as $idx => $tag) {
                $placeholder = ':hidden_tag_' . $idx;
                $tagPlaceholders[] = $placeholder;
                $params[$placeholder] = $tag;
            }
            $params[':hidden_max_reviews'] = intval($context['max_review_count'] ?? 200);
            if (!empty($tagPlaceholders)) {
                $where[] = '(
                    EXISTS (
                        SELECT 1
                        FROM beach_tags bt_hidden
                        WHERE bt_hidden.beach_id = b.id
                        AND bt_hidden.tag IN (' . implode(', ', $tagPlaceholders) . ')
                    )
                    OR COALESCE(b.google_review_count, 0) <= :hidden_max_reviews
                )';
            } else {
                $where[] = 'COALESCE(b.google_review_count, 0) <= :hidden_max_reviews';
            }
        }
    }

    if (!empty($filters['tags']) && is_array($filters['tags'])) {
        $tagPlaceholders = [];
        foreach ($filters['tags'] as $idx => $tag) {
            $placeholder = ':filter_tag_' . $idx;
            $tagPlaceholders[] = $placeholder;
            $params[$placeholder] = $tag;
        }
        if (!empty($tagPlaceholders)) {
            $where[] = 'EXISTS (
                SELECT 1
                FROM beach_tags bt_filter
                WHERE bt_filter.beach_id = b.id
                AND bt_filter.tag IN (' . implode(', ', $tagPlaceholders) . ')
            )';
        }
    }

    if (!empty($filters['municipality'])) {
        $params[':municipality'] = $filters['municipality'];
        $where[] = 'b.municipality = :municipality';
    }

    if (!empty($filters['q'])) {
        $search = '%' . $filters['q'] . '%';
        $params[':search_name'] = $search;
        $params[':search_municipality'] = $search;
        $params[':search_description'] = $search;
        $where[] = '(b.name LIKE :search_name
            OR b.municipality LIKE :search_municipality
            OR b.description LIKE :search_description)';
    }

    return $where;
}

function collectionOrderByClause(string $sort, bool $hasDistance): string {
    switch ($sort) {
        case 'rating':
            return 'COALESCE(b.google_rating, 0) DESC, COALESCE(b.google_review_count, 0) DESC, b.name ASC';
        case 'reviews':
            return 'COALESCE(b.google_review_count, 0) DESC, COALESCE(b.google_rating, 0) DESC, b.name ASC';
        case 'distance':
            if ($hasDistance) {
                return 'distance_km ASC, b.name ASC';
            }
            return 'b.name ASC';
        case 'name':
        default:
            return 'b.name ASC';
    }
}
