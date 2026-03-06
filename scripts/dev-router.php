<?php
declare(strict_types=1);

$publicRoot = realpath(__DIR__ . '/../public');
if ($publicRoot === false) {
    http_response_code(500);
    echo "public/ docroot not found\n";
    return true;
}

require_once __DIR__ . '/../inc/locale_routes.php';

// Ensure public entrypoints can reliably load ../bootstrap.php via DOCUMENT_ROOT.
$_SERVER['DOCUMENT_ROOT'] = $publicRoot;

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$uriPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
$uriPath = rawurldecode($uriPath);

$queryString = $_SERVER['QUERY_STRING'] ?? '';
if ($uriPath === '' || $uriPath[0] !== '/') {
    $uriPath = '/' . ltrim($uriPath, '/');
}

$canonicalPath = normalizeLocalePath($uriPath);
if ($canonicalPath !== $uriPath) {
    $target = $canonicalPath;
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

$requestedPath = $publicRoot . $uriPath;

// Canonical redirects: .php -> extensionless public URLs.
if (preg_match('~^/(best-beaches|best-beaches-san-juan|best-snorkeling-beaches|best-surfing-beaches|best-family-beaches|beaches-near-san-juan|beaches-near-san-juan-airport|hidden-beaches-puerto-rico)\.php$~', $uriPath, $matches)) {
    $target = '/' . $matches[1];
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

if (preg_match('~^/(quiz|quiz-results|compare|offline|login|logout|verify|favorites|profile|onboarding|terms|privacy|go)\.php$~', $uriPath, $matches)) {
    $target = '/' . $matches[1];
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

if (preg_match('~^/guides/([a-z0-9-]+)\.php$~', $uriPath, $matches)) {
    $target = '/guides/' . $matches[1];
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

if ($uriPath === '/admin/index.php') {
    $target = '/admin';
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

if (preg_match('~^/admin/(beaches|reviews|users|emails|place-id-audit|referrals)\.php$~', $uriPath, $matches)) {
    $target = '/admin/' . $matches[1];
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

if ($uriPath === '/guides/index.php') {
    $target = '/guides/';
    if ($queryString !== '') {
        $target .= '?' . $queryString;
    }
    header('Location: ' . $target, true, 301);
    return true;
}

// Serve existing static files directly.
if (is_file($requestedPath)) {
    return false;
}

// Serve directory indexes when present (ex: /guides/ -> /guides/index.php).
if (is_dir($requestedPath)) {
    $indexPhp = rtrim($requestedPath, '/') . '/index.php';
    if (is_file($indexPhp)) {
        require $indexPhp;
        return true;
    }
}

// Nginx-equivalent rewrites used by this project.
if ($uriPath === '/sitemap.xml') {
    require $publicRoot . '/sitemap.php';
    return true;
}

if (preg_match('~^/go/([a-z0-9-]+)$~', $uriPath, $matches)) {
    $_GET['c'] = $matches[1];
    require $publicRoot . '/go.php';
    return true;
}

if (preg_match('~^/guides/([a-z0-9-]+)$~', $uriPath, $matches)) {
    $_GET['slug'] = $matches[1];
    require $publicRoot . '/guides/cms-router.php';
    return true;
}

if (preg_match('~^/es/guias/([a-z0-9-]+)$~', $uriPath)) {
    $match = localeRouteMatch($uriPath);
    if (is_array($match)) {
        $routeKey = (string) ($match['route_key'] ?? '');
        if (str_starts_with($routeKey, 'guide_')) {
            $routes = localeRoutes();
            if (isset($routes[$routeKey]['script'])) {
                $scriptPath = (string) $routes[$routeKey]['script'];
                $slug = pathinfo($scriptPath, PATHINFO_FILENAME);
                if ($slug !== '' && $slug !== 'index' && $slug !== 'cms-router') {
                    $_GET['slug'] = $slug;
                    require $publicRoot . '/guides/cms-router.php';
                    return true;
                }
            }
        }
    }
}

$localizedRouteTarget = resolvePublicScriptFromLocalizedPath($uriPath);
if (is_array($localizedRouteTarget) && isset($localizedRouteTarget['script'])) {
    $scriptPath = (string) $localizedRouteTarget['script'];
    $queryMap = isset($localizedRouteTarget['query']) && is_array($localizedRouteTarget['query'])
        ? $localizedRouteTarget['query']
        : [];
    foreach ($queryMap as $key => $value) {
        $_GET[(string) $key] = $value;
    }
    require $publicRoot . $scriptPath;
    return true;
}

if (preg_match('~^/beach/([a-z0-9-]+)$~', $uriPath, $matches)) {
    $_GET['slug'] = $matches[1];
    require $publicRoot . '/beach.php';
    return true;
}

if (preg_match('~^/beaches-in-([a-z-]+)$~', $uriPath, $matches)) {
    $_GET['m'] = $matches[1];
    require $publicRoot . '/municipality.php';
    return true;
}

// Extensionless editorial URLs (try /path.php when /path is requested).
if (strpos(basename($uriPath), '.') === false) {
    $candidate = $publicRoot . $uriPath . '.php';
    if (is_file($candidate)) {
        require $candidate;
        return true;
    }
}

// Fallback to homepage.
require $publicRoot . '/index.php';
return true;
