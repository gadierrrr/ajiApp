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
<article class="place-card group relative rounded-2xl overflow-hidden bg-brand-darker/50 border border-white/10 hover:border-brand-yellow/30 transition-all duration-300"
     data-place-type="<?= h($placeType) ?>"
     data-place-id="<?= $placeId ?>">

    <!-- Image Container -->
    <a href="<?= h($placeUrl) ?>" class="block relative aspect-[4/3] overflow-hidden">
        <img src="<?= h($coverImage) ?>"
             alt="<?= $placeName ?>"
             class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
             loading="lazy"
             data-fallback-src="/assets/images/placeholder-beach.jpg">

        <!-- Gradient for text readability -->
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/70 to-transparent"></div>

        <!-- Top badges row -->
        <div class="absolute top-3 left-3 right-3 flex justify-between items-start z-20">
            <!-- Favorite button -->
            <?php if (function_exists('isAuthenticated') && isAuthenticated()): ?>
            <button class="favorite-btn w-9 h-9 flex items-center justify-center rounded-full bg-black/40 backdrop-blur-sm border border-white/20 hover:bg-black/60 transition-colors"
                    hx-post="/api/toggle-place-favorite.php"
                    hx-vals='{"type":"<?= h($placeType) ?>","place_id":"<?= $placeId ?>"}'
                    hx-target="this"
                    hx-swap="outerHTML"
                    aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>">
                <i data-lucide="heart" class="w-4 h-4 <?= $isFavorite ? 'text-red-400 fill-red-400' : 'text-white/80' ?>"></i>
            </button>
            <?php else: ?>
            <span></span>
            <?php endif; ?>

            <!-- Type badge -->
            <div class="bg-black/40 backdrop-blur-md rounded-full px-3 py-1 border border-white/20">
                <span class="text-xs text-white font-medium inline-flex items-center gap-1.5">
                    <i data-lucide="<?= h($typeIcon) ?>" class="w-3.5 h-3.5"></i>
                    <?= h($typeLabel) ?>
                </span>
            </div>
        </div>

        <!-- Rating badge (bottom-right on image) -->
        <?php if ($placeRating): ?>
        <div class="absolute bottom-3 right-3 z-20 inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold bg-brand-yellow text-brand-darker">
            <i data-lucide="star" class="w-3.5 h-3.5 fill-current"></i>
            <?= number_format($placeRating, 1) ?>
        </div>
        <?php endif; ?>

        <!-- Name & Municipality overlay on image -->
        <div class="absolute bottom-0 left-0 <?= $placeRating ? 'right-16' : 'right-0' ?> p-4 z-20" style="text-shadow: 0 1px 3px rgba(0,0,0,0.8), 0 2px 8px rgba(0,0,0,0.6);">
            <span class="text-xs text-brand-yellow uppercase tracking-wider font-medium"><?= $placeMunicipality ?></span>
            <h3 class="text-lg font-bold text-white mt-0.5 line-clamp-1"><?= $placeName ?></h3>
        </div>
    </a>

    <!-- Card Content - Dark glass style -->
    <div class="p-4 bg-brand-darker/80">
        <!-- Tags -->
        <?php if (!empty($placeTags)): ?>
        <div class="flex flex-wrap gap-1.5 mb-3">
            <?php foreach (array_slice($placeTags, 0, 3) as $tag): ?>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-white/10 text-white/80 border border-white/10">
                <?= h(getPlaceTagLabel($placeType, $tag)) ?>
            </span>
            <?php endforeach; ?>
            <?php if (count($placeTags) > 3): ?>
            <span class="text-xs text-white/40">+<?= count($placeTags) - 3 ?></span>
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

        <!-- Action Buttons -->
        <div class="flex gap-2 mt-3">
            <a href="<?= h($placeUrl) ?>"
               class="flex-1 flex items-center justify-center gap-1.5 bg-brand-yellow hover:bg-yellow-300 text-brand-darker text-sm font-semibold h-10 px-3 rounded-lg transition-colors">
                <i data-lucide="book-open" class="w-4 h-4 shrink-0"></i>
                <span>Details</span>
            </a>
            <a href="<?= h($directionsUrl) ?>" target="_blank" rel="noopener"
               class="flex-1 flex items-center justify-center gap-1.5 bg-white/10 hover:bg-white/20 text-white text-sm font-medium h-10 px-3 rounded-lg transition-colors border border-white/10">
                <i data-lucide="navigation" class="w-4 h-4 shrink-0"></i>
                <span>Go</span>
            </a>
            <button type="button"
                    onclick="if(typeof sharePlace==='function')sharePlace('<?= h($placeType) ?>','<?= h($placeSlug) ?>','<?= h(addslashes($placeName)) ?>')"
                    class="flex-none flex items-center justify-center bg-white/10 hover:bg-white/20 text-white text-sm h-10 w-10 rounded-lg transition-colors border border-white/10"
                    aria-label="Share <?= $placeName ?>"
                    title="Share">
                <i data-lucide="share-2" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</article>
