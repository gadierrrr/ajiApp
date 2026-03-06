<?php
/**
 * Beach Finder - Main Page
 * Discover Puerto Rico's beaches
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/geo.php';
require_once APP_ROOT . '/inc/collection_query.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/components/seo-schemas.php';

$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
if ($requestPath === '/index.php') {
    $queryString = $_SERVER['QUERY_STRING'] ?? '';
    $target = '/';
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    exit;
}

// Locale-aware front controller: resolve Spanish (and future locale) routes,
// handle trailing-slash redirects, or 404 unknown paths.
// Real directory paths like /guides/ are served by Nginx's directory index
// and never reach this code.
if ($requestPath !== '/') {
    // Check locale route match first (before trailing-slash redirect).
    $routeMatch = localeRouteMatch($requestPath);

    if (is_array($routeMatch) && ($routeMatch['route_key'] ?? '') === 'home') {
        // /es is a valid localized homepage route — fall through to render homepage.
    } elseif (is_array($routeMatch)) {
        // Non-home locale route (e.g. /es/mejores-playas-familiares).
        // Resolve to internal PHP script and include it.
        $resolved = resolvePublicScriptFromLocalizedPath($requestPath);
        if (is_array($resolved) && !empty($resolved['script'])) {
            // Inject query params (e.g. slug for beach detail pages).
            foreach (($resolved['query'] ?? []) as $qk => $qv) {
                $_GET[(string) $qk] = $qv;
            }
            include APP_ROOT . '/public' . $resolved['script'];
            exit;
        }
        // Fallback: if resolution fails unexpectedly, 404.
        http_response_code(404);
        include APP_ROOT . '/public/errors/404.php';
        exit;
    } elseif (str_ends_with($requestPath, '/')) {
        // Trailing-slash redirect for non-matched paths.
        $clean = rtrim($requestPath, '/');
        $qs = $_SERVER['QUERY_STRING'] ?? '';
        header('Location: ' . $clean . ($qs !== '' ? '?' . $qs : ''), true, 301);
        exit;
    } else {
        // Check for accented municipality URLs (e.g., /beaches-in-guánica → /beaches-in-guanica)
        $decodedPath = urldecode($requestPath);
        if (preg_match('#^/(beaches-in-|es/playas-en-)(.+)$#u', $decodedPath, $m)) {
            $ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $m[2]);
            if ($ascii !== false && $ascii !== '' && $ascii !== $m[2]) {
                $qs = $_SERVER['QUERY_STRING'] ?? '';
                header('Location: /' . $m[1] . $ascii . ($qs !== '' ? '?' . $qs : ''), true, 301);
                exit;
            }
        }
        // No locale match, no trailing slash — unknown URL → 404.
        http_response_code(404);
        include APP_ROOT . '/public/errors/404.php';
        exit;
    }
}

// Page metadata
$pageTitle = __('pages.home.title');
$pageDescription = __('pages.home.description');

// Add structured data for homepage
$extraHead = websiteSchema() . organizationSchema();

// Get filter parameters from URL
$selectedTags = [];
if (isset($_GET['tags'])) {
    $selectedTags = array_merge($selectedTags, (array)$_GET['tags']);
}
if (isset($_GET['tags[]'])) {
    $selectedTags = array_merge($selectedTags, (array)$_GET['tags[]']);
}
if (isset($_GET['activity']) && is_string($_GET['activity'])) {
    $selectedTags[] = $_GET['activity'];
}
$selectedMunicipality = $_GET['municipality'] ?? '';
$sortBy = $_GET['sort'] ?? 'name';
$viewMode = $_GET['view'] ?? 'list';
$selectedCollection = $_GET['collection'] ?? '';
$includeAll = isset($_GET['include_all']) && in_array((string)$_GET['include_all'], ['1', 'true'], true);
$hasLifeguard = isset($_GET['has_lifeguard']) && in_array((string)$_GET['has_lifeguard'], ['1', 'true'], true);
$amenities = [];
if (isset($_GET['amenities'])) {
    $amenities = array_merge($amenities, (array)$_GET['amenities']);
}
if (isset($_GET['amenities[]'])) {
    $amenities = array_merge($amenities, (array)$_GET['amenities[]']);
}
if (in_array('lifeguards', $amenities, true) || in_array('lifeguard', $amenities, true)) {
    $hasLifeguard = true;
}
if (!$selectedCollection && (($_GET['near'] ?? '') === 'san-juan')) {
    $selectedCollection = 'beaches-near-san-juan';
}
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 12;
$searchQuery = trim($_GET['q'] ?? '');

// Validate filters
$selectedTags = array_values(array_unique(array_filter($selectedTags, 'isValidTag')));
if ($selectedMunicipality && !isValidMunicipality($selectedMunicipality)) {
    $selectedMunicipality = '';
}
if (!isValidCollectionKey((string)$selectedCollection)) {
    $selectedCollection = '';
}

// Build query
$sql = 'SELECT DISTINCT b.* FROM beaches b';
$params = [];
$where = ['b.publish_status = "published"'];

// Join tags table if filtering by tags
if (!empty($selectedTags)) {
    $sql .= ' INNER JOIN beach_tags bt ON b.id = bt.beach_id';
    $placeholders = [];
    foreach ($selectedTags as $i => $tag) {
        $placeholders[] = ':tag' . $i;
        $params[':tag' . $i] = $tag;
    }
    $where[] = 'bt.tag IN (' . implode(',', $placeholders) . ')';
}

// Municipality filter
if ($selectedMunicipality) {
    $where[] = 'b.municipality = :municipality';
    $params[':municipality'] = $selectedMunicipality;
}

// Lifeguard filter
if ($hasLifeguard) {
    $where[] = 'b.has_lifeguard = 1';
}

// Search query filter - searches name, municipality, and description
if ($searchQuery) {
    $where[] = '(b.name LIKE :search OR b.municipality LIKE :search2 OR b.description LIKE :search3)';
    $searchPattern = '%' . $searchQuery . '%';
    $params[':search'] = $searchPattern;
    $params[':search2'] = $searchPattern;
    $params[':search3'] = $searchPattern;
}

$sql .= ' WHERE ' . implode(' AND ', $where);

// Sorting
switch ($sortBy) {
    case 'rating':
        $sql .= ' ORDER BY b.google_rating DESC NULLS LAST, b.name ASC';
        break;
    case 'distance':
        // Distance sorting handled client-side with JS
        $sql .= ' ORDER BY b.name ASC';
        break;
    default:
        $sql .= ' ORDER BY b.name ASC';
}

// Get all beaches (for map view and client-side filtering)
$allBeaches = query($sql, $params);

// Batch fetch tags and amenities (2 queries instead of 2*N queries)
attachBeachMetadata($allBeaches);

// Paginate for list view
$totalBeaches = count($allBeaches);
$totalPages = ceil($totalBeaches / $perPage);
$beaches = array_slice($allBeaches, ($page - 1) * $perPage, $perPage);

// Get user favorites if logged in
$userFavorites = [];
if (isAuthenticated()) {
    $favorites = query('SELECT beach_id FROM user_favorites WHERE user_id = :user_id', [':user_id' => $_SESSION['user_id']]);
    $userFavorites = array_column($favorites, 'beach_id');
}

// Get data for hero section
$tagCounts = getBeachCountsByTag();
$popularBeaches = getPopularBeaches(4);
$siteStats = getSiteStats();
$publishedCount = queryOne('SELECT COUNT(*) as cnt FROM beaches WHERE publish_status = "published"')['cnt'];

// Include header
include APP_ROOT . '/components/header.php';
?>

<!-- Hero Section - Premium Dark Glassmorphism -->
<header class="relative w-full min-h-[85vh] flex flex-col items-center justify-center overflow-hidden">
    <!-- Background with gradient overlay and scale effect -->
    <div class="absolute inset-0 -z-10">
        <img src="/images/beaches/jobos-beach-isabela-18513-67085.jpg"
             alt="Jobos Beach in Isabela, Puerto Rico - famous for surfing"
             class="w-full h-full object-cover scale-110"
             loading="eager">
        <div class="absolute inset-0 bg-hero-gradient"></div>
        <div class="absolute inset-0 bg-black/30"></div>
    </div>

    <!-- Hero Content -->
    <div class="text-center z-10 px-4 py-16 md:py-24 w-full max-w-6xl mx-auto">
        <!-- Headline - Single H1 with styled spans -->
        <h1 class="animate-fade-in-up">
            <span class="block text-2xl sm:text-3xl md:text-5xl lg:text-5xl font-bold text-white">
                <?= h(__('pages.home.hero_headline_1', ['count' => number_format($publishedCount)])) ?>
            </span>
            <span class="block text-3xl sm:text-4xl md:text-5xl lg:text-6xl font-serif italic text-brand-yellow animate-fade-in-up delay-200 lg:whitespace-nowrap">
                <?= h(__('pages.home.hero_headline_2')) ?>
            </span>
        </h1>

        <!-- Subtitle with integrated stats -->
        <p class="text-sm sm:text-base text-gray-200 max-w-2xl mx-auto mt-6 md:mt-8 mb-8 md:mb-10 animate-fade-in-up delay-300">
            <?= h(__('pages.home.hero_subtitle', ['count' => number_format($totalBeaches)])) ?>
            <span class="text-white/70 mx-1">•</span>
            <span class="inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5 text-brand-yellow inline" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                <?= h(__('pages.home.hero_subtitle_reviews', ['rating' => number_format($siteStats['avg_rating'], 1), 'reviewsK' => number_format($siteStats['total_reviews'] / 1000)])) ?>
            </span>
        </p>

        <!-- Enhanced Search Bar -->
        <div class="animate-fade-in-up delay-400 mb-8 md:mb-10">
            <form action="/#beaches" method="GET" class="hero-search-form bg-black/40 backdrop-blur-md border border-white/20 rounded-2xl sm:rounded-full p-1.5 sm:p-2 max-w-xl md:max-w-2xl mx-auto" id="hero-search-form">
                <div class="flex items-center">
                    <i data-lucide="search" class="w-5 h-5 text-white/70 ml-3 sm:ml-4 flex-shrink-0" aria-hidden="true"></i>
                    <input type="text"
                           name="q"
                           id="hero-search-input"
                           placeholder="<?= h(__('pages.home.search_placeholder')) ?>"
                           value="<?= h($searchQuery) ?>"
                           class="flex-1 bg-transparent border-none text-white placeholder-white/40 px-3 sm:px-4 py-2.5 focus:outline-none text-sm sm:text-base"
                           aria-label="<?= h(__('common.search')) ?>"
                           autocomplete="off">
                    <button type="submit" class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-5 sm:px-8 py-2.5 sm:py-3 rounded-xl sm:rounded-full font-semibold text-sm sm:text-base transition-colors flex-shrink-0">
                        <?= h(__('pages.home.search_button')) ?>
                    </button>
                </div>
                <!-- Search Autocomplete Dropdown -->
                <div id="search-autocomplete" class="hidden absolute left-0 right-0 top-full mt-2 bg-brand-dark/95 backdrop-blur-md border border-white/20 rounded-xl shadow-glass overflow-hidden z-50">
                    <div id="search-results" class="max-h-64 overflow-y-auto">
                        <!-- Results populated by JS -->
                    </div>
                </div>
            </form>
        </div>

        <!-- Filter Category Chips - Compact horizontal pills -->
        <div class="animate-fade-in-up delay-500">
            <?php
            $heroCategories = [
                'surfing' => [
                    'label' => __('pages.home.category_surfing'), 'emoji' => '🏄‍♂️',
                    'bg' => 'bg-emerald-500/20', 'border' => 'border-emerald-400/40',
                    'hoverBg' => 'hover:bg-emerald-500/30', 'hoverBorder' => 'hover:border-emerald-400/60',
                    'countColor' => 'text-emerald-300/70',
                ],
                'snorkeling' => [
                    'label' => __('pages.home.category_snorkeling'), 'emoji' => '🤿',
                    'bg' => 'bg-teal-500/20', 'border' => 'border-teal-400/40',
                    'hoverBg' => 'hover:bg-teal-500/30', 'hoverBorder' => 'hover:border-teal-400/60',
                    'countColor' => 'text-teal-300/70',
                ],
                'family-friendly' => [
                    'label' => __('pages.home.category_family'), 'emoji' => '👨‍👩‍👧',
                    'bg' => 'bg-amber-500/20', 'border' => 'border-amber-400/40',
                    'hoverBg' => 'hover:bg-amber-500/30', 'hoverBorder' => 'hover:border-amber-400/60',
                    'countColor' => 'text-amber-300/70',
                ],
                'secluded' => [
                    'label' => __('pages.home.category_secluded'), 'emoji' => '🌴',
                    'bg' => 'bg-rose-500/20', 'border' => 'border-rose-400/40',
                    'hoverBg' => 'hover:bg-rose-500/30', 'hoverBorder' => 'hover:border-rose-400/60',
                    'countColor' => 'text-rose-300/70',
                ],
            ];
            ?>
            <div class="flex flex-wrap items-center justify-center gap-2 sm:gap-3 max-w-2xl mx-auto">
                <?php foreach ($heroCategories as $tag => $cat):
                    $isActive = in_array($tag, $selectedTags);
                    $count = $tagCounts[$tag] ?? 0;
                ?>
                <a href="/?tags[]=<?= h($tag) ?>#beaches"
                   class="inline-flex items-center gap-1.5 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full backdrop-blur-sm border transition-all duration-200 text-sm <?= $isActive ? 'bg-brand-yellow/20 border-brand-yellow text-brand-yellow' : $cat['bg'] . ' ' . $cat['hoverBg'] . ' ' . $cat['border'] . ' ' . $cat['hoverBorder'] . ' text-white' ?>"
                   aria-pressed="<?= $isActive ? 'true' : 'false' ?>">
                    <span><?= $cat['emoji'] ?></span>
                    <span class="font-medium"><?= h($cat['label']) ?></span>
                    <span class="text-xs <?= $isActive ? 'text-brand-yellow/70' : $cat['countColor'] ?>"><?= $count ?></span>
                </a>
                <?php endforeach; ?>
                <a href="#beaches"
                   class="inline-flex items-center gap-1 px-3 sm:px-4 py-1.5 sm:py-2 rounded-full bg-white/5 hover:bg-white/10 border border-white/10 hover:border-white/20 text-white/60 hover:text-white transition-all duration-200 text-sm">
                    <i data-lucide="sliders-horizontal" class="w-3.5 h-3.5"></i>
                    <span><?= h(__('pages.home.category_more')) ?></span>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Search Autocomplete Script -->
<script <?= cspNonceAttr() ?>>
(function() {
    const searchInput = document.getElementById('hero-search-input');
    const searchForm = document.getElementById('hero-search-form');
    const autocomplete = document.getElementById('search-autocomplete');
    const resultsContainer = document.getElementById('search-results');
    let debounceTimer = null;
    let beaches = <?= json_encode(array_map(function($b) {
        return [
            'name' => $b['name'],
            'slug' => $b['slug'],
            'municipality' => $b['municipality'],
            'tags' => $b['tags'] ?? []
        ];
    }, array_slice($allBeaches, 0, 100))) ?>;

    if (!searchInput || !autocomplete || !resultsContainer) return;

    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim().toLowerCase();

        if (query.length < 2) {
            autocomplete.classList.add('hidden');
            return;
        }

        debounceTimer = setTimeout(function() {
            const results = beaches.filter(function(beach) {
                return beach.name.toLowerCase().includes(query) ||
                       beach.municipality.toLowerCase().includes(query) ||
                       (beach.tags && beach.tags.some(function(t) { return t.toLowerCase().includes(query); }));
            }).slice(0, 6);

            if (results.length === 0) {
                autocomplete.classList.add('hidden');
                return;
            }

            resultsContainer.innerHTML = results.map(function(beach) {
                return '<a href="/beach/' + beach.slug + '" class="flex items-center gap-3 px-4 py-3 hover:bg-white/10 transition-colors border-b border-white/5 last:border-0">' +
                    '<div class="w-8 h-8 rounded-full bg-brand-yellow/20 flex items-center justify-center flex-shrink-0">' +
                    '<svg class="w-4 h-4 text-brand-yellow" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg>' +
                    '</div>' +
                    '<div class="flex-1 min-w-0">' +
                    '<div class="text-white font-medium truncate">' + beach.name + '</div>' +
                    '<div class="text-xs text-white/70">' + beach.municipality + '</div>' +
                    '</div>' +
                    '<svg class="w-4 h-4 text-white/30 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6"/></svg>' +
                    '</a>';
            }).join('');

            autocomplete.classList.remove('hidden');
        }, 150);
    });

    // Hide on click outside
    document.addEventListener('click', function(e) {
        if (!searchForm.contains(e.target)) {
            autocomplete.classList.add('hidden');
        }
    });

    // Hide on escape
    searchInput.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            autocomplete.classList.add('hidden');
        }
    });

    // Position autocomplete relative to form
    searchForm.style.position = 'relative';
})();
</script>

<!-- Trending Now - Horizontal Carousel -->
<?php
$trendingBeaches = getTrendingBeaches(8);
        $showDiscovery = !empty($selectedTags) || !empty($selectedMunicipality) || $hasLifeguard ? false : true;
?>
<?php if ($showDiscovery && !empty($trendingBeaches)): ?>
<section class="py-12 md:py-16 pl-4 sm:pl-6 md:pl-20 bg-brand-dark">
    <!-- Section Header -->
    <div class="flex justify-between items-center pr-4 sm:pr-6 md:pr-20 mb-6 md:mb-8">
        <h2 class="text-2xl md:text-3xl font-bold text-white"><?= h(__('pages.home.trending_now')) ?></h2>
        <div class="flex gap-2">
            <button data-action="scrollCarousel" data-action-args='["trending",-1]' class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition-colors" aria-label="<?= h(__('common.previous')) ?>">
                <i data-lucide="chevron-left" class="w-5 h-5" aria-hidden="true"></i>
            </button>
            <button data-action="scrollCarousel" data-action-args='["trending",1]' class="w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 border border-white/20 flex items-center justify-center text-white transition-colors" aria-label="<?= h(__('common.next')) ?>">
                <i data-lucide="chevron-right" class="w-5 h-5" aria-hidden="true"></i>
            </button>
        </div>
    </div>

    <!-- Carousel -->
    <div id="trending-carousel" class="flex overflow-x-auto gap-6 snap-x snap-mandatory hide-scrollbar pb-4">
        <?php foreach ($trendingBeaches as $i => $tb):
            $tagsList = $tb['tags'] ?? [];
            $viewCount = $tb['view_count'] ?? rand(500, 2500);
        ?>
        <a href="/beach/<?= h($tb['slug']) ?>"
           class="w-[280px] sm:w-[320px] md:w-[400px] lg:w-[450px] snap-start relative group rounded-xl overflow-hidden flex-shrink-0 shadow-2xl">
            <!-- Image with zoom on hover -->
            <div class="relative aspect-[4/5] overflow-hidden">
                <img src="<?= h(getThumbnailUrl($tb['cover_image'])) ?>"
                     alt="<?= h($tb['name']) ?>"
                     class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110"
                     loading="lazy">

                <!-- Subtle gradient for text readability at bottom only -->
                <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/80 to-transparent"></div>

                <!-- View count badge with fire emoji -->
                <div class="absolute top-4 right-4 z-20 bg-black/40 backdrop-blur-md rounded-full px-3 py-1.5 border border-white/20 flex items-center gap-1.5">
                    <span>🔥</span>
                    <span class="text-xs text-white font-medium"><?= h(formatViewCount($viewCount)) ?></span>
                </div>

                <!-- Ranking number -->
                <div class="absolute top-4 left-4 z-20 text-white/30 text-6xl font-bold"><?= $i + 1 ?></div>

                <!-- Bottom content -->
                <div class="absolute bottom-0 left-0 w-full p-5 md:p-6 z-20" style="text-shadow: 0 2px 4px rgba(0,0,0,0.8), 0 4px 12px rgba(0,0,0,0.6);">
                    <span class="text-xs text-brand-yellow uppercase tracking-wider font-medium"><?= h($tb['municipality']) ?></span>
                    <h3 class="text-xl md:text-2xl font-bold text-white font-serif mt-1"><?= h($tb['name']) ?></h3>

                    <!-- Rating -->
                    <?php if ($tb['google_rating']): ?>
                    <div class="flex items-center gap-2 mt-2">
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" viewBox="0 0 24 24"><path fill="#FACC15" d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                            <span class="text-sm font-medium text-white"><?= number_format($tb['google_rating'], 1) ?></span>
                        </div>
                        <?php if ($tb['google_review_count']): ?>
                        <span class="text-xs text-white/60">(<?= number_format($tb['google_review_count']) ?> <?= h(__('beach.reviews')) ?>)</span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Hover reveal description -->
                    <?php
                    $lang = getCurrentLanguage();
                    $tbDesc = ($lang === 'es' && !empty($tb['description_es'])) ? $tb['description_es'] : ($tb['description'] ?? __('beach.beach_fallback_desc'));
                    ?>
                    <p class="text-sm text-gray-300 mt-2 opacity-0 group-hover:opacity-100 transition-opacity duration-300 line-clamp-2">
                        <?= h(substr($tbDesc, 0, 100)) ?>
                    </p>
                </div>
            </div>
        </a>
        <?php endforeach; ?>

        <!-- More to Explore Terminal Card -->
        <a href="/#beaches" class="w-[280px] sm:w-[320px] md:w-[400px] lg:w-[450px] snap-start relative group rounded-xl overflow-hidden flex-shrink-0 shadow-2xl">
            <div class="relative aspect-[4/5] bg-brand-darker border border-white/10 flex flex-col items-center justify-center p-8 text-center">
                <div class="font-mono text-brand-yellow text-sm mb-4 opacity-60">> ls -la beaches/</div>
                <h3 class="text-2xl font-bold text-white mb-2"><?= h(__('pages.home.more_to_explore')) ?></h3>
                <p class="text-gray-400 text-sm mb-6 opacity-60 group-hover:opacity-100 transition-opacity">
                    <?= h(__('pages.home.more_to_explore_desc', ['count' => number_format($totalBeaches)])) ?>
                </p>
                <div class="w-12 h-12 rounded-full bg-brand-yellow/20 flex items-center justify-center group-hover:bg-brand-yellow/30 transition-colors">
                    <i data-lucide="arrow-right" class="w-6 h-6 text-brand-yellow"></i>
                </div>
            </div>
        </a>
    </div>
</section>

<script <?= cspNonceAttr() ?>>
function scrollCarousel(id, direction) {
    const carousel = document.getElementById(id + '-carousel');
    if (carousel) {
        const scrollAmount = carousel.offsetWidth * 0.8;
        carousel.scrollBy({ left: direction * scrollAmount, behavior: 'smooth' });
    }
}
</script>
<?php endif; ?>

<!-- Main Content -->
<section id="beaches" class="py-12 md:py-16 bg-brand-dark scroll-mt-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Filters -->
        <?php
        $locationEnabled = false; // Set by JS when location is granted
        $maxDistance = 50;
        include APP_ROOT . '/components/filters.php';
        ?>

        <!-- List View -->
        <div id="list-view" class="<?= $viewMode === 'list' ? '' : 'hidden' ?>">
            <?php
            $userLocation = null; // Set by JS when location is granted
            include APP_ROOT . '/components/beach-grid.php';
            ?>

            <!-- Load More / Pagination -->
            <?php if ($totalPages > 1 && $page < $totalPages):
                $apiParams = array_filter([
                    'tags' => $selectedTags ?: null,
                    'municipality' => $selectedMunicipality ?: null,
                    'q' => $searchQuery ?: null,
                    'sort' => $sortBy !== 'name' ? $sortBy : null,
                    'has_lifeguard' => $hasLifeguard ? '1' : null,
                    'page' => $page + 1
                ]);
            ?>
            <div id="load-more-container" class="text-center mt-8">
                <button id="load-more-btn"
                        hx-get="/api/beaches.php?<?= http_build_query($apiParams) ?>"
                        hx-target="#beach-grid"
                        hx-swap="beforeend"
                        class="bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                    <?= h(__('pages.home.load_more')) ?>
                    <span class="htmx-indicator ml-2">...</span>
                </button>
                <p class="text-sm text-white/60 mt-2">
                    <?= h(__('pages.home.showing_count', ['shown' => min($page * $perPage, $totalBeaches), 'total' => $totalBeaches])) ?>
                </p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Map View -->
        <div id="map-view" class="<?= $viewMode === 'map' ? '' : 'hidden' ?>">
            <div id="map-container"></div>
        </div>

    </div>
</section>

<!-- Beach Details Drawer -->
<div id="beach-drawer" class="drawer-overlay" role="dialog" aria-modal="true" aria-label="Beach details" data-action="closeBeachDrawer" data-action-args='["__event__"]'>
    <div class="drawer-content" data-action-stop data-action="noop" data-on="click">
        <div id="drawer-content-inner">
            <!-- Content loaded via HTMX -->
        </div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" data-action="closeShareModal">
    <div class="share-modal-content" data-action-stop data-action="noop" data-on="click">
        <div class="flex justify-between items-center mb-4">
            <h3 id="share-modal-title" class="text-lg font-semibold"><?= h(__('pages.home.share_beach')) ?></h3>
            <button data-action="closeShareModal" class="text-gray-400 hover:text-gray-600 p-1" aria-label="<?= h(__('common.close')) ?>">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="share-modal-body">
            <!-- Content set by JS -->
        </div>
    </div>
</div>

<!-- Quiz CTA Section -->
<section id="experiences" class="py-16 md:py-24 px-4 sm:px-6 md:px-20 text-center bg-brand-dark">
    <div class="max-w-4xl mx-auto border border-brand-yellow/20 rounded-3xl p-8 md:p-12 bg-white/5 backdrop-blur-sm">
        <h3 class="text-3xl md:text-4xl font-serif italic text-white mb-6">
            <?= h(__('pages.home.quiz_headline')) ?>
        </h3>
        <p class="text-gray-400 mb-8 text-base md:text-lg max-w-2xl mx-auto">
            <?= h(__('pages.home.quiz_subtitle')) ?>
        </p>
        <a href="/quiz" class="inline-block px-8 md:px-12 py-3 md:py-4 bg-brand-yellow text-brand-darker font-bold rounded-full hover:scale-105 transition-transform">
            <?= h(__('pages.home.quiz_button')) ?>
        </a>
    </div>
</section>

<!-- Pass data to JavaScript (beaches lazy-loaded for performance) -->
<script <?= cspNonceAttr() ?>>
window.BeachFinder = {
    beaches: [],
    beachesLoaded: false,
    selectedTags: <?= json_encode($selectedTags) ?>,
    selectedMunicipality: <?= json_encode($selectedMunicipality) ?>,
    selectedCollection: <?= json_encode($selectedCollection) ?>,
    includeAll: <?= $includeAll ? 'true' : 'false' ?>,
    hasLifeguard: <?= $hasLifeguard ? 'true' : 'false' ?>,
    searchQuery: <?= json_encode($searchQuery) ?>,
    sortBy: <?= json_encode($sortBy) ?>,
    viewMode: <?= json_encode($viewMode) ?>,
    userFavorites: <?= json_encode($userFavorites) ?>,
    isAuthenticated: <?= isAuthenticated() ? 'true' : 'false' ?>,
    csrfToken: <?= json_encode(csrfToken()) ?>,
    mapCenter: <?= json_encode(getPRCenter()) ?>,
    totalBeaches: <?= $totalBeaches ?>,
    tagLabels: <?= json_encode(array_combine(TAGS, array_map('getTagLabel', TAGS))) ?>,
    hasActiveFilters: <?= (!empty($selectedTags) || !empty($selectedMunicipality) || !empty($searchQuery) || !empty($selectedCollection) || $includeAll || $hasLifeguard) ? 'true' : 'false' ?>,
    loadBeaches: function() {
        if (this.beachesLoaded || this._loading) return Promise.resolve(this.beaches);
        this._loading = true;
        const mapParams = new URLSearchParams(window.location.search);
        mapParams.delete('view');
        mapParams.delete('page');
        const mapUrl = '/api/beaches-map.php' + (mapParams.toString() ? '?' + mapParams.toString() : '');
        return fetch(mapUrl)
            .then(r => r.json())
            .then(data => {
                this.beaches = data.beaches || [];
                this.beachesLoaded = true;
                this._loading = false;
                if (typeof state !== 'undefined') {
                    state.beaches = this.beaches;
                    state.filteredBeaches = [...this.beaches];
                }
                return this.beaches;
            })
            .catch(err => {
                console.warn('Failed to load beach data:', err);
                this._loading = false;
                return [];
            });
    }
};

// Auto-scroll to results when filters are active
(function() {
    function scrollToResults() {
        const beachesSection = document.getElementById('beaches');
        if (beachesSection) {
            beachesSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // On page load: scroll if filters are active or hash is #beaches
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        if (window.BeachFinder.hasActiveFilters || window.location.hash === '#beaches' || urlParams.get('view') === 'map') {
            // Small delay to ensure page is rendered
            setTimeout(scrollToResults, 100);
        }
    });

    // After HTMX swaps beach grid (filter applied via HTMX)
    document.body.addEventListener('htmx:afterSwap', function(e) {
        if (e.detail.target?.id === 'beach-grid') {
            scrollToResults();
        }
    });
})();
</script>

<?php
// Extra scripts for map
$extraScripts = '<script defer src="/assets/js/map.js" ' . cspNonceAttr() . '></script>';
include APP_ROOT . '/components/footer.php';
?>
