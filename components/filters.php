<?php
/**
 * Filter Controls Component (Enhanced)
 *
 * @param array $selectedTags - Currently selected tags
 * @param string $selectedMunicipality - Currently selected municipality
 * @param string $sortBy - Current sort option
 * @param string $viewMode - Current view mode (list/map)
 * @param bool $locationEnabled - Whether user location is enabled
 * @param int $maxDistance - Max distance filter in km
 */

require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/constants.php';

$selectedTags = $selectedTags ?? [];
$selectedMunicipality = $selectedMunicipality ?? '';
$sortBy = $sortBy ?? 'name';
$viewMode = $viewMode ?? 'list';
$locationEnabled = $locationEnabled ?? false;
$maxDistance = $maxDistance ?? 50;
$activeFilterCount = count($selectedTags) + ($selectedMunicipality ? 1 : 0);
?>

<!-- Skip Link for Accessibility -->
<a href="#beach-grid" class="skip-link"><?= h(__('filters.skip_to_results')) ?></a>

<!-- Mobile Filter Bar -->
<div class="md:hidden flex items-center gap-2 mb-4">
    <!-- Near Me Button (Mobile) -->
    <button type="button"
            id="mobile-nearme-btn"
            data-action="requestUserLocation"
            class="inline-flex items-center justify-center gap-1.5 bg-white/5 backdrop-blur-sm border border-white/20 rounded-lg px-3 h-11 text-sm font-medium text-white/80 hover:bg-white/10 transition-colors <?= $locationEnabled ? 'bg-green-500/20 border-green-500/50 text-green-300' : '' ?>">
        <i data-lucide="navigation" id="mobile-nearme-icon" class="w-4 h-4" aria-hidden="true"></i>
        <span id="mobile-nearme-text" class="whitespace-nowrap"><?= $locationEnabled ? h(__('filters.near_me_active')) : h(__('filters.near_me')) ?></span>
    </button>

    <button type="button"
            id="mobile-filter-btn"
            data-action="openFilterDrawer"
            class="flex-1 flex items-center justify-center gap-2 bg-white/5 backdrop-blur-sm border border-white/20 rounded-lg px-4 h-11 text-sm font-medium text-white/80 hover:bg-white/10 transition-colors">
        <i data-lucide="sliders-horizontal" class="w-4 h-4" aria-hidden="true"></i>
        <span><?= h(__('common.filter')) ?></span>
        <?php if ($activeFilterCount > 0): ?>
        <span class="bg-brand-yellow text-brand-darker text-xs px-2 py-0.5 rounded-full"><?= $activeFilterCount ?></span>
        <?php endif; ?>
    </button>

    <!-- View Toggle (Mobile) -->
    <div class="flex rounded-lg border border-white/20 overflow-hidden" role="group" aria-label="<?= __('aria.view_mode') ?>">
        <button type="button"
                data-action="setViewMode" data-action-args='["list"]'
                id="mobile-view-list-btn"
                aria-pressed="<?= $viewMode === 'list' ? 'true' : 'false' ?>"
                class="inline-flex items-center gap-1.5 px-3 h-11 text-sm font-medium <?= $viewMode === 'list' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/5 text-white/80 hover:bg-white/10' ?>">
            <i data-lucide="list" class="w-4 h-4" aria-hidden="true"></i>
        </button>
        <button type="button"
                data-action="setViewMode" data-action-args='["map"]'
                id="mobile-view-map-btn"
                aria-pressed="<?= $viewMode === 'map' ? 'true' : 'false' ?>"
                class="inline-flex items-center gap-1.5 px-3 h-11 text-sm font-medium <?= $viewMode === 'map' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/5 text-white/80 hover:bg-white/10' ?>">
            <i data-lucide="map" class="w-4 h-4" aria-hidden="true"></i>
        </button>
    </div>
</div>

