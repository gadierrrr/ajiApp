<?php
/**
 * Collection explorer results list.
 *
 * Required:
 * - $collectionData (array)
 * - $collectionState (array)
 * - $userFavorites (array<int|string>)
 */

$collectionData = $collectionData ?? [];
$collectionState = $collectionState ?? [];
$userFavorites = $userFavorites ?? [];

$beaches = $collectionData['beaches'] ?? [];
$total = intval($collectionData['total'] ?? 0);
$viewMode = in_array($collectionState['view'] ?? 'cards', ['cards', 'list', 'grid', 'map'], true)
    ? $collectionState['view']
    : 'cards';
$page = max(1, intval($collectionState['page'] ?? 1));
$limit = max(1, intval($collectionState['limit'] ?? 15));
$startRank = (($page - 1) * $limit) + 1;
$contextFallback = !empty($collectionData['context_fallback']);
$activeFilterParts = [];
$activeSearch = trim((string)($collectionState['q'] ?? ''));
if ($activeSearch !== '') {
    $activeFilterParts[] = 'Search "' . $activeSearch . '"';
}
$activeTags = $collectionState['tags'] ?? [];
if (!empty($activeTags)) {
    $tagLabels = array_map(function(string $tag): string {
        if (function_exists('getTagLabel')) {
            return (string) getTagLabel($tag);
        }
        return $tag;
    }, array_values($activeTags));
    $visibleTags = array_slice($tagLabels, 0, 2);
    $activeFilterParts[] = 'Tags: ' . implode(', ', $visibleTags) . (count($tagLabels) > 2 ? ' +' . (count($tagLabels) - 2) : '');
}
if (!empty($collectionState['include_all'])) {
    $activeFilterParts[] = 'All beaches enabled';
}
$activeMunicipality = trim((string)($collectionState['municipality'] ?? ''));
if ($activeMunicipality !== '') {
    $activeFilterParts[] = 'Municipality: ' . $activeMunicipality;
}
$activeFiltersText = !empty($activeFilterParts) ? implode(' | ', $activeFilterParts) : 'No active filters';
$collectionKey = (string)($collectionData['collection']['key'] ?? ($collectionState['collection'] ?? ''));
$viewHrefs = [];
foreach (['cards', 'list', 'grid', 'map'] as $mode) {
    $params = [
        'collection' => $collectionKey,
        'include_all' => !empty($collectionState['include_all']) ? '1' : '0',
        'view' => $mode,
        'sort' => (string)($collectionState['sort'] ?? 'rating'),
    ];
    $q = trim((string)($collectionState['q'] ?? ''));
    if ($q !== '') {
        $params['q'] = $q;
    }
    if ($activeMunicipality !== '') {
        $params['municipality'] = $activeMunicipality;
    }
    foreach (array_values($activeTags) as $idx => $tag) {
        if (is_string($tag) && $tag !== '') {
            $params['tags[' . $idx . ']'] = $tag;
        }
    }
    if ($mode !== 'map') {
        $params['page'] = '1';
        $params['limit'] = (string)$limit;
    }
    $viewHrefs[$mode] = '?' . http_build_query($params);
}
?>
<section class="collection-results">
    <div class="collection-results__header">
        <div class="collection-results__meta" aria-live="polite" aria-atomic="true">
            <p class="collection-results__count">
                Showing <strong><?= number_format(count($beaches)) ?></strong>
                of <strong><?= number_format($total) ?></strong> beaches
            </p>
            <p class="collection-results__filters"><?= h($activeFiltersText) ?></p>
        </div>
        <div class="collection-view-switch" role="group" aria-label="Switch collection view">
            <?php foreach (['cards' => 'Cards', 'list' => 'List', 'grid' => 'Grid', 'map' => 'Map'] as $mode => $label): ?>
            <a href="<?= h((string)($viewHrefs[$mode] ?? '?')) ?>"
               class="collection-view-switch__btn <?= $mode === $viewMode ? 'is-active' : '' ?>"
               data-ce-action="set-view"
               data-ce-view="<?= h($mode) ?>"
               aria-pressed="<?= $mode === $viewMode ? 'true' : 'false' ?>"
               aria-label="<?= h($label) ?> view">
                <?= h($label) ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($contextFallback): ?>
    <div class="collection-results__notice" role="status">
        No beaches matched this page's default context, so we're showing all beaches.
    </div>
    <?php endif; ?>

    <?php if ($total === 0): ?>
    <div class="collection-empty">
        <h3>No beaches match the current filters.</h3>
        <p>Try clearing filters or switching to all beaches.</p>
        <button type="button" class="collection-empty__btn" data-ce-action="clear-all">Clear all filters</button>
    </div>
    <?php else: ?>
    <div id="collection-list-view" class="<?= $viewMode === 'map' ? 'hidden' : '' ?>">
        <div class="collection-results__list collection-results__list--<?= h($viewMode === 'map' ? 'cards' : $viewMode) ?>">
            <?php foreach ($beaches as $index => $beach):
                $rank = $startRank + $index;
                $isFavorite = in_array($beach['id'], $userFavorites, true);
                include __DIR__ . '/card.php';
            endforeach; ?>
        </div>
    </div>
    <div id="collection-map-view" class="<?= $viewMode === 'map' ? '' : 'hidden' ?>">
        <div id="collection-map-loading" class="text-sm text-gray-400 mb-3">Loading map...</div>
        <div id="collection-map-error" class="hidden text-sm text-red-400 mb-3">Unable to load map right now.</div>
        <div id="collection-map-empty" class="hidden text-sm text-gray-400 mb-3">No beaches with mappable coordinates were found for this view.</div>
        <div id="collection-map-container" class="rounded-xl overflow-hidden border border-white/10" style="height: 520px;"></div>
    </div>
    <?php endif; ?>
</section>
