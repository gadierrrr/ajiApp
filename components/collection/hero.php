<?php
/**
 * Collection explorer hero.
 *
 * Required:
 * - $collectionContext (array)
 */

$hero = $collectionContext['hero'] ?? [];
$heroTitle = $hero['title'] ?? 'Puerto Rico Beach Collections';
$heroSubtitle = $hero['subtitle'] ?? 'Explore curated beaches with filters and map view.';
$heroMeta = $hero['meta'] ?? '';

// Resolve translated hero strings from i18n if available
$pageKey = $collectionContext['page_key'] ?? '';
if ($pageKey !== '' && function_exists('__')) {
    $t = __('pages.' . $pageKey . '.hero_title');
    if ($t !== 'pages.' . $pageKey . '.hero_title') {
        $heroTitle = $t;
    }
    $t = __('pages.' . $pageKey . '.hero_subtitle');
    if ($t !== 'pages.' . $pageKey . '.hero_subtitle') {
        $heroSubtitle = $t;
    }
    $t = __('pages.' . $pageKey . '.hero_meta');
    if ($t !== 'pages.' . $pageKey . '.hero_meta') {
        $heroMeta = $t;
    }
}
?>
<section class="collection-hero">
    <div class="collection-hero__inner">
        <h1 class="collection-hero__title"><?= h($heroTitle) ?></h1>
        <p class="collection-hero__subtitle"><?= h($heroSubtitle) ?></p>
        <?php if ($heroMeta !== ''): ?>
        <p class="collection-hero__meta"><?= h($heroMeta) ?></p>
        <?php endif; ?>
    </div>
</section>
