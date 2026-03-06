<?php
/**
 * Guide Hero Component
 * Consistent dark gradient hero for all guide pages (matches homepage palette)
 *
 * Required variables:
 * - $pageTitle (string): Main heading
 * - $pageDescription (string): Subtitle text
 * Optional variables:
 * - $breadcrumbs (array): Breadcrumb navigation items
 * - $heroImage (string): URL of a background photo; when set, shown behind a dark overlay
 */
$bgStyle = '';
if (!empty($heroImage)) {
    $esc = htmlspecialchars($heroImage, ENT_QUOTES, 'UTF-8');
    $bgStyle = ' style="background-image: linear-gradient(rgba(15,23,42,0.65),rgba(15,23,42,0.75)), url(' . $esc . '); background-size: cover; background-position: center;"';
}
?>
<section class="hero-gradient-dark text-white py-16"<?= $bgStyle ?>>
    <div class="container mx-auto px-4 container-padding">
        <?php if (isset($breadcrumbs) && !empty($breadcrumbs)): ?>
        <nav class="text-sm mb-6 text-gray-200" aria-label="Breadcrumb">
            <?php foreach ($breadcrumbs as $index => $crumb): ?>
                <?php if ($index > 0): ?><span class="mx-2" aria-hidden="true">&gt;</span><?php endif; ?>
                <?php if (isset($crumb['url'])): ?>
                    <a href="<?= h($crumb['url']) ?>" class="hover:text-white"><?= h($crumb['name']) ?></a>
                <?php else: ?>
                    <span aria-current="page"><?= h($crumb['name']) ?></span>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <h1 class="text-4xl md:text-5xl font-bold mb-4"><?= h($pageTitle) ?></h1>
        <p class="text-xl text-gray-100 max-w-3xl"><?= h($pageDescription) ?></p>
        <?php if (!empty($heroCtas)): ?>
        <?= $heroCtas ?>
        <?php endif; ?>
    </div>
</section>
<?php unset($heroCtas); ?>