<!-- Mobile Filter Drawer -->
<div id="filter-drawer" class="filter-drawer-overlay md:hidden" data-action="closeFilterDrawer" data-action-args='["__event__"]' role="dialog" aria-modal="true" aria-labelledby="filter-drawer-title">
    <div class="filter-drawer bg-brand-dark border-t border-white/10" data-action-stop data-action="noop" data-on="click">
        <div class="filter-drawer-handle bg-white/30" aria-hidden="true"></div>

        <!-- Header -->
        <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
            <h2 id="filter-drawer-title" class="text-lg font-semibold text-white"><?= h(__('common.filter')) ?></h2>
            <button type="button" data-action="closeFilterDrawer" class="p-2 text-white/60 hover:text-white" aria-label="<?= __('aria.close_filters') ?>">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>

        <!-- Drawer Content -->
        <div class="p-4 space-y-5 overflow-y-auto max-h-[60vh]">
            <!-- Location Button -->
            <div>
                <label class="block text-sm font-medium text-white/80 mb-2"><?= h(__('filters.location')) ?></label>
                <button type="button"
                        id="mobile-location-btn"
                        data-action="requestUserLocation"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 h-11 rounded-lg border border-white/20 bg-white/5 hover:bg-white/10 transition-colors text-sm font-medium text-white/80">
                    <i data-lucide="map-pin" id="mobile-location-icon" class="w-4 h-4" aria-hidden="true"></i>
                    <span id="mobile-location-text"><?= h(__('filters.use_location')) ?></span>
                </button>
            </div>

            <!-- Municipality -->
            <div>
                <label for="mobile-municipality-filter" class="block text-sm font-medium text-white/80 mb-2"><?= h(__('filters.municipality')) ?></label>
                <select id="mobile-municipality-filter"
                        class="w-full px-3 h-11 border border-white/20 bg-white/5 rounded-lg text-sm text-white focus:ring-2 focus:ring-brand-yellow focus:border-brand-yellow">
                    <option value=""><?= h(__('filters.all_municipalities')) ?></option>
                    <?php foreach (MUNICIPALITIES as $muni): ?>
                    <option value="<?= h($muni) ?>" <?= $selectedMunicipality === $muni ? 'selected' : '' ?>><?= h($muni) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Distance Slider -->
            <div id="mobile-distance-container" class="<?= $locationEnabled ? '' : 'hidden' ?>">
                <label class="block text-sm font-medium text-white/80 mb-2"><?= h(__('filters.distance_label')) ?></label>
                <div class="flex items-center gap-3">
                    <span class="text-sm text-white/50">5km</span>
                    <input type="range"
                           id="mobile-distance-filter"
                           min="5"
                           max="100"
                           step="5"
                           value="<?= h($maxDistance) ?>"
                           class="flex-1 accent-brand-yellow">
                    <span class="text-sm text-white/50">100km</span>
                </div>
                <div class="text-center mt-1">
                    <span id="mobile-distance-value" class="text-sm font-medium text-brand-yellow"><?= h($maxDistance) ?>km</span>
                </div>
            </div>

            <!-- Sort -->
            <div>
                <label for="mobile-sort-filter" class="block text-sm font-medium text-white/80 mb-2"><?= h(__('filters.sort_by')) ?></label>
                <select id="mobile-sort-filter"
                        class="w-full px-3 h-11 border border-white/20 bg-white/5 rounded-lg text-sm text-white focus:ring-2 focus:ring-brand-yellow focus:border-brand-yellow">
                    <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?>><?= h(__('filters.sort_name')) ?></option>
                    <option value="distance" <?= $sortBy === 'distance' ? 'selected' : '' ?> id="mobile-sort-distance-option" <?= $locationEnabled ? '' : 'disabled' ?>><?= h(__('filters.sort_distance')) ?></option>
                    <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?>><?= h(__('filters.sort_rating')) ?></option>
                </select>
            </div>

            <!-- Tags -->
            <div>
                <label class="block text-sm font-medium text-white/80 mb-2"><?= h(__('filters.beach_type')) ?></label>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (TAGS as $tag): ?>
                    <button type="button"
                            data-action="toggleTagMobile" data-action-args='["<?= h($tag) ?>"]'
                            data-tag="<?= h($tag) ?>"
                            aria-pressed="<?= in_array($tag, $selectedTags) ? 'true' : 'false' ?>"
                            class="mobile-tag-btn px-3 h-9 rounded-full text-sm font-medium transition-colors
                                   <?= in_array($tag, $selectedTags)
                                       ? 'bg-brand-yellow text-brand-darker'
                                       : 'bg-white/10 text-white/80 hover:bg-brand-yellow hover:text-brand-darker' ?>">
                        <?= h(getTagLabel($tag)) ?>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div class="sticky bottom-0 bg-brand-darker border-t border-white/10 p-4 flex gap-3">
            <button type="button" data-action="clearFiltersMobile" class="flex-1 px-4 h-11 border border-white/20 text-white/80 rounded-lg font-medium hover:bg-white/10 transition-colors">
                <?= h(__('filters.clear_all')) ?>
            </button>
            <button type="button" data-action="applyFiltersMobile" class="flex-1 px-4 h-11 bg-brand-yellow text-brand-darker rounded-lg font-semibold hover:bg-yellow-300 transition-colors">
                <?= h(__('filters.show_results')) ?>
            </button>
        </div>
    </div>
