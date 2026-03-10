<?php
/**
 * Universal place detail page.
 *
 * Dispatches by type — renders the full detail view for any non-beach place.
 * Beaches continue to use beach.php.
 *
 * URL: /river/:slug, /trail/:slug, /waterfall/:slug, etc.
 * Params: ?type=river&slug=rio-tanama (set by routing)
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_query.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/session.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/security_headers.php';

$type = trim($_GET['type'] ?? '');
$slug = trim($_GET['slug'] ?? '');
$lang = getCurrentLanguage();

if (!isValidPlaceType($type) || $slug === '') {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

$place = fetchPlaceBySlug($type, $slug);
if (!$place) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

$config = getPlaceTypeConfig($type);
$placeName = $place['name'] ?? '';
$placeMunicipality = $place['municipality'] ?? '';
$description = ($lang === 'es' && !empty($place['description_es']))
    ? $place['description_es']
    : ($place['description'] ?? '');
$placeRating = $place['google_rating'] ?? null;
$placeReviewCount = $place['google_review_count'] ?? 0;
$coverImage = $place['cover_image'] ?? '/assets/images/placeholder-beach.jpg';
$directionsUrl = getPlaceDirectionsUrl($place);
$typeLabel = getPlaceLabel($type, $lang);
$typeIcon = $config['icon'];
$typeEmoji = $config['emoji'];
$placeTags = $place['tags'] ?? [];
$placeAmenities = $place['amenities'] ?? [];
$gallery = $place['gallery'] ?? [];
$extras = getPlaceTypeExtras($place);

// Reviews
$reviews = query(
    'SELECT r.*, u.name AS user_name FROM place_reviews r
     LEFT JOIN users u ON u.id = r.user_id
     WHERE r.place_type = :type AND r.place_id = :id AND r.status = "published"
     ORDER BY r.created_at DESC LIMIT 10',
    [':type' => $type, ':id' => $place['id']]
) ?: [];

// Favorite check
$isFavorite = false;
if (function_exists('isAuthenticated') && isAuthenticated()) {
    $userId = currentUser()['id'] ?? null;
    if ($userId) {
        $isFavorite = isPlaceFavorited($userId, $type, $place['id']);
    }
}

// Similar places
$similarPlaces = [];
if (!empty($placeTags)) {
    $tagPlaceholders = implode(',', array_fill(0, count($placeTags), '?'));
    $table = $config['table'];

    if ($config['tag_table'] === 'place_tags') {
        $similarParams = array_merge([$type], $placeTags, [$place['id']]);
        $similarPlaces = query(
            "SELECT DISTINCT p.id, p.slug, p.name, p.municipality, p.lat, p.lng,
                    p.cover_image, p.google_rating, p.google_review_count, p.description
             FROM {$table} p
             INNER JOIN place_tags pt ON pt.place_id = p.id AND pt.place_type = ?
             WHERE pt.tag IN ({$tagPlaceholders})
             AND p.id <> ? AND p.publish_status = 'published'
             ORDER BY p.google_rating DESC LIMIT 4",
            $similarParams
        ) ?: [];
    } else {
        $fk = $config['tag_fk'];
        $similarParams = array_merge($placeTags, [$place['id']]);
        $similarPlaces = query(
            "SELECT DISTINCT p.id, p.slug, p.name, p.municipality, p.lat, p.lng,
                    p.cover_image, p.google_rating, p.google_review_count, p.description
             FROM {$table} p
             INNER JOIN {$config['tag_table']} bt ON bt.{$fk} = p.id
             WHERE bt.tag IN ({$tagPlaceholders})
             AND p.id <> ? AND p.publish_status = 'published'
             ORDER BY p.google_rating DESC LIMIT 4",
            $similarParams
        ) ?: [];
    }
    foreach ($similarPlaces as &$sp) {
        $sp['place_type'] = $type;
    }
    unset($sp);
}

// Page meta
$pageTitle = $placeName . ' - ' . $typeLabel . ' in ' . $placeMunicipality;
$pageDescription = $description ? mb_substr(strip_tags($description), 0, 160) : "{$placeName} is a {$typeLabel} located in {$placeMunicipality}, Puerto Rico.";
$canonicalPath = getPlaceUrl($place, $lang);

include APP_ROOT . '/components/header.php';
include APP_ROOT . '/components/nav.php';
?>

<!-- Hero -->
<section class="place-hero relative h-72 md:h-96 overflow-hidden">
    <img src="<?= h($coverImage) ?>" alt="<?= h($placeName) ?>"
         class="w-full h-full object-cover"
         data-fallback-src="/assets/images/placeholder-beach.jpg">
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/30 to-transparent"></div>

    <div class="absolute bottom-6 left-6 right-6 md:bottom-10 md:left-10 md:right-10">
        <!-- Breadcrumb -->
        <nav class="text-sm text-white/60 mb-3">
            <a href="<?= h(routeUrl('explore', $lang)) ?>" class="hover:text-white">Explore</a>
            <span class="mx-1">/</span>
            <?php
                $collectionRoutes = [
                    'beach' => 'best_beaches', 'river' => 'best_rivers',
                    'waterfall' => 'best_waterfalls', 'trail' => 'best_trails',
                    'restaurant' => 'best_restaurants', 'photo_spot' => 'best_photo_spots',
                ];
            ?>
            <a href="<?= h(routeUrl($collectionRoutes[$type] ?? 'explore', $lang)) ?>" class="hover:text-white">
                <?= h(getPlaceLabelPlural($type, $lang)) ?>
            </a>
            <span class="mx-1">/</span>
            <span class="text-white"><?= h($placeName) ?></span>
        </nav>

        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm mb-3">
            <i data-lucide="<?= h($typeIcon) ?>" class="w-3.5 h-3.5"></i>
            <?= h($typeLabel) ?>
        </span>
        <h1 class="text-3xl md:text-4xl font-bold text-white"><?= h($placeName) ?></h1>
        <p class="text-white/80 flex items-center gap-2 mt-2 text-lg">
            <i data-lucide="map-pin" class="w-5 h-5"></i>
            <?= h($placeMunicipality) ?>, Puerto Rico
        </p>
    </div>

    <?php if (function_exists('isAuthenticated') && isAuthenticated()): ?>
    <button class="absolute top-4 right-4 md:top-6 md:right-6 p-3 rounded-full bg-black/40 backdrop-blur-sm hover:bg-black/60 transition <?= $isFavorite ? 'text-red-500' : 'text-white' ?>"
            hx-post="/api/toggle-place-favorite.php"
            hx-vals='{"type":"<?= h($type) ?>","place_id":"<?= h($place['id']) ?>"}'
            hx-swap="outerHTML">
        <i data-lucide="heart" class="w-6 h-6 <?= $isFavorite ? 'fill-current' : '' ?>"></i>
    </button>
    <?php endif; ?>
</section>

<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Main content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Rating & actions -->
            <div class="flex flex-wrap items-center gap-4">
                <?php if ($placeRating): ?>
                <div class="flex items-center gap-2">
                    <div class="flex items-center gap-0.5 text-yellow-500">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i data-lucide="star" class="w-5 h-5 <?= $i <= round($placeRating) ? 'fill-current' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="text-lg font-semibold text-gray-900 dark:text-white"><?= number_format($placeRating, 1) ?></span>
                    <span class="text-gray-400">(<?= number_format($placeReviewCount) ?>)</span>
                </div>
                <?php endif; ?>
                <a href="<?= h($directionsUrl) ?>" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm font-medium">
                    <i data-lucide="navigation" class="w-4 h-4"></i>
                    Get Directions
                </a>
            </div>

            <!-- Description -->
            <?php if ($description): ?>
            <div class="prose-light max-w-none">
                <?= nl2br(h($description)) ?>
            </div>
            <?php endif; ?>

            <!-- Type-specific details -->
            <?php if (!empty($extras)): ?>
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Details</h2>
                <dl class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <?php foreach ($extras as $key => $value): ?>
                        <?php if ($value === null || $value === '') continue; ?>
                        <div>
                            <dt class="text-sm text-gray-500 dark:text-gray-400"><?= h(ucfirst(str_replace('_', ' ', $key))) ?></dt>
                            <dd class="text-base font-medium text-gray-900 dark:text-white mt-0.5">
                                <?php if (is_bool($value) || $value === 0 || $value === 1): ?>
                                    <?= $value ? 'Yes' : 'No' ?>
                                <?php else: ?>
                                    <?= h((string) $value) ?>
                                <?php endif; ?>
                            </dd>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>
            <?php endif; ?>

            <!-- Gallery -->
            <?php if (!empty($gallery)): ?>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Photos</h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                    <?php foreach ($gallery as $photo): ?>
                    <img src="<?= h($photo['image_url']) ?>"
                         alt="<?= h($photo['caption'] ?? $placeName) ?>"
                         class="rounded-xl aspect-[4/3] object-cover hover:opacity-90 transition cursor-pointer"
                         loading="lazy">
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Reviews -->
            <?php if (!empty($reviews)): ?>
            <div>
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Reviews</h2>
                <div class="space-y-4">
                    <?php foreach ($reviews as $review): ?>
                    <div class="border border-gray-100 dark:border-gray-700 rounded-xl p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-gray-900 dark:text-white"><?= h($review['user_name'] ?? 'Anonymous') ?></span>
                            <div class="flex items-center text-yellow-500">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i data-lucide="star" class="w-3.5 h-3.5 <?= $i <= ($review['rating'] ?? 0) ? 'fill-current' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php if (!empty($review['review_text'])): ?>
                        <p class="text-sm text-gray-600 dark:text-gray-400"><?= h($review['review_text']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($review['visit_date'])): ?>
                        <p class="text-xs text-gray-400 mt-2">Visited: <?= h($review['visit_date']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Tags -->
            <?php if (!empty($placeTags)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Activities & Features</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($placeTags as $tag): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                        <?= h(getPlaceTagLabel($type, $tag)) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Amenities -->
            <?php if (!empty($placeAmenities)): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Amenities</h3>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($placeAmenities as $amenity): ?>
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                        <?= h(getPlaceAmenityLabel($type, $amenity)) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Map -->
            <?php if (!empty($place['lat']) && !empty($place['lng'])): ?>
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-card p-5">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Location</h3>
                <div class="aspect-[4/3] bg-gray-100 dark:bg-gray-700 rounded-lg overflow-hidden" id="place-map"
                     data-lat="<?= h($place['lat']) ?>" data-lng="<?= h($place['lng']) ?>">
                </div>
                <a href="<?= h($directionsUrl) ?>" target="_blank" rel="noopener"
                   class="block text-center text-sm text-blue-600 hover:text-blue-700 mt-3 font-medium">
                    Open in Google Maps
                </a>
            </div>
            <?php endif; ?>

            <!-- Notes / Tips -->
            <?php
            $notes = ($lang === 'es' && !empty($place['notes_es']))
                ? $place['notes_es']
                : ($place['notes'] ?? '');
            ?>
            <?php if ($notes): ?>
            <div class="bg-yellow-50 dark:bg-yellow-900/20 rounded-xl p-5">
                <h3 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2 flex items-center gap-2">
                    <i data-lucide="lightbulb" class="w-4 h-4"></i>
                    Tips
                </h3>
                <p class="text-sm text-yellow-700 dark:text-yellow-300"><?= nl2br(h($notes)) ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Similar places -->
    <?php if (!empty($similarPlaces)): ?>
    <section class="mt-12">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">
            Similar <?= h(getPlaceLabelPlural($type, $lang)) ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($similarPlaces as $place): ?>
                <?php $isFavorite = false; ?>
                <?php include APP_ROOT . '/components/place-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php
$extraScripts = '';
include APP_ROOT . '/components/footer.php';
?>
