<?php
/**
 * Municipality Landing Pages
 * Dynamic SEO-optimized pages for each municipality
 * URL: /beaches-in-{municipality-slug}
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/components/seo-schemas.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
$lang = getCurrentLanguage();

// Get municipality from slug or query parameter
$municipalitySlug = $_GET['m'] ?? '';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($requestPath === '/municipality.php' && $municipalitySlug !== '') {
    $targetPath = $lang === 'es'
        ? '/es/playas-en-' . $municipalitySlug
        : '/beaches-in-' . $municipalitySlug;
    header('Location: ' . $targetPath, true, 301);
    exit;
}

if (!$municipalitySlug) {
    http_response_code(404);
    header('Location: /');
    exit;
}

// Convert slug back to municipality name (e.g., "san-juan" -> "San Juan")
$municipality = ucwords(str_replace('-', ' ', $municipalitySlug));

// Validate municipality exists
if (!isValidMunicipality($municipality)) {
    http_response_code(404);
    header('Location: /');
    exit;
}

// Fetch beaches in this municipality
$beaches = query("
    SELECT b.*
    FROM beaches b
    WHERE b.municipality = :municipality
    AND b.publish_status = 'published'
    ORDER BY
        CASE WHEN b.google_rating IS NOT NULL THEN 1 ELSE 2 END,
        b.google_rating DESC,
        b.name ASC
", [':municipality' => $municipality]);

if (empty($beaches)) {
    http_response_code(404);
    header('Location: /');
    exit;
}

// Attach metadata (tags, amenities)
attachBeachMetadata($beaches);

// Calculate stats
$beachCount = count($beaches);
$avgRating = 0;
$ratedBeaches = array_filter($beaches, fn($b) => !empty($b['google_rating']));
if (!empty($ratedBeaches)) {
    $avgRating = array_sum(array_column($ratedBeaches, 'google_rating')) / count($ratedBeaches);
}

// Get top beaches
$topBeaches = array_slice($beaches, 0, 3);

// Get popular tags for this municipality
$tagCounts = [];
foreach ($beaches as $beach) {
    foreach ($beach['tags'] ?? [] as $tag) {
        $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
    }
}
arsort($tagCounts);
$topTags = array_slice(array_keys($tagCounts), 0, 5);

// Page metadata
$pageTitle = __('pages.municipality.title', ['municipality' => $municipality, 'count' => $beachCount]);
$pageDescription = __('pages.municipality.description', ['municipality' => $municipality, 'count' => $beachCount]);
$canonicalUrl = getPublicBaseUrl() . routeUrl('municipality', $lang, ['municipality' => $municipalitySlug]);

// Structured data
$extraHead = articleSchema(
    $pageTitle,
    $pageDescription,
    routeUrl('municipality', $lang, ['municipality' => $municipalitySlug]),
    $topBeaches[0]['cover_image'] ?? null
);
$extraHead .= collectionPageSchema($pageTitle, $pageDescription, $beaches);

// Dynamic FAQs based on municipality
$pageFaqs = [
    [
        'question' => __('pages.municipality.faq_how_many_q', ['municipality' => $municipality]),
        'answer' => __('pages.municipality.faq_how_many_a', ['municipality' => $municipality, 'count' => $beachCount]),
    ],
    [
        'question' => __('pages.municipality.faq_best_q', ['municipality' => $municipality]),
        'answer' => !empty($topBeaches)
            ? __('pages.municipality.faq_best_a', [
                'beach' => $topBeaches[0]['name'],
                'municipality' => $municipality,
                'rating' => $topBeaches[0]['google_rating']
            ])
            : __('pages.municipality.faq_best_a_fallback', ['municipality' => $municipality])
    ],
    [
        'question' => __('pages.municipality.faq_activities_q', ['municipality' => $municipality]),
        'answer' => (function() use ($topTags, $municipality) {
            $tagsList = !empty($topTags) ? implode(', ', array_map('getTagLabel', array_slice($topTags, 0, 3))) : __('pages.municipality.faq_activities_fallback');
            return __('pages.municipality.faq_activities_a', ['municipality' => $municipality, 'tags' => $tagsList]);
        })()
    ],
    [
        'question' => __('pages.municipality.faq_getting_there_q', ['municipality' => $municipality]),
        'answer' => __('pages.municipality.faq_getting_there_a', ['municipality' => $municipality]),
    ],
];
$extraHead .= faqSchema($pageFaqs);

// Breadcrumbs
$extraHead .= breadcrumbSchema([
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => __('nav.beaches'), 'url' => routeUrl('home', $lang) . '#beaches'],
    ['name' => $municipality, 'url' => routeUrl('municipality', $lang, ['municipality' => $municipalitySlug])]
]);

include APP_ROOT . '/components/header.php';
?>

<!-- Hero Section -->
<section class="hero-gradient-dark text-white py-12 md:py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Breadcrumbs -->
        <nav class="text-white/70 text-sm mb-4" aria-label="Breadcrumb">
            <a href="<?= h(routeUrl('home', $lang)) ?>" class="hover:text-brand-yellow transition-colors"><?= h(__('nav.home')) ?></a>
            <span class="mx-2">/</span>
            <a href="<?= h(routeUrl('home', $lang)) ?>#beaches" class="hover:text-brand-yellow transition-colors"><?= h(__('nav.beaches')) ?></a>
            <span class="mx-2">/</span>
            <span class="text-white/70"><?= h($municipality) ?></span>
        </nav>

        <h1 class="text-3xl md:text-5xl font-bold mb-4">
            <?= h(__('pages.municipality.hero_title', ['municipality' => $municipality])) ?>
        </h1>
        <p class="text-lg md:text-xl opacity-90 max-w-3xl page-description">
            <?= h(__('pages.municipality.hero_subtitle', ['municipality' => $municipality, 'count' => $beachCount])) ?>
        </p>

        <!-- Stats Bar -->
        <div class="flex flex-wrap gap-6 mt-6 text-sm">
            <div class="flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-brand-yellow"></i>
                <span><?= h(__('pages.municipality.beaches_count', ['count' => $beachCount])) ?></span>
            </div>
            <?php if ($avgRating > 0): ?>
            <div class="flex items-center gap-2">
                <i data-lucide="star" class="w-5 h-5 text-brand-yellow"></i>
                <span><strong><?= number_format($avgRating, 1) ?></strong> <?= h(__('pages.municipality.avg_rating')) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($topTags)): ?>
            <div class="flex items-center gap-2">
                <i data-lucide="activity" class="w-5 h-5 text-brand-yellow"></i>
                <span><?= h(getTagLabel($topTags[0])) ?>, <?= h(getTagLabel($topTags[1] ?? $topTags[0])) ?> <?= h(__('pages.municipality.and_more')) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="bg-brand-dark border-b border-white/10 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <?php
        $contextType = 'municipality';
        $contextKey = (string) $municipalitySlug;
        $filtersQuery = '';
        $title = __('pages.municipality.send_title');
        $subtitle = __('pages.municipality.send_subtitle', ['municipality' => $municipality]);
        include APP_ROOT . '/components/send-list-capture.php';
        ?>
    </div>
</section>

<!-- Quick Filter Tags -->
<?php if (!empty($topTags)): ?>
<section class="bg-brand-dark border-b border-white/10 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center gap-3 overflow-x-auto hide-scrollbar">
            <span class="text-sm text-white/60 whitespace-nowrap"><?= h(__('pages.municipality.popular')) ?></span>
            <?php foreach ($topTags as $tag): ?>
            <a href="/?municipality=<?= urlencode($municipality) ?>&tags[]=<?= h($tag) ?>#beaches"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-white/10 hover:bg-brand-yellow/20 border border-white/10 hover:border-brand-yellow/30 text-white/80 hover:text-brand-yellow text-sm transition-colors whitespace-nowrap">
                <?= h(getTagLabel($tag)) ?>
                <span class="text-xs text-white/70"><?= $tagCounts[$tag] ?></span>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Main Content -->
<section class="py-12 md:py-16 bg-brand-dark">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Intro Paragraph (SEO Content) -->
        <div class="max-w-4xl mb-8 text-gray-300 leading-relaxed">
            <p class="mb-4">
                <?= h(__('pages.municipality.intro_p1', ['municipality' => $municipality, 'count' => $beachCount])) ?>
            </p>
            <?php if (!empty($topBeaches)): ?>
            <?php
                $beachNamesList = [];
                foreach (array_slice($topBeaches, 0, 3) as $tb) {
                    $beachNamesList[] = '<strong>' . h($tb['name']) . '</strong>';
                }
                $beachNames = implode(', ', $beachNamesList);
            ?>
            <p>
                <?= __('pages.municipality.intro_popular', ['beaches' => $beachNames]) ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Beach Grid -->
        <div id="beach-grid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            foreach ($beaches as $beach):
                $isFavorite = false;
                if (isAuthenticated()) {
                    $userFavorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]);
                    $isFavorite = in_array($beach['id'], array_column($userFavorites, 'beach_id'));
                }
                include APP_ROOT . '/components/beach-card.php';
            endforeach;
            ?>
        </div>

        <!-- FAQs Section -->
        <div class="mt-16 max-w-4xl">
            <h2 class="text-2xl font-bold text-white mb-6"><?= h(__('pages.municipality.faq_title')) ?></h2>
            <div class="space-y-4">
                <?php foreach ($pageFaqs as $faq): ?>
                <details class="bg-white/5 border border-white/10 rounded-lg p-4 hover:bg-white/10 transition-colors">
                    <summary class="font-semibold text-white cursor-pointer"><?= h($faq['question']) ?></summary>
                    <p class="mt-3 text-gray-300 leading-relaxed"><?= h($faq['answer']) ?></p>
                </details>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<?php
$extraScripts = '<script defer src="/assets/js/map.js" ' . cspNonceAttr() . '></script>';
include APP_ROOT . '/components/footer.php';
?>
