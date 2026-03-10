<?php
/**
 * Universal filter component for any place type.
 *
 * Reads tags/amenities from the type registry and renders filter chips.
 *
 * Expected variables:
 *   $filterType - Place type key (e.g., 'river', 'trail')
 *   $activeFilters - Current active filter array from placeFiltersFromRequest()
 */

if (!isset($filterType)) return;

require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/constants.php';

$filterConfig = getPlaceTypeConfig($filterType);
if (!$filterConfig) return;

$filterTags = getPlaceTypeTags($filterType);
$filterAmenities = getPlaceTypeAmenities($filterType);
$activeTags = $activeFilters['tags'] ?? [];
$activeMunicipality = $activeFilters['municipality'] ?? '';
$activeSort = $activeFilters['sort'] ?? 'name';
$searchQuery = $activeFilters['q'] ?? '';
$sortOptions = $filterConfig['sort_options'] ?? ['name', 'rating', 'distance'];
$typeLabel = getPlaceLabelPlural($filterType, getCurrentLanguage());
$apiUrl = '/api/places.php?type=' . urlencode($filterType);
?>

<div class="place-filters" data-place-type="<?= h($filterType) ?>">
    <!-- Search -->
    <div class="mb-4">
        <div class="relative">
            <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400"></i>
            <input type="text"
                   name="q"
                   value="<?= h($searchQuery) ?>"
                   placeholder="Search <?= h($typeLabel) ?>..."
                   class="w-full pl-10 pr-4 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                   hx-get="<?= h($apiUrl) ?>"
                   hx-trigger="keyup changed delay:300ms"
                   hx-target="#place-results"
                   hx-include=".place-filters">
        </div>
    </div>

    <!-- Sort & View toggle -->
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
            <label class="text-sm text-gray-500 dark:text-gray-400">Sort:</label>
            <select name="sort"
                    class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-800"
                    hx-get="<?= h($apiUrl) ?>"
                    hx-trigger="change"
                    hx-target="#place-results"
                    hx-include=".place-filters">
                <?php foreach ($sortOptions as $opt): ?>
                <option value="<?= h($opt) ?>" <?= $activeSort === $opt ? 'selected' : '' ?>>
                    <?= h(ucfirst($opt)) ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Municipality filter -->
        <select name="municipality"
                class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-800"
                hx-get="<?= h($apiUrl) ?>"
                hx-trigger="change"
                hx-target="#place-results"
                hx-include=".place-filters">
            <option value="">All Municipalities</option>
            <?php foreach (MUNICIPALITIES as $mun): ?>
            <option value="<?= h($mun) ?>" <?= $activeMunicipality === $mun ? 'selected' : '' ?>>
                <?= h($mun) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Tag chips -->
    <?php if (!empty($filterTags)): ?>
    <div class="flex flex-wrap gap-2 mb-4">
        <?php foreach ($filterTags as $tag): ?>
        <?php
            $isActive = in_array($tag, $activeTags, true);
            $chipClass = $isActive
                ? 'bg-blue-600 text-white'
                : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-blue-50 dark:hover:bg-blue-900/20';
        ?>
        <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition <?= $chipClass ?>">
            <input type="checkbox" name="tags[]" value="<?= h($tag) ?>"
                   <?= $isActive ? 'checked' : '' ?>
                   class="sr-only"
                   hx-get="<?= h($apiUrl) ?>"
                   hx-trigger="change"
                   hx-target="#place-results"
                   hx-include=".place-filters">
            <?= h(getPlaceTagLabel($filterType, $tag)) ?>
        </label>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Type-specific filters -->
    <?php if ($filterType === 'trail'): ?>
    <div class="flex items-center gap-2 mb-4">
        <label class="text-sm text-gray-500">Difficulty:</label>
        <select name="difficulty"
                class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-800"
                hx-get="<?= h($apiUrl) ?>"
                hx-trigger="change"
                hx-target="#place-results"
                hx-include=".place-filters">
            <option value="">Any</option>
            <?php foreach (TRAIL_DIFFICULTIES as $diff): ?>
            <option value="<?= h($diff) ?>" <?= ($activeFilters['difficulty'] ?? '') === $diff ? 'selected' : '' ?>>
                <?= h(TRAIL_DIFFICULTY_LABELS[$diff] ?? ucfirst($diff)) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if ($filterType === 'restaurant'): ?>
    <div class="flex items-center gap-2 mb-4">
        <label class="text-sm text-gray-500">Price:</label>
        <select name="price_range"
                class="text-sm border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 bg-white dark:bg-gray-800"
                hx-get="<?= h($apiUrl) ?>"
                hx-trigger="change"
                hx-target="#place-results"
                hx-include=".place-filters">
            <option value="">Any</option>
            <?php foreach (PRICE_RANGES as $pr): ?>
            <option value="<?= h($pr) ?>" <?= ($activeFilters['price_range'] ?? '') === $pr ? 'selected' : '' ?>>
                <?= h($pr) ?> - <?= h(PRICE_RANGE_LABELS[$pr] ?? '') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
</div>
