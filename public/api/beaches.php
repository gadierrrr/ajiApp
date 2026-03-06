<?php
/**
 * API: Get Beaches
 *
 * GET /api/beaches.php
 * Query params:
 *   - tags[]: Filter by tags (OR logic)
 *   - municipality: Filter by municipality
 *   - sort: Sort by 'name', 'rating', or 'distance'
 *   - page: Page number (default 1)
 *   - limit: Results per page (default 12, max 50)
 *   - format: 'html' or 'json' (auto-detected for HTMX)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
if (session_status() === PHP_SESSION_NONE && isset($_COOKIE[session_name()])) {
    session_cache_limiter('');
    session_start();
}
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/i18n.php';

// Detect HTMX request
$isHtmxRequest = isset($_SERVER['HTTP_HX_REQUEST']);
$format = $_GET['format'] ?? ($isHtmxRequest ? 'html' : 'json');

// Set caching headers (24h browser cache, 24h CDN cache - beach data rarely changes)
header('Cache-Control: public, max-age=86400, s-maxage=86400, stale-while-revalidate=604800');
header('Vary: HX-Request');

// Get parameters
$tags = isset($_GET['tags']) ? (array)$_GET['tags'] : [];
$municipality = $_GET['municipality'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = min(50, max(1, intval($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;
$searchQuery = trim($_GET['q'] ?? '');
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

// Validate inputs
$tags = array_filter($tags, 'isValidTag');
if ($municipality && !isValidMunicipality($municipality)) {
    $municipality = '';
}

// Build query
$sql = 'SELECT DISTINCT b.id, b.slug, b.name, b.municipality, b.lat, b.lng,
               b.sargassum, b.surf, b.wind, b.cover_image, b.access_label,
               b.google_rating, b.google_review_count
        FROM beaches b';

$countSql = 'SELECT COUNT(DISTINCT b.id) as total FROM beaches b';
$params = [];
$where = ['b.publish_status = "published"'];

// Join for tag filtering
if (!empty($tags)) {
    $sql .= ' INNER JOIN beach_tags bt ON b.id = bt.beach_id';
    $countSql .= ' INNER JOIN beach_tags bt ON b.id = bt.beach_id';

    $placeholders = [];
    foreach ($tags as $i => $tag) {
        $placeholders[] = ':tag' . $i;
        $params[':tag' . $i] = $tag;
    }
    $where[] = 'bt.tag IN (' . implode(',', $placeholders) . ')';
}

// Municipality filter
if ($municipality) {
    $where[] = 'b.municipality = :municipality';
    $params[':municipality'] = $municipality;
}

if ($hasLifeguard) {
    $where[] = 'b.has_lifeguard = 1';
}

// Search query filter - searches name, municipality, and description
if ($searchQuery) {
    $where[] = '(b.name LIKE :search OR b.municipality LIKE :search2 OR b.description LIKE :search3)';
    $searchPattern = '%' . $searchQuery . '%';
    $params[':search'] = $searchPattern;
    $params[':search2'] = $searchPattern;
    $params[':search3'] = $searchPattern;
}

$whereClause = ' WHERE ' . implode(' AND ', $where);
$sql .= $whereClause;
$countSql .= $whereClause;

// Sorting
switch ($sortBy) {
    case 'rating':
        $sql .= ' ORDER BY b.google_rating DESC NULLS LAST, b.name ASC';
        break;
    default:
        $sql .= ' ORDER BY b.name ASC';
}

// Get total count
$total = queryOne($countSql, $params)['total'] ?? 0;

// Pagination
$sql .= " LIMIT {$limit} OFFSET {$offset}";

// Execute query
$beaches = query($sql, $params);

// Batch fetch tags and amenities (2 queries instead of 2*N queries)
attachBeachMetadata($beaches);

// Get user favorites if logged in
$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]);
    $userFavorites = array_column($favorites, 'beach_id');
}

// Return HTML for HTMX requests
if ($format === 'html') {
    header('Content-Type: text/html; charset=utf-8');

    if (empty($beaches)) {
        // Empty state with context
        $context = '';
        $suggestions = [];

        if (!empty($tags) && $municipality) {
            $context = "No beaches found with selected tags in <strong>" . h($municipality) . "</strong>";
            $suggestions[] = 'Try removing some filters';
            $suggestions[] = 'Search in a different municipality';
        } elseif (!empty($tags)) {
            $context = "No beaches match the selected filters";
            $suggestions[] = 'Try selecting different tags';
            $suggestions[] = 'Clear filters to see all beaches';
        } elseif ($municipality) {
            $context = "No beaches found in <strong>" . h($municipality) . "</strong>";
            $suggestions[] = 'This municipality may not have beaches in our database';
            $suggestions[] = 'Try searching nearby municipalities';
        } else {
            $context = "No beaches found";
        }

        echo '<div class="empty-state col-span-full">';
        echo '<div class="empty-state-icon">🏖️</div>';
        echo '<h3 class="empty-state-title">No beaches found</h3>';
        if ($context) {
            echo '<p class="empty-state-context">' . $context . '</p>';
        }
        if (!empty($suggestions)) {
            echo '<ul class="empty-state-suggestions">';
            foreach ($suggestions as $suggestion) {
                echo '<li>' . h($suggestion) . '</li>';
            }
            echo '</ul>';
        }
        echo '<button data-action="clearFilters" class="empty-state-action">Clear all filters</button>';
        echo '</div>';
        exit;
    }

    // Render beach cards
    foreach ($beaches as $beach) {
        $distance = null;
        $isFavorite = in_array($beach['id'], $userFavorites);
        include APP_ROOT . '/components/beach-card.php';
    }

    // Update load more button using out-of-band swap
    $totalPages = ceil($total / $limit);
    $showing = min($page * $limit, $total);

    if ($page < $totalPages) {
        // More pages available - render updated button
        $nextParams = array_merge($_GET, ['page' => $page + 1]);
        unset($nextParams['format']);
        echo '<div id="load-more-container" hx-swap-oob="true" class="text-center mt-8">';
        echo '<button id="load-more-btn"';
        echo '        hx-get="/api/beaches.php?' . h(http_build_query($nextParams)) . '"';
        echo '        hx-target="#beach-grid"';
        echo '        hx-swap="beforeend"';
        echo '        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">';
        echo 'Load More Beaches';
        echo '<span class="htmx-indicator ml-2">...</span>';
        echo '</button>';
        echo '<p class="text-sm text-gray-500 mt-2">';
        echo 'Showing ' . $showing . ' of ' . $total . ' beaches';
        echo '</p>';
        echo '</div>';
    } else {
        // No more pages - remove the button
        echo '<div id="load-more-container" hx-swap-oob="true" class="text-center mt-8">';
        echo '<p class="text-gray-500">All ' . $total . ' beaches loaded</p>';
        echo '</div>';
    }

    exit;
}

// Return JSON (default for non-HTMX requests)
header('Content-Type: application/json');
jsonResponse([
    'success' => true,
    'data' => $beaches,
    'meta' => [
        'total' => (int)$total,
        'page' => $page,
        'limit' => $limit,
        'pages' => ceil($total / $limit),
        'filters' => [
            'tags' => $tags,
            'municipality' => $municipality,
            'has_lifeguard' => $hasLifeguard,
            'sort' => $sortBy,
            'q' => $searchQuery
        ]
    ]
]);
