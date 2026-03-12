<?php
/**
 * User Profile Page
 * Shows favorites, reviews, photos, and check-ins history
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/weather.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

// Require authentication
requireAuth();

$user = currentUser();
$pageTitle = __('profile.my_profile');
$pageDescription = __('profile.description');

// Get active tab
$activeTab = $_GET['tab'] ?? 'favorites';
$validTabs = ['favorites', 'reviews', 'photos', 'checkins'];
if (!in_array($activeTab, $validTabs, true)) {
    $activeTab = 'favorites';
}

$deleteError = trim((string) ($_GET['delete_error'] ?? ''));
$deleteErrorMessages = [
    'csrf' => __('profile.delete_error_csrf'),
    'email' => __('profile.delete_error_email'),
    'phrase' => __('profile.delete_error_phrase'),
    'method' => __('profile.delete_error_method'),
    'last_admin' => __('profile.delete_error_last_admin'),
    'failed' => __('profile.delete_error_failed'),
];

// Get user's favorite beaches
$favorites = query(
    'SELECT b.* FROM beaches b
     INNER JOIN user_favorites uf ON b.id = uf.beach_id
     WHERE uf.user_id = :user_id AND b.publish_status = "published"
     ORDER BY uf.created_at DESC',
    [':user_id' => $user['id']]
);

// Get tags for favorites
foreach ($favorites as &$beach) {
    $beach['tags'] = array_column(
        query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
        'tag'
    );
}
unset($beach);

// Get user's reviews
$reviews = query(
    'SELECT r.*, b.name as beach_name, b.slug as beach_slug, b.cover_image as beach_image
     FROM beach_reviews r
     INNER JOIN beaches b ON r.beach_id = b.id
     WHERE r.user_id = :user_id
     ORDER BY r.created_at DESC',
    [':user_id' => $user['id']]
);

// Get user's photos
$photos = query(
    'SELECT p.*, b.name as beach_name, b.slug as beach_slug
     FROM beach_photos p
     INNER JOIN beaches b ON p.beach_id = b.id
     WHERE p.user_id = :user_id AND p.status = "approved"
     ORDER BY p.created_at DESC',
    [':user_id' => $user['id']]
);

// Get user's check-ins
$checkins = query(
    'SELECT c.*, b.name as beach_name, b.slug as beach_slug, b.cover_image as beach_image
     FROM beach_checkins c
     INNER JOIN beaches b ON c.beach_id = b.id
     WHERE c.user_id = :user_id
     ORDER BY c.created_at DESC
     LIMIT 50',
    [':user_id' => $user['id']]
);

// Stats
$stats = [
    'favorites' => count($favorites),
    'reviews' => count($reviews),
    'photos' => count($photos),
    'checkins' => count($checkins)
];

// Calculate member since
$memberSince = date('F Y', strtotime($user['created_at'] ?? 'now'));

$userFavorites = array_column($favorites, 'id');

// Get weather for favorite beaches (max 5 for dashboard)
$favoritesForWeather = array_slice($favorites, 0, 5);
$weatherData = getBatchWeatherForBeaches($favoritesForWeather, 5);

// Get explorer level info and progress
$explorerLevel = $user['explorer_level'] ?? 'newcomer';
$beachesVisited = (int)($user['total_beaches_visited'] ?? 0);
$levelInfo = getExplorerLevelInfo($explorerLevel);
$progress = getExplorerProgress($beachesVisited, $explorerLevel);

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => '/'],
    ['name' => __('profile.my_profile')]
];

include APP_ROOT . '/components/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pt-24">
    <!-- Breadcrumbs -->
    <div class="mb-6">
        <?php include APP_ROOT . '/components/breadcrumbs.php'; ?>
    </div>
    <!-- Profile Header -->
    <div class="bg-brand-darker/50 backdrop-blur-md rounded-xl border border-white/10 p-6 mb-8">
        <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
            <!-- Avatar -->
            <div class="flex-shrink-0">
                <?php if (!empty($user['avatar_url'])): ?>
                <img src="<?= h($user['avatar_url']) ?>"
                     alt="<?= h($user['name'] ?? 'User') ?>"
                     class="w-24 h-24 rounded-full object-cover border-4 border-brand-yellow/30">
                <?php else: ?>
                <div class="w-24 h-24 rounded-full bg-gradient-to-br from-brand-yellow to-yellow-500 flex items-center justify-center text-brand-darker text-3xl font-bold">
                    <?= strtoupper(substr($user['name'] ?? $user['email'] ?? 'U', 0, 1)) ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- User Info -->
            <div class="flex-1 text-center sm:text-left">
                <div class="flex items-center justify-center sm:justify-start gap-3 flex-wrap">
                    <h1 class="text-2xl font-bold text-white">
                        <?= h($user['name'] ?? __('profile.beach_explorer')) ?>
                    </h1>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium border <?= h($levelInfo['colorClass']) ?>">
                        <span><?= h($levelInfo['icon']) ?></span>
                        <span><?= h($levelInfo['label']) ?></span>
                    </span>
                </div>
                <p class="text-gray-400 mt-1">
                    <i data-lucide="calendar" class="w-4 h-4 inline-block mr-1"></i>
                    <?= h(__('profile.member_since', ['date' => $memberSince])) ?>
                </p>

                <!-- Stats -->
                <div class="flex flex-wrap justify-center sm:justify-start gap-6 mt-4">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-brand-yellow"><?= $stats['favorites'] ?></div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide"><?= h(__('profile.favorites')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-amber-400"><?= $stats['reviews'] ?></div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide"><?= h(__('profile.reviews')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-purple-400"><?= $stats['photos'] ?></div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide"><?= h(__('profile.photos')) ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-400"><?= $stats['checkins'] ?></div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide"><?= h(__('profile.checkins')) ?></div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-2">
                <a href="/" class="inline-flex items-center gap-2 bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-4 py-2 rounded-lg font-medium text-sm transition-colors">
                    <i data-lucide="compass" class="w-4 h-4"></i>
                    <span><?= h(__('profile.explore_beaches')) ?></span>
                </a>
	                <a href="/logout" class="inline-flex items-center gap-2 border border-white/20 hover:border-white/40 text-gray-300 hover:text-white px-4 py-2 rounded-lg font-medium text-sm transition-colors">
	                    <i data-lucide="log-out" class="w-4 h-4"></i>
	                    <span><?= h(__('profile.sign_out')) ?></span>
	                </a>
            </div>
        </div>
    </div>

    <?php if ($deleteError !== ''): ?>
    <div class="bg-red-500/10 border border-red-500/30 text-red-200 px-4 py-3 rounded-xl mb-8">
        <?= h($deleteErrorMessages[$deleteError] ?? __('profile.delete_error_failed')) ?>
    </div>
    <?php endif; ?>

    <!-- Dashboard Widgets -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Explorer Progress Card -->
        <div class="bg-brand-darker/50 backdrop-blur-md rounded-xl border border-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i data-lucide="trophy" class="w-5 h-5 text-brand-yellow"></i>
                    <?= h(__('profile.explorer_progress')) ?>
                </h2>
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-sm font-medium border <?= h($levelInfo['colorClass']) ?>">
                    <span><?= h($levelInfo['icon']) ?></span>
                    <span><?= h($levelInfo['label']) ?></span>
                </span>
            </div>

            <div class="mb-4">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-400"><?php
                        $exploredParts = explode('|', __('profile.beaches_explored', ['count' => $beachesVisited]));
                        echo h($beachesVisited === 1 ? $exploredParts[0] : ($exploredParts[1] ?? $exploredParts[0]));
                    ?></span>
                    <?php if ($progress['next_level']): ?>
                    <span class="text-brand-yellow"><?= h($progress['message']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="h-3 bg-white/10 rounded-full overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-brand-yellow to-yellow-400 rounded-full transition-all duration-500"
                         style="width: <?= $progress['percentage'] ?>%"></div>
                </div>
            </div>

            <?php if ($progress['next_level']): ?>
            <p class="text-sm text-gray-500">
                <?php
                    $visitParts = explode('|', __('profile.visit_more', ['count' => $progress['beaches_needed']]));
                    echo h($progress['beaches_needed'] === 1 ? $visitParts[0] : ($visitParts[1] ?? $visitParts[0]));
                ?>
                <span class="<?= h($progress['next_level_info']['colorClass']) ?> px-2 py-0.5 rounded-full text-xs font-medium">
                    <?= h($progress['next_level_info']['icon']) ?> <?= h($progress['next_level_info']['label']) ?>
                </span>
            </p>
            <?php else: ?>
            <p class="text-sm text-purple-400">
                <?= h(__('profile.max_rank')) ?>
            </p>
            <?php endif; ?>
        </div>

        <!-- Favorite Beaches Weather Card -->
        <div class="bg-brand-darker/50 backdrop-blur-md rounded-xl border border-white/10 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i data-lucide="sun" class="w-5 h-5 text-brand-yellow"></i>
                    <?= h(__('profile.your_beaches_today')) ?>
                </h2>
                <?php if (!empty($favoritesForWeather)): ?>
                <span class="text-xs text-gray-500"><?= h(__('profile.weather_for_favorites')) ?></span>
                <?php endif; ?>
            </div>

            <?php if (empty($favoritesForWeather)): ?>
            <div class="text-center py-6">
                <p class="text-gray-400 mb-3"><?= h(__('profile.save_beaches_weather')) ?></p>
                <a href="/" class="text-brand-yellow hover:text-yellow-300 text-sm font-medium">
                    <?= h(__('profile.explore_beaches_link')) ?>
                </a>
            </div>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($favoritesForWeather as $beach):
                    $weather = $weatherData[$beach['id']] ?? null;
                    $current = $weather['current'] ?? null;
                ?>
                <a href="/beach/<?= h($beach['slug']) ?>"
                   class="flex items-center justify-between p-3 rounded-lg bg-white/5 hover:bg-white/10 border border-white/5 hover:border-brand-yellow/30 transition-all group">
                    <div class="flex items-center gap-3 min-w-0">
                        <img src="<?= h(getThumbnailUrl($beach['cover_image'] ?? '')) ?>"
                             alt="<?= h($beach['name']) ?>"
                             class="w-10 h-10 rounded-lg object-cover flex-shrink-0">
                        <span class="text-white font-medium truncate group-hover:text-brand-yellow transition-colors">
                            <?= h($beach['name']) ?>
                        </span>
                    </div>
                    <?php if ($current): ?>
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span class="text-2xl" title="<?= h($current['description'] ?? '') ?>">
                            <?= h($current['icon'] ?? '🌤️') ?>
                        </span>
                        <span class="text-white font-medium">
                            <?= round($current['temperature'] ?? 0) ?>°F
                        </span>
                        <?php
                        $score = $current['beach_score'] ?? 50;
                        $scoreColor = $score >= 80 ? 'text-green-400' : ($score >= 60 ? 'text-blue-400' : ($score >= 40 ? 'text-yellow-400' : 'text-red-400'));
                        ?>
                        <span class="<?= $scoreColor ?> text-sm font-medium" title="Beach Score">
                            <?= $score ?>%
                        </span>
                    </div>
                    <?php else: ?>
                    <span class="text-gray-500 text-sm">--</span>
                    <?php endif; ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php if (count($favorites) > 5): ?>
            <p class="text-xs text-gray-500 mt-3 text-center">
                <?= h(__('profile.showing_top_5')) ?>
            </p>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <section class="bg-red-950/40 backdrop-blur-md rounded-xl border border-red-500/30 p-6 mb-8" aria-labelledby="account-danger-zone">
        <div class="flex items-start gap-3 mb-5">
            <i data-lucide="triangle-alert" class="w-5 h-5 text-red-300 mt-0.5"></i>
            <div>
                <h2 id="account-danger-zone" class="text-lg font-semibold text-white"><?= h(__('profile.danger_zone')) ?></h2>
                <p class="text-sm text-red-100/80 mt-1"><?= h(__('profile.danger_zone_desc')) ?></p>
            </div>
        </div>

        <p class="text-sm text-red-100 mb-5">
            <?= h(__('profile.delete_account_warning')) ?>
        </p>

        <form method="POST" action="/api/account/delete.php" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <?= csrfField() ?>
            <input type="hidden" name="redirect_tab" value="<?= h($activeTab) ?>">

            <div>
                <label for="delete-account-email" class="block text-sm font-medium text-white mb-2">
                    <?= h(__('profile.delete_account_email_label')) ?>
                </label>
                <input
                    id="delete-account-email"
                    name="confirm_email"
                    type="email"
                    inputmode="email"
                    autocomplete="email"
                    required
                    placeholder="<?= h((string) ($user['email'] ?? '')) ?>"
                    class="w-full rounded-lg border border-red-300/30 bg-black/20 px-4 py-3 text-white placeholder:text-red-100/40 focus:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-300/40"
                >
            </div>

            <div>
                <label for="delete-account-phrase" class="block text-sm font-medium text-white mb-2">
                    <?= h(__('profile.delete_account_phrase_label')) ?>
                </label>
                <input
                    id="delete-account-phrase"
                    name="confirm_phrase"
                    type="text"
                    autocapitalize="characters"
                    autocomplete="off"
                    spellcheck="false"
                    required
                    placeholder="DELETE"
                    class="w-full rounded-lg border border-red-300/30 bg-black/20 px-4 py-3 text-white placeholder:text-red-100/40 focus:border-red-200 focus:outline-none focus:ring-2 focus:ring-red-300/40"
                >
                <p class="text-xs text-red-100/70 mt-2"><?= h(__('profile.delete_account_phrase_help')) ?></p>
            </div>

            <div class="md:col-span-2 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <p class="text-sm text-red-100/80"><?= h(__('profile.delete_account_confirm')) ?></p>
                <button
                    type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-red-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-red-400"
                >
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span><?= h(__('profile.delete_account_submit')) ?></span>
                </button>
            </div>
        </form>
    </section>

    <!-- Tabs -->
    <div class="border-b border-white/10 mb-6">
        <nav class="flex gap-1 overflow-x-auto" role="tablist" aria-label="Profile sections">
            <a href="?tab=favorites"
               role="tab"
               aria-selected="<?= $activeTab === 'favorites' ? 'true' : 'false' ?>"
               class="flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors <?= $activeTab === 'favorites' ? 'border-brand-yellow text-brand-yellow' : 'border-transparent text-gray-400 hover:text-white hover:border-white/30' ?>">
                <i data-lucide="heart" class="w-4 h-4"></i>
                <span><?= h(__('profile.favorites')) ?></span>
                <span class="bg-white/10 text-gray-300 text-xs px-2 py-0.5 rounded-full"><?= $stats['favorites'] ?></span>
            </a>
            <a href="?tab=reviews"
               role="tab"
               aria-selected="<?= $activeTab === 'reviews' ? 'true' : 'false' ?>"
               class="flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors <?= $activeTab === 'reviews' ? 'border-brand-yellow text-brand-yellow' : 'border-transparent text-gray-400 hover:text-white hover:border-white/30' ?>">
                <i data-lucide="star" class="w-4 h-4"></i>
                <span><?= h(__('profile.reviews')) ?></span>
                <span class="bg-white/10 text-gray-300 text-xs px-2 py-0.5 rounded-full"><?= $stats['reviews'] ?></span>
            </a>
            <a href="?tab=photos"
               role="tab"
               aria-selected="<?= $activeTab === 'photos' ? 'true' : 'false' ?>"
               class="flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors <?= $activeTab === 'photos' ? 'border-brand-yellow text-brand-yellow' : 'border-transparent text-gray-400 hover:text-white hover:border-white/30' ?>">
                <i data-lucide="image" class="w-4 h-4"></i>
                <span><?= h(__('profile.photos')) ?></span>
                <span class="bg-white/10 text-gray-300 text-xs px-2 py-0.5 rounded-full"><?= $stats['photos'] ?></span>
            </a>
            <a href="?tab=checkins"
               role="tab"
               aria-selected="<?= $activeTab === 'checkins' ? 'true' : 'false' ?>"
               class="flex items-center gap-2 px-4 py-3 text-sm font-medium whitespace-nowrap border-b-2 transition-colors <?= $activeTab === 'checkins' ? 'border-brand-yellow text-brand-yellow' : 'border-transparent text-gray-400 hover:text-white hover:border-white/30' ?>">
                <i data-lucide="map-pin" class="w-4 h-4"></i>
                <span><?= h(__('profile.checkins')) ?></span>
                <span class="bg-white/10 text-gray-300 text-xs px-2 py-0.5 rounded-full"><?= $stats['checkins'] ?></span>
            </a>
        </nav>
    </div>

    <!-- Tab Content -->
    <div role="tabpanel">
        <?php if ($activeTab === 'favorites'): ?>
        <!-- Favorites Tab -->
        <?php if (empty($favorites)): ?>
        <div class="text-center py-16 bg-white/5 border border-white/10 rounded-xl">
            <div class="text-6xl mb-4">❤️</div>
            <h2 class="text-xl font-semibold text-white mb-2"><?= h(__('profile.no_favorites')) ?></h2>
            <p class="text-gray-400 mb-6"><?= h(__('profile.no_favorites_cta')) ?></p>
            <a href="/" class="inline-block bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <?= h(__('profile.explore_beaches')) ?>
            </a>
        </div>
        <?php else: ?>
        <div id="beach-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php
            $beaches = $favorites;
            foreach ($beaches as $beach):
                $distance = null;
                $isFavorite = true;
                include APP_ROOT . '/components/beach-card.php';
            endforeach;
            ?>
        </div>
        <?php endif; ?>

        <?php elseif ($activeTab === 'reviews'): ?>
        <!-- Reviews Tab -->
        <?php if (empty($reviews)): ?>
        <div class="text-center py-16 bg-white/5 border border-white/10 rounded-xl">
            <div class="text-6xl mb-4">⭐</div>
            <h2 class="text-xl font-semibold text-white mb-2"><?= h(__('profile.no_reviews')) ?></h2>
            <p class="text-gray-400 mb-6"><?= h(__('profile.no_reviews_cta')) ?></p>
            <a href="/" class="inline-block bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <?= h(__('profile.find_beach_review')) ?>
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-4">
            <?php foreach ($reviews as $review): ?>
            <div class="bg-brand-darker/50 backdrop-blur-md rounded-xl border border-white/10 p-5 hover:border-brand-yellow/30 transition-all">
                <div class="flex items-start gap-4">
                    <!-- Beach Image -->
                    <a href="/beach/<?= h($review['beach_slug']) ?>" class="flex-shrink-0">
                        <img src="<?= h(getThumbnailUrl($review['beach_image'] ?? '/images/beaches/placeholder-beach.webp')) ?>"
                             alt="<?= h($review['beach_name']) ?>"
                             class="w-20 h-20 rounded-lg object-cover">
                    </a>

                    <!-- Review Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <a href="/beach/<?= h($review['beach_slug']) ?>" class="font-semibold text-white hover:text-brand-yellow transition-colors">
                                    <?= h($review['beach_name']) ?>
                                </a>
                                <div class="flex items-center gap-2 mt-1">
                                    <!-- Stars -->
                                    <div class="flex text-amber-400 text-sm">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span><?= $i <= $review['rating'] ? '★' : '☆' ?></span>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-sm text-gray-500">
                                        <?= date('M j, Y', strtotime($review['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($review['title'])): ?>
                        <h3 class="font-medium text-white mt-2"><?= h($review['title']) ?></h3>
                        <?php endif; ?>

                        <?php if (!empty($review['review_text'])): ?>
                        <p class="text-gray-400 text-sm mt-1 line-clamp-2"><?= h($review['review_text']) ?></p>
                        <?php endif; ?>

                        <?php if (!empty($review['helpful_count'])): ?>
                        <div class="mt-2 text-xs text-gray-500">
                            <i data-lucide="thumbs-up" class="w-3 h-3 inline-block"></i>
                            <?= h(__('profile.found_helpful', ['count' => $review['helpful_count']])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($activeTab === 'photos'): ?>
        <!-- Photos Tab -->
        <?php if (empty($photos)): ?>
        <div class="text-center py-16 bg-white/5 border border-white/10 rounded-xl">
            <div class="text-6xl mb-4">📷</div>
            <h2 class="text-xl font-semibold text-white mb-2"><?= h(__('profile.no_photos')) ?></h2>
            <p class="text-gray-400 mb-6"><?= h(__('profile.no_photos_cta')) ?></p>
            <a href="/" class="inline-block bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <?= h(__('profile.find_beach')) ?>
            </a>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
            <?php foreach ($photos as $photo): ?>
            <div class="group relative aspect-square rounded-xl overflow-hidden bg-brand-dark">
                <img src="<?= h($photo['thumbnail_url'] ?? $photo['photo_url']) ?>"
                     alt="<?= h(__('profile.photo_at', ['name' => $photo['beach_name']])) ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                     loading="lazy">
                <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity">
                    <div class="absolute bottom-0 left-0 right-0 p-3">
                        <a href="/beach/<?= h($photo['beach_slug']) ?>" class="text-white text-sm font-medium hover:underline">
                            <?= h($photo['beach_name']) ?>
                        </a>
                        <p class="text-white/70 text-xs mt-0.5">
                            <?= date('M j, Y', strtotime($photo['created_at'])) ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php elseif ($activeTab === 'checkins'): ?>
        <!-- Check-ins Tab -->
        <?php if (empty($checkins)): ?>
        <div class="text-center py-16 bg-white/5 border border-white/10 rounded-xl">
            <div class="text-6xl mb-4">📍</div>
            <h2 class="text-xl font-semibold text-white mb-2"><?= h(__('profile.no_checkins')) ?></h2>
            <p class="text-gray-400 mb-6"><?= h(__('profile.no_checkins_cta')) ?></p>
            <a href="/" class="inline-block bg-brand-yellow hover:bg-yellow-300 text-brand-darker px-6 py-3 rounded-lg font-medium transition-colors">
                <?= h(__('profile.explore_beaches')) ?>
            </a>
        </div>
        <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($checkins as $checkin): ?>
            <div class="bg-brand-darker/50 backdrop-blur-md rounded-lg border border-white/10 p-4 flex items-center gap-4 hover:border-brand-yellow/30 transition-all">
                <!-- Beach Image -->
                <a href="/beach/<?= h($checkin['beach_slug']) ?>" class="flex-shrink-0">
                    <img src="<?= h(getThumbnailUrl($checkin['beach_image'] ?? '/images/beaches/placeholder-beach.webp')) ?>"
                         alt="<?= h($checkin['beach_name']) ?>"
                         class="w-14 h-14 rounded-lg object-cover">
                </a>

                <!-- Check-in Info -->
                <div class="flex-1 min-w-0">
                    <a href="/beach/<?= h($checkin['beach_slug']) ?>" class="font-medium text-white hover:text-brand-yellow transition-colors">
                        <?= h($checkin['beach_name']) ?>
                    </a>
                    <div class="flex flex-wrap items-center gap-2 mt-1">
                        <span class="text-xs text-gray-500">
                            <?= date('M j, Y \a\t g:i A', strtotime($checkin['created_at'])) ?>
                        </span>
                        <?php if (!empty($checkin['crowd_level'])): ?>
                        <span class="inline-flex items-center gap-1 text-xs bg-blue-500/20 text-blue-400 px-2 py-0.5 rounded-full">
                            <i data-lucide="users" class="w-3 h-3"></i>
                            <?= h(ucfirst($checkin['crowd_level'])) ?>
                        </span>
                        <?php endif; ?>
                        <?php if (!empty($checkin['weather'])): ?>
                        <span class="inline-flex items-center gap-1 text-xs bg-amber-500/20 text-amber-400 px-2 py-0.5 rounded-full">
                            <?= h(ucfirst($checkin['weather'])) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Check-in Icon -->
                <div class="flex-shrink-0 text-green-400">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Beach Details Drawer -->
<div id="beach-drawer" class="drawer-overlay" role="dialog" aria-modal="true" aria-label="Beach details" data-action="closeBeachDrawer" data-action-args='["__event__"]'>
    <div class="drawer-content" data-action-stop data-action="noop" data-on="click">
        <div id="drawer-content-inner"></div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="share-modal" role="dialog" aria-modal="true" aria-labelledby="share-modal-title" data-action="closeShareModal">
    <div class="share-modal-content" data-action-stop data-action="noop" data-on="click">
        <div class="flex justify-between items-center mb-4">
            <h3 id="share-modal-title" class="text-lg font-semibold"><?= h(__('profile.share_beach')) ?></h3>
            <button data-action="closeShareModal" class="text-gray-400 hover:text-gray-600" aria-label="Close">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="share-modal-body"></div>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
window.AJIApp = {
    beaches: <?= json_encode($favorites) ?>,
    userFavorites: <?= json_encode($userFavorites) ?>,
    isAuthenticated: true,
    csrfToken: <?= json_encode(csrfToken()) ?>
};
</script>

<?php include APP_ROOT . '/components/footer.php'; ?>
