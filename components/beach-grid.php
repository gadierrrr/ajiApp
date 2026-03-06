<?php
/**
 * Beach Grid Component
 * Renders a grid of beach cards
 *
 * @param array $beaches - Array of beach data
 * @param array $userFavorites - Array of beach IDs that are favorites
 * @param array|null $userLocation - User's location [lat, lng] if available
 * @param bool $showLiveData - Whether to show crowd/weather data
 */

// $beaches, $userFavorites, $userLocation, $showLiveData should be set before including

$beaches = $beaches ?? [];
$userFavorites = $userFavorites ?? [];
$userLocation = $userLocation ?? null;
$showLiveData = $showLiveData ?? true;

// Batch fetch crowd data if enabled
$crowdDataMap = [];
if ($showLiveData && !empty($beaches)) {
    require_once __DIR__ . '/../inc/crowd.php';
    $beachIds = array_column($beaches, 'id');
    $crowdDataMap = getBatchCrowdLevels($beachIds, 4);
}

// Weather data is now loaded asynchronously via AJAX to prevent slow page loads
// See /api/weather-batch.php and assets/js/app.js for implementation
$weatherDataMap = [];
?>

<?php if (empty($beaches)): ?>
<div class="col-span-full text-center py-16">
    <i data-lucide="umbrella" class="w-16 h-16 mx-auto text-white/30 mb-4" aria-hidden="true"></i>
    <h3 class="text-xl font-semibold text-white mb-2"><?= h(__('filters.no_results_title')) ?></h3>
    <p class="text-white/60 mb-4"><?= h(__('filters.no_results_hint')) ?></p>
    <button data-action="clearFilters" class="text-brand-yellow hover:text-yellow-300 font-medium inline-flex items-center gap-1.5">
        <i data-lucide="x" class="w-4 h-4" aria-hidden="true"></i>
        <?= h(__('filters.clear_filters')) ?>
    </button>
</div>
<?php else: ?>

<div id="beach-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php foreach ($beaches as $beach):
        // Calculate distance if user location available
        $distance = null;
        if ($userLocation) {
            require_once __DIR__ . '/../inc/geo.php';
            $distance = calculateDistance(
                $userLocation['lat'],
                $userLocation['lng'],
                (float)$beach['lat'],
                (float)$beach['lng']
            );
        }

        // Check if favorite
        $isFavorite = in_array($beach['id'], $userFavorites);

        // Get crowd data for this beach
        $crowdData = $crowdDataMap[$beach['id']] ?? null;

        // Get weather data for this beach (from batch fetch)
        $weatherData = $weatherDataMap[$beach['id']] ?? null;

        // Include beach card
        include __DIR__ . '/beach-card.php';
    endforeach; ?>
</div>

<?php endif; ?>
