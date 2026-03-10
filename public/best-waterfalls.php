<?php
/**
 * Best Waterfalls collection page.
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

$placeType = 'waterfall';
$lang = getCurrentLanguage();
$config = getPlaceTypeConfig($placeType);

$pageTitle = __('pages.best_waterfalls.title', [], 'Best Waterfalls in Puerto Rico');
$pageDescription = __('pages.best_waterfalls.description', [], 'Explore the most stunning waterfalls in Puerto Rico, from easy-access cascades to hidden jungle gems.');
$heroTitle = __('pages.best_waterfalls.hero_title', [], 'Best Waterfalls');
$heroSubtitle = __('pages.best_waterfalls.hero_subtitle', [], 'Majestic cascades, jungle hikes, and refreshing natural pools.');
$canonicalPath = routeUrl('best_waterfalls', $lang);

$filters = $_GET;
$filters['sort'] = $filters['sort'] ?? 'rating';
$result = fetchPlaces($placeType, $filters, 60);
$places = $result['places'];
$activeFilters = $result['effective_filters'];

include APP_ROOT . '/components/header.php';
include APP_ROOT . '/components/nav.php';
?>

<section class="hero-gradient-dark relative py-16 px-4">
    <div class="max-w-6xl mx-auto text-center">
        <span class="text-4xl mb-3 block"><?= $config['emoji'] ?></span>
        <h1 class="text-3xl md:text-4xl font-bold text-white mb-3"><?= h($heroTitle) ?></h1>
        <p class="text-lg text-white/80 max-w-2xl mx-auto"><?= h($heroSubtitle) ?></p>
        <p class="text-white/60 mt-3"><?= number_format($result['total']) ?> waterfalls found</p>
    </div>
</section>

<div class="max-w-6xl mx-auto px-4 py-8">
    <?php
    $filterType = $placeType;
    include APP_ROOT . '/components/place-filters.php';
    ?>

    <div id="place-results" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mt-6">
        <?php if (empty($places)): ?>
        <div class="col-span-full text-center py-12 text-gray-400">
            <p>No waterfalls found matching your filters.</p>
        </div>
        <?php else: ?>
            <?php foreach ($places as $place): ?>
                <?php $isFavorite = false; ?>
                <?php include APP_ROOT . '/components/place-card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div id="place-pagination"></div>
</div>

<?php
$extraScripts = '';
include APP_ROOT . '/components/footer.php';
?>
