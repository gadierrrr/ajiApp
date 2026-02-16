<?php
/**
 * API: Get Beach Data for Map View.
 *
 * Supports both generic filters and collection context handoff from
 * collection explorer pages.
 *
 * GET /api/beaches-map.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=86400, s-maxage=86400');

$maxIds = 200;
$idInputs = [];
if (isset($_GET['ids'])) {
    $rawIds = $_GET['ids'];
    if (is_array($rawIds)) {
        foreach ($rawIds as $rawId) {
            $idInputs[] = (string)$rawId;
        }
    } else {
        $idInputs = array_merge($idInputs, explode(',', (string)$rawIds));
    }
}
if (isset($_GET['ids[]'])) {
    foreach ((array)$_GET['ids[]'] as $rawId) {
        $idInputs[] = (string)$rawId;
    }
}

$validIds = [];
$invalidIdsCount = 0;
foreach ($idInputs as $rawId) {
    $rawId = trim($rawId);
    if ($rawId === '') {
        continue;
    }
    if (!preg_match('/^[A-Za-z0-9_-]{1,64}$/', $rawId)) {
        $invalidIdsCount++;
        continue;
    }
    if (!in_array($rawId, $validIds, true)) {
        $validIds[] = $rawId;
    }
    if (count($validIds) >= $maxIds) {
        break;
    }
}

$strictIds = in_array((string)($_GET['strict_ids'] ?? ''), ['1', 'true'], true);
$collectionKey = isset($_GET['collection']) ? (string)$_GET['collection'] : '';
if ($collectionKey === '' && (($_GET['near'] ?? '') === 'san-juan')) {
    $collectionKey = 'beaches-near-san-juan';
}
$beaches = [];
$meta = [
    'source' => 'filters',
    'collection' => null,
    'context_fallback' => false,
    'requested_ids_count' => count($validIds),
    'returned_ids_count' => 0,
    'invalid_ids_count' => $invalidIdsCount,
];

if (!empty($validIds)) {
    $params = [];
    $idPlaceholders = [];
    foreach ($validIds as $idx => $id) {
        $placeholder = ':id_' . $idx;
        $idPlaceholders[] = $placeholder;
        $params[$placeholder] = $id;
    }

    $sql = 'SELECT b.id, b.slug, b.name, b.municipality, b.lat, b.lng,
                   b.cover_image, b.google_rating
            FROM beaches b
            WHERE b.publish_status = "published"
            AND b.id IN (' . implode(', ', $idPlaceholders) . ')';
    $rows = query($sql, $params) ?: [];

    // Preserve input order for deterministic marker ordering.
    $rowsById = [];
    foreach ($rows as $row) {
        $rowsById[(string)$row['id']] = $row;
    }
    foreach ($validIds as $id) {
        if (isset($rowsById[$id])) {
            $beaches[] = $rowsById[$id];
        }
    }
    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }
    if ($strictIds && empty($beaches)) {
        $beaches = [];
    }
    $meta['source'] = 'ids';
} elseif ($collectionKey !== '' && isValidCollectionKey($collectionKey)) {
    $mapLimit = min(500, max(1, intval($_GET['limit'] ?? 500)));
    $filtersInput = $_GET;
    $filtersInput['page'] = 1;
    $filtersInput['limit'] = $mapLimit;
    $filters = collectionFiltersFromRequest($collectionKey, $filtersInput, 500);

    $collectionData = fetchCollectionBeaches($collectionKey, $filters, 500);
    $beaches = $collectionData['beaches'] ?? [];
    $meta['source'] = 'collection';
    $meta['collection'] = $collectionKey;
    $meta['context_fallback'] = !empty($collectionData['context_fallback']);
    $meta['filters'] = $collectionData['effective_filters'] ?? [];
} else {
    $tags = isset($_GET['tags']) ? (array)$_GET['tags'] : [];
    if (isset($_GET['tags[]'])) {
        $tags = array_merge($tags, (array)$_GET['tags[]']);
    }
    $tags = array_values(array_filter($tags, 'isValidTag'));
    $hasLifeguard = isset($_GET['has_lifeguard']) && in_array((string)$_GET['has_lifeguard'], ['1', 'true'], true);
    $amenities = [];
    if (isset($_GET['amenities'])) {
        $amenities = array_merge($amenities, (array)$_GET['amenities']);
    }
    if (isset($_GET['amenities[]'])) {
        $amenities = array_merge($amenities, (array)$_GET['amenities[]']);
    }
    if (in_array('lifeguards', $amenities, true) || in_array('lifeguard', $amenities, true)) {
        $hasLifeguard = true;
    }

    $municipality = '';
    if (isset($_GET['municipality']) && is_string($_GET['municipality']) && isValidMunicipality($_GET['municipality'])) {
        $municipality = $_GET['municipality'];
    }

    $searchQuery = trim((string)($_GET['q'] ?? ''));
    $sort = isset($_GET['sort']) ? (string)$_GET['sort'] : 'name';
    if (!in_array($sort, ['name', 'rating', 'reviews'], true)) {
        $sort = 'name';
    }

    $params = [];
    $where = ['b.publish_status = "published"'];

    if (!empty($tags)) {
        $tagPlaceholders = [];
        foreach ($tags as $idx => $tag) {
            $placeholder = ':tag_' . $idx;
            $tagPlaceholders[] = $placeholder;
            $params[$placeholder] = $tag;
        }
        $where[] = 'EXISTS (
            SELECT 1 FROM beach_tags bt
            WHERE bt.beach_id = b.id
            AND bt.tag IN (' . implode(', ', $tagPlaceholders) . ')
        )';
    }

    if ($municipality !== '') {
        $params[':municipality'] = $municipality;
        $where[] = 'b.municipality = :municipality';
    }

    if ($hasLifeguard) {
        $where[] = 'b.has_lifeguard = 1';
    }

    if ($searchQuery !== '') {
        $search = '%' . $searchQuery . '%';
        $params[':search_name'] = $search;
        $params[':search_municipality'] = $search;
        $params[':search_description'] = $search;
        $where[] = '(b.name LIKE :search_name
            OR b.municipality LIKE :search_municipality
            OR b.description LIKE :search_description)';
    }

    $whereClause = ' WHERE ' . implode(' AND ', $where);
    $orderBy = 'b.name ASC';
    if ($sort === 'rating') {
        $orderBy = 'COALESCE(b.google_rating, 0) DESC, COALESCE(b.google_review_count, 0) DESC, b.name ASC';
    } elseif ($sort === 'reviews') {
        $orderBy = 'COALESCE(b.google_review_count, 0) DESC, COALESCE(b.google_rating, 0) DESC, b.name ASC';
    }

    $sql = 'SELECT b.id, b.slug, b.name, b.municipality, b.lat, b.lng,
                   b.cover_image, b.google_rating
            FROM beaches b' . $whereClause . '
            ORDER BY ' . $orderBy . '
            LIMIT 500';
    $beaches = query($sql, $params) ?: [];
    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    $meta['filters'] = [
        'tags' => $tags,
        'municipality' => $municipality,
        'has_lifeguard' => $hasLifeguard,
        'q' => $searchQuery,
        'sort' => $sort,
    ];
}

$meta['returned_ids_count'] = count($beaches);

echo json_encode([
    'beaches' => $beaches,
    'total' => count($beaches),
    'meta' => $meta,
]);
