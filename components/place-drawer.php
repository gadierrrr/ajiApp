<?php
/**
 * Universal detail drawer for any place type.
 *
 * Expected variables:
 *   $place - Full place record with place_type, extras, reviews, gallery, etc.
 */

if (!isset($place) || !is_array($place)) return;

require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/constants.php';

$placeType = $place['place_type'] ?? 'beach';
$config = getPlaceTypeConfig($placeType);
$placeName = h($place['name'] ?? '');
$placeMunicipality = h($place['municipality'] ?? '');
$placeRating = $place['google_rating'] ?? null;
$placeReviewCount = $place['google_review_count'] ?? 0;
$coverImage = $place['cover_image'] ?? '/assets/images/placeholder-beach.jpg';
$placeUrl = getPlaceUrl($place, getCurrentLanguage());
$directionsUrl = getPlaceDirectionsUrl($place);
$description = $place['description'] ?? '';
$isFavorite = $place['is_favorite'] ?? false;
$typeLabel = getPlaceLabel($placeType, getCurrentLanguage());
$typeIcon = $config['icon'] ?? 'map-pin';
$typeEmoji = $config['emoji'] ?? '';
$placeTags = $place['tags'] ?? [];
$placeAmenities = $place['amenities'] ?? [];
$gallery = $place['gallery'] ?? [];
$reviews = $place['reviews'] ?? [];
$extras = $place['extras'] ?? getPlaceTypeExtras($place);
$lang = getCurrentLanguage();
?>

<div class="place-drawer-content">
    <!-- Header with image -->
    <div class="relative h-56 overflow-hidden">
        <img src="<?= h($coverImage) ?>" alt="<?= $placeName ?>"
             class="w-full h-full object-cover"
             data-fallback-src="/assets/images/placeholder-beach.jpg">
        <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>

        <div class="absolute bottom-4 left-4 right-4">
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-white/20 text-white backdrop-blur-sm mb-2">
                <i data-lucide="<?= h($typeIcon) ?>" class="w-3.5 h-3.5"></i>
                <?= h($typeLabel) ?>
            </span>
            <h2 class="text-2xl font-bold text-white"><?= $placeName ?></h2>
            <p class="text-white/80 flex items-center gap-1 mt-1">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
                <?= $placeMunicipality ?>
            </p>
        </div>

        <?php if (function_exists('isAuthenticated') && isAuthenticated()): ?>
        <button class="absolute top-4 right-4 p-2 rounded-full bg-black/40 backdrop-blur-sm hover:bg-black/60 transition <?= $isFavorite ? 'text-red-500' : 'text-white' ?>"
                hx-post="/api/toggle-place-favorite.php"
                hx-vals='{"type":"<?= h($placeType) ?>","place_id":"<?= h($place['id']) ?>"}'
                hx-swap="outerHTML">
            <i data-lucide="heart" class="w-5 h-5 <?= $isFavorite ? 'fill-current' : '' ?>"></i>
        </button>
        <?php endif; ?>
    </div>

    <div class="p-5 space-y-5">
        <!-- Rating -->
        <?php if ($placeRating): ?>
        <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 text-yellow-500">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i data-lucide="star" class="w-4 h-4 <?= $i <= round($placeRating) ? 'fill-current' : '' ?>"></i>
                <?php endfor; ?>
            </div>
            <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
                <?= number_format($placeRating, 1) ?>
                <span class="text-gray-400">(<?= number_format($placeReviewCount) ?> reviews)</span>
            </span>
        </div>
        <?php endif; ?>

        <!-- Description -->
        <?php if ($description): ?>
        <div class="prose-light text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
            <?= nl2br(h($description)) ?>
        </div>
        <?php endif; ?>

        <!-- Type-specific details -->
        <?php if (!empty($extras)): ?>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Details</h3>
            <dl class="grid grid-cols-2 gap-3 text-sm">
                <?php foreach ($extras as $key => $value): ?>
                    <?php if ($value === null || $value === '') continue; ?>
                    <div>
                        <dt class="text-gray-500 dark:text-gray-400"><?= h(ucfirst(str_replace('_', ' ', $key))) ?></dt>
                        <dd class="font-medium text-gray-900 dark:text-white">
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

        <!-- Tags -->
        <?php if (!empty($placeTags)): ?>
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Activities & Features</h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($placeTags as $tag): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300">
                    <?= h(getPlaceTagLabel($placeType, $tag)) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Amenities -->
        <?php if (!empty($placeAmenities)): ?>
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Amenities</h3>
            <div class="flex flex-wrap gap-2">
                <?php foreach ($placeAmenities as $amenity): ?>
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300">
                    <?= h(getPlaceAmenityLabel($placeType, $amenity)) ?>
                </span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Gallery -->
        <?php if (!empty($gallery)): ?>
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">Photos</h3>
            <div class="grid grid-cols-3 gap-2">
                <?php foreach (array_slice($gallery, 0, 6) as $photo): ?>
                <img src="<?= h($photo['image_url']) ?>"
                     alt="<?= h($photo['caption'] ?? $placeName) ?>"
                     class="rounded-lg aspect-square object-cover cursor-pointer hover:opacity-80 transition"
                     loading="lazy">
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Reviews -->
        <?php if (!empty($reviews)): ?>
        <div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Recent Reviews</h3>
            <div class="space-y-3">
                <?php foreach (array_slice($reviews, 0, 3) as $review): ?>
                <div class="border-l-2 border-blue-200 dark:border-blue-800 pl-3">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-medium text-gray-900 dark:text-white"><?= h($review['user_name'] ?? 'Anonymous') ?></span>
                        <div class="flex items-center text-yellow-500">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i data-lucide="star" class="w-3 h-3 <?= $i <= ($review['rating'] ?? 0) ? 'fill-current' : '' ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php if (!empty($review['review_text'])): ?>
                    <p class="text-sm text-gray-600 dark:text-gray-400 line-clamp-3"><?= h($review['review_text']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action buttons -->
        <div class="flex gap-3 pt-2">
            <a href="<?= h($placeUrl) ?>"
               class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition font-medium text-sm">
                <i data-lucide="eye" class="w-4 h-4"></i>
                View Full Page
            </a>
            <a href="<?= h($directionsUrl) ?>" target="_blank" rel="noopener"
               class="flex-1 flex items-center justify-center gap-2 px-4 py-3 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition font-medium text-sm">
                <i data-lucide="navigation" class="w-4 h-4"></i>
                Directions
            </a>
        </div>
    </div>
</div>
