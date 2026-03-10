<?php
/**
 * Explore page — unified search across all place types.
 *
 * Shows category cards, cross-type search, and featured places.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/place_types.php';
require_once APP_ROOT . '/inc/place_query.php';
require_once APP_ROOT . '/inc/place_helpers.php';
require_once APP_ROOT . '/inc/session.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/security_headers.php';

$lang = getCurrentLanguage();
$searchQuery = trim($_GET['q'] ?? '');
$selectedType = trim($_GET['type'] ?? '');

// Page meta
$pageTitle = __('explore.title', [], 'Explore Puerto Rico');
$pageDescription = __('explore.description', [], 'Discover beaches, rivers, waterfalls, trails, restaurants, and photo spots across Puerto Rico.');
$canonicalPath = routeUrl('explore', $lang);

// Get counts per type
$typeCounts = [];
foreach (PLACE_TYPES as $typeKey => $typeConfig) {
    $table = $typeConfig['table'];
    $row = queryOne("SELECT COUNT(*) AS c FROM {$table} WHERE publish_status = 'published'");
    $typeCounts[$typeKey] = intval($row['c'] ?? 0);
}

// If search query provided, fetch cross-type results
$searchResults = null;
if ($searchQuery !== '' || $selectedType !== '') {
    $filters = $_GET;
    if ($selectedType !== '' && isValidPlaceType($selectedType)) {
        $searchResults = fetchPlaces($selectedType, $filters, 24);
    } else {
        $searchResults = fetchAllPlaces($filters, 24);
    }
}

include APP_ROOT . '/components/header.php';
include APP_ROOT . '/components/nav.php';
?>

<!-- Hero -->
<section class="hero-gradient-dark relative py-20 px-4">
    <div class="max-w-6xl mx-auto text-center">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-4"><?= h($pageTitle) ?></h1>
        <p class="text-lg text-white/80 mb-8 max-w-2xl mx-auto"><?= h($pageDescription) ?></p>

        <!-- Search bar -->
        <div class="max-w-xl mx-auto">
            <form action="<?= h($canonicalPath) ?>" method="GET" class="relative">
                <i data-lucide="search" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-gray-400"></i>
                <input type="text" name="q" value="<?= h($searchQuery) ?>"
                       placeholder="<?= h(__('explore.search_placeholder', [], 'Search all places in Puerto Rico...')) ?>"
                       class="w-full pl-12 pr-4 py-4 rounded-xl text-lg border-0 shadow-lg focus:ring-2 focus:ring-blue-500">
                <?php if ($selectedType): ?>
                <input type="hidden" name="type" value="<?= h($selectedType) ?>">
                <?php endif; ?>
            </form>
        </div>
    </div>
</section>

<!-- Category cards -->
<section class="max-w-6xl mx-auto px-4 -mt-8 relative z-10">
    <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
        <?php foreach (PLACE_TYPES as $typeKey => $typeConfig): ?>
        <?php
            $label = getPlaceLabelPlural($typeKey, $lang);
            $icon = $typeConfig['icon'];
            $emoji = $typeConfig['emoji'];
            $count = $typeCounts[$typeKey] ?? 0;
            $isActive = ($selectedType === $typeKey);

            // Build link to collection page or filtered explore
            $collectionRoutes = [
                'beach' => 'best_beaches',
                'river' => 'best_rivers',
                'waterfall' => 'best_waterfalls',
                'trail' => 'best_trails',
                'restaurant' => 'best_restaurants',
                'photo_spot' => 'best_photo_spots',
            ];
            $routeKey = $collectionRoutes[$typeKey] ?? 'explore';
            $categoryUrl = routeUrl($routeKey, $lang);
        ?>
        <a href="<?= h($categoryUrl) ?>"
           class="group block bg-white dark:bg-gray-800 rounded-xl shadow-card hover:shadow-card-hover p-4 text-center transition-all <?= $isActive ? 'ring-2 ring-blue-500' : '' ?>">
            <div class="text-3xl mb-2"><?= $emoji ?></div>
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 transition"><?= h($label) ?></h3>
            <p class="text-xs text-gray-400 mt-1"><?= number_format($count) ?> places</p>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<?php if ($searchResults): ?>
<!-- Search Results -->
<section class="max-w-6xl mx-auto px-4 py-12">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">
            <?php if ($searchQuery): ?>
                Results for "<?= h($searchQuery) ?>"
            <?php elseif ($selectedType): ?>
                <?= h(getPlaceLabelPlural($selectedType, $lang)) ?>
            <?php else: ?>
                All Places
            <?php endif; ?>
            <span class="text-gray-400 text-lg font-normal">(<?= number_format($searchResults['total']) ?>)</span>
        </h2>
    </div>

    <div id="place-results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php foreach ($searchResults['places'] as $place): ?>
            <?php include APP_ROOT . '/components/place-card.php'; ?>
        <?php endforeach; ?>
    </div>
    <div id="place-pagination"></div>
</section>
<?php else: ?>
<!-- Featured sections (when no search) -->
<section class="max-w-6xl mx-auto px-4 py-12 space-y-12">
    <?php
    // Show a few featured places per category that has data
    foreach (PLACE_TYPES as $typeKey => $typeConfig):
        if ($typeCounts[$typeKey] === 0) continue;
        $featured = fetchPlaces($typeKey, ['sort' => 'rating', 'limit' => 4]);
        if (empty($featured['places'])) continue;

        $collectionRoutes = [
            'beach' => 'best_beaches', 'river' => 'best_rivers',
            'waterfall' => 'best_waterfalls', 'trail' => 'best_trails',
            'restaurant' => 'best_restaurants', 'photo_spot' => 'best_photo_spots',
        ];
        $seeAllUrl = routeUrl($collectionRoutes[$typeKey] ?? 'explore', $lang);
    ?>
    <div>
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span><?= $typeConfig['emoji'] ?></span>
                <?= h(getPlaceLabelPlural($typeKey, $lang)) ?>
            </h2>
            <a href="<?= h($seeAllUrl) ?>" class="text-sm font-medium text-blue-600 hover:text-blue-700">
                View All &rarr;
            </a>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <?php foreach ($featured['places'] as $place): ?>
                <?php include APP_ROOT . '/components/place-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?php
$extraScripts = '';
include APP_ROOT . '/components/footer.php';
?>
