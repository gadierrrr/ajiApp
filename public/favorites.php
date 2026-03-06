<?php
/**
 * User Favorites Page
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

// Require authentication
requireAuth();

$user = currentUser();
$pageTitle = __('favorites_page.title');

// Get user's favorite beaches
$favorites = query(
    'SELECT b.* FROM beaches b
     INNER JOIN user_favorites uf ON b.id = uf.beach_id
     WHERE uf.user_id = :user_id AND b.publish_status = "published"
     ORDER BY uf.created_at DESC',
    [':user_id' => $user['id']]
);

// Get tags and amenities for each beach
foreach ($favorites as &$beach) {
    $beach['tags'] = array_column(
        query('SELECT tag FROM beach_tags WHERE beach_id = :id', [':id' => $beach['id']]),
        'tag'
    );
    $beach['amenities'] = array_column(
        query('SELECT amenity FROM beach_amenities WHERE beach_id = :id', [':id' => $beach['id']]),
        'amenity'
    );
}

$userFavorites = array_column($favorites, 'id');

// Breadcrumbs
$breadcrumbs = [
    ['name' => __('nav.home'), 'url' => '/'],
    ['name' => __('profile.my_profile'), 'url' => '/profile'],
    ['name' => __('profile.favorites')]
];

include APP_ROOT . '/components/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Breadcrumbs -->
    <div class="mb-6">
        <?php include APP_ROOT . '/components/breadcrumbs.php'; ?>
    </div>
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900"><?= h(__('favorites_page.title')) ?></h1>
            <p class="text-gray-600 mt-1">
                <?php
                    $savedParts = explode('|', __('favorites_page.saved_count', ['count' => count($favorites)]));
                    echo h(count($favorites) === 1 ? $savedParts[0] : ($savedParts[1] ?? $savedParts[0]));
                ?>
            </p>
        </div>
        <a href="/" class="text-blue-600 hover:text-blue-700 font-medium">
            ← <?= h(__('favorites_page.explore_more')) ?>
        </a>
    </div>

    <?php if (empty($favorites)): ?>
    <div class="text-center py-16 bg-gray-50 rounded-xl">
        <div class="text-6xl mb-4">🏖️</div>
        <h2 class="text-xl font-semibold text-gray-700 mb-2"><?= h(__('profile.no_favorites')) ?></h2>
        <p class="text-gray-500 mb-6"><?= h(__('profile.no_favorites_cta')) ?></p>
        <a href="/" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-medium">
            <?= h(__('profile.explore_beaches')) ?>
        </a>
    </div>
    <?php else: ?>

    <div id="beach-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php
        $beaches = $favorites;
        $userLocation = null;
        foreach ($beaches as $beach):
            $distance = null;
            $isFavorite = true;
            include APP_ROOT . '/components/beach-card.php';
        endforeach;
        ?>
    </div>

    <?php endif; ?>
</div>

<!-- Beach Details Drawer -->
<div id="beach-drawer" class="drawer-overlay" data-action="closeBeachDrawer" data-action-args='["__event__"]'>
    <div class="drawer-content" data-action-stop data-action="noop" data-on="click">
        <div id="drawer-content-inner"></div>
    </div>
</div>

<!-- Share Modal -->
<div id="share-modal" class="share-modal" data-action="closeShareModal">
    <div class="share-modal-content" data-action-stop data-action="noop" data-on="click">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold"><?= h(__('profile.share_beach')) ?></h3>
            <button data-action="closeShareModal" class="text-gray-400 hover:text-gray-600">✕</button>
        </div>
        <div id="share-modal-body"></div>
    </div>
</div>

<script <?= cspNonceAttr() ?>>
window.BeachFinder = {
    beaches: <?= json_encode($favorites) ?>,
    userFavorites: <?= json_encode($userFavorites) ?>,
    isAuthenticated: true,
    csrfToken: <?= json_encode(csrfToken()) ?>
};
</script>

<?php include APP_ROOT . '/components/footer.php'; ?>
