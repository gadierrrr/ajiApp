<?php
/**
 * Shared collection explorer block (hero + toolbar + results).
 *
 * Required:
 * - $collectionKey (string)
 * - $collectionData (array)
 * - $collectionContext (array)
 * - $collectionState (array)
 *
 * Optional:
 * - $collectionAnchorId (string)
 * - $userFavorites (array)
 */

$collectionAnchorId = $collectionAnchorId ?? 'collection-explorer';
$userFavorites = $userFavorites ?? [];

$_t = function_exists('__');
$_lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
?>
<section id="<?= h($collectionAnchorId) ?>" class="collection-page scroll-mt-24">
    <div id="collection-explorer-root"
         class="collection-page__inner"
         data-collection="<?= h($collectionKey) ?>"
         data-default-sort="<?= h($collectionContext['default_sort'] ?? 'rating') ?>"
         data-default-limit="<?= h((string)($collectionContext['default_limit'] ?? 15)) ?>"
         data-authenticated="<?= isAuthenticated() ? '1' : '0' ?>"
         data-csrf="<?= h(csrfToken()) ?>">
        <?php include __DIR__ . '/hero.php'; ?>
        <?php include __DIR__ . '/toolbar.php'; ?>
        <div class="collection-page__flow">
            <div class="collection-page__capture">
                <?php
                $contextType = 'collection';
                $contextKey = (string) $collectionKey;
                $filtersQuery = (string) ($_SERVER['QUERY_STRING'] ?? '');
                $title = $_t ? __('collection.send_list_title') : 'Send me this list';
                $subtitle = $_t ? __('collection.send_list_subtitle') : 'Get the beaches and Google Maps links in your inbox (no account required).';
                include APP_ROOT . '/components/send-list-capture.php';
                ?>
            </div>
            <div id="collection-results" class="collection-page__results">
                <?php include __DIR__ . '/results.php'; ?>
            </div>
        </div>
    </div>
</section>
<script <?= cspNonceAttr() ?>>
window.BF_MAP_CONTEXT = {
    mode: "collection",
    collection: <?= json_encode((string)$collectionKey) ?>,
    filters: {
        q: <?= json_encode((string)($collectionState['q'] ?? '')) ?>,
        tags: <?= json_encode(array_values($collectionState['tags'] ?? [])) ?>,
        municipality: <?= json_encode((string)($collectionState['municipality'] ?? '')) ?>,
        sort: <?= json_encode((string)($collectionState['sort'] ?? ($collectionContext['default_sort'] ?? 'rating'))) ?>,
        include_all: <?= !empty($collectionState['include_all']) ? 'true' : 'false' ?>
    },
    listViewId: "collection-list-view",
    mapViewId: "collection-map-view",
    mapContainerId: "collection-map-container",
    mapLoadingId: "collection-map-loading",
    mapErrorId: "collection-map-error",
    mapEmptyId: "collection-map-empty",
    autoScroll: false,
    updateUrl: false,
    i18n: {
        viewDetails: <?= json_encode($_t ? __('collection.view_details') : 'View Details') ?>,
        directions: <?= json_encode($_t ? __('collection.get_directions') : 'Get Directions') ?>,
        addFavorite: <?= json_encode($_t ? __('collection.add_favorite') : 'Add to favorites') ?>,
        removeFavorite: <?= json_encode($_t ? __('collection.remove_favorite') : 'Remove from favorites') ?>
    },
    beachUrlPrefix: <?= json_encode($_lang === 'es' ? '/es/playa/' : '/beach/') ?>
};
</script>
