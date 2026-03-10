<?php
/**
 * Universal place card component.
 *
 * Common card structure (image, name, municipality, rating, tags)
 * with type-specific extras dispatched from components/category/.
 *
 * Expected variables:
 *   $place - Place record with place_type, slug, name, etc.
 *   $isFavorite (optional) - Boolean, whether user has favorited
 */

if (!isset($place) || !is_array($place)) return;

require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/constants.php';

$placeType = $place['place_type'] ?? 'beach';
$config = getPlaceTypeConfig($placeType);
$placeSlug = h($place['slug'] ?? '');
$placeName = h($place['name'] ?? '');
$placeMunicipality = h($place['municipality'] ?? '');
$placeRating = $place['google_rating'] ?? null;
$placeReviewCount = $place['google_review_count'] ?? 0;
$coverImage = $place['cover_image'] ?? '/assets/images/placeholder-beach.jpg';
$placeUrl = getPlaceUrl($place, getCurrentLanguage());
$directionsUrl = getPlaceDirectionsUrl($place);
$placeTags = $place['tags'] ?? [];
$isFavorite = $isFavorite ?? false;
$typeIcon = $config['icon'] ?? 'map-pin';
$typeEmoji = $config['emoji'] ?? '';
$typeLabel = getPlaceLabel($placeType, getCurrentLanguage());
$distanceKm = $place['distance_km'] ?? null;
$placeId = h($place['id'] ?? '');
?>
<div class="place-card group relative bg-white dark:bg-gray-800 rounded-xl shadow-card hover:shadow-card-hover transition-all duration-300 overflow-hidden"
     data-place-type="<?= h($placeType) ?>"
     data-place-id="<?= $placeId ?>">

    <!-- Image -->
    <a href="<?= h($placeUrl) ?>" class="block relative aspect-[4/3] overflow-hidden">
        <img src="<?= h($coverImage) ?>"
             alt="<?= $placeName ?>"
             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105"
             loading="lazy"
             data-fallback-src="/assets/images/placeholder-beach.jpg">

        <!-- Type badge -->
        <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-black/50 text-white backdrop-blur-sm">
            <i data-lucide="<?= h($typeIcon) ?>" class="w-3.5 h-3.5"></i>
            <?= h($typeLabel) ?>
        </span>

        <!-- Rating badge -->
        <?php if ($placeRating): ?>
        <span class="absolute top-3 right-3 inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-bold bg-yellow-400 text-gray-900">
            <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
            <?= number_format($placeRating, 1) ?>
        </span>
        <?php endif; ?>

        <!-- Favorite button -->
        <?php if (function_exists('isAuthenticated') && isAuthenticated()): ?>
        <button class="absolute bottom-3 right-3 p-2 rounded-full bg-black/40 backdrop-blur-sm hover:bg-black/60 transition <?= $isFavorite ? 'text-red-500' : 'text-white' ?>"
                hx-post="/api/toggle-place-favorite.php"
                hx-vals='{"type":"<?= h($placeType) ?>","place_id":"<?= $placeId ?>"}'
                hx-swap="outerHTML">
            <i data-lucide="heart" class="w-5 h-5 <?= $isFavorite ? 'fill-current' : '' ?>"></i>
        </button>
        <?php endif; ?>

        <!-- Gradient overlay -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/30 to-transparent pointer-events-none"></div>
    </a>

    <!-- Content -->
    <div class="p-4">
        <!-- Name & Municipality -->
        <a href="<?= h($placeUrl) ?>" class="block">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                <?= $placeName ?>
            </h3>
        </a>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5 flex items-center gap-1">
            <i data-lucide="map-pin" class="w-3.5 h-3.5"></i>
            <?= $placeMunicipality ?>
            <?php if ($distanceKm !== null): ?>
                <span class="ml-1">&middot; <?= number_format($distanceKm, 1) ?> km</span>
            <?php endif; ?>
        </p>

        <!-- Tags -->
        <?php if (!empty($placeTags)): ?>
        <div class="flex flex-wrap gap-1.5 mt-3">
            <?php foreach (array_slice($placeTags, 0, 3) as $tag): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                <?= h(getPlaceTagLabel($placeType, $tag)) ?>
            </span>
            <?php endforeach; ?>
            <?php if (count($placeTags) > 3): ?>
            <span class="text-xs text-gray-400">+<?= count($placeTags) - 3 ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Type-specific extras -->
        <?php
        $extrasFile = $config['card_extras'] ?? null;
        if ($extrasFile) {
            $extrasPath = __DIR__ . '/category/' . $extrasFile;
            if (file_exists($extrasPath)) {
                include $extrasPath;
            }
        }
        ?>

        <!-- Actions -->
        <div class="flex items-center gap-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700">
            <a href="<?= h($placeUrl) ?>"
               class="flex-1 text-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:text-blue-700 py-1.5 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                Details
            </a>
            <a href="<?= h($directionsUrl) ?>" target="_blank" rel="noopener"
               class="flex-1 text-center text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-700 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-800 transition">
                <i data-lucide="navigation" class="w-3.5 h-3.5 inline-block mr-1"></i>
                Directions
            </a>
        </div>
    </div>
</div>