</div>

<!-- Desktop Filters -->
<div class="filters-container bg-white/5 backdrop-blur-sm rounded-2xl border border-white/10 p-5 mb-6 hidden md:block" role="search" aria-label="<?= __('aria.beach_filters') ?>">
    <!-- Top Row: Location Button, View Toggle, Sort -->
    <div class="flex flex-wrap items-center gap-3 mb-4 filter-row">
        <!-- Location Button -->
        <button type="button"
                id="location-btn"
                data-action="requestUserLocation"
                aria-label="<?= __('aria.enable_location') ?>"
                class="inline-flex items-center gap-2 px-4 h-10 rounded-full border border-white/20 bg-white/5 hover:bg-white/10 transition-colors text-sm font-medium text-white/80">
            <i data-lucide="map-pin" id="location-icon" class="w-4 h-4" aria-hidden="true"></i>
            <span id="location-text"><?= h(__('filters.use_location')) ?></span>
        </button>

        <!-- View Toggle -->
        <div class="flex rounded-full border border-white/20 overflow-hidden ml-auto" role="group" aria-label="<?= __('aria.view_mode') ?>">
            <button type="button"
                    data-action="setViewMode" data-action-args='["list"]'
                    id="view-list-btn"
                    aria-pressed="<?= $viewMode === 'list' ? 'true' : 'false' ?>"
                    class="inline-flex items-center gap-1.5 px-4 h-10 text-sm font-medium <?= $viewMode === 'list' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/5 text-white/80 hover:bg-white/10' ?>">
                <i data-lucide="list" class="w-4 h-4" aria-hidden="true"></i>
                <span class="sr-only-mobile"><?= __('aria.list_view') ?></span>
            </button>
            <button type="button"
                    data-action="setViewMode" data-action-args='["map"]'
                    id="view-map-btn"
                    aria-pressed="<?= $viewMode === 'map' ? 'true' : 'false' ?>"
                    class="inline-flex items-center gap-1.5 px-4 h-10 text-sm font-medium <?= $viewMode === 'map' ? 'bg-brand-yellow text-brand-darker' : 'bg-white/5 text-white/80 hover:bg-white/10' ?>">
                <i data-lucide="map" class="w-4 h-4" aria-hidden="true"></i>
                <span class="sr-only-mobile"><?= __('aria.map_view') ?></span>
            </button>
        </div>
    </div>

    <!-- Second Row: Municipality, Distance (if location), Sort -->
    <div class="flex flex-wrap items-center gap-3 mb-4 filter-row">
        <!-- Municipality Filter (Searchable) -->
        <div class="flex-1 min-w-[200px]">
            <label for="municipality-filter" class="sr-only"><?= __('aria.filter_municipality') ?></label>
            <select id="municipality-filter"
                    data-action="applyFilters" data-on="change"
                    aria-label="<?= __('aria.filter_municipality') ?>"
                    class="w-full px-3 h-10 border border-white/20 bg-white/5 rounded-lg text-sm text-white focus:ring-2 focus:ring-brand-yellow focus:border-brand-yellow">
                <option value="" class="bg-brand-dark"><?= h(__('filters.all_municipalities')) ?></option>
                <?php foreach (MUNICIPALITIES as $muni): ?>
                <option value="<?= h($muni) ?>" <?= $selectedMunicipality === $muni ? 'selected' : '' ?> class="bg-brand-dark">
                    <?= h($muni) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Distance Slider (shown when location enabled) -->
        <div id="distance-filter-container" class="flex-1 min-w-[200px] <?= $locationEnabled ? '' : 'hidden' ?>">
            <div class="flex items-center gap-2">
                <label for="distance-filter" class="text-sm text-white/60 whitespace-nowrap"><?= h(__('filters.within')) ?></label>
                <input type="range"
                       id="distance-filter"
                       min="5"
                       max="100"
                       step="5"
                       value="<?= h($maxDistance) ?>"
                       data-action="applyFilters" data-on="change"
                       aria-valuemin="5"
                       aria-valuemax="100"
                       aria-valuenow="<?= h($maxDistance) ?>"
                       aria-valuetext="<?= h($maxDistance) ?> kilometers"
                       class="flex-1 accent-brand-yellow">
                <span id="distance-value" class="text-sm font-medium text-brand-yellow min-w-[50px]" aria-live="polite"><?= h($maxDistance) ?>km</span>
            </div>
        </div>

        <!-- Sort -->
        <div class="min-w-[150px]">
            <label for="sort-filter" class="sr-only"><?= __('aria.sort_by') ?></label>
            <select id="sort-filter"
                    data-action="applyFilters" data-on="change"
                    aria-label="<?= __('aria.sort_by') ?>"
                    class="w-full px-3 h-10 border border-white/20 bg-white/5 rounded-lg text-sm text-white focus:ring-2 focus:ring-brand-yellow focus:border-brand-yellow">
                <option value="name" <?= $sortBy === 'name' ? 'selected' : '' ?> class="bg-brand-dark"><?= h(__('filters.sort_by_name')) ?></option>
                <option value="distance" <?= $sortBy === 'distance' ? 'selected' : '' ?> id="sort-distance-option" <?= $locationEnabled ? '' : 'disabled' ?> class="bg-brand-dark">
                    <?= h(__('filters.sort_by_distance')) ?>
                </option>
                <option value="rating" <?= $sortBy === 'rating' ? 'selected' : '' ?> class="bg-brand-dark"><?= h(__('filters.sort_by_rating')) ?></option>
            </select>
        </div>
    </div>

    <!-- Tag Filters - Glassmorphism Pills -->
    <div class="flex flex-wrap gap-2" role="group" aria-label="<?= __('aria.filter_type') ?>">
        <?php foreach (TAGS as $tag): ?>
        <button type="button"
                data-action="toggleTag" data-action-args='["<?= h($tag) ?>"]'
                data-tag="<?= h($tag) ?>"
                aria-pressed="<?= in_array($tag, $selectedTags) ? 'true' : 'false' ?>"
                class="tag-btn flex items-center gap-2 px-4 py-2 rounded-full text-sm font-medium transition-all
                       <?= in_array($tag, $selectedTags)
                           ? 'bg-brand-yellow text-brand-darker'
                           : 'bg-white/5 backdrop-blur-sm border border-white/20 text-white/80 hover:bg-white/10 hover:border-brand-yellow/50' ?>">
            <?= h(getTagLabel($tag)) ?>
        </button>
        <?php endforeach; ?>

        <!-- Clear Filters -->
        <button type="button"
                data-action="clearFilters"
                id="clear-filters-btn"
                aria-label="<?= __('aria.clear_all_filters') ?>"
                class="inline-flex items-center gap-1 px-4 py-2 rounded-full text-sm font-medium text-red-400 hover:bg-red-500/10 transition-colors <?= empty($selectedTags) && empty($selectedMunicipality) ? 'hidden' : '' ?>">
            <i data-lucide="x" class="w-3.5 h-3.5" aria-hidden="true"></i>
            <span><?= h(__('common.clear')) ?></span>
        </button>
    </div>
