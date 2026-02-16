<?php
/**
 * Collection explorer toolbar.
 *
 * Required:
 * - $collectionKey (string)
 * - $collectionContext (array)
 * - $collectionState (array)
 */

$selectedTags = $collectionState['tags'] ?? [];
$isIncludeAll = !empty($collectionState['include_all']);
$searchQuery = $collectionState['q'] ?? '';
$selectedSort = $collectionState['sort'] ?? ($collectionContext['default_sort'] ?? 'rating');
$activeFilterCount = count($selectedTags) + ($isIncludeAll ? 1 : 0);

$filterChips = [
    ['tag' => 'snorkeling', 'label' => 'Snorkeling', 'emoji' => '🤿'],
    ['tag' => 'surfing', 'label' => 'Surfing', 'emoji' => '🏄'],
    ['tag' => 'family-friendly', 'label' => 'Family-Friendly', 'emoji' => '👨‍👩‍👧'],
    ['tag' => 'scenic', 'label' => 'Sunset Views', 'emoji' => '🌅'],
    ['tag' => 'accessible', 'label' => 'Easy Access', 'emoji' => '🚗'],
    ['tag' => 'secluded', 'label' => 'Remote', 'emoji' => '🌴'],
];

$mapParams = [
    'view' => 'map',
    'collection' => $collectionKey,
    'include_all' => $isIncludeAll ? '1' : '0',
];
if ($searchQuery !== '') {
    $mapParams['q'] = $searchQuery;
}
if (!empty($selectedTags)) {
    $mapParams['tags'] = $selectedTags;
}
if (($collectionState['municipality'] ?? '') !== '') {
    $mapParams['municipality'] = $collectionState['municipality'];
}
if ($selectedSort !== '' && $selectedSort !== ($collectionContext['default_sort'] ?? 'rating')) {
    $mapParams['sort'] = $selectedSort;
}
$mapHref = '?' . http_build_query($mapParams);
?>

<section class="collection-toolbar" aria-label="Beach explorer controls">
    <div class="collection-toolbar__search-row">
        <label class="sr-only" for="ce-search">Search beaches by name or location</label>
        <div class="collection-search">
            <span class="collection-search__icon" aria-hidden="true">🔎</span>
            <input
                type="search"
                id="ce-search"
                class="collection-search__input"
                placeholder="Search beaches by name or location..."
                value="<?= h($searchQuery) ?>"
                autocomplete="off">
        </div>
    </div>

    <div class="collection-toolbar__control-row">
        <a id="ce-map-link"
           class="collection-toolbar__btn collection-toolbar__btn--map"
           href="<?= h($mapHref) ?>"
           data-ce-action="set-view"
           data-ce-view="map"
           aria-label="Open map view with current filters">
            🗺️ Map View
        </a>

        <label class="sr-only" for="ce-sort">Sort beaches</label>
        <select id="ce-sort" class="collection-toolbar__sort" aria-label="Sort beaches">
            <option value="rating" <?= $selectedSort === 'rating' ? 'selected' : '' ?>>Sort: Top Rated</option>
            <option value="reviews" <?= $selectedSort === 'reviews' ? 'selected' : '' ?>>Sort: Most Reviewed</option>
            <option value="name" <?= $selectedSort === 'name' ? 'selected' : '' ?>>Sort: Name A-Z</option>
            <option value="distance" <?= $selectedSort === 'distance' ? 'selected' : '' ?>>Sort: Distance</option>
        </select>
    </div>

    <div class="collection-toolbar__chips">
        <div class="collection-toolbar__chips-header">
            <div class="collection-toolbar__chips-meta">
                <span class="collection-toolbar__label">Filter by:</span>
                <span class="collection-toolbar__count"
                      data-ce-filter-count
                      <?= $activeFilterCount > 0 ? '' : 'hidden' ?>>
                    <?= intval($activeFilterCount) ?> active
                </span>
            </div>
            <button type="button"
                    class="collection-toolbar__clear"
                    data-ce-action="clear-filters"
                    <?= $activeFilterCount > 0 ? '' : 'disabled' ?>>
                Clear
            </button>
        </div>

        <div class="collection-toolbar__chip-rail" role="group" aria-label="Beach filters">
        <button type="button"
                class="collection-chip <?= $isIncludeAll ? 'is-active' : '' ?>"
                data-ce-action="toggle-all"
                aria-pressed="<?= $isIncludeAll ? 'true' : 'false' ?>">
            All Beaches
        </button>

        <?php foreach ($filterChips as $chip):
            $isActive = in_array($chip['tag'], $selectedTags, true);
        ?>
        <button type="button"
                class="collection-chip <?= $isActive ? 'is-active' : '' ?>"
                data-ce-action="toggle-tag"
                data-ce-tag="<?= h($chip['tag']) ?>"
                aria-pressed="<?= $isActive ? 'true' : 'false' ?>">
            <span class="collection-chip__emoji" aria-hidden="true"><?= h($chip['emoji']) ?></span>
            <span><?= h($chip['label']) ?></span>
        </button>
        <?php endforeach; ?>
        </div>
    </div>
</section>
