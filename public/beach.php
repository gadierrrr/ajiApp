<?php
/**
 * Individual Beach Detail Page
 * SEO-friendly full page for each beach
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
// Session started conditionally in header.php (only when cookie exists)
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/referrals.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$lang = getCurrentLanguage();

// Get slug from URL (set by Nginx rewrite or query param)
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    http_response_code(404);
    $pageTitle = __('errors.beach_not_found');
    include APP_ROOT . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">🏖️</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">' . h(__('errors.beach_not_found')) . '</h1>
            <p class="text-gray-600 mb-6">' . h(__('errors.beach_not_found_message')) . '</p>
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                ' . h(__('errors.browse_all_beaches')) . '
            </a>
          </div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

// Fetch beach
$beach = queryOne('SELECT * FROM beaches WHERE slug = :slug AND publish_status = "published"', [':slug' => $slug]);

if (!$beach) {
    http_response_code(404);
    $pageTitle = __('errors.beach_not_found');
    include APP_ROOT . '/components/header.php';
    echo '<div class="max-w-2xl mx-auto px-4 py-16 text-center">
            <div class="text-6xl mb-4">🏖️</div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">' . h(__('errors.beach_not_found')) . '</h1>
            <p class="text-gray-600 mb-6">' . h(__('errors.beach_not_found_message')) . '</p>
            <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
                ' . h(__('errors.browse_all_beaches')) . '
            </a>
          </div>';
    include APP_ROOT . '/components/footer.php';
    exit;
}

// Fetch related data
$beach['tags'] = array_column(
    query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
    'tag'
);
$beach['amenities'] = array_column(
    query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
    'amenity'
);
$beach['gallery'] = array_column(
    query('SELECT image_url FROM beach_gallery WHERE beach_id = :id ORDER BY position', [':id' => $beach['id']]),
    'image_url'
);
$beach['features'] = query(
    'SELECT title, title_es, description, description_es FROM beach_features WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);
$beach['tips'] = query(
    'SELECT category, tip, tip_es FROM beach_tips WHERE beach_id = :id ORDER BY position',
    [':id' => $beach['id']]
);

// Get extended content sections
$extendedSections = query("
    SELECT section_type, heading, heading_es, content, content_es, display_order
    FROM beach_content_sections
    WHERE beach_id = :id AND status = 'published'
    ORDER BY display_order ASC
", [':id' => $beach['id']]);

// Fetch user reviews
$reviews = query("
    SELECT
        r.id, r.rating, r.title, r.review_text, r.visit_date, r.visit_type,
        r.helpful_count, r.created_at, r.would_recommend, r.user_id,
        u.name as user_name, u.avatar_url
    FROM beach_reviews r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.beach_id = :id AND r.status = 'published'
    ORDER BY r.created_at DESC
", [':id' => $beach['id']]);

$userReviewCount = count($reviews);
$avgUserRating = $beach['avg_user_rating'] ?? null;

// Page metadata
$pageTitle = $beach['name'] . ' - ' . $beach['municipality'] . ' | ' . ($lang === 'es' ? 'Playas de Puerto Rico' : 'Puerto Rico Beach Finder');
$_descSource = ($lang === 'es' && !empty($beach['description_es']))
    ? $beach['description_es']
    : ($beach['description'] ?? '');
$_descFallback = $lang === 'es'
    ? 'Descubre ' . $beach['name'] . ' en ' . $beach['municipality'] . ', Puerto Rico.'
    : 'Discover ' . $beach['name'] . ' in ' . $beach['municipality'] . ', Puerto Rico. View beach conditions, amenities, photos, and directions.';
$pageDescription = $_descSource
    ? (mb_strlen($_descSource) > 155
        ? mb_substr($_descSource, 0, strrpos(mb_substr($_descSource, 0, 155), ' ') ?: 155) . '...'
        : $_descSource)
    : $_descFallback;

// Generate structured data using SEO component (consolidated with reviews)
$extraHead = beachSchema($beach, $reviews);

// Add TouristAttraction schema for travel queries
$extraHead .= touristAttractionSchema($beach);

// Add breadcrumbs
$municipalitySlug = strtolower(str_replace(' ', '-', $beach['municipality']));
$extraHead .= breadcrumbSchema([
    ['name' => __('nav.home'), 'url' => routeUrl('home', $lang)],
    ['name' => $beach['municipality'], 'url' => routeUrl('municipality', $lang, ['municipality' => $municipalitySlug])],
    ['name' => $beach['name'], 'url' => routeUrl('beach_detail', $lang, ['slug' => $beach['slug']])]
]);

// Generate dynamic FAQ schema
$faqs = generateBeachFAQs($beach);
$extraHead .= faqSchema($faqs);

// Add speakable schema for voice assistants
$extraHead .= speakableSchema();

// Set Open Graph image
$ogImage = $beach['cover_image'] ? absoluteUrl($beach['cover_image']) : null;

// Get WebP version of cover image for optimized delivery
$webpImage = getWebPImage($beach['cover_image'] ?? '');

$referralLocale = $lang === 'es' ? 'es' : 'en';
$referralBaseCtx = [
    'page_type' => 'beach',
    'page_slug' => (string) $beach['slug'],
];
$beachReferralHero = referralRenderBeachAnchor(
    (string) $beach['id'],
    'hero',
    $referralLocale,
    $referralBaseCtx
);
$beachReferralMid = referralRenderBeachAnchor(
    (string) $beach['id'],
    'mid_content',
    $referralLocale,
    $referralBaseCtx
);
$beachReferralBottom = referralRenderBeachAnchor(
    (string) $beach['id'],
    'bottom',
    $referralLocale,
    $referralBaseCtx
);

include APP_ROOT . '/components/header.php';
?>

<!-- Hero Image - IslaFinder Style (80vh) -->
<div class="relative h-[70vh] md:h-[80vh] overflow-hidden">
    <?php if ($beach['cover_image']): ?>
    <picture>
        <?php if ($webpImage['webp']): ?>
        <source srcset="<?= h($webpImage['webp']) ?>" type="image/webp">
        <?php endif; ?>
        <img src="<?= h($beach['cover_image']) ?>"
             alt="<?= h(getBeachImageAlt($beach, 'scenic beach view')) ?>"
             class="w-full h-full object-cover">
    </picture>
    <?php else: ?>
    <div class="w-full h-full bg-gradient-to-br from-brand-dark to-brand-darker flex items-center justify-center">
        <span class="text-8xl">🏖️</span>
    </div>
    <?php endif; ?>
    <div class="absolute inset-0 hero-gradient-beach"></div>

    <!-- Title overlay - positioned at bottom -->
    <div class="absolute bottom-0 left-0 right-0 p-6 md:p-10 lg:p-16">
        <div class="max-w-7xl mx-auto">
            <!-- Breadcrumbs -->
            <nav class="text-white/70 text-sm mb-4" aria-label="Breadcrumb">
                <a href="<?= h(routeUrl('home', $lang)) ?>" class="hover:text-brand-yellow transition-colors"><?= h(__('nav.home')) ?></a>
                <span class="mx-2">/</span>
                <a href="<?= h(routeUrl('home', $lang)) ?>" class="hover:text-brand-yellow transition-colors"><?= h(__('nav.beaches')) ?></a>
                <span class="mx-2">/</span>
                <a href="<?= h(routeUrl('municipality', $lang, ['municipality' => strtolower(str_replace(' ', '-', $beach['municipality']))])) ?>" class="hover:text-brand-yellow transition-colors"><?= h($beach['municipality']) ?></a>
                <span class="mx-2">/</span>
                <span class="text-white/70"><?= h($beach['name']) ?></span>
            </nav>

            <!-- Beach Name - Large Uppercase with Location -->
            <h1 class="text-4xl sm:text-5xl md:text-7xl lg:text-8xl xl:text-9xl font-bold text-white uppercase tracking-tight leading-none">
                <?= h($beach['name']) ?>
                <span class="block text-2xl sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl mt-2 md:mt-4 text-brand-yellow font-serif normal-case italic">
                    <?= h($beach['municipality']) ?>, Puerto Rico
                </span>
            </h1>
        </div>
    </div>
</div>

<!-- Main Content -->
<div class="bg-brand-dark">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    <!-- Quick Info Bar -->
    <div class="flex flex-wrap items-center gap-3 p-3 bg-white/5 backdrop-blur-sm rounded-xl border border-white/10 mb-6">
        <?php if ($beach['google_rating']): ?>
        <div class="flex items-center gap-1.5 bg-brand-yellow/10 border border-brand-yellow/30 px-3 py-1.5 rounded-lg" aria-label="Google rating: <?= number_format($beach['google_rating'], 1) ?> out of 5">
            <svg class="w-4 h-4" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
            </svg>
            <span class="font-bold text-brand-yellow"><?= number_format($beach['google_rating'], 1) ?></span>
            <?php if ($beach['google_review_count']): ?>
            <span class="text-white/70 text-sm">(<?= number_format($beach['google_review_count']) ?>)</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($avgUserRating): ?>
        <div class="flex items-center gap-1.5 bg-cyan-500/10 border border-cyan-500/30 px-3 py-1.5 rounded-lg" aria-label="Community rating: <?= number_format($avgUserRating, 1) ?> out of 5">
            <i data-lucide="star" class="w-4 h-4 text-cyan-400 fill-cyan-400" aria-hidden="true"></i>
            <span class="font-bold text-cyan-400"><?= number_format($avgUserRating, 1) ?></span>
            <span class="text-white/70 text-sm">(<?= $userReviewCount ?>)</span>
        </div>
        <?php endif; ?>

        <?php if (!empty($beach['tags'])): ?>
        <div class="flex flex-wrap gap-1.5">
            <?php foreach (array_slice($beach['tags'], 0, 3) as $tag): ?>
            <a href="/?tags[]=<?= h($tag) ?>" class="text-xs bg-white/10 hover:bg-white/20 text-white/80 px-2 py-1 rounded-full transition-colors">
                <?= h(getTagLabel($tag)) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="ml-auto flex gap-2">
            <a href="<?= h(getDirectionsUrl($beach)) ?>" target="_blank"
               data-bf-track="directions"
               data-bf-beach-id="<?= h($beach['id']) ?>"
               data-bf-beach-slug="<?= h($beach['slug']) ?>"
               data-bf-municipality="<?= h($beach['municipality']) ?>"
               data-bf-source="beach_page"
               class="inline-flex items-center gap-1.5 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg font-semibold text-sm transition-colors">
                <i data-lucide="navigation" class="w-4 h-4" aria-hidden="true"></i>
                <span><?= h(__('beach.directions')) ?></span>
            </a>
            <button data-action="shareBeach" data-action-args='["<?= h($beach['slug']) ?>","<?= h(addslashes($beach['name'])) ?>"]' aria-label="Share this beach"
                    class="inline-flex items-center gap-1.5 bg-white/10 hover:bg-white/20 text-white px-3 py-2 rounded-lg text-sm transition-colors">
                <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
                <span class="hidden sm:inline"><?= h(__('beach.share')) ?></span>
            </button>
        </div>
    </div>

    <?php if ($beachReferralHero !== ''): ?>
    <div class="mb-6">
        <?= $beachReferralHero ?>
    </div>
    <?php endif; ?>

    <?php
    // Pre-fetch data needed for sidebar (weather loaded client-side for fast TTFB)
    require_once APP_ROOT . '/inc/crowd.php';
    $weather = null;
    $recommendation = null;
    $crowdLevel = getBeachCrowdLevel($beach['id'], 4);
    $sunTimes = getSunTimes($beach['lat'], $beach['lng']);
    ?>

    <!-- Two-Column Layout -->
    <div class="lg:flex lg:gap-8">

        <!-- Left Column: Main Content -->
        <div class="lg:w-[63%] space-y-6">

            <?php if ($beachReferralMid !== ''): ?>
            <?= $beachReferralMid ?>
            <?php endif; ?>

            <!-- Quick Facts - Condensed 2x2 Grid -->
            <section>
                <h2 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <i data-lucide="clipboard-list" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    <span><?= h(__('beach.quick_facts')) ?></span>
                </h2>
                <div class="grid grid-cols-2 gap-3">
                    <?php if (!empty($beach['tags'])): ?>
                    <?php $icon = 'activity'; $label = __('beach.best_for'); $value = getTagLabel($beach['tags'][0]); $subtext = count($beach['tags']) > 1 ? __('beach.tags_more', ['count' => count($beach['tags']) - 1]) : ''; ?>
                    <?php include APP_ROOT . '/components/quick-fact-card.php'; ?>
                    <?php endif; ?>

                    <?php if ($beach['best_time']): ?>
                    <?php $icon = 'clock'; $label = __('beach.best_time'); $value = ($lang === 'es' && !empty($beach['best_time_es'])) ? $beach['best_time_es'] : $beach['best_time']; $subtext = ''; ?>
                    <?php include APP_ROOT . '/components/quick-fact-card.php'; ?>
                    <?php endif; ?>

                    <?php if ($beach['parking_details']): ?>
                    <?php
                    $icon = 'car'; $label = __('beach.parking');
                    $_parkingVal = ($lang === 'es' && !empty($beach['parking_details_es'])) ? $beach['parking_details_es'] : $beach['parking_details'];
                    $value = mb_strlen($_parkingVal) > 20 ? mb_substr($_parkingVal, 0, 20) . '...' : $_parkingVal; $subtext = '';
                    ?>
                    <?php include APP_ROOT . '/components/quick-fact-card.php'; ?>
                    <?php endif; ?>

                    <?php if ($beach['access_label']): ?>
                    <?php $icon = 'accessibility'; $label = __('beach.access'); $value = $beach['access_label']; $subtext = ''; ?>
                    <?php include APP_ROOT . '/components/quick-fact-card.php'; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- About + Highlights Merged -->
            <section>
                <h2 class="text-xl font-bold text-white mb-3"><?= h(__('beach.about')) ?> <?= h($beach['name']) ?></h2>
                <?php
                $_aboutDesc = ($lang === 'es' && !empty($beach['description_es']))
                    ? $beach['description_es']
                    : ($beach['description'] ?? '');
                if ($_aboutDesc): ?>
                <p class="text-gray-300 leading-relaxed mb-4"><?= nl2br(h($_aboutDesc)) ?></p>
                <?php endif; ?>

                <?php if (!empty($beach['features'])): ?>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php foreach ($beach['features'] as $feature): ?>
                    <span class="inline-flex items-center gap-1.5 bg-white/5 border border-white/10 text-white/80 px-3 py-1.5 rounded-lg text-sm">
                        <i data-lucide="sparkles" class="w-3.5 h-3.5 text-brand-yellow" aria-hidden="true"></i>
                        <?= h(($lang === 'es' && !empty($feature['title_es'])) ? $feature['title_es'] : $feature['title']) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </section>

            <!-- Visitor Tips -->
            <?php if (!empty($beach['tips'])): ?>
            <section>
                <h2 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <i data-lucide="lightbulb" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    <span><?= h(__('beach.visitor_tips')) ?></span>
                </h2>
                <ul class="space-y-2">
                    <?php foreach ($beach['tips'] as $tip): ?>
                    <li class="flex items-start gap-2 text-sm">
                        <span class="yellow-bullet mt-1.5 flex-shrink-0"></span>
                        <span class="text-gray-300"><?= h(($lang === 'es' && !empty($tip['tip_es'])) ? $tip['tip_es'] : $tip['tip']) ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <!-- Extended Content Sections -->
            <?php if (!empty($extendedSections)): ?>
            <div class="extended-content space-y-6 mt-8">
                <?php foreach ($extendedSections as $section): ?>
                    <?php
                    $_sectionHeading = ($lang === 'es' && !empty($section['heading_es']))
                        ? $section['heading_es'] : $section['heading'];
                    $_sectionContent = ($lang === 'es' && !empty($section['content_es']))
                        ? $section['content_es'] : $section['content'];
                    ?>
                    <section class="beach-detail-card p-6 rounded-xl" id="section-<?= h($section['section_type']) ?>">
                        <h2 class="text-xl font-bold text-white mb-4 flex items-center gap-2">
                            <i data-lucide="<?= h(CONTENT_SECTIONS[$section['section_type']]['icon'] ?? 'info') ?>" class="w-5 h-5 text-brand-yellow"></i>
                            <?= h($_sectionHeading) ?>
                        </h2>
                        <div class="prose prose-invert prose-brand max-w-none">
                            <?= sanitizeContentHtml($_sectionContent) ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Gallery (if exists) -->
            <?php if (!empty($beach['gallery'])): ?>
            <section>
                <h2 class="text-lg font-bold text-white mb-3 flex items-center gap-2">
                    <i data-lucide="images" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    <?= h(__('beach.photos')) ?>
                </h2>
                <div class="gallery-grid">
                    <?php foreach ($beach['gallery'] as $idx => $image): ?>
                    <img src="<?= h($image) ?>" alt="<?= h($beach['name']) ?> - <?= h(__('beach.photo_label')) ?> <?= $idx + 1 ?>"
                         class="rounded-lg cursor-pointer hover:opacity-90 transition-opacity gallery-image"
                         data-gallery-index="<?= $idx ?>" data-action="openLightbox" data-action-args='[<?= $idx ?>]' loading="lazy">
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>

            <!-- Visitor Photos - Compact -->
            <section id="user-photos">
                <?php
                $userPhotos = query("SELECT p.id, p.filename, p.caption, p.created_at, u.name as user_name FROM beach_photos p LEFT JOIN users u ON p.user_id = u.id WHERE p.beach_id = :beach_id AND p.status = 'published' ORDER BY p.created_at DESC LIMIT 12", [':beach_id' => $beach['id']]);
                $totalUserPhotos = queryOne("SELECT COUNT(*) as count FROM beach_photos WHERE beach_id = :beach_id AND status = 'published'", [':beach_id' => $beach['id']]);
                ?>
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <i data-lucide="camera" class="w-5 h-5 text-purple-400" aria-hidden="true"></i>
                        <span><?= h(__('beach.visitor_photos')) ?></span>
                        <?php if ($totalUserPhotos['count'] > 0): ?>
                        <span class="text-sm font-normal text-gray-400">(<?= $totalUserPhotos['count'] ?>)</span>
                        <?php endif; ?>
                    </h2>
                    <?php if (isAuthenticated()): ?>
                    <button data-action="openPhotoUploadModal" data-action-args='["<?= h($beach['id']) ?>","<?= h(addslashes($beach['name'])) ?>"]'
                            class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1.5 rounded-lg font-medium transition-colors flex items-center gap-1.5 text-sm">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span><?= __('beach.add_photo') ?></span>
                    </button>
                    <?php else: ?>
                    <a href="<?= h(routeUrl('login', $lang)) ?>?redirect=<?= urlencode(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']]) . '#user-photos') ?>"
                       class="text-sm text-purple-400 hover:text-purple-300 font-medium"><?= h(__('beach.sign_in_to_add')) ?></a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($userPhotos)): ?>
                <div class="grid grid-cols-4 sm:grid-cols-6 gap-2">
                    <?php foreach ($userPhotos as $photo): ?>
                    <button data-action="openPhotoModal" data-action-args='["/uploads/photos/<?= h($photo['filename']) ?>","<?= h(addslashes($photo['caption'] ?? '')) ?>"]'
                            class="aspect-square rounded-lg overflow-hidden hover:opacity-90 transition-opacity">
                        <img src="/uploads/photos/thumbs/<?= h($photo['filename']) ?>" alt="<?= h($photo['caption'] ?? 'Visitor photo') ?>" class="w-full h-full object-cover" loading="lazy">
                    </button>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-400"><?= h(__('beach.no_photos_yet')) ?></p>
                <?php endif; ?>
            </section>

            <!-- Reviews - Compact -->
            <section id="reviews">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-bold text-white"><?= h(__('beach.reviews_title')) ?></h2>
                        <?php if ($avgUserRating): ?>
                        <div class="flex items-center gap-1 text-sm">
                            <span class="text-brand-yellow">★</span>
                            <span class="text-white"><?= number_format($avgUserRating, 1) ?></span>
                            <span class="text-gray-400">(<?= $userReviewCount ?>)</span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if (isAuthenticated()): ?>
                    <button data-action="openReviewForm" data-action-args='["<?= h($beach['id']) ?>","<?= h(addslashes($beach['name'])) ?>"]'
                            class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-3 py-1.5 rounded-lg font-medium text-sm transition-colors">
                        <?= h(__('beach.write_review')) ?>
                    </button>
                    <?php else: ?>
                    <a href="<?= h(routeUrl('login', $lang)) ?>?redirect=<?= urlencode(routeUrl('beach_detail', $lang, ['slug' => $beach['slug']]) . '#reviews') ?>"
                       class="text-sm text-brand-yellow hover:text-yellow-300 font-medium"><?= h(__('beach.sign_in_to_review')) ?></a>
                    <?php endif; ?>
                </div>
                <?php if (!empty($reviews)): ?>
                <div class="space-y-3">
                    <?php foreach ($reviews as $review): ?>
                    <?php include APP_ROOT . '/components/review-card.php'; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-sm text-gray-400"><?= h(__('beach.no_reviews_yet')) ?></p>
                <?php endif; ?>
            </section>

        </div><!-- End Left Column -->

        <!-- Right Column: Sidebar -->
        <div class="lg:w-[37%] mt-8 lg:mt-0">
            <div class="lg:sticky lg:top-24 space-y-4">

                <!-- Weather Widget (loaded client-side) -->
                <div class="beach-detail-card p-4" id="weather-widget-container"
                     data-lat="<?= h($beach['lat']) ?>" data-lng="<?= h($beach['lng']) ?>">
                    <div class="animate-pulse">
                        <div class="flex items-center justify-between mb-3">
                            <div class="h-6 bg-white/10 rounded w-32"></div>
                            <div class="h-8 bg-white/10 rounded w-16"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <div class="h-14 bg-white/10 rounded"></div>
                            <div class="h-14 bg-white/10 rounded"></div>
                            <div class="h-14 bg-white/10 rounded"></div>
                        </div>
                    </div>
                </div>

                <!-- Current Conditions -->
                <?php if ($beach['sargassum'] || $beach['surf'] || $beach['wind']): ?>
                <div class="beach-detail-card p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-white text-sm"><?= h(__('beach.conditions')) ?></h3>
                        <?php if ($beach['updated_at']): ?>
                        <span class="text-xs text-gray-400"><?= h(timeAgo($beach['updated_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="space-y-2">
                        <?php if ($beach['sargassum']): ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400 inline-flex items-center gap-1.5">
                                <i data-lucide="leaf" class="w-3.5 h-3.5" aria-hidden="true"></i><?= __('beach.condition_sargassum') ?>
                            </span>
                            <span class="<?= getConditionClass($beach['sargassum'], 'sargassum') ?> px-2 py-0.5 rounded text-xs">
                                <?= h(getConditionLabel('sargassum', $beach['sargassum'])) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if ($beach['surf']): ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400 inline-flex items-center gap-1.5">
                                <i data-lucide="waves" class="w-3.5 h-3.5" aria-hidden="true"></i><?= __('beach.condition_surf') ?>
                            </span>
                            <span class="<?= getConditionClass($beach['surf'], 'surf') ?> px-2 py-0.5 rounded text-xs">
                                <?= h(getConditionLabel('surf', $beach['surf'])) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        <?php if ($beach['wind']): ?>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400 inline-flex items-center gap-1.5">
                                <i data-lucide="wind" class="w-3.5 h-3.5" aria-hidden="true"></i><?= __('beach.condition_wind') ?>
                            </span>
                            <span class="<?= getConditionClass($beach['wind'], 'wind') ?> px-2 py-0.5 rounded text-xs">
                                <?= h(getConditionLabel('wind', $beach['wind'])) ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Live Updates / Crowd -->
                <div class="beach-detail-card p-4">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="font-bold text-white text-sm flex items-center gap-1.5">
                            <i data-lucide="radio" class="w-3.5 h-3.5 text-green-400" aria-hidden="true"></i>
                            <?= h(__('beach.live_updates')) ?>
                        </h3>
                        <button data-action="openCheckinModal" data-action-args='["<?= h($beach['id']) ?>","<?= h(addslashes($beach['name'])) ?>"]'
                                class="text-xs <?= isAuthenticated() ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-white/10 hover:bg-white/20 text-white' ?> px-2 py-1 rounded font-medium transition-colors border border-white/10">
                            <?= h(__('beach.check_in')) ?>
                        </button>
                    </div>
                    <?php if ($crowdLevel): ?>
                    <?php
                    $crowdColors = [
                        'green' => 'bg-green-500/10 text-green-400 border-green-500/20',
                        'yellow' => 'bg-yellow-500/10 text-yellow-400 border-yellow-500/20',
                        'orange' => 'bg-orange-500/10 text-orange-400 border-orange-500/20',
                        'red' => 'bg-red-500/10 text-red-400 border-red-500/20',
                        'gray' => 'bg-white/5 text-gray-400 border-white/10'
                    ];
                    $crowdColorClass = $crowdColors[$crowdLevel['color']] ?? $crowdColors['gray'];
                    ?>
                    <div class="p-2 rounded-lg border <?= $crowdColorClass ?> text-sm">
                        <div class="flex items-center gap-2">
                            <span>👥</span>
                            <span class="font-medium"><?= h($crowdLevel['label']) ?></span>
                            <span class="text-xs opacity-75 ml-auto"><?= h($crowdLevel['time_label']) ?></span>
                        </div>
                    </div>
                    <?php else: ?>
                    <p class="text-xs text-gray-400 text-center py-2"><?= h(__('beach.no_crowd_data')) ?></p>
                    <?php endif; ?>
                </div>

                <!-- Map + Directions -->
                <div class="beach-detail-card overflow-hidden">
                    <div id="beach-map" class="h-40"></div>
                    <div class="p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-gray-400 inline-flex items-center gap-1">
                                <i data-lucide="map-pin" class="w-3 h-3 text-brand-yellow" aria-hidden="true"></i>
                                <?= h($beach['municipality']) ?>
                            </span>
                            <?php if ($beach['lat'] && $beach['lng']): ?>
                            <span class="text-xs text-gray-400"><?= number_format($beach['lat'], 4) ?>°N, <?= number_format(abs($beach['lng']), 4) ?>°W</span>
                            <?php endif; ?>
                        </div>
                        <a href="<?= h(getDirectionsUrl($beach)) ?>" target="_blank"
                           data-bf-track="directions"
                           data-bf-beach-id="<?= h($beach['id']) ?>"
                           data-bf-beach-slug="<?= h($beach['slug']) ?>"
                           data-bf-municipality="<?= h($beach['municipality']) ?>"
                           data-bf-source="beach_page_map"
                           class="mt-2 block w-full text-center bg-brand-yellow hover:bg-yellow-300 text-brand-darker py-2 rounded-lg font-medium text-sm transition-colors">
                            <?= h(__('beach.get_directions')) ?>
                        </a>
                    </div>
                </div>

                <!-- Amenities -->
                <?php if (!empty($beach['amenities'])): ?>
                <div class="beach-detail-card p-4">
                    <h3 class="font-bold text-white text-sm mb-3"><?= h(__('beach.amenities_title')) ?></h3>
                    <div class="flex flex-wrap gap-1.5">
                        <?php foreach ($beach['amenities'] as $amenity): ?>
                        <span class="inline-flex items-center gap-1 text-xs bg-white/5 text-gray-300 px-2 py-1 rounded">
                            <i data-lucide="check" class="w-3 h-3 text-green-400" aria-hidden="true"></i>
                            <?= h(getAmenityLabel($amenity)) ?>
                        </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Practical Info -->
                <div class="beach-detail-card p-4 space-y-3">
                    <h3 class="font-bold text-white text-sm"><?= h(__('beach.practical_info')) ?></h3>
                    <?php if ($beach['safety_info']): ?>
                    <div class="text-sm">
                        <span class="text-amber-400 inline-flex items-center gap-1"><i data-lucide="alert-triangle" class="w-3.5 h-3.5"></i> <?= h(__('beach.safety')) ?></span>
                        <p class="text-gray-400 text-xs mt-0.5"><?= h(($lang === 'es' && !empty($beach['safety_info_es'])) ? $beach['safety_info_es'] : $beach['safety_info']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php if ($sunTimes): ?>
                    <div class="flex gap-4 text-xs">
                        <span class="text-gray-400 inline-flex items-center gap-1"><i data-lucide="sunrise" class="w-3.5 h-3.5 text-orange-400"></i> <?= h($sunTimes['sunrise']) ?></span>
                        <span class="text-gray-400 inline-flex items-center gap-1"><i data-lucide="sunset" class="w-3.5 h-3.5 text-rose-400"></i> <?= h($sunTimes['sunset']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

            </div>
        </div><!-- End Right Column -->

    </div><!-- End Two-Column Layout -->

    <?php if ($beachReferralBottom !== ''): ?>
    <section class="mt-8">
        <?= $beachReferralBottom ?>
    </section>
    <?php endif; ?>

    <!-- Related Planning Guides -->
    <?php
    $relatedGuides = getRelatedGuides($beach['tags'], 3);
    if (!empty($relatedGuides)):
    ?>
    <section class="mt-8 pt-6 border-t border-white/10">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="book-open" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
            <?= h(__('beach.planning_visit')) ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($relatedGuides as $guide): ?>
            <a href="<?= h($guide['url']) ?>" class="block bg-white/5 hover:bg-white/10 rounded-xl p-5 border border-white/10 hover:border-brand-yellow/50 transition-all group">
                <div class="flex items-start gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-lg bg-brand-yellow/20 flex items-center justify-center group-hover:bg-brand-yellow/30 transition-colors">
                        <i data-lucide="<?= h($guide['icon']) ?>" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-semibold text-white text-sm mb-1 group-hover:text-brand-yellow transition-colors">
                            <?= h($guide['title']) ?>
                        </h3>
                        <p class="text-xs text-gray-400"><?= h(__('beach.essential_tips')) ?></p>
                    </div>
                    <i data-lucide="arrow-right" class="w-4 h-4 text-gray-500 group-hover:text-brand-yellow transition-colors flex-shrink-0" aria-hidden="true"></i>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Similar Beaches - Full Width Below -->
    <?php
    $similarBeaches = getSimilarBeaches($beach['id'], $beach['tags'], 4);
    if (!empty($similarBeaches)):
    ?>
    <section class="mt-8 pt-6 border-t border-white/10">
        <h2 class="text-lg font-bold text-white mb-4 flex items-center gap-2">
            <i data-lucide="sparkles" class="w-5 h-5 text-brand-yellow" aria-hidden="true"></i>
            <?= h(__('beach.similar_beaches')) ?>
        </h2>
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($similarBeaches as $similar): ?>
            <a href="<?= h(routeUrl('beach_detail', $lang, ['slug' => $similar['slug']])) ?>" class="group beach-detail-card overflow-hidden hover:border-brand-yellow/30 transition-all">
                <div class="aspect-video relative overflow-hidden">
                    <img src="<?= h(getThumbnailUrl($similar['cover_image'])) ?>" alt="<?= h($similar['name']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy">
                </div>
                <div class="p-3">
                    <h3 class="font-semibold text-white text-sm group-hover:text-brand-yellow transition-colors line-clamp-1"><?= h($similar['name']) ?></h3>
                    <p class="text-xs text-gray-400"><?= h($similar['municipality']) ?></p>
                    <?php if ($similar['google_rating']): ?>
                    <div class="flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" viewBox="0 0 24 24"><path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                        <span class="text-xs font-medium text-brand-yellow"><?= number_format($similar['google_rating'], 1) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</div>
</div>

<!-- Sticky Quick Actions Bar (Mobile Only): Directions / Save / Share -->
<?php
$directionsUrl = getDirectionsUrl($beach);
$isFavorite = false;
if (isAuthenticated()) {
    $existingFav = queryOne(
        'SELECT id FROM user_favorites WHERE user_id = :user_id AND beach_id = :beach_id',
        [':user_id' => $_SESSION['user_id'], ':beach_id' => $beach['id']]
    );
    $isFavorite = (bool)$existingFav;
}
?>
<div class="beach-sticky-bar"
     aria-label="Quick actions"
     data-bf-beach-id="<?= h($beach['id']) ?>"
     data-bf-beach-slug="<?= h($beach['slug']) ?>"
     data-bf-municipality="<?= h($beach['municipality']) ?>"
     data-bf-source="beach_page_sticky">
    <a href="<?= h($directionsUrl) ?>"
       target="_blank"
       rel="noopener"
       data-bf-track="directions"
       class="sticky-directions"
       aria-label="Get directions">
        <i data-lucide="navigation" class="w-4 h-4" aria-hidden="true"></i>
        <span><?= h(__('beach.directions')) ?></span>
    </a>

    <button type="button"
            class="sticky-directions"
            style="background: rgba(255,255,255,0.10); color: white;"
            data-action="toggleStickyFavorite"
            aria-label="<?= $isFavorite ? 'Remove from favorites' : 'Add to favorites' ?>"
            aria-pressed="<?= $isFavorite ? 'true' : 'false' ?>"
            id="sticky-favorite-btn">
        <span id="sticky-favorite-icon" aria-hidden="true"><?= $isFavorite ? '❤️' : '🤍' ?></span>
        <span><?= h(__('beach.save')) ?></span>
    </button>

    <button type="button"
            class="sticky-directions"
            style="background: rgba(255,255,255,0.10); color: white;"
            data-action="shareBeach" data-action-args='["<?= h($beach['slug']) ?>","<?= h(addslashes($beach['name'])) ?>"]'
            aria-label="<?= h(__('beach.share')) ?>">
        <i data-lucide="share-2" class="w-4 h-4" aria-hidden="true"></i>
        <span><?= h(__('beach.share')) ?></span>
    </button>
</div>

<!-- Share Modal -->
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" data-action="closeShareModal">
    <div class="share-modal-content" data-action-stop data-action="noop" data-on="click">
        <div class="flex justify-between items-center mb-4">
            <h3 id="share-modal-title" class="text-lg font-semibold"><?= h(__('beach.share_beach')) ?></h3>
            <button data-action="closeShareModal" class="text-gray-400 hover:text-gray-600" aria-label="Close share dialog">
                <i data-lucide="x" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>
        <div id="share-modal-body"></div>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Initialize small map for sidebar
document.addEventListener('DOMContentLoaded', () => {
    const mapContainer = document.getElementById('beach-map');
    if (mapContainer && typeof maplibregl !== 'undefined') {
        const map = new maplibregl.Map({
            container: 'beach-map',
            style: 'https://basemaps.cartocdn.com/gl/positron-gl-style/style.json',
            center: [<?= $beach['lng'] ?>, <?= $beach['lat'] ?>],
            zoom: 13,
            interactive: false
        });

        new maplibregl.Marker({ color: '#2563eb' })
            .setLngLat([<?= $beach['lng'] ?>, <?= $beach['lat'] ?>])
            .addTo(map);
    }
});
</script>

<script <?= cspNonceAttr() ?>>
async function toggleStickyFavorite() {
    const btn = document.getElementById('sticky-favorite-btn');
    const icon = document.getElementById('sticky-favorite-icon');
    if (!btn || !icon) return;

    <?php if (!isAuthenticated()): ?>
    if (typeof showSignupPrompt === 'function') {
        showSignupPrompt('favorites', '/beach/<?= h($beach['slug']) ?>');
    } else {
        window.location.href = '/login?redirect=' + encodeURIComponent('/beach/<?= h($beach['slug']) ?>');
    }
    return;
    <?php endif; ?>

    if (btn.dataset.loading === '1') return;
    btn.dataset.loading = '1';

    try {
        const body = new URLSearchParams();
        body.set('beach_id', <?= json_encode($beach['id']) ?>);
        body.set('csrf_token', <?= json_encode(csrfToken()) ?>);
        const res = await fetch('/api/toggle-favorite.php?format=json', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        });
        const payload = await res.json();
        if (!res.ok || !payload.success) throw new Error(payload.error || 'Failed');

        const isFav = payload.is_favorite === true;
        btn.setAttribute('aria-pressed', isFav ? 'true' : 'false');
        btn.setAttribute('aria-label', isFav ? 'Remove from favorites' : 'Add to favorites');
        icon.textContent = isFav ? '❤️' : '🤍';
        if (typeof showToast === 'function') {
            showToast(isFav ? '<?= __('beach.saved_toast') ?>' : '<?= __('beach.removed_toast') ?>', isFav ? 'success' : 'info', 2500);
        }
        if (typeof window.bfTrack === 'function') {
            window.bfTrack(isFav ? 'favorite_add' : 'favorite_remove', { source: 'beach_page_sticky', beach_slug: <?= json_encode($beach['slug']) ?> });
        }
    } catch (e) {
        if (typeof showToast === 'function') showToast('<?= __('beach.favorite_error') ?>', 'error', 3500);
    } finally {
        delete btn.dataset.loading;
    }
}
</script>

<?php if (!empty($beach['gallery'])): ?>
<!-- Gallery Lightbox -->
<div id="gallery-lightbox" class="lightbox-overlay" data-action="closeLightbox" data-action-args='["__event__"]' role="dialog" aria-modal="true" aria-label="Image gallery">
    <div class="lightbox-container" data-action-stop data-action="noop" data-on="click">
        <!-- Close button -->
        <button data-action="closeLightbox" class="lightbox-close" aria-label="Close gallery">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>

        <!-- Previous button -->
        <button data-action="navigateLightbox" data-action-args='[-1]' class="lightbox-nav lightbox-prev" aria-label="Previous image">
            <i data-lucide="chevron-left" class="w-8 h-8"></i>
        </button>

        <!-- Image container -->
        <div class="lightbox-content">
            <img id="lightbox-image" src="" alt="" class="lightbox-image">
            <div id="lightbox-counter" class="lightbox-counter"></div>
        </div>

        <!-- Next button -->
        <button data-action="navigateLightbox" data-action-args='[1]' class="lightbox-nav lightbox-next" aria-label="Next image">
            <i data-lucide="chevron-right" class="w-8 h-8"></i>
        </button>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Gallery Lightbox
const galleryImages = <?= json_encode($beach['gallery']) ?>;
let currentImageIndex = 0;

function openLightbox(index) {
    currentImageIndex = index;
    updateLightboxImage();
    document.getElementById('gallery-lightbox').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Re-initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function closeLightbox(e) {
    if (e && e.target !== document.getElementById('gallery-lightbox')) return;
    document.getElementById('gallery-lightbox').classList.remove('open');
    document.body.style.overflow = '';
}

function navigateLightbox(direction) {
    currentImageIndex += direction;
    if (currentImageIndex >= galleryImages.length) currentImageIndex = 0;
    if (currentImageIndex < 0) currentImageIndex = galleryImages.length - 1;
    updateLightboxImage();
}

function updateLightboxImage() {
    const img = document.getElementById('lightbox-image');
    const counter = document.getElementById('lightbox-counter');
    img.src = galleryImages[currentImageIndex];
    img.alt = '<?= h($beach['name']) ?> - <?= h(__('beach.photo_label')) ?> ' + (currentImageIndex + 1);
    counter.textContent = (currentImageIndex + 1) + ' / ' + galleryImages.length;
}

// Keyboard navigation
document.addEventListener('keydown', (e) => {
    const lightbox = document.getElementById('gallery-lightbox');
    if (!lightbox || !lightbox.classList.contains('open')) return;

    if (e.key === 'Escape') closeLightbox();
    if (e.key === 'ArrowLeft') navigateLightbox(-1);
    if (e.key === 'ArrowRight') navigateLightbox(1);
});

// Touch swipe support
let touchStartX = 0;
let touchEndX = 0;

document.getElementById('gallery-lightbox')?.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
}, { passive: true });

document.getElementById('gallery-lightbox')?.addEventListener('touchend', (e) => {
    touchEndX = e.changedTouches[0].screenX;
    handleSwipe();
}, { passive: true });

function handleSwipe() {
    const swipeThreshold = 50;
    const diff = touchStartX - touchEndX;
    if (Math.abs(diff) > swipeThreshold) {
        if (diff > 0) navigateLightbox(1); // Swipe left = next
        else navigateLightbox(-1); // Swipe right = prev
    }
}
</script>
<?php endif; ?>

<!-- Report Outdated Info Modal -->
<div id="report-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="report-modal-title" data-action="closeReportModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" data-action-stop data-action="noop" data-on="click">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="report-modal-title" class="text-lg font-semibold text-gray-900"><?= __('beach.report_modal_title') ?></h2>
            <button data-action="closeReportModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form id="report-form" class="p-6 space-y-4" data-action="submitReport" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="report-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                <?= __('beach.report_help_text', ['name' => '<strong id="report-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.report_what_updating') ?></label>
                <div class="space-y-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="conditions" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_conditions') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="amenities" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_amenities') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="access" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_access') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="safety" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_safety') ?></span>
                    </label>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="issues[]" value="other" class="rounded border-gray-300">
                        <span class="text-sm text-gray-700"><?= __('beach.report_opt_other') ?></span>
                    </label>
                </div>
            </div>

            <div>
                <label for="report-details" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.report_details') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <textarea name="details" id="report-details" rows="3" maxlength="500"
                          placeholder="<?= h(__('beach.report_placeholder')) ?>"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none text-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="report-submit-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition-colors text-sm">
                    <?= __('beach.report_submit') ?>
                </button>
                <button type="button" data-action="closeReportModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors text-sm">
                    <?= __('beach.report_cancel') ?>
                </button>
            </div>

            <div id="report-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
function openReportModal(beachId, beachName) {
    document.getElementById('report-beach-id').value = beachId;
    document.getElementById('report-beach-name').textContent = beachName || 'this beach';
    document.getElementById('report-modal').classList.remove('hidden');
    document.getElementById('report-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('report-form').reset();
    document.getElementById('report-message').classList.add('hidden');

    // Re-init Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeReportModal() {
    document.getElementById('report-modal').classList.add('hidden');
    document.getElementById('report-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

async function submitReport(event) {
    event.preventDefault();

    const form = document.getElementById('report-form');
    const submitBtn = document.getElementById('report-submit-btn');
    const messageDiv = document.getElementById('report-message');

    // Check if at least one issue is selected
    const checkboxes = form.querySelectorAll('input[name="issues[]"]:checked');
    if (checkboxes.length === 0) {
        messageDiv.textContent = '<?= __('beach.report_select_issue') ?>';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = '<?= __('beach.report_submitting') ?>';
    messageDiv.classList.add('hidden');

    // For now, just show success (you can implement the API endpoint later)
    setTimeout(() => {
        messageDiv.textContent = '<?= __('beach.report_success') ?>';
        messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');

        // Show toast and close after delay
        if (typeof showToast === 'function') {
            showToast('<?= __('beach.report_toast_success') ?>', 'success', 3000);
        }

        setTimeout(() => {
            closeReportModal();
            submitBtn.disabled = false;
            submitBtn.textContent = '<?= __('beach.report_submit') ?>';
        }, 1500);
    }, 500);
}

// Close on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeReportModal();
});
</script>

<!-- Check-In Modal -->
<div id="checkin-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="checkin-modal-title" data-action="closeCheckinModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full max-h-[90vh] overflow-y-auto" data-action-stop data-action="noop" data-on="click">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="checkin-modal-title" class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                <i data-lucide="map-pin" class="w-5 h-5 text-green-600" aria-hidden="true"></i>
                <span><?= __('beach.checkin_modal_title') ?></span>
            </h2>
            <button data-action="closeCheckinModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="checkin-form" class="p-6 space-y-5" data-action="submitCheckin" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="checkin-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">
            <input type="hidden" name="website" value="" autocomplete="off">

            <p class="text-sm text-gray-600">
                <?= __('beach.checkin_share_text', ['name' => '<strong id="checkin-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <!-- Crowd Level -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_crowded') ?></label>
                <div class="grid grid-cols-5 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="empty" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🏝️</span>
                            <span class="text-xs"><?= __('beach.checkin_empty') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="light" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥</span>
                            <span class="text-xs"><?= __('beach.checkin_light') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="moderate" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥👥</span>
                            <span class="text-xs"><?= __('beach.checkin_moderate') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="busy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">👥👥👥</span>
                            <span class="text-xs"><?= __('beach.checkin_busy') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="crowd_level" value="packed" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🔥</span>
                            <span class="text-xs"><?= __('beach.checkin_packed') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Parking Status -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_parking') ?></label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="plenty" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🅿️</span>
                            <span class="text-xs"><?= __('beach.checkin_plenty') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="available" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">✓</span>
                            <span class="text-xs"><?= __('beach.checkin_available') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="limited" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">⚠️</span>
                            <span class="text-xs"><?= __('beach.checkin_limited') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="parking_status" value="full" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🚫</span>
                            <span class="text-xs"><?= __('beach.checkin_full') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Water Conditions -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_water') ?></label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="calm" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">😌</span>
                            <span class="text-xs"><?= __('beach.checkin_calm') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="small-waves" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌊</span>
                            <span class="text-xs"><?= __('beach.checkin_small_waves') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="choppy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌊🌊</span>
                            <span class="text-xs"><?= __('beach.checkin_choppy') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="water_condition" value="rough" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">⚠️</span>
                            <span class="text-xs"><?= __('beach.checkin_rough') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Sargassum -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.checkin_sargassum') ?></label>
                <div class="grid grid-cols-4 gap-2">
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="none" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">✨</span>
                            <span class="text-xs"><?= __('beach.checkin_none') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="light" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿</span>
                            <span class="text-xs"><?= __('beach.checkin_light') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="moderate" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿🌿</span>
                            <span class="text-xs"><?= __('beach.checkin_moderate') ?></span>
                        </div>
                    </label>
                    <label class="checkin-option">
                        <input type="radio" name="sargassum_level" value="heavy" class="sr-only">
                        <div class="checkin-option-box">
                            <span class="text-lg">🌿🌿🌿</span>
                            <span class="text-xs"><?= __('beach.checkin_heavy') ?></span>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Notes -->
            <div>
                <label for="checkin-notes" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.checkin_notes') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <textarea name="notes" id="checkin-notes" rows="2" maxlength="280"
                          placeholder="<?= h(__('beach.checkin_notes_placeholder')) ?>"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm"></textarea>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="checkin-submit-btn"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2.5 rounded-lg font-medium transition-colors flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-4 h-4"></i>
                    <span><?= __('beach.checkin_submit') ?></span>
                </button>
                <button type="button" data-action="closeCheckinModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    <?= __('beach.checkin_cancel') ?>
                </button>
            </div>

            <div id="checkin-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>


<script <?= cspNonceAttr() ?>>
function openCheckinModal(beachId, beachName) {
    document.getElementById('checkin-beach-id').value = beachId;
    document.getElementById('checkin-beach-name').textContent = beachName || 'this beach';
    document.getElementById('checkin-modal').classList.remove('hidden');
    document.getElementById('checkin-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('checkin-form').reset();
    document.getElementById('checkin-message').classList.add('hidden');

    // Re-init Lucide icons
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeCheckinModal() {
    document.getElementById('checkin-modal').classList.add('hidden');
    document.getElementById('checkin-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

async function submitCheckin(event) {
    event.preventDefault();

    const form = document.getElementById('checkin-form');
    const submitBtn = document.getElementById('checkin-submit-btn');
    const messageDiv = document.getElementById('checkin-message');

    // Check if at least one option is selected
    const hasSelection = form.querySelector('input[type="radio"]:checked') || form.querySelector('#checkin-notes').value.trim();
    if (!hasSelection) {
        messageDiv.textContent = '<?= __('beach.checkin_select_condition') ?>';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="animate-pulse"><?= __('beach.checkin_submitting') ?></span>';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/checkin.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || '<?= __('beach.checkin_success') ?>';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('<?= __('beach.checkin_toast_success') ?>', 'success', 3000);
            }

            if (typeof window.bfTrack === 'function') {
                window.bfTrack('U1_checkin_submitted', { source: 'beach_page', beach_slug: <?= json_encode($beach['slug']) ?> });
            }

            // Refresh check-ins list
            if (typeof htmx !== 'undefined') {
                htmx.trigger('#checkins-list', 'load');
            }

            setTimeout(() => {
                closeCheckinModal();
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span><?= __('beach.checkin_submit') ?></span>';
            }, 1000);

            // Reward gate for guests: contribute -> signup.
            if (data.requires_signup && typeof showSignupPrompt === 'function') {
                showSignupPrompt('favorites', '/beach/<?= h($beach['slug']) ?>?src=checkin');
            }
        } else {
            messageDiv.textContent = data.error || '<?= __('beach.checkin_error') ?>';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span><?= __('beach.checkin_submit') ?></span>';
        }
    } catch (error) {
        console.error('Check-in error:', error);
        messageDiv.textContent = '<?= __('beach.checkin_network_error') ?>';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i data-lucide="check" class="w-4 h-4"></i><span><?= __('beach.checkin_submit') ?></span>';
    }
}

// Close checkin modal on escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeCheckinModal();
});
</script>

<!-- Review Form Modal -->
<div id="review-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="review-modal-title" data-action="closeReviewModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto" data-action-stop data-action="noop" data-on="click">
        <div class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="review-modal-title" class="text-lg font-semibold text-gray-900"><?= __('beach.review_modal_title') ?></h2>
            <button data-action="closeReviewModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="review-form" class="p-6 space-y-5" data-action="submitReview" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="review-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                <?= __('beach.review_share_experience', ['name' => '<strong id="review-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <!-- Rating -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2"><?= __('beach.review_your_rating') ?> <span class="text-red-500 a11y-error-text">*</span></label>
                <div class="flex gap-1" id="star-rating">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-action="setRating" data-action-args='[<?= $i ?>]' data-star="<?= $i ?>"
                            class="star-btn text-3xl text-gray-300 hover:text-amber-400 transition-colors">
                        ★
                    </button>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="review-rating" value="0" required>
            </div>

            <!-- Title -->
            <div>
                <label for="review-title" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.review_title_label') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <input type="text" name="title" id="review-title" maxlength="100"
                       placeholder="<?= h(__('beach.review_title_placeholder')) ?>"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Review Text -->
            <div>
                <label for="review-text" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= __('beach.review_your_review') ?> <span class="text-red-500 a11y-error-text">*</span>
                </label>
                <textarea name="review_text" id="review-text" rows="4" minlength="20" maxlength="5000" required
                          placeholder="<?= h(__('beach.review_body_placeholder')) ?>"
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                <p class="text-xs text-gray-400 mt-1"><?= __('beach.review_min_chars') ?></p>
            </div>

            <!-- Pros/Cons -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="review-pros" class="block text-sm font-medium text-green-700 mb-1">
                        <?= __('reviews.pros') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                    </label>
                    <textarea name="pros" id="review-pros" rows="2" maxlength="500"
                              placeholder="<?= h(__('reviews.pros_placeholder')) ?>"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none text-sm"></textarea>
                </div>
                <div>
                    <label for="review-cons" class="block text-sm font-medium text-red-700 mb-1">
                        <?= __('reviews.cons') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                    </label>
                    <textarea name="cons" id="review-cons" rows="2" maxlength="500"
                              placeholder="<?= h(__('reviews.cons_placeholder')) ?>"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none text-sm"></textarea>
                </div>
            </div>

            <!-- Visit Details -->
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="review-visit-date" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= __('beach.review_when_visit') ?>
                    </label>
                    <input type="month" name="visit_date" id="review-visit-date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <label for="review-visited-with" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= __('beach.review_who_with') ?>
                    </label>
                    <select name="visited_with" id="review-visited-with"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value=""><?= __('beach.review_select') ?></option>
                        <option value="solo"><?= __('beach.review_solo') ?></option>
                        <option value="partner"><?= __('beach.review_partner') ?></option>
                        <option value="family"><?= __('beach.review_family') ?></option>
                        <option value="friends"><?= __('beach.review_friends') ?></option>
                        <option value="group"><?= __('beach.review_group') ?></option>
                    </select>
                </div>
            </div>

            <!-- Photo Upload -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    <?= __('beach.review_add_photos') ?> <span class="text-gray-400"><?= __('beach.optional') ?></span>
                </label>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center hover:border-blue-400 transition-colors">
                    <input type="file" name="photos[]" id="review-photos" accept="image/jpeg,image/png,image/webp" multiple
                           class="hidden" data-action="previewPhotos" data-action-args='["__this__"]' data-on="change">
                    <label for="review-photos" class="cursor-pointer">
                        <i data-lucide="camera" class="w-8 h-8 mx-auto text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600"><?= __('beach.review_click_upload') ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= __('beach.review_file_types') ?></p>
                    </label>
                </div>
                <div id="photo-preview" class="flex gap-2 mt-2 flex-wrap"></div>
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="review-submit-btn"
                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white py-2.5 rounded-lg font-medium transition-colors">
                    <?= __('beach.review_submit') ?>
                </button>
                <button type="button" data-action="closeReviewModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    <?= __('beach.review_cancel') ?>
                </button>
            </div>

            <div id="review-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<!-- Photo Upload Modal (standalone) -->
<div id="photo-upload-modal" class="fixed inset-0 bg-black/50 z-50 hidden items-center justify-center p-4"
     role="dialog" aria-modal="true" aria-labelledby="photo-upload-modal-title" data-action="closePhotoUploadModal">
    <div class="bg-white rounded-xl shadow-2xl max-w-md w-full" data-action-stop data-action="noop" data-on="click">
        <div class="border-b border-gray-200 px-6 py-4 flex items-center justify-between">
            <h2 id="photo-upload-modal-title" class="text-lg font-semibold text-gray-900"><?= __('beach.upload_modal_title') ?></h2>
            <button data-action="closePhotoUploadModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>

        <form id="photo-upload-form" class="p-6 space-y-4" data-action="submitPhotoUpload" data-action-args='["__event__"]' data-on="submit">
            <input type="hidden" name="beach_id" id="upload-beach-id">
            <input type="hidden" name="csrf_token" value="<?= h(csrfToken()) ?>">

            <p class="text-sm text-gray-600">
                <?= __('beach.upload_share_photos', ['name' => '<strong id="upload-beach-name">' . h($beach['name']) . '</strong>']) ?>
            </p>

            <div>
                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-purple-400 transition-colors">
                    <input type="file" name="photo" id="upload-photo" accept="image/jpeg,image/png,image/webp" required
                           class="hidden" data-action="previewUploadPhoto" data-action-args='["__this__"]' data-on="change">
                    <label for="upload-photo" class="cursor-pointer">
                        <i data-lucide="image-plus" class="w-10 h-10 mx-auto text-gray-400 mb-2"></i>
                        <p class="text-sm text-gray-600"><?= __('beach.upload_click_select') ?></p>
                        <p class="text-xs text-gray-400 mt-1"><?= __('beach.upload_file_types') ?></p>
                    </label>
                </div>
                <div id="upload-preview" class="mt-3 hidden">
                    <img id="upload-preview-img" src="" alt="Preview" class="max-h-48 mx-auto rounded-lg">
                </div>
            </div>

            <div>
                <label for="upload-caption" class="block text-sm font-medium text-gray-700 mb-1">
                    Caption <span class="text-gray-400">(optional)</span>
                </label>
                <input type="text" name="caption" id="upload-caption" maxlength="200"
                       placeholder="Add a caption to your photo..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500">
            </div>

            <div class="flex gap-3 pt-2">
                <button type="submit" id="upload-submit-btn"
                        class="flex-1 bg-purple-600 hover:bg-purple-700 text-white py-2.5 rounded-lg font-medium transition-colors">
                    Upload Photo
                </button>
                <button type="button" data-action="closePhotoUploadModal"
                        class="px-4 py-2.5 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
            </div>

            <div id="upload-message" class="hidden text-sm px-4 py-3 rounded-lg"></div>
        </form>
    </div>
</div>

<!-- Photo Lightbox Modal -->
<div id="photo-lightbox" class="fixed inset-0 bg-black/90 z-50 hidden items-center justify-center"
     role="dialog" aria-modal="true" data-action="closePhotoModal">
    <button data-action="closePhotoModal" class="absolute top-4 right-4 text-white/70 hover:text-white p-2" aria-label="Close">
        <i data-lucide="x" class="w-8 h-8"></i>
    </button>
    <div class="max-w-5xl max-h-[90vh] p-4" data-action-stop data-action="noop" data-on="click">
        <img id="photo-lightbox-img" src="" alt="" class="max-w-full max-h-[85vh] object-contain rounded-lg">
        <p id="photo-lightbox-caption" class="text-white/80 text-center mt-3 text-sm"></p>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
// Star rating
function setRating(rating) {
    document.getElementById('review-rating').value = rating;
    const stars = document.querySelectorAll('#star-rating .star-btn');
    stars.forEach((star, idx) => {
        if (idx < rating) {
            star.classList.remove('text-gray-300');
            star.classList.add('text-amber-400');
        } else {
            star.classList.remove('text-amber-400');
            star.classList.add('text-gray-300');
        }
    });
}

// Review modal
function openReviewForm(beachId, beachName) {
    document.getElementById('review-beach-id').value = beachId;
    document.getElementById('review-beach-name').textContent = beachName || 'this beach';
    document.getElementById('review-modal').classList.remove('hidden');
    document.getElementById('review-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    // Reset form
    document.getElementById('review-form').reset();
    document.getElementById('review-rating').value = '0';
    document.querySelectorAll('#star-rating .star-btn').forEach(s => {
        s.classList.remove('text-amber-400');
        s.classList.add('text-gray-300');
    });
    document.getElementById('photo-preview').innerHTML = '';
    document.getElementById('review-message').classList.add('hidden');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeReviewModal() {
    document.getElementById('review-modal').classList.add('hidden');
    document.getElementById('review-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function previewPhotos(input) {
    const preview = document.getElementById('photo-preview');
    preview.innerHTML = '';

    if (input.files) {
        Array.from(input.files).slice(0, 5).forEach((file, idx) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const div = document.createElement('div');
                div.className = 'relative w-16 h-16 rounded-lg overflow-hidden';
                div.innerHTML = `
                    <img src="${e.target.result}" class="w-full h-full object-cover" alt="Preview">
                    <button type="button" data-action="removePhoto" data-action-args='[${idx}]' class="absolute top-0 right-0 bg-red-500 text-white rounded-full w-5 h-5 text-xs">×</button>
                `;
                preview.appendChild(div);
            };
            reader.readAsDataURL(file);
        });
    }
}

async function submitReview(event) {
    event.preventDefault();

    const form = document.getElementById('review-form');
    const submitBtn = document.getElementById('review-submit-btn');
    const messageDiv = document.getElementById('review-message');

    const rating = document.getElementById('review-rating').value;
    if (!rating || rating === '0') {
        messageDiv.textContent = 'Please select a rating.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Submitting...';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || 'Review submitted!';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('Review submitted!', 'success', 3000);
            }

            setTimeout(() => {
                closeReviewModal();
                location.reload();
            }, 1500);
        } else {
            messageDiv.textContent = data.error || 'Failed to submit review';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit Review';
        }
    } catch (error) {
        console.error('Review error:', error);
        messageDiv.textContent = 'Network error. Please try again.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Review';
    }
}

// Photo upload modal
function openPhotoUploadModal(beachId, beachName) {
    document.getElementById('upload-beach-id').value = beachId;
    document.getElementById('upload-beach-name').textContent = beachName || 'this beach';
    document.getElementById('photo-upload-modal').classList.remove('hidden');
    document.getElementById('photo-upload-modal').classList.add('flex');
    document.body.style.overflow = 'hidden';

    document.getElementById('photo-upload-form').reset();
    document.getElementById('upload-preview').classList.add('hidden');
    document.getElementById('upload-message').classList.add('hidden');

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closePhotoUploadModal() {
    document.getElementById('photo-upload-modal').classList.add('hidden');
    document.getElementById('photo-upload-modal').classList.remove('flex');
    document.body.style.overflow = '';
}

function previewUploadPhoto(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('upload-preview-img').src = e.target.result;
            document.getElementById('upload-preview').classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    }
}

async function submitPhotoUpload(event) {
    event.preventDefault();

    const form = document.getElementById('photo-upload-form');
    const submitBtn = document.getElementById('upload-submit-btn');
    const messageDiv = document.getElementById('upload-message');

    submitBtn.disabled = true;
    submitBtn.textContent = 'Uploading...';
    messageDiv.classList.add('hidden');

    try {
        const formData = new FormData(form);
        const response = await fetch('/api/photos.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            messageDiv.textContent = data.message || 'Photo uploaded!';
            messageDiv.className = 'bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');

            if (typeof showToast === 'function') {
                showToast('Photo uploaded!', 'success', 3000);
            }

            setTimeout(() => {
                closePhotoUploadModal();
                location.reload();
            }, 1500);
        } else {
            messageDiv.textContent = data.error || 'Failed to upload photo';
            messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
            messageDiv.classList.remove('hidden');
            submitBtn.disabled = false;
            submitBtn.textContent = 'Upload Photo';
        }
    } catch (error) {
        console.error('Upload error:', error);
        messageDiv.textContent = 'Network error. Please try again.';
        messageDiv.className = 'bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-lg';
        messageDiv.classList.remove('hidden');
        submitBtn.disabled = false;
        submitBtn.textContent = 'Upload Photo';
    }
}

// Photo lightbox
function openPhotoModal(url, caption) {
    document.getElementById('photo-lightbox-img').src = url;
    document.getElementById('photo-lightbox-img').alt = caption || '';
    document.getElementById('photo-lightbox-caption').textContent = caption || '';
    document.getElementById('photo-lightbox').classList.remove('hidden');
    document.getElementById('photo-lightbox').classList.add('flex');
    document.body.style.overflow = 'hidden';

    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closePhotoModal() {
    document.getElementById('photo-lightbox').classList.add('hidden');
    document.getElementById('photo-lightbox').classList.remove('flex');
    document.body.style.overflow = '';
}

// Review voting
async function voteReview(reviewId, btn) {
    <?php if (!isAuthenticated()): ?>
    window.location.href = '/login?redirect=' + encodeURIComponent(window.location.pathname + '#reviews');
    return;
    <?php endif; ?>

    try {
        const formData = new FormData();
        formData.append('action', 'vote');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', '<?= h(csrfToken()) ?>');

        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            const isVoted = btn.dataset.voted === 'true';
            btn.dataset.voted = isVoted ? 'false' : 'true';

            if (isVoted) {
                btn.classList.remove('text-blue-600');
                btn.classList.add('text-gray-500');
            } else {
                btn.classList.remove('text-gray-500');
                btn.classList.add('text-blue-600');
            }

            // Update count
            let countEl = btn.querySelector('.helpful-count');
            if (countEl) {
                const count = parseInt(countEl.textContent) + (isVoted ? -1 : 1);
                if (count > 0) {
                    countEl.textContent = count;
                } else {
                    countEl.remove();
                }
            } else if (!isVoted) {
                const span = document.createElement('span');
                span.className = 'helpful-count text-xs bg-gray-100 px-1.5 py-0.5 rounded-full';
                span.textContent = '1';
                btn.appendChild(span);
            }
        }
    } catch (error) {
        console.error('Vote error:', error);
    }
}

// Delete review
async function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete your review? This cannot be undone.')) return;

    try {
        const formData = new FormData();
        formData.append('action', 'delete');
        formData.append('review_id', reviewId);
        formData.append('csrf_token', '<?= h(csrfToken()) ?>');

        const response = await fetch('/api/reviews.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            if (typeof showToast === 'function') {
                showToast('Review deleted', 'success', 3000);
            }
            location.reload();
        } else {
            alert(data.error || 'Failed to delete review');
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Network error. Please try again.');
    }
}

// Share review
function shareReview(reviewId) {
    const url = window.location.origin + window.location.pathname + '#review-' + reviewId;
    if (navigator.share) {
        navigator.share({ url: url });
    } else if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(() => {
            if (typeof showToast === 'function') {
                showToast('Link copied!', 'success', 2000);
            }
        });
    }
}

// Report review
function reportReview(reviewId) {
    alert('Report functionality coming soon. For now, please contact us at support@puertoricobeachfinder.com');
}

// Keyboard shortcuts
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeReviewModal();
        closePhotoUploadModal();
        closePhotoModal();
    }
});
</script>

<script <?= cspNonceAttr() ?>>
// Load weather data client-side (avoids blocking TTFB with external API call)
(function() {
    const container = document.getElementById('weather-widget-container');
    if (!container) return;
    const lat = container.dataset.lat;
    const lng = container.dataset.lng;
    if (!lat || !lng) return;

    // Fetch rendered weather widget HTML
    fetch('/api/weather-widget.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng) + '&size=sidebar')
        .then(r => r.ok ? r.text() : Promise.reject())
        .then(html => { container.innerHTML = html; })
        .catch(() => { container.innerHTML = '<div class="text-sm text-gray-400">Weather unavailable</div>'; });

    // Fetch weather JSON for sticky bar
    fetch('/api/weather.php?lat=' + encodeURIComponent(lat) + '&lng=' + encodeURIComponent(lng))
        .then(r => r.ok ? r.json() : Promise.reject())
        .then(data => {
            if (!data.success || !data.data) return;
            const w = data.data;
            const iconEl = document.getElementById('sticky-weather-icon');
            const tempEl = document.getElementById('sticky-weather-temp');
            const verdictEl = document.getElementById('sticky-weather-verdict');
            if (iconEl && w.current) iconEl.textContent = w.current.icon || '🌤️';
            if (tempEl && w.current) tempEl.textContent = w.current.temperature ? Math.round(w.current.temperature) + '°F' : '--';
            if (verdictEl && w.recommendation) verdictEl.textContent = w.recommendation.verdict || 'Check weather';
        })
        .catch(() => {
            const verdictEl = document.getElementById('sticky-weather-verdict');
            if (verdictEl) verdictEl.textContent = 'Check weather';
        });
})();
</script>

<?php include APP_ROOT . '/components/footer.php'; ?>
