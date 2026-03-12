<?php
/**
 * Site Header Component
 * Include at the top of all pages
 */

require_once __DIR__ . '/../inc/session.php';
if (isset($_COOKIE['AJI_APP_SESSION']) && session_status() === PHP_SESSION_NONE) {
    session_cache_limiter('');   // Prevent PHP from emitting Pragma/Expires
    session_start();
}
require_once __DIR__ . '/../inc/security_headers.php';
require_once __DIR__ . '/../inc/helpers.php';
require_once __DIR__ . '/../inc/i18n.php';
require_once __DIR__ . '/../inc/locale_routes.php';

$user = currentUser();
$appName = $_ENV['APP_NAME'] ?? 'AJI';
$appUrl = getPublicBaseUrl();
$currentLang = getCurrentLanguage();
$allowedBodyVariants = ['default', 'collection-light', 'collection-dark'];
$requestedBodyVariant = isset($bodyVariant) ? (string) $bodyVariant : 'default';
$bodyVariant = in_array($requestedBodyVariant, $allowedBodyVariants, true) ? $requestedBodyVariant : 'default';
$bodyClasses = 'min-h-screen flex flex-col font-sans';
if ($bodyVariant === 'collection-light') {
    $bodyClasses .= ' collection-light bg-gray-100 text-gray-900';
    $htmlTheme = 'light';
} elseif ($bodyVariant === 'collection-dark') {
    $bodyClasses .= ' collection-dark bg-brand-darker text-brand-text';
    $htmlTheme = 'dark';
} else {
    $bodyClasses .= ' bg-brand-darker text-brand-text';
    $htmlTheme = 'dark';
}
?>
<!DOCTYPE html>
<html lang="<?= getHtmlLang() ?>" data-theme="<?= h($htmlTheme) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' | ' : '' ?><?= h($appName) ?></title>

    <?php if (isset($pageDescription)): ?>
    <meta name="description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1a2c32">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="AJI">
    <link rel="manifest" href="/manifest.json">
    <link rel="apple-touch-icon" href="/assets/icons/icon-152x152.png">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="icon" href="/assets/icons/icon-96x96.png" sizes="96x96" type="image/png">

    <!-- Open Graph / Social -->
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= h($appName) ?>">
    <meta property="og:title" content="<?= isset($pageTitle) ? h($pageTitle) : h($appName) ?>">
    <?php if (isset($pageDescription)): ?>
    <meta property="og:description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>
    <?php if (isset($ogImage)): ?>
    <meta property="og:image" content="<?= h($ogImage) ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="<?= isset($pageTitle) ? h($pageTitle) : 'Puerto Rico' ?>">
    <?php else: ?>
    <meta property="og:image" content="<?= h($appUrl) ?>/assets/icons/icon-512x512.png">
    <meta property="og:image:width" content="512">
    <meta property="og:image:height" content="512">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:site" content="@AJIApp">
    <meta name="twitter:creator" content="@AJIApp">
    <meta name="twitter:title" content="<?= isset($pageTitle) ? h($pageTitle) : h($appName) ?>">
    <?php if (isset($pageDescription)): ?>
    <meta name="twitter:description" content="<?= h($pageDescription) ?>">
    <?php endif; ?>
    <?php if (isset($ogImage)): ?>
    <meta name="twitter:image" content="<?= h($ogImage) ?>">
    <meta name="twitter:image:alt" content="<?= isset($pageTitle) ? h($pageTitle) : 'Puerto Rico' ?>">
    <?php else: ?>
    <meta name="twitter:image" content="<?= h($appUrl) ?>/assets/icons/icon-512x512.png">
    <?php endif; ?>

    <!-- Canonical URL -->
    <?php
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $canonicalPath = '';

    $normalizeCanonicalPath = static function (string $path): string {
        if ($path === '' || $path[0] !== '/') {
            $path = '/' . ltrim($path, '/');
        }

        if ($path === '/index.php') {
            return '/';
        }

        if ($path === '/sitemap.php') {
            return '/sitemap.xml';
        }

        // Canonicalize extensionless public pages (keep this as an allowlist to avoid surprises).
        $extensionlessPages = [
            '/best-beaches.php',
            '/best-beaches-san-juan.php',
            '/best-snorkeling-beaches.php',
            '/best-surfing-beaches.php',
            '/best-family-beaches.php',
            '/beaches-near-san-juan.php',
            '/beaches-near-san-juan-airport.php',
            '/hidden-beaches-puerto-rico.php',
            '/quiz.php',
            '/quiz-results.php',
            '/compare.php',
            '/offline.php',
            '/login.php',
            '/logout.php',
            '/verify.php',
            '/favorites.php',
            '/profile.php',
            '/onboarding.php',
            '/terms.php',
            '/privacy.php',
        ];

        if (in_array($path, $extensionlessPages, true)) {
            return substr($path, 0, -4);
        }

        if ($path === '/guides/index.php') {
            return '/guides/';
        }

        if (str_starts_with($path, '/guides/') && str_ends_with($path, '.php')) {
            return substr($path, 0, -4);
        }

        return $path;
    };

    if (isset($canonicalUrl)) {
        $providedCanonical = (string) $canonicalUrl;
        if (preg_match('#^https?://#i', $providedCanonical)) {
            $parsed = parse_url($providedCanonical);
            $canonicalPath = (string) ($parsed['path'] ?? '/');
        } else {
            $canonicalPath = $providedCanonical;
        }
    } elseif (isset($beach['slug'])) {
        $canonicalPath = '/beach/' . $beach['slug'];
    } elseif ($requestPath === '/' || $requestPath === '/index.php') {
        // Homepage and filtered views canonicalize to homepage.
        $canonicalPath = '/';
    } else {
        $canonicalPath = $requestPath;
    }

    $canonicalPath = $normalizeCanonicalPath($canonicalPath);
    $canonicalPath = localizePath($canonicalPath, $currentLang);
    $canonical = absoluteUrl($canonicalPath);
    $canonicalEn = absoluteUrl(localizePath($canonicalPath, 'en'));
    $canonicalEs = absoluteUrl(localizePath($canonicalPath, 'es'));
    $emitHreflang = isIndexableLocalePath($canonicalPath);

    // Normalize before checking indexability so /login and /login.php behave the same.
    $normalizedRequestPath = $normalizeCanonicalPath($requestPath);
    $requestIndexable = isIndexableLocalePath($normalizedRequestPath);
    $robots = $requestIndexable
        ? 'index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1'
        : 'noindex, nofollow, noarchive';
    if (isset($robotsOverride) && is_string($robotsOverride) && trim($robotsOverride) !== '') {
        $robots = trim($robotsOverride);
    }
    ?>
    <link rel="canonical" href="<?= h($canonical) ?>">
    <meta property="og:url" content="<?= h($canonical) ?>">
    <?php if ($emitHreflang): ?>
    <link rel="alternate" hreflang="en" href="<?= h($canonicalEn) ?>">
    <link rel="alternate" hreflang="es-PR" href="<?= h($canonicalEs) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= h($canonicalEn) ?>">
    <?php endif; ?>

    <!-- Robots Meta Tags -->
    <meta name="robots" content="<?= h($robots) ?>">

    <?php
    $umamiEnabled = function_exists('envBool') ? envBool('UMAMI_ENABLED', false) : false;
    $umamiWebsiteId = function_exists('env') ? (string) (env('UMAMI_WEBSITE_ID') ?? '') : '';
    $umamiScriptUrl = function_exists('env') ? (string) (env('UMAMI_SCRIPT_URL', 'https://cloud.umami.is/script.js') ?? 'https://cloud.umami.is/script.js') : 'https://cloud.umami.is/script.js';
    $umamiDomains = function_exists('env') ? (string) (env('UMAMI_DOMAINS', '') ?? '') : '';
    ?>
    <?php if ($umamiEnabled && $umamiWebsiteId !== ''): ?>
    <script defer src="<?= h($umamiScriptUrl) ?>"
            data-website-id="<?= h($umamiWebsiteId) ?>"
            <?php if ($umamiDomains !== ''): ?>data-domains="<?= h($umamiDomains) ?>"<?php endif; ?>
            <?= cspNonceAttr() ?>></script>
    <?php endif; ?>

    <!-- Geographic Meta Tags -->
    <meta name="geo.region" content="US-PR">
    <meta name="geo.placename" content="Puerto Rico">
    <meta name="geo.position" content="18.2208;-66.5901">
    <meta name="ICBM" content="18.2208, -66.5901">

    <!-- Preconnect to CDNs (placed early for optimal performance) -->
    <link rel="preconnect" href="https://basemaps.cartocdn.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://unpkg.com" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- DNS Prefetch for third-party resources -->
    <link rel="dns-prefetch" href="https://basemaps.cartocdn.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

    <!-- Preload critical CSS -->
    <link rel="preload" href="/assets/css/tailwind.min.css?v=3.5" as="style">
    <link rel="preload" href="/assets/css/styles.css?v=3.8" as="style">

    <!-- Inter + Playfair Display Fonts - loaded asynchronously to avoid render blocking -->
    <link rel="preload" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,400;1,500;1,600;1,700&display=swap" as="style" data-lazy-style>
    <noscript><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:ital,wght@1,400;1,500;1,600;1,700&display=swap" rel="stylesheet"></noscript>

    <!-- Tailwind CSS (local build - no render-blocking JS) -->
    <link rel="stylesheet" href="/assets/css/tailwind.min.css?v=3.5">

    <?php if (!isset($skipMapCSS) || !$skipMapCSS): ?>
    <!-- MapLibre GL CSS - loaded asynchronously to avoid render blocking -->
    <link rel="preload"
          href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css"
          as="style"
          data-lazy-style
          integrity="sha384-p5cy4wHtKSqjnLUNjQ+8ffCwUp0vlLS+6lg1lc3qqXax2E1EmVCMCAimU+R0MOZH"
          crossorigin="anonymous">
    <noscript><link href="https://unpkg.com/maplibre-gl@3.6.2/dist/maplibre-gl.css" rel="stylesheet"></noscript>
    <?php endif; ?>

    <!-- Lucide Icons (pinned version, deferred for performance) -->
    <script defer
            src="https://unpkg.com/lucide@0.294.0/dist/umd/lucide.min.js"
            integrity="sha384-43WP8IQ+5H0ncT+LNM4dZnu+hPINYmeOuNMhTvHfszzXdFjBEji77gkq7TyjQl/U"
            crossorigin="anonymous"
            <?= cspNonceAttr() ?>></script>

    <!-- CSP event delegation (must load before page scripts) -->
    <script src="/assets/js/csp-bindings.js" <?= cspNonceAttr() ?>></script>

    <!-- Custom styles -->
    <link rel="stylesheet" href="/assets/css/styles.css?v=3.8">

    <!-- Deferred scripts (non-blocking) -->
    <script defer
            src="https://unpkg.com/htmx.org@1.9.10/dist/htmx.min.js"
            integrity="sha384-D1Kt99CQMDuVetoL1lrYwg5t+9QdHe7NLX/SoJYkXDFfX37iInKRy5xLSi8nO7UC"
            crossorigin="anonymous"
            <?= cspNonceAttr() ?>></script>

    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="<?= h($bodyClasses) ?>">
    <?php include __DIR__ . '/nav.php'; ?>

    <!-- Main Content -->
    <main id="main-content" class="flex-1" role="main" aria-label="Page content">