</div>

<!-- Applied Filters Summary (Filter Chips) -->
<div id="applied-filters" class="flex flex-wrap gap-2 mb-4" role="region" aria-label="<?= __('aria.applied_filters') ?>" style="display: none;">
    <!-- Dynamically populated by JavaScript -->
</div>

<!-- Results Count -->
<div class="flex items-center justify-between mb-4">
    <p id="results-count" class="text-white/60 text-sm" aria-live="polite" aria-atomic="true">
        <?php
        // Show server-side count initially - JS will update when filters change
        $displayCount = $totalBeaches ?? count($beaches ?? []);
        $searchQuery = $_GET['q'] ?? '';
        if ($searchQuery) {
            echo h(__('filters.results_count_search', ['count' => $displayCount, 'query' => $searchQuery]));
        } else {
            echo h(__('filters.results_count', ['count' => $displayCount]));
        }
        ?>
    </p>
</div>

<!-- Mobile Filter Drawer JavaScript -->
<script <?= cspNonceAttr() ?>>
// Mobile Filter Drawer State
let mobileFilterState = {
    selectedTags: <?= json_encode($selectedTags) ?>,
    selectedMunicipality: '<?= h($selectedMunicipality) ?>',
    sortBy: '<?= h($sortBy) ?>',
    maxDistance: <?= (int)$maxDistance ?>
};

