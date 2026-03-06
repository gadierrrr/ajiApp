<?php
/**
 * Collection explorer card.
 *
 * Required:
 * - $beach (array)
 * - $viewMode (string)
 * - $rank (int)
 * - $isFavorite (bool)
 */

$cardViewMode = in_array($viewMode ?? 'cards', ['cards', 'list', 'grid'], true) ? $viewMode : 'cards';
$name = $beach['name'] ?? (function_exists('__') ? __('beach.unknown') : 'Unknown Beach');
$slug = $beach['slug'] ?? '';
$municipality = $beach['municipality'] ?? '';
$imageUrl = $beach['cover_image'] ?? '/images/beaches/placeholder-beach.webp';
$description = trim((string)($beach['description'] ?? ''));
$_lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
if ($_lang === 'es' && !empty($beach['description_es'])) {
    $description = trim((string)$beach['description_es']);
}
$fallback = $_t ? __('collection.explore_beach_fallback') : 'Explore this beach in Puerto Rico.';
$excerpt = $description !== ''
    ? mb_substr($description, 0, 210) . (mb_strlen($description) > 210 ? '...' : '')
    : $fallback;
$rating = $beach['google_rating'] ?? null;
$reviewCount = intval($beach['google_review_count'] ?? 0);
$distanceKm = isset($beach['distance_km']) ? floatval($beach['distance_km']) : null;
$tags = array_slice($beach['tags'] ?? [], 0, 4);
$amenities = $beach['amenities'] ?? [];

$_t = function_exists('__');

// Build locale-aware beach detail URL (uses $_lang set above)
$beachUrl = function_exists('routeUrl')
    ? routeUrl('beach_detail', $_lang, ['slug' => $slug])
    : '/beach/' . $slug;

$traits = [];
if ($distanceKm !== null) {
    $traits[] = $_t
        ? __('collection.km_away', ['distance' => number_format($distanceKm, 1)])
        : number_format($distanceKm, 1) . ' km away';
}
if (!empty($beach['access_label'])) {
    $traits[] = ucfirst(str_replace('-', ' ', (string)$beach['access_label']));
}
if (in_array('parking', $amenities, true)) {
    $traits[] = $_t ? __('collection.parking_available') : 'Parking available';
}
if (!empty($beach['has_lifeguard'])) {
    $traits[] = $_t ? __('collection.lifeguard') : 'Lifeguard';
}
if (!empty($beach['safe_for_children'])) {
    $traits[] = $_t ? __('collection.family_friendly') : 'Family-friendly';
}
$traits = array_slice(array_values(array_unique($traits)), 0, 3);
?>

<article class="collection-card collection-card--<?= h($cardViewMode) ?>">
    <div class="collection-card__media">
        <a class="collection-card__media-link" href="<?= h($beachUrl) ?>" aria-label="<?= h($_t ? __('collection.open_details', ['name' => $name]) : 'Open ' . $name . ' details') ?>">
            <img src="<?= h($imageUrl) ?>" alt="<?= h($name) ?>" loading="lazy" decoding="async">
        </a>
        <div class="collection-card__badge">
            <?php if ($rank === 1): ?>
                <?= h($_t ? __('collection.top_pick') : '#1 Top Pick') ?>
            <?php else: ?>
                <?= h($_t ? __('collection.rank', ['rank' => $rank]) : '#' . intval($rank)) ?>
            <?php endif; ?>
        </div>

        <button type="button"
                class="collection-card__favorite"
                data-ce-action="<?= isAuthenticated() ? 'favorite' : 'favorite-login' ?>"
                data-beach-id="<?= h($beach['id'] ?? '') ?>"
                data-favorite="<?= $isFavorite ? '1' : '0' ?>"
                aria-label="<?= h($isFavorite ? ($_t ? __('collection.remove_favorite') : 'Remove from favorites') : ($_t ? __('collection.add_favorite') : 'Add to favorites')) ?>"
                aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>">
            <?= $isFavorite ? '&#x2764;&#xFE0F;' : '&#x1F90D;' ?>
        </button>
    </div>

    <div class="collection-card__content">
        <div class="collection-card__top-row">
            <div>
                <h3 class="collection-card__title">
                    <a class="collection-card__title-link" href="<?= h($beachUrl) ?>"><?= h($name) ?></a>
                </h3>
                <p class="collection-card__location">&#x1F4CD; <?= h($municipality) ?>, Puerto Rico</p>
            </div>
            <?php if ($rating): ?>
            <div class="collection-card__rating" aria-label="<?= h($_t ? __('collection.rated_stars', ['rating' => (string)$rating]) : 'Rated ' . (string)$rating . ' stars') ?>">
                <div class="collection-card__stars">&#x2605;&#x2605;&#x2605;&#x2605;&#x2605;</div>
                <div class="collection-card__score"><?= number_format((float)$rating, 1) ?></div>
                <?php if ($reviewCount > 0): ?>
                <div class="collection-card__reviews"><?= h($_t ? __('collection.reviews_count', ['count' => number_format($reviewCount)]) : number_format($reviewCount) . ' reviews') ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php if ($cardViewMode !== 'list'): ?>
        <p class="collection-card__excerpt"><?= h($excerpt) ?></p>
        <?php endif; ?>

        <?php if (!empty($tags)): ?>
        <div class="collection-card__chips">
            <?php foreach ($tags as $tag): ?>
            <span class="collection-mini-chip"><?= h(getTagLabel($tag)) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($traits)): ?>
        <div class="collection-card__traits">
            <?php foreach ($traits as $trait): ?>
            <span><?= h($trait) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="collection-card__actions">
            <a class="collection-card__primary" href="<?= h($beachUrl) ?>"><?= h($_t ? __('collection.view_details') : 'View Details') ?></a>
            <a class="collection-card__secondary" href="<?= h(getDirectionsUrl($beach)) ?>" target="_blank" rel="noopener noreferrer">
                <?= h($_t ? __('collection.get_directions') : 'Get Directions') ?>
            </a>
        </div>
    </div>
</article>
