<?php
/**
 * Guide map panel (list -> in-page map toggle scoped by explicit beach IDs).
 *
 * Expected variables:
 * - $guideMapIds (array<int|string>)
 * - $guideMapTitle (string)
 * - $guideMapDescription (string)
 * - $guideMapButtonLabel (string)
 * Optional:
 * - $guideMapEmptyNotice (string)
 */

if (!function_exists('__')) { require_once __DIR__ . '/../inc/i18n.php'; }

$guideMapIdsRaw = isset($guideMapIds) && is_array($guideMapIds) ? $guideMapIds : [];
$guideMapIds = [];
foreach ($guideMapIdsRaw as $rawId) {
        $candidate = is_scalar($rawId) ? trim((string)$rawId) : '';
        if ($candidate === '' || !preg_match('/^[A-Za-z0-9_-]{1,64}$/', $candidate)) {
            continue;
        }
        if (!in_array($candidate, $guideMapIds, true)) {
            $guideMapIds[] = $candidate;
        }
}
$guideMapTitle = isset($guideMapTitle) ? (string)$guideMapTitle : __('guide_map.map_view');
$guideMapDescription = isset($guideMapDescription) ? (string)$guideMapDescription : __('guide_map.map_desc');
$guideMapButtonLabel = isset($guideMapButtonLabel) ? (string)$guideMapButtonLabel : __('guide_map.view_map');
$guideMapEmptyNotice = isset($guideMapEmptyNotice) ? (string)$guideMapEmptyNotice : __('guide_map.empty_notice');
$hasGuideMap = !empty($guideMapIds);
?>
<div id="guide-list-view" class="">
    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
        <h2 class="text-2xl font-bold text-gray-900 mb-4"><?= h($guideMapTitle) ?></h2>
        <p class="text-gray-700 mb-6"><?= h($guideMapDescription) ?></p>
        <?php if ($hasGuideMap): ?>
        <a href="?view=map#guide-map-view"
           data-context-map-action="show-map"
           class="inline-block bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors">
            <?= h($guideMapButtonLabel) ?>
        </a>
        <?php else: ?>
        <span class="inline-block bg-gray-300 text-gray-600 px-6 py-3 rounded-lg font-semibold cursor-not-allowed">
            <?= __('guide_map.map_unavailable') ?>
        </span>
        <p class="text-sm text-gray-500 mt-3"><?= h($guideMapEmptyNotice) ?></p>
        <?php endif; ?>
    </div>
</div>

<?php if ($hasGuideMap): ?>
<div id="guide-map-view" class="hidden bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-8 mt-12">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <h2 class="text-2xl font-bold text-gray-900"><?= __('guide_map.map_view') ?></h2>
        <a href="?view=list#guide-list-view"
           data-context-map-action="show-list"
           class="inline-flex items-center justify-center bg-white text-gray-700 border border-gray-300 px-4 py-2 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
            <?= __('guide_map.list_view') ?>
        </a>
    </div>
    <div id="guide-map-loading" class="text-sm text-gray-500 mb-3"><?= __('guide_map.loading_map') ?></div>
    <div id="guide-map-error" class="hidden text-sm text-red-600 mb-3"><?= __('guide_map.map_error') ?></div>
    <div id="guide-map-empty" class="hidden text-sm text-gray-500 mb-3"><?= h($guideMapEmptyNotice) ?></div>
    <div id="guide-map-container" class="rounded-xl overflow-hidden border border-green-200 bg-white" style="height: 520px;"></div>
</div>

<script <?= cspNonceAttr() ?>>
window.BF_MAP_CONTEXT = {
    mode: "ids",
    ids: <?= json_encode($guideMapIds) ?>,
    listViewId: "guide-list-view",
    mapViewId: "guide-map-view",
    mapContainerId: "guide-map-container",
    mapLoadingId: "guide-map-loading",
    mapErrorId: "guide-map-error",
    mapEmptyId: "guide-map-empty",
    autoScroll: true,
    updateUrl: true
};
</script>
<?php endif; ?>
