<?php
/**
 * XML Sitemap Generator
 * Generates sitemap.xml dynamically
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/locale_routes.php';

$appUrl = getPublicBaseUrl();

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Build lastmod lookup for static routes based on file modification time
$routeScriptLastmod = [];
foreach (localeRoutes() as $routeKey => $route) {
    if (!($route['indexable'] ?? false)) continue;
    $script = $route['script'] ?? '';
    if ($script !== '') {
        $scriptFile = $_SERVER['DOCUMENT_ROOT'] . $script;
        if (file_exists($scriptFile)) {
            $routeScriptLastmod[$routeKey] = date('Y-m-d', filemtime($scriptFile));
        }
    }
}
$fallbackDate = '2026-02-01';

// Pre-query municipality lastmod dates
$municipalityLastmod = [];
$muniRows = query("SELECT municipality, MAX(updated_at) as last_update FROM beaches WHERE publish_status = 'published' GROUP BY municipality");
foreach ($muniRows ?: [] as $row) {
    $municipalityLastmod[$row['municipality']] = $row['last_update']
        ? date('Y-m-d', strtotime($row['last_update']))
        : $fallbackDate;
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">

<?php
foreach (sitemapLocaleRoutes() as $entry):
    $localePaths = [$entry['en'], $entry['es']];
    // Determine lastmod from script file
    $routeLastmod = $fallbackDate;
    foreach (localeRoutes() as $routeKey => $route) {
        if (normalizeLocalePath((string)$route['en']) === $entry['en']) {
            $routeLastmod = $routeScriptLastmod[$routeKey] ?? $fallbackDate;
            break;
        }
    }
    foreach ($localePaths as $localePath):
?>
    <url>
        <loc><?= h($appUrl) ?><?= h($localePath) ?></loc>
        <lastmod><?= $routeLastmod ?></lastmod>
        <changefreq><?= h($entry['changefreq']) ?></changefreq>
        <priority><?= h($entry['priority']) ?></priority>
    </url>
<?php
    endforeach;
endforeach;
?>

    <!-- Individual Beach Pages -->
<?php
$beaches = query("
    SELECT slug, name, cover_image, updated_at
    FROM beaches
    WHERE publish_status = 'published'
    ORDER BY name
");

foreach ($beaches as $beach):
    $lastmod = $beach['updated_at'] ? date('Y-m-d', strtotime($beach['updated_at'])) : $fallbackDate;
    $imageUrl = strpos($beach['cover_image'], 'http') === 0
        ? $beach['cover_image']
        : $appUrl . $beach['cover_image'];
?>
    <url>
        <loc><?= h($appUrl) ?>/beach/<?= h($beach['slug']) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <?php if ($beach['cover_image'] && strpos($beach['cover_image'], 'placeholder') === false): ?>
        <image:image>
            <image:loc><?= h($imageUrl) ?></image:loc>
            <image:title><?= h($beach['name']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/es/playa/<?= h($beach['slug']) ?></loc>
        <lastmod><?= $lastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
        <?php if ($beach['cover_image'] && strpos($beach['cover_image'], 'placeholder') === false): ?>
        <image:image>
            <image:loc><?= h($imageUrl) ?></image:loc>
            <image:title><?= h($beach['name']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
<?php endforeach; ?>

    <!-- Municipality Landing Pages -->
<?php
$municipalities = array_unique(array_column(
    query("SELECT DISTINCT municipality FROM beaches WHERE publish_status = 'published' ORDER BY municipality"),
    'municipality'
));

foreach ($municipalities as $municipality):
    $slug = strtolower(str_replace(' ', '-', stripAccents($municipality)));
    $muniLastmod = $municipalityLastmod[$municipality] ?? $fallbackDate;
?>
    <url>
        <loc><?= h($appUrl) ?>/beaches-in-<?= h($slug) ?></loc>
        <lastmod><?= $muniLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/es/playas-en-<?= h($slug) ?></loc>
        <lastmod><?= $muniLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.7</priority>
    </url>
<?php endforeach; ?>

    <!-- Non-Beach Place Pages -->
<?php
require_once APP_ROOT . '/inc/place_types.php';

$placeTypeRoutes = [
    'river'      => ['en' => 'river',      'es' => 'rio'],
    'waterfall'  => ['en' => 'waterfall',   'es' => 'cascada'],
    'trail'      => ['en' => 'trail',       'es' => 'sendero'],
    'restaurant' => ['en' => 'restaurant',  'es' => 'restaurante'],
    'photo_spot' => ['en' => 'photo-spot',  'es' => 'punto-foto'],
];

foreach ($placeTypeRoutes as $typeKey => $prefixes):
    $table = PLACE_TYPES[$typeKey]['table'] ?? null;
    if (!$table) continue;
    $places = query("SELECT slug, name, cover_image, updated_at FROM {$table} WHERE publish_status = 'published' ORDER BY name");
    if (!$places) continue;

    foreach ($places as $p):
        $placeLastmod = $p['updated_at'] ? date('Y-m-d', strtotime($p['updated_at'])) : $fallbackDate;
        $placeImageUrl = (!empty($p['cover_image']) && strpos($p['cover_image'], 'http') === 0)
            ? $p['cover_image']
            : $appUrl . ($p['cover_image'] ?? '');
?>
    <url>
        <loc><?= h($appUrl) ?>/<?= h($prefixes['en']) ?>/<?= h($p['slug']) ?></loc>
        <lastmod><?= $placeLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
        <?php if (!empty($p['cover_image']) && strpos($p['cover_image'], 'placeholder') === false): ?>
        <image:image>
            <image:loc><?= h($placeImageUrl) ?></image:loc>
            <image:title><?= h($p['name']) ?></image:title>
        </image:image>
        <?php endif; ?>
    </url>
    <url>
        <loc><?= h($appUrl) ?>/es/<?= h($prefixes['es']) ?>/<?= h($p['slug']) ?></loc>
        <lastmod><?= $placeLastmod ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.6</priority>
    </url>
<?php
    endforeach;
endforeach;
?>

</urlset>
