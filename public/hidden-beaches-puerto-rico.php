<?php
/**
 * Hidden Beaches in Puerto Rico - SEO Landing Page
 * Target keywords: hidden beaches puerto rico, secret beaches puerto rico
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
$pageTitle = __('pages.hidden_beaches.title');
$pageDescription = __('pages.hidden_beaches.description');
$canonicalUrl = absoluteUrl('/hidden-beaches-puerto-rico');

$collectionKey = 'hidden-beaches-puerto-rico';
$collectionAnchorId = 'hidden-beaches';
$collectionData = fetchCollectionBeaches($collectionKey, collectionFiltersFromRequest($collectionKey, $_GET));
$collectionContext = $collectionData['collection'];
$collectionState = $collectionData['effective_filters'];
$hiddenBeaches = $collectionData['beaches'];

$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]) ?: [];
    $userFavorites = array_column($favorites, 'beach_id');
}

// Generate structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    '/hidden-beaches-puerto-rico',
    $hiddenBeaches[0]['cover_image'] ?? null,
    '2026-01-15'
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $hiddenBeaches);
$extraHead .= websiteSchema();

// FAQ data
$pageFaqs = [
    [
        'question' => __('pages.hidden_beaches.faq_1_q'),
        'answer' => __('pages.hidden_beaches.faq_1_a')
    ],
    [
        'question' => __('pages.hidden_beaches.faq_2_q'),
        'answer' => __('pages.hidden_beaches.faq_2_a')
    ],
    [
        'question' => __('pages.hidden_beaches.faq_3_q'),
        'answer' => __('pages.hidden_beaches.faq_3_a')
    ],
    [
        'question' => __('pages.hidden_beaches.faq_4_q'),
        'answer' => __('pages.hidden_beaches.faq_4_a')
    ],
    [
        'question' => __('pages.hidden_beaches.faq_5_q'),
        'answer' => __('pages.hidden_beaches.faq_5_a')
    ],
    [
        'question' => __('pages.hidden_beaches.faq_6_q'),
        'answer' => __('pages.hidden_beaches.faq_6_a')
    ],
    [
        'question' => __('pages.hidden_beaches.faq_7_q'),
        'answer' => __('pages.hidden_beaches.faq_7_a')
    ],
    [
        'question' => __('pages.hidden_beaches.faq_8_q'),
        'answer' => __('pages.hidden_beaches.faq_8_a')
    ]
];
$extraHead .= faqSchema($pageFaqs);

// HowTo Schema for finding hidden beaches (locale-aware)
$howToSteps = [
    [
        'name' => __('pages.hidden_beaches.howto_step1_name'),
        'text' => __('pages.hidden_beaches.howto_step1_text')
    ],
    [
        'name' => __('pages.hidden_beaches.howto_step2_name'),
        'text' => __('pages.hidden_beaches.howto_step2_text')
    ],
    [
        'name' => __('pages.hidden_beaches.howto_step3_name'),
        'text' => __('pages.hidden_beaches.howto_step3_text')
    ],
    [
        'name' => __('pages.hidden_beaches.howto_step4_name'),
        'text' => __('pages.hidden_beaches.howto_step4_text')
    ],
    [
        'name' => __('pages.hidden_beaches.howto_step5_name'),
        'text' => __('pages.hidden_beaches.howto_step5_text')
    ]
];
$extraHead .= howToSchema(
    __('pages.hidden_beaches.howto_title'),
    __('pages.hidden_beaches.howto_desc'),
    $howToSteps
);

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => __('footer.hidden_beaches')]
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
            <span class="text-gray-500"><?= h(__('pages.hidden_beaches.jump_to')) ?></span>
            <a href="#hidden-beaches" class="text-amber-700 hover:underline"><?= h(__('pages.hidden_beaches.jump_top_list')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#by-region" class="text-amber-700 hover:underline"><?= h(__('pages.hidden_beaches.jump_by_region')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#access-guide" class="text-amber-700 hover:underline"><?= h(__('pages.hidden_beaches.jump_access_guide')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#what-to-bring" class="text-amber-700 hover:underline"><?= h(__('pages.hidden_beaches.jump_what_to_bring')) ?></a>
            <span class="text-gray-300">|</span>
            <a href="#faq" class="text-amber-700 hover:underline"><?= h(__('pages.hidden_beaches.jump_faq')) ?></a>
        </div>
    </div>
</section>

<!-- Introduction -->
<section class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4">
        <div class="prose prose-lg max-w-none beach-description">
            <p><?= __('pages.hidden_beaches.intro_p1') ?></p>

            <p><?= __('pages.hidden_beaches.intro_p2') ?></p>

            <p><?= __('pages.hidden_beaches.intro_p3') ?></p>

            <p><?= __('pages.hidden_beaches.intro_p4') ?></p>
        </div>
    </div>
</section>

<!-- Hidden Beaches by Region -->
<section id="by-region" class="py-12 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.hidden_beaches.by_region_title')) ?>
        </h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🌊</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.region_northwest_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-4"><?= h(__('pages.hidden_beaches.region_northwest_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• <?= h(__('pages.hidden_beaches.region_northwest_1')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_northwest_2')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_northwest_3')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_northwest_4')) ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🏝️</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.region_east_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-4"><?= h(__('pages.hidden_beaches.region_east_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• <?= h(__('pages.hidden_beaches.region_east_1')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_east_2')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_east_3')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_east_4')) ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🌅</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.region_south_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-4"><?= h(__('pages.hidden_beaches.region_south_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• <?= h(__('pages.hidden_beaches.region_south_1')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_south_2')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_south_3')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_south_4')) ?></li>
                </ul>
            </div>

            <div class="bg-white rounded-xl p-6 shadow-md">
                <div class="text-4xl mb-4">🐚</div>
                <h3 class="text-lg font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.region_vieques_title')) ?></h3>
                <p class="text-gray-600 text-sm mb-4"><?= h(__('pages.hidden_beaches.region_vieques_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-1">
                    <li>• <?= h(__('pages.hidden_beaches.region_vieques_1')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_vieques_2')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_vieques_3')) ?></li>
                    <li>• <?= h(__('pages.hidden_beaches.region_vieques_4')) ?></li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Access Difficulty Guide -->
<section id="access-guide" class="py-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.hidden_beaches.access_guide_title')) ?>
        </h2>

        <div class="grid md:grid-cols-3 gap-6">
            <div class="bg-slate-50 border-2 border-slate-200 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-slate-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                        1
                    </div>
                    <h3 class="text-lg font-bold text-gray-900"><?= h(__('pages.hidden_beaches.access_easy_title')) ?></h3>
                </div>
                <p class="text-gray-700 text-sm mb-4"><?= h(__('pages.hidden_beaches.access_easy_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-amber-700 font-bold">✓</span>
                        <span><?= h(__('pages.hidden_beaches.access_easy_1')) ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-700 font-bold">✓</span>
                        <span><?= h(__('pages.hidden_beaches.access_easy_2')) ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-amber-700 font-bold">✓</span>
                        <span><?= h(__('pages.hidden_beaches.access_easy_3')) ?></span>
                    </li>
                </ul>
                <p class="text-xs text-gray-600 mt-4 italic"><?= h(__('pages.hidden_beaches.access_easy_examples')) ?></p>
            </div>

            <div class="bg-yellow-50 border-2 border-yellow-200 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-yellow-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                        2
                    </div>
                    <h3 class="text-lg font-bold text-gray-900"><?= h(__('pages.hidden_beaches.access_moderate_title')) ?></h3>
                </div>
                <p class="text-gray-700 text-sm mb-4"><?= h(__('pages.hidden_beaches.access_moderate_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-yellow-600 font-bold">!</span>
                        <span><?= h(__('pages.hidden_beaches.access_moderate_1')) ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-yellow-600 font-bold">!</span>
                        <span><?= h(__('pages.hidden_beaches.access_moderate_2')) ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-yellow-600 font-bold">!</span>
                        <span><?= h(__('pages.hidden_beaches.access_moderate_3')) ?></span>
                    </li>
                </ul>
                <p class="text-xs text-gray-600 mt-4 italic"><?= h(__('pages.hidden_beaches.access_moderate_examples')) ?></p>
            </div>

            <div class="bg-red-50 border-2 border-red-200 rounded-xl p-6">
                <div class="flex items-center gap-3 mb-4">
                    <div class="bg-red-500 text-white rounded-full w-10 h-10 flex items-center justify-center font-bold">
                        3
                    </div>
                    <h3 class="text-lg font-bold text-gray-900"><?= h(__('pages.hidden_beaches.access_difficult_title')) ?></h3>
                </div>
                <p class="text-gray-700 text-sm mb-4"><?= h(__('pages.hidden_beaches.access_difficult_desc')) ?></p>
                <ul class="text-sm text-gray-700 space-y-2">
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">⚠</span>
                        <span><?= h(__('pages.hidden_beaches.access_difficult_1')) ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">⚠</span>
                        <span><?= h(__('pages.hidden_beaches.access_difficult_2')) ?></span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-600 font-bold">⚠</span>
                        <span><?= h(__('pages.hidden_beaches.access_difficult_3')) ?></span>
                    </li>
                </ul>
                <p class="text-xs text-gray-600 mt-4 italic"><?= h(__('pages.hidden_beaches.access_difficult_examples')) ?></p>
            </div>
        </div>
    </div>
</section>

<!-- What to Bring -->
<section id="what-to-bring" class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.hidden_beaches.packing_title')) ?>
        </h2>

        <div class="bg-white rounded-xl shadow-md p-8">
            <p class="text-gray-700 mb-6">
                <?= h(__('pages.hidden_beaches.packing_intro')) ?>
            </p>

            <div class="grid md:grid-cols-2 gap-8">
                <div>
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-2xl">💧</span> <?= h(__('pages.hidden_beaches.packing_hydration_title')) ?>
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= __('pages.hidden_beaches.packing_hydration_1') ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_hydration_2')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_hydration_3')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_hydration_4')) ?></span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">☀️</span> <?= h(__('pages.hidden_beaches.packing_sun_title')) ?>
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= __('pages.hidden_beaches.packing_sun_1') ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_sun_2')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_sun_3')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_sun_4')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_sun_5')) ?></span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">🏊</span> <?= h(__('pages.hidden_beaches.packing_gear_title')) ?>
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_gear_1')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_gear_2')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_gear_3')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_gear_4')) ?></span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h3 class="font-bold text-gray-900 mb-4 flex items-center gap-2">
                        <span class="text-2xl">🎒</span> <?= h(__('pages.hidden_beaches.packing_safety_title')) ?>
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= __('pages.hidden_beaches.packing_safety_1') ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_safety_2')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_safety_3')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_safety_4')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_safety_5')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_safety_6')) ?></span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">♻️</span> <?= h(__('pages.hidden_beaches.packing_lnt_title')) ?>
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= __('pages.hidden_beaches.packing_lnt_1') ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_lnt_2')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_lnt_3')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_lnt_4')) ?></span>
                        </li>
                    </ul>

                    <h3 class="font-bold text-gray-900 mb-4 mt-6 flex items-center gap-2">
                        <span class="text-2xl">💵</span> <?= h(__('pages.hidden_beaches.packing_money_title')) ?>
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-700">
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= __('pages.hidden_beaches.packing_money_1') ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_money_2')) ?></span>
                        </li>
                        <li class="flex items-start gap-2">
                            <span class="text-amber-700">✓</span>
                            <span><?= h(__('pages.hidden_beaches.packing_money_3')) ?></span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-8 p-4 bg-slate-50 border-l-4 border-amber-500 rounded">
                <p class="text-sm text-gray-800">
                    <?= __('pages.hidden_beaches.packing_pro_tip') ?>
                </p>
            </div>
        </div>
    </div>
</section>

<!-- Responsible Tourism Tips -->
<section class="py-12 bg-gradient-to-br from-slate-50 to-slate-100">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.hidden_beaches.responsible_title')) ?>
        </h2>

        <div class="bg-white rounded-xl shadow-md p-8">
            <p class="text-gray-700 mb-6">
                <?= h(__('pages.hidden_beaches.responsible_intro')) ?>
            </p>

            <div class="space-y-6">
                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-xl">
                        🚯
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.responsible_lnt_title')) ?></h3>
                        <p class="text-gray-700 text-sm">
                            <?= h(__('pages.hidden_beaches.responsible_lnt_desc')) ?>
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-xl">
                        🐠
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.responsible_marine_title')) ?></h3>
                        <p class="text-gray-700 text-sm">
                            <?= h(__('pages.hidden_beaches.responsible_marine_desc')) ?>
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-yellow-100 rounded-full flex items-center justify-center text-xl">
                        🥾
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.responsible_trails_title')) ?></h3>
                        <p class="text-gray-700 text-sm">
                            <?= h(__('pages.hidden_beaches.responsible_trails_desc')) ?>
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-xl">
                        🤝
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.responsible_community_title')) ?></h3>
                        <p class="text-gray-700 text-sm">
                            <?= h(__('pages.hidden_beaches.responsible_community_desc')) ?>
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center text-xl">
                        🔇
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.responsible_quiet_title')) ?></h3>
                        <p class="text-gray-700 text-sm">
                            <?= h(__('pages.hidden_beaches.responsible_quiet_desc')) ?>
                        </p>
                    </div>
                </div>

                <div class="flex gap-4">
                    <div class="flex-shrink-0 w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center text-xl">
                        🤐
                    </div>
                    <div>
                        <h3 class="font-bold text-gray-900 mb-2"><?= h(__('pages.hidden_beaches.responsible_share_title')) ?></h3>
                        <p class="text-gray-700 text-sm">
                            <?= h(__('pages.hidden_beaches.responsible_share_desc')) ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-8 p-4 bg-slate-50 border-l-4 border-amber-500 rounded">
                <p class="text-sm text-gray-800">
                    <?= __('pages.hidden_beaches.responsible_remember') ?>
                </p>
            </div>
        </div>
    </div>
</section>

<?php $currentCollectionKey = $collectionKey; include APP_ROOT . '/components/collection/related-collections.php'; ?>

<!-- FAQ Section -->
<section id="faq" class="py-12 bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.hidden_beaches.faq_title')) ?>
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
<section id="map" class="py-12 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-8 text-center">
            <?= h(__('pages.hidden_beaches.map_title')) ?>
        </h2>
        <div class="text-center">
            <a href="?view=map&collection=hidden-beaches-puerto-rico#top-beaches" class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <span>🗺️</span>
                <span><?= h(__('pages.hidden_beaches.map_button')) ?></span>
            </a>
            <p class="text-gray-600 mt-4"><?= h(__('pages.hidden_beaches.map_desc')) ?></p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="py-12 bg-gradient-to-br bg-brand-dark text-brand-text">
    <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-4"><?= h(__('pages.hidden_beaches.cta_title')) ?></h2>
        <p class="text-lg opacity-90 mb-6"><?= h(__('pages.hidden_beaches.cta_desc')) ?></p>
        <a href="<?= h(routeUrl('quiz', $lang)) ?>" class="inline-block bg-white text-brand-darker hover:bg-gray-100 px-8 py-3 rounded-lg font-semibold transition-colors">
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
