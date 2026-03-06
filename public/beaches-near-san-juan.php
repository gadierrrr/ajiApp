<?php
/**
 * Beaches Near San Juan - SEO Landing Page
 * Target keywords: beaches near san juan, san juan beaches, beaches puerto rico capital
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
$pageTitle = __('pages.beaches_near_san_juan.title');
$pageDescription = __('pages.beaches_near_san_juan.description');
$canonicalUrl = absoluteUrl('/beaches-near-san-juan');

$collectionKey = 'beaches-near-san-juan';
$collectionAnchorId = 'beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$sanJuanBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/beaches-near-san-juan',
    $sanJuanBeaches[0]['cover_image'] ?? null,
    '2026-01-01'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $sanJuanBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => __('pages.beaches_near_san_juan.faq_1_q'),
        'answer' => __('pages.beaches_near_san_juan.faq_1_a')
    ],
    [
        'question' => __('pages.beaches_near_san_juan.faq_2_q'),
        'answer' => __('pages.beaches_near_san_juan.faq_2_a')
    ],
    [
        'question' => __('pages.beaches_near_san_juan.faq_3_q'),
        'answer' => __('pages.beaches_near_san_juan.faq_3_a')
    ],
    [
        'question' => __('pages.beaches_near_san_juan.faq_4_q'),
        'answer' => __('pages.beaches_near_san_juan.faq_4_a')
    ],
    [
        'question' => __('pages.beaches_near_san_juan.faq_5_q'),
        'answer' => __('pages.beaches_near_san_juan.faq_5_a')
    ]
];
$extraHead .= faqSchema($pageFaqs);

$bodyVariant = 'collection-dark';
include APP_ROOT . '/components/header.php';
?>
<?php include APP_ROOT . '/components/collection/explorer.php'; ?>

<div class="collection-legacy-content">

<!-- Quick Navigation -->
<section class="collection-content-nav bg-white border-b">
    <div class="max-w-7xl mx-auto px-4 py-4">
        <div class="flex flex-wrap gap-2 justify-center text-sm">
            <span class="text-gray-500"><?= h(__('pages.beaches_near_san_juan.jump_to')) ?></span>
            <a href="#beaches" class="text-amber-700 hover:underline"><?= h(__('pages.beaches_near_san_juan.jump_beach_list')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#neighborhoods" class="text-amber-700 hover:underline"><?= h(__('pages.beaches_near_san_juan.jump_neighborhoods')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#getting-there" class="text-amber-700 hover:underline"><?= h(__('pages.beaches_near_san_juan.jump_getting_there')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-700 hover:underline"><?= h(__('pages.beaches_near_san_juan.jump_faq')) ?></a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p><?= __('pages.beaches_near_san_juan.intro_p1') ?></p>

            <p><?= __('pages.beaches_near_san_juan.intro_p2') ?></p>
        </div>
    </div>
</section>

<!-- By Neighborhood -->
<section id="neighborhoods" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.beaches_near_san_juan.neighborhoods_title')) ?>
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_condado_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_condado_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_condado_1')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_condado_2')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_condado_3')) ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_isla_verde_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_isla_verde_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_isla_verde_1')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_isla_verde_2')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_isla_verde_3')) ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_ocean_park_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_ocean_park_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_ocean_park_1')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_ocean_park_2')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_ocean_park_3')) ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <h3 class="text-lg font-bold text-gray-900 mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_puerta_de_tierra_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-3"><?= h(__('pages.beaches_near_san_juan.neighborhood_puerta_de_tierra_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_puerta_de_tierra_1')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_puerta_de_tierra_2')) ?></li>
                    <li><?= h(__('pages.beaches_near_san_juan.neighborhood_puerta_de_tierra_3')) ?></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Getting There -->
<section id="getting-there" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.beaches_near_san_juan.getting_there_title')) ?>
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚕</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h(__('pages.beaches_near_san_juan.getting_there_taxi_title')) ?></h3>
                <p class="text-gray-600 text-sm"><?= h(__('pages.beaches_near_san_juan.getting_there_taxi_desc')) ?></p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚌</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h(__('pages.beaches_near_san_juan.getting_there_bus_title')) ?></h3>
                <p class="text-gray-600 text-sm"><?= h(__('pages.beaches_near_san_juan.getting_there_bus_desc')) ?></p>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-3xl mb-4">🚶</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h(__('pages.beaches_near_san_juan.getting_there_walking_title')) ?></h3>
                <p class="text-gray-600 text-sm"><?= h(__('pages.beaches_near_san_juan.getting_there_walking_desc')) ?></p>
            </div>
        </div>
    </div>
</section>

<?php $currentCollectionKey = $collectionKey; include APP_ROOT . '/components/collection/related-collections.php'; ?>

<!-- FAQ Section -->
<section id="faq" class="py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.beaches_near_san_juan.faq_title')) ?>
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
            <?= h(__('pages.beaches_near_san_juan.map_title')) ?>
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=beaches-near-san-juan#top-beaches" class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span><?= h(__('pages.beaches_near_san_juan.map_button')) ?></span>
            </a>
            <p class="text-gray-600 mt-4"><?= h(__('pages.beaches_near_san_juan.map_desc')) ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-brand-yellow text-brand-darker">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4"><?= h(__('pages.beaches_near_san_juan.cta_title')) ?></h2>
        <p class="text-lg opacity-90 mb-6"><?= h(__('pages.beaches_near_san_juan.cta_desc')) ?></p>
        <a href="<?= h(routeUrl('quiz', $lang)) ?>" class="inline-block bg-white text-amber-700 hover:bg-slate-50 px-8 py-3 rounded-lg font-semibold transition-colors">
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