function openFilterDrawer() {
    const drawer = document.getElementById('filter-drawer');
    if (drawer) {
        drawer.classList.add('open');
        document.body.style.overflow = 'hidden';

        // Sync mobile drawer state with current state
        syncMobileDrawerState();

        // Re-initialize Lucide icons if needed
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }
}

function closeFilterDrawer(e) {
    if (e && e.target !== document.getElementById('filter-drawer')) return;
    const drawer = document.getElementById('filter-drawer');
    if (drawer) {
        drawer.classList.remove('open');
        document.body.style.overflow = '';
    }
}

function syncMobileDrawerState() {
    // Sync from global state to mobile drawer
    if (typeof state !== 'undefined') {
        mobileFilterState.selectedTags = [...state.selectedTags];
        mobileFilterState.selectedMunicipality = state.selectedMunicipality;
        mobileFilterState.sortBy = state.sortBy;
        mobileFilterState.maxDistance = state.maxDistance;
    }

    // Update mobile drawer UI
    const mobileSelect = document.getElementById('mobile-municipality-filter');
    if (mobileSelect) mobileSelect.value = mobileFilterState.selectedMunicipality;

    const mobileSortSelect = document.getElementById('mobile-sort-filter');
    if (mobileSortSelect) mobileSortSelect.value = mobileFilterState.sortBy;

    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');
    if (mobileDistanceSlider) {
        mobileDistanceSlider.value = mobileFilterState.maxDistance;
        const valueDisplay = document.getElementById('mobile-distance-value');
        if (valueDisplay) valueDisplay.textContent = mobileFilterState.maxDistance + 'km';
    }

    // Sync tags
    document.querySelectorAll('.mobile-tag-btn').forEach(btn => {
        const tag = btn.dataset.tag;
        if (mobileFilterState.selectedTags.includes(tag)) {
            btn.classList.add('bg-brand-yellow', 'text-brand-darker');
            btn.classList.remove('bg-white/10', 'text-white/80');
        } else {
            btn.classList.remove('bg-brand-yellow', 'text-brand-darker');
            btn.classList.add('bg-white/10', 'text-white/80');
        }
    });
}

function toggleTagMobile(tag) {
    const idx = mobileFilterState.selectedTags.indexOf(tag);
    if (idx > -1) {
        mobileFilterState.selectedTags.splice(idx, 1);
    } else {
        mobileFilterState.selectedTags.push(tag);
    }

    // Update button UI
    const btn = document.querySelector(`.mobile-tag-btn[data-tag="${tag}"]`);
    if (btn) {
        btn.classList.toggle('bg-brand-yellow');
        btn.classList.toggle('text-brand-darker');
        btn.classList.toggle('bg-white/10');
        btn.classList.toggle('text-white/80');
    }
}

function clearFiltersMobile() {
    mobileFilterState.selectedTags = [];
    mobileFilterState.selectedMunicipality = '';
    mobileFilterState.sortBy = 'name';
    mobileFilterState.maxDistance = 50;

    // Reset UI
    const mobileSelect = document.getElementById('mobile-municipality-filter');
    if (mobileSelect) mobileSelect.value = '';

    const mobileSortSelect = document.getElementById('mobile-sort-filter');
    if (mobileSortSelect) mobileSortSelect.value = 'name';

    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');
    if (mobileDistanceSlider) {
        mobileDistanceSlider.value = 50;
        const valueDisplay = document.getElementById('mobile-distance-value');
        if (valueDisplay) valueDisplay.textContent = '50km';
    }

    document.querySelectorAll('.mobile-tag-btn').forEach(btn => {
        btn.classList.remove('bg-brand-yellow', 'text-brand-darker');
        btn.classList.add('bg-white/10', 'text-white/80');
    });
}

