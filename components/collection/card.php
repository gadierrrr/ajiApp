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
$name = $beach['name'] ?? 'Unknown Beach';
$slug = $beach['slug'] ?? '';
$municipality = $beach['municipality'] ?? '';
$imageUrl = $beach['cover_image'] ?? '/images/beaches/placeholder-beach.webp';
$description = trim((string)($beach['description'] ?? ''));
$excerpt = $description !== '' ? substr($description, 0, 210) . (strlen($description) > 210 ? '...' : '') : 'Explore this beach in Puerto Rico.';
$rating = $beach['google_rating'] ?? null;
$reviewCount = intval($beach['google_review_count'] ?? 0);
$distanceKm = isset($beach['distance_km']) ? floatval($beach['distance_km']) : null;
$tags = array_slice($beach['tags'] ?? [], 0, 4);
$amenities = $beach['amenities'] ?? [];

$traits = [];
if ($distanceKm !== null) {
    $traits[] = number_format($distanceKm, 1) . ' km away';
}
if (!empty($beach['access_label'])) {
    $traits[] = ucfirst(str_replace('-', ' ', (string)$beach['access_label']));
}
if (in_array('parking', $amenities, true)) {
    $traits[] = 'Parking available';
}
if (!empty($beach['has_lifeguard'])) {
    $traits[] = 'Lifeguard';
}
if (!empty($beach['safe_for_children'])) {
    $traits[] = 'Family-friendly';
}
$traits = array_slice(array_values(array_unique($traits)), 0, 3);
?>

<article class="collection-card collection-card--<?= h($cardViewMode) ?>">
    <div class="collection-card__media">
        <a class="collection-card__media-link" href="/beach/<?= h($slug) ?>" aria-label="Open <?= h($name) ?> details">
            <img src="<?= h($imageUrl) ?>" alt="<?= h($name) ?>" loading="lazy" decoding="async">
        </a>
        <div class="collection-card__badge">
            <?= $rank === 1 ? '#1 Top Pick' : '#' . intval($rank) ?>
        </div>

        <button type="button"
                class="collection-card__favorite"
                data-ce-action="<?= isAuthenticated() ? 'favorite' : 'favorite-login' ?>"
                data-beach-id="<?= h($beach['id'] ?? '') ?>"
                data-favorite="<?= $isFavorite ? '1' : '0' ?>"
                aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>"
                aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>">
            <?= $isFavorite ? '❤️' : '🤍' ?>
        </button>
    </div>

    <div class="collection-card__content">
        <div class="collection-card__top-row">
            <div>
                <h3 class="collection-card__title">
                    <a class="collection-card__title-link" href="/beach/<?= h($slug) ?>"><?= h($name) ?></a>
                </h3>
                <p class="collection-card__location">📍 <?= h($municipality) ?>, Puerto Rico</p>
            </div>
            <?php if ($rating): ?>
            <div class="collection-card__rating" aria-label="Rated <?= h((string)$rating) ?> stars">
                <div class="collection-card__stars">★★★★★</div>
                <div class="collection-card__score"><?= number_format((float)$rating, 1) ?></div>
                <?php if ($reviewCount > 0): ?>
                <div class="collection-card__reviews"><?= number_format($reviewCount) ?> reviews</div>
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
            <a class="collection-card__primary" href="/beach/<?= h($slug) ?>">View Details</a>
            <a class="collection-card__secondary" href="<?= h(getDirectionsUrl($beach)) ?>" target="_blank" rel="noopener noreferrer">
                Get Directions
            </a>
        </div>
    </div>
</article>
