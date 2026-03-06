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

$_t = function_exists('__');

$filterChips = [
    ['tag' => 'snorkeling', 'label' => $_t ? __('collection.chip_snorkeling') : 'Snorkeling', 'emoji' => "\xF0\x9F\xA4\xBF"],
    ['tag' => 'surfing', 'label' => $_t ? __('collection.chip_surfing') : 'Surfing', 'emoji' => "\xF0\x9F\x8F\x84"],
    ['tag' => 'family-friendly', 'label' => $_t ? __('collection.chip_family') : 'Family-Friendly', 'emoji' => "\xF0\x9F\x91\xA8\xE2\x80\x8D\xF0\x9F\x91\xA9\xE2\x80\x8D\xF0\x9F\x91\xA7"],
    ['tag' => 'scenic', 'label' => $_t ? __('collection.chip_sunset') : 'Sunset Views', 'emoji' => "\xF0\x9F\x8C\x85"],
    ['tag' => 'accessible', 'label' => $_t ? __('collection.chip_accessible') : 'Easy Access', 'emoji' => "\xF0\x9F\x9A\x97"],
    ['tag' => 'secluded', 'label' => $_t ? __('collection.chip_secluded') : 'Remote', 'emoji' => "\xF0\x9F\x8C\xB4"],
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

$activeCountTpl = $_t ? __('collection.active_count', ['count' => '__COUNT__']) : '__COUNT__ active';
?>

<section class="collection-toolbar" aria-label="Beach explorer controls">
    <div class="collection-toolbar__search-row">
        <label class="sr-only" for="ce-search"><?= h($_t ? __('collection.search_label') : 'Search beaches by name or location') ?></label>
        <div class="collection-search">
            <span class="collection-search__icon" aria-hidden="true">&#x1F50E;</span>
            <input
                type="search"
                id="ce-search"
                class="collection-search__input"
                placeholder="<?= h($_t ? __('collection.search_placeholder') : 'Search beaches by name or location...') ?>"
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
           aria-label="<?= h($_t ? __('collection.map_view') : 'Map View') ?>">
            &#x1F5FA;&#xFE0F; <?= h($_t ? __('collection.map_view') : 'Map View') ?>
        </a>

        <label class="sr-only" for="ce-sort"><?= h($_t ? __('collection.sort_label') : 'Sort beaches') ?></label>
        <select id="ce-sort" class="collection-toolbar__sort" aria-label="<?= h($_t ? __('collection.sort_label') : 'Sort beaches') ?>">
            <option value="rating" <?= $selectedSort === 'rating' ? 'selected' : '' ?>><?= h($_t ? __('collection.sort_top_rated') : 'Sort: Top Rated') ?></option>
            <option value="reviews" <?= $selectedSort === 'reviews' ? 'selected' : '' ?>><?= h($_t ? __('collection.sort_most_reviewed') : 'Sort: Most Reviewed') ?></option>
            <option value="name" <?= $selectedSort === 'name' ? 'selected' : '' ?>><?= h($_t ? __('collection.sort_name_az') : 'Sort: Name A-Z') ?></option>
            <option value="distance" <?= $selectedSort === 'distance' ? 'selected' : '' ?>><?= h($_t ? __('collection.sort_distance') : 'Sort: Distance') ?></option>
        </select>
    </div>

    <div class="collection-toolbar__chips">
        <div class="collection-toolbar__chips-header">
            <div class="collection-toolbar__chips-meta">
                <span class="collection-toolbar__label"><?= h($_t ? __('collection.filter_by') : 'Filter by:') ?></span>
                <span class="collection-toolbar__count"
                      data-ce-filter-count
                      data-active-tpl="<?= h($activeCountTpl) ?>"
                      <?= $activeFilterCount > 0 ? '' : 'hidden' ?>>
                    <?= h(str_replace('__COUNT__', (string)intval($activeFilterCount), $activeCountTpl)) ?>
                </span>
            </div>
            <button type="button"
                    class="collection-toolbar__clear"
                    data-ce-action="clear-filters"
                    <?= $activeFilterCount > 0 ? '' : 'disabled' ?>>
                <?= h($_t ? __('collection.clear') : 'Clear') ?>
            </button>
        </div>

        <div class="collection-toolbar__chip-rail" role="group" aria-label="Beach filters">
        <button type="button"
                class="collection-chip <?= $isIncludeAll ? 'is-active' : '' ?>"
                data-ce-action="toggle-all"
                aria-pressed="<?= $isIncludeAll ? 'true' : 'false' ?>">
            <?= h($_t ? __('collection.all_beaches') : 'All Beaches') ?>
        </button>

        <?php foreach ($filterChips as $chip):
            $isActive = in_array($chip['tag'], $selectedTags, true);
        ?>
        <button type="button"
                class="collection-chip <?= $isActive ? 'is-active' : '' ?>"
                data-ce-action="toggle-tag"
                data-ce-tag="<?= h($chip['tag']) ?>"
                aria-pressed="<?= $isActive ? 'true' : 'false' ?>">
            <span class="collection-chip__emoji" aria-hidden="true"><?= $chip['emoji'] ?></span>
            <span><?= h($chip['label']) ?></span>
        </button>
        <?php endforeach; ?>
        </div>
    </div>
</section>