function applyFiltersMobile() {
    // Collect values from mobile drawer
    const mobileSelect = document.getElementById('mobile-municipality-filter');
    const mobileSortSelect = document.getElementById('mobile-sort-filter');
    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');

    mobileFilterState.selectedMunicipality = mobileSelect?.value || '';
    mobileFilterState.sortBy = mobileSortSelect?.value || 'name';
    mobileFilterState.maxDistance = parseInt(mobileDistanceSlider?.value || 50);

    // Sync to global state
    if (typeof state !== 'undefined') {
        state.selectedTags = [...mobileFilterState.selectedTags];
        state.selectedMunicipality = mobileFilterState.selectedMunicipality;
        state.sortBy = mobileFilterState.sortBy;
        state.maxDistance = mobileFilterState.maxDistance;

        // Sync desktop UI
        const desktopSelect = document.getElementById('municipality-filter');
        if (desktopSelect) {
            desktopSelect.value = state.selectedMunicipality;
        }

        const desktopSortSelect = document.getElementById('sort-filter');
        if (desktopSortSelect) desktopSortSelect.value = state.sortBy;

        const desktopDistanceSlider = document.getElementById('distance-filter');
        if (desktopDistanceSlider) {
            desktopDistanceSlider.value = state.maxDistance;
            const valueDisplay = document.getElementById('distance-value');
            if (valueDisplay) valueDisplay.textContent = state.maxDistance + 'km';
        }

        // Sync tag buttons
        document.querySelectorAll('.tag-btn:not(.mobile-tag-btn)').forEach(btn => {
            const tag = btn.dataset.tag;
            if (state.selectedTags.includes(tag)) {
                btn.classList.add('bg-brand-yellow', 'text-brand-darker');
                btn.classList.remove('bg-white/5', 'text-white/80', 'border-white/20');
                btn.setAttribute('aria-pressed', 'true');
            } else {
                btn.classList.remove('bg-brand-yellow', 'text-brand-darker');
                btn.classList.add('bg-white/5', 'text-white/80', 'border-white/20');
                btn.setAttribute('aria-pressed', 'false');
            }
        });
    }

    // Close drawer and apply filters
    closeFilterDrawer();

    // Apply filters
    if (typeof applyFiltersWithHtmx === 'function') {
        applyFiltersWithHtmx();
    } else if (typeof applyFilters === 'function') {
        applyFilters();
    }

    // Update filter badge count
    updateMobileFilterBadge();
}

function updateMobileFilterBadge() {
    const count = (typeof state !== 'undefined')
        ? state.selectedTags.length + (state.selectedMunicipality ? 1 : 0)
        : mobileFilterState.selectedTags.length + (mobileFilterState.selectedMunicipality ? 1 : 0);

    const btn = document.getElementById('mobile-filter-btn');
    if (!btn) return;

    const badge = btn.querySelector('span.bg-brand-yellow');
    if (count > 0) {
        if (badge) {
            badge.textContent = count;
        } else {
            const newBadge = document.createElement('span');
            newBadge.className = 'bg-brand-yellow text-brand-darker text-xs px-2 py-0.5 rounded-full';
            newBadge.textContent = count;
            btn.appendChild(newBadge);
        }
    } else if (badge) {
        badge.remove();
    }
}

// Update mobile distance display on slider change
document.addEventListener('DOMContentLoaded', () => {
    const mobileDistanceSlider = document.getElementById('mobile-distance-filter');
    if (mobileDistanceSlider) {
        mobileDistanceSlider.addEventListener('input', (e) => {
            const valueDisplay = document.getElementById('mobile-distance-value');
            if (valueDisplay) valueDisplay.textContent = e.target.value + 'km';
        });
    }

    // ESC key to close filter drawer
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const drawer = document.getElementById('filter-drawer');
            if (drawer && drawer.classList.contains('open')) {
                closeFilterDrawer();
            }
        }
    });
});
</script>
