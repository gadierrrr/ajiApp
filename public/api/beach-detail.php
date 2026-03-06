<?php
/**
 * API: Get Beach Details
 *
 * GET /api/beach-detail.php?id=xxx OR ?slug=xxx
 * Returns full beach data including features, tips, gallery
 * Can return HTML (for HTMX) or JSON
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

// Get beach by ID or slug
$beachId = $_GET['id'] ?? '';
$slug = $_GET['slug'] ?? '';
$format = $_GET['format'] ?? 'html'; // html or json

// Accept explicit lang parameter (from JS), otherwise use i18n system detection
$lang = $_GET['lang'] ?? '';
if ($lang && in_array($lang, ['en', 'es'], true)) {
    // Override cookie superglobal so getCurrentLanguage() and __() use this lang
    $_COOKIE['lang'] = $lang;
} else {
    $lang = getCurrentLanguage();
}

if (!$beachId && !$slug) {
    if ($format === 'json') {
        jsonResponse(['success' => false, 'error' => 'Beach ID or slug required'], 400);
    } else {
        echo '<div class="p-8 text-center text-red-600">' . h(__('beach.beach_not_found')) . '</div>';
        exit;
    }
}

// Fetch beach
if ($beachId) {
    $beach = queryOne('SELECT * FROM beaches WHERE id = :id AND publish_status = "published"', [':id' => $beachId]);
} else {
    $beach = queryOne('SELECT * FROM beaches WHERE slug = :slug AND publish_status = "published"', [':slug' => $slug]);
}

if (!$beach) {
    if ($format === 'json') {
        jsonResponse(['success' => false, 'error' => 'Beach not found'], 404);
    } else {
        echo '<div class="p-8 text-center"><div class="text-4xl mb-4">🏖️</div><p class="text-gray-600">' . h(__('beach.beach_not_found')) . '</p></div>';
        exit;
    }
}

// Fetch related data
$beach['tags'] = array_column(
    query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
    'tag'
);

$beach['amenities'] = array_column(
    query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
    'amenity'
);

$beach['gallery'] = array_column(
    query('SELECT image_url FROM beach_gallery WHERE beach_id = :id ORDER BY position', [':id' => $beach['id']]),
    'image_url'
);

$beach['aliases'] = array_column(
    query('SELECT alias FROM beach_aliases WHERE beach_id = :id', [':id' => $beach['id']]),
    'alias'
);

$beach['features'] = query(
    'SELECT title, title_es, description, description_es FROM beach_features WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);

$beach['tips'] = query(
    'SELECT category, tip, tip_es FROM beach_tips WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);

// Fetch user reviews (latest 5 for drawer)
$reviews = query("
    SELECT
        r.id, r.rating, r.title, r.review_text, r.visit_date, r.visit_type,
        r.helpful_count, r.created_at, r.would_recommend,
        u.name as user_name
    FROM beach_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.beach_id = :id AND r.status = 'published'
    ORDER BY r.created_at DESC
    LIMIT 5
", [':id' => $beach['id']]);

// Fetch safety information
$safety = queryOne('SELECT * FROM beach_safety WHERE beach_id = :id', [':id' => $beach['id']]) ?? [];

// Return based on format
if ($format === 'json') {
    // Set caching headers for JSON (24h cache - beach data rarely changes)
    header('Cache-Control: public, max-age=86400, s-maxage=86400');
    jsonResponse([
        'success' => true,
        'data' => $beach
    ]);
} else {
    // Return HTML for HTMX
    include APP_ROOT . '/components/beach-drawer.php';
}
