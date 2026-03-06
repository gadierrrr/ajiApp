<?php
/**
 * Best Surfing Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: surfing puerto rico, best surf beaches puerto rico, rincon surfing
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$lang = getCurrentLanguage();

// Page metadata
$pageTitle = __('pages.best_surfing_beaches.title');
$pageDescription = __('pages.best_surfing_beaches.description');
$canonicalUrl = absoluteUrl('/best-surfing-beaches');

$collectionKey = 'best-surfing-beaches';
$collectionAnchorId = 'top-beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$surfingBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/best-surfing-beaches',
    $surfingBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $surfingBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => __('pages.best_surfing_beaches.faq_1_q'),
        'answer' => __('pages.best_surfing_beaches.faq_1_a')
    ],
    [
        'question' => __('pages.best_surfing_beaches.faq_2_q'),
        'answer' => __('pages.best_surfing_beaches.faq_2_a')
    ],
    [
        'question' => __('pages.best_surfing_beaches.faq_3_q'),
        'answer' => __('pages.best_surfing_beaches.faq_3_a')
    ],
    [
        'question' => __('pages.best_surfing_beaches.faq_4_q'),
        'answer' => __('pages.best_surfing_beaches.faq_4_a')
    ],
    [
        'question' => __('pages.best_surfing_beaches.faq_5_q'),
        'answer' => __('pages.best_surfing_beaches.faq_5_a')
    ]
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => __('footer.best_beaches'), 'url' => routeUrl('best_beaches', $lang)],
    ['name' => __('footer.surfing_beaches')]
];

$bodyVariant = 'collection-dark';
include APP_ROOT . '/components/header.php';
?>
<?php include APP_ROOT . '/components/collection/explorer.php'; ?>

<div class="collection-legacy-content">

<!-- Quick Navigation -->
<section class="collection-content-nav bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500"><?= h(__('pages.best_surfing_beaches.jump_to')) ?></span>
            <a href="#top-beaches" class="text-amber-700 hover:underline"><?= h(__('pages.best_surfing_beaches.jump_top_spots')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#by-level" class="text-amber-700 hover:underline"><?= h(__('pages.best_surfing_beaches.jump_by_level')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#season" class="text-amber-700 hover:underline"><?= h(__('pages.best_surfing_beaches.jump_season')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-700 hover:underline"><?= h(__('pages.best_surfing_beaches.jump_faq')) ?></a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p><?= __('pages.best_surfing_beaches.intro_p1') ?></p>

            <p><?= __('pages.best_surfing_beaches.intro_p2') ?></p>
        </div>
    </div>
</section>

<!-- By Skill Level -->
<section id="by-level" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.best_surfing_beaches.by_level_title')) ?>
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌱</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3"><?= h(__('pages.best_surfing_beaches.level_beginner_title')) ?></h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><?= __('pages.best_surfing_beaches.level_beginner_1') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_beginner_2') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_beginner_3') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_beginner_4') ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3"><?= h(__('pages.best_surfing_beaches.level_intermediate_title')) ?></h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><?= __('pages.best_surfing_beaches.level_intermediate_1') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_intermediate_2') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_intermediate_3') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_intermediate_4') ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🔥</div>
                <h3 class="text-lg font-bold text-gray-900 mb-3"><?= h(__('pages.best_surfing_beaches.level_advanced_title')) ?></h3>
                <ul class="text-gray-600 text-sm space-y-2">
                    <li><?= __('pages.best_surfing_beaches.level_advanced_1') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_advanced_2') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_advanced_3') ?></li>
                    <li><?= __('pages.best_surfing_beaches.level_advanced_4') ?></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Surf Season Info -->
<section id="season" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.best_surfing_beaches.season_title')) ?>
        </h2>

        <div class="grid md:grid-cols-2 gap-8">
            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('pages.best_surfing_beaches.season_winter_title')) ?></h3>
                <ul class="text-gray-700 space-y-2">
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_swell')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_winter_swell')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_height')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_winter_height')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_coasts')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_winter_coasts')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_temp')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_winter_temp')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_spots')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_winter_spots')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_crowds')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_winter_crowds')) ?></li>
                </ul>
            </div>

            <div class="bg-slate-50 rounded-xl p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4"><?= h(__('pages.best_surfing_beaches.season_summer_title')) ?></h3>
                <ul class="text-gray-700 space-y-2">
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_swell')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_summer_swell')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_height')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_summer_height')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_coasts')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_summer_coasts')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_temp')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_summer_temp')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_spots')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_summer_spots')) ?></li>
                    <li><strong><?= h(__('pages.best_surfing_beaches.season_label_crowds')) ?></strong> <?= h(__('pages.best_surfing_beaches.season_summer_crowds')) ?></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<?php $currentCollectionKey = $collectionKey; include APP_ROOT . '/components/collection/related-collections.php'; ?>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.best_surfing_beaches.faq_title')) ?>
        </h2>

        <div class="space-y-4">
            <?php foreach ($pageFaqs as $faq): ?>
            <details class="bg-white rounded-lg shadow-md group">
                <summary class="flex items-center justify-between p-6 cursor-pointer font-semibold text-gray-900">
                    <?= h($faq['question']) ?>
                    <span class="text-amber-700 group-open:rotate-180 transition-transform">▼</span>
                </summary>
                <div class="px-6 pb-6 text-gray-700">
                    <?= h($faq['answer']) ?>
                </div>
            </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Map Section -->
<section class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.best_surfing_beaches.map_title')) ?>
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=best-surfing-beaches#top-beaches" class="inline-flex items-center gap-2 bg-orange-600 hover:bg-orange-700 text-white px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span><?= h(__('pages.best_surfing_beaches.map_button')) ?></span>
            </a>
            <p class="text-gray-600 mt-4"><?= h(__('pages.best_surfing_beaches.map_desc')) ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-orange-600 text-white">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4"><?= h(__('pages.best_surfing_beaches.cta_title')) ?></h2>
        <p class="text-lg opacity-90 mb-6"><?= h(__('pages.best_surfing_beaches.cta_desc')) ?></p>
        <a href="<?= h(routeUrl('quiz', $lang)) ?>" class="inline-block bg-white text-orange-600 hover:bg-orange-50 px-8 py-3 rounded-lg font-semibold transition-colors">
            <?= h(__('pages.common.take_quiz')) ?>
        </a>
    </div>
</section>

</div>

<?php
$skipAppScripts = true;
$extraScripts = '<script defer src="/assets/js/collection-explorer.min.js" ' . cspNonceAttr() . '></script>';
?>
<?php include APP_ROOT . '/components/footer.php'; ?>
