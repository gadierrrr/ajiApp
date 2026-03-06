<?php
/**
 * Locale route map and URL helpers.
 *
 * This file maps public routes to:
 * - English path
 * - Spanish path
 * - Internal PHP script target
 * - Indexability and sitemap metadata
 */

if (defined('LOCALE_ROUTES_INCLUDED')) {
    return;
}
define('LOCALE_ROUTES_INCLUDED', true);

/**
 * Normalize a URL path for stable locale comparisons.
 */
function normalizeLocalePath(string $path): string
{
    // Treat input as a request path first (not a URL), then strip query/fragment.
    $qPos = strpos($path, '?');
    if ($qPos !== false) {
        $path = substr($path, 0, $qPos);
    }
    $hashPos = strpos($path, '#');
    if ($hashPos !== false) {
        $path = substr($path, 0, $hashPos);
    }

    if ($path === '') {
        $path = '/';
    }
    if ($path[0] !== '/') {
        $path = '/' . ltrim($path, '/');
    }

    $path = preg_replace('#/+#', '/', $path) ?? $path;

    if ($path === '/guides' || $path === '/guides/') {
        return '/guides/';
    }
    if ($path === '/es/guias' || $path === '/es/guias/') {
        return '/es/guias/';
    }

    if ($path !== '/') {
        $path = rtrim($path, '/');
    }

    return $path === '' ? '/' : $path;
}

/**
 * Static locale-aware route table.
 */
function localeRoutes(): array
{
    static $routes = null;
    if (is_array($routes)) {
        return $routes;
    }

    $routes = [
        'home' => [
            'en' => '/',
            'es' => '/es',
            'script' => '/index.php',
            'indexable' => true,
            'changefreq' => 'daily',
            'priority' => '1.0',
        ],
        'quiz' => [
            'en' => '/quiz',
            'es' => '/es/quiz-playa',
            'script' => '/quiz.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.8',
        ],
        'quiz_results' => [
            'en' => '/quiz-results',
            'es' => '/es/resultados-quiz-playa',
            'script' => '/quiz-results.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.4',
        ],
        'compare' => [
            'en' => '/compare',
            'es' => '/es/comparar-playas',
            'script' => '/compare.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.7',
        ],
        'best_beaches' => [
            'en' => '/best-beaches',
            'es' => '/es/mejores-playas',
            'script' => '/best-beaches.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'best_beaches_san_juan' => [
            'en' => '/best-beaches-san-juan',
            'es' => '/es/mejores-playas-san-juan',
            'script' => '/best-beaches-san-juan.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'best_snorkeling_beaches' => [
            'en' => '/best-snorkeling-beaches',
            'es' => '/es/mejores-playas-para-snorkel',
            'script' => '/best-snorkeling-beaches.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'best_surfing_beaches' => [
            'en' => '/best-surfing-beaches',
            'es' => '/es/mejores-playas-para-surf',
            'script' => '/best-surfing-beaches.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'best_family_beaches' => [
            'en' => '/best-family-beaches',
            'es' => '/es/mejores-playas-familiares',
            'script' => '/best-family-beaches.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'beaches_near_san_juan' => [
            'en' => '/beaches-near-san-juan',
            'es' => '/es/playas-cerca-de-san-juan',
            'script' => '/beaches-near-san-juan.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'beaches_near_airport' => [
            'en' => '/beaches-near-san-juan-airport',
            'es' => '/es/playas-cerca-del-aeropuerto-san-juan',
            'script' => '/beaches-near-san-juan-airport.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'hidden_beaches' => [
            'en' => '/hidden-beaches-puerto-rico',
            'es' => '/es/playas-escondidas-puerto-rico',
            'script' => '/hidden-beaches-puerto-rico.php',
            'indexable' => true,
            'changefreq' => 'weekly',
            'priority' => '0.9',
        ],
        'guides_index' => [
            'en' => '/guides/',
            'es' => '/es/guias/',
            'script' => '/guides/index.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_transportation' => [
            'en' => '/guides/getting-to-puerto-rico-beaches',
            'es' => '/es/guias/llegar-a-playas-de-puerto-rico',
            'script' => '/guides/getting-to-puerto-rico-beaches.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_safety' => [
            'en' => '/guides/beach-safety-tips',
            'es' => '/es/guias/consejos-seguridad-playa',
            'script' => '/guides/beach-safety-tips.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_best_time' => [
            'en' => '/guides/best-time-visit-puerto-rico-beaches',
            'es' => '/es/guias/mejor-epoca-visitar-playas-puerto-rico',
            'script' => '/guides/best-time-visit-puerto-rico-beaches.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_packing' => [
            'en' => '/guides/beach-packing-list',
            'es' => '/es/guias/lista-empaque-playa',
            'script' => '/guides/beach-packing-list.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_culebra_vieques' => [
            'en' => '/guides/culebra-vs-vieques',
            'es' => '/es/guias/culebra-vs-vieques',
            'script' => '/guides/culebra-vs-vieques.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_bio_bays' => [
            'en' => '/guides/bioluminescent-bays',
            'es' => '/es/guias/bahias-bioluminiscentes',
            'script' => '/guides/bioluminescent-bays.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_snorkeling' => [
            'en' => '/guides/snorkeling-guide',
            'es' => '/es/guias/guia-snorkel',
            'script' => '/guides/snorkeling-guide.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_surfing' => [
            'en' => '/guides/surfing-guide',
            'es' => '/es/guias/guia-surf',
            'script' => '/guides/surfing-guide.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_photography' => [
            'en' => '/guides/beach-photography-tips',
            'es' => '/es/guias/consejos-fotografia-playa',
            'script' => '/guides/beach-photography-tips.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_family_planning' => [
            'en' => '/guides/family-beach-vacation-planning',
            'es' => '/es/guias/planificacion-vacaciones-familia-playa',
            'script' => '/guides/family-beach-vacation-planning.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'guide_spring_break' => [
            'en' => '/guides/spring-break-beaches-puerto-rico',
            'es' => '/es/guias/playas-spring-break-puerto-rico',
            'script' => '/guides/spring-break-beaches-puerto-rico.php',
            'indexable' => true,
            'changefreq' => 'monthly',
            'priority' => '0.8',
        ],
        'offline' => [
            'en' => '/offline',
            'es' => '/es/sin-conexion',
            'script' => '/offline.php',
            'indexable' => false,
        ],
        'login' => [
            'en' => '/login',
            'es' => '/es/iniciar-sesion',
            'script' => '/login.php',
            'indexable' => false,
        ],
        'logout' => [
            'en' => '/logout',
            'es' => '/es/cerrar-sesion',
            'script' => '/logout.php',
            'indexable' => false,
        ],
        'verify' => [
            'en' => '/verify',
            'es' => '/es/verificar',
            'script' => '/verify.php',
            'indexable' => false,
        ],
        'favorites' => [
            'en' => '/favorites',
            'es' => '/es/favoritos',
            'script' => '/favorites.php',
            'indexable' => false,
        ],
        'profile' => [
            'en' => '/profile',
            'es' => '/es/perfil',
            'script' => '/profile.php',
            'indexable' => false,
        ],
        'onboarding' => [
            'en' => '/onboarding',
            'es' => '/es/bienvenida',
            'script' => '/onboarding.php',
            'indexable' => false,
        ],
        'terms' => [
            'en' => '/terms',
            'es' => '/es/terminos',
            'script' => '/terms.php',
            'indexable' => false,
        ],
        'privacy' => [
            'en' => '/privacy',
            'es' => '/es/privacidad',
            'script' => '/privacy.php',
            'indexable' => false,
        ],
    ];

    return $routes;
}

/**
 * Resolve locale from path prefix.
 * Returns null when no explicit locale is encoded in the URL.
 */
function resolveLocaleFromPath(string $path): ?string
{
    $path = normalizeLocalePath($path);
    if ($path === '/es' || str_starts_with($path, '/es/')) {
        return 'es';
    }
    if ($path === '/en' || str_starts_with($path, '/en/')) {
        return 'en';
    }
    return null;
}

/**
 * Match static or dynamic locale route for a path.
 */
function localeRouteMatch(string $path): ?array
{
    $path = normalizeLocalePath($path);

    foreach (localeRoutes() as $routeKey => $route) {
        if ($path === normalizeLocalePath((string) $route['en'])) {
            return [
                'route_key' => $routeKey,
                'locale' => 'en',
                'params' => [],
                'indexable' => (bool) ($route['indexable'] ?? true),
            ];
        }
        if ($path === normalizeLocalePath((string) $route['es'])) {
            return [
                'route_key' => $routeKey,
                'locale' => 'es',
                'params' => [],
                'indexable' => (bool) ($route['indexable'] ?? true),
            ];
        }
    }

    if (preg_match('#^/beach/([a-z0-9-]+)$#', $path, $matches)) {
        return [
            'route_key' => 'beach_detail',
            'locale' => 'en',
            'params' => ['slug' => $matches[1]],
            'indexable' => true,
        ];
    }
    if (preg_match('#^/es/playa/([a-z0-9-]+)$#', $path, $matches)) {
        return [
            'route_key' => 'beach_detail',
            'locale' => 'es',
            'params' => ['slug' => $matches[1]],
            'indexable' => true,
        ];
    }

    if (preg_match('#^/beaches-in-([a-z-]+)$#', $path, $matches)) {
        return [
            'route_key' => 'municipality',
            'locale' => 'en',
            'params' => ['municipality' => $matches[1]],
            'indexable' => true,
        ];
    }
    if (preg_match('#^/es/playas-en-([a-z-]+)$#', $path, $matches)) {
        return [
            'route_key' => 'municipality',
            'locale' => 'es',
            'params' => ['municipality' => $matches[1]],
            'indexable' => true,
        ];
    }

    return null;
}

/**
 * Build a localized path for a known route key.
 */
function routeUrl(string $routeKey, string $locale = 'en', array $params = []): string
{
    $locale = $locale === 'es' ? 'es' : 'en';
    $routes = localeRoutes();

    if (isset($routes[$routeKey])) {
        return normalizeLocalePath((string) $routes[$routeKey][$locale]);
    }

    if ($routeKey === 'beach_detail') {
        $slug = trim((string) ($params['slug'] ?? ''));
        if ($slug === '') {
            return $locale === 'es' ? '/es' : '/';
        }
        return $locale === 'es'
            ? '/es/playa/' . $slug
            : '/beach/' . $slug;
    }

    if ($routeKey === 'municipality') {
        $municipality = trim((string) ($params['municipality'] ?? ''));
        if ($municipality === '') {
            return $locale === 'es' ? '/es' : '/';
        }
        return $locale === 'es'
            ? '/es/playas-en-' . $municipality
            : '/beaches-in-' . $municipality;
    }

    return $locale === 'es' ? '/es' : '/';
}

/**
 * Localize an arbitrary path to the target locale.
 */
function localizePath(string $path, string $targetLocale): string
{
    $targetLocale = $targetLocale === 'es' ? 'es' : 'en';
    $normalized = normalizeLocalePath($path);
    $match = localeRouteMatch($normalized);

    if (is_array($match)) {
        return routeUrl((string) $match['route_key'], $targetLocale, (array) ($match['params'] ?? []));
    }

    if ($targetLocale === 'es') {
        if ($normalized === '/es' || str_starts_with($normalized, '/es/')) {
            return $normalized;
        }
        if ($normalized === '/') {
            return '/es';
        }
        return '/es' . $normalized;
    }

    if ($normalized === '/es') {
        return '/';
    }
    if (str_starts_with($normalized, '/es/')) {
        $stripped = substr($normalized, 3);
        return $stripped === '' ? '/' : normalizeLocalePath($stripped);
    }
    if ($normalized === '/en') {
        return '/';
    }
    if (str_starts_with($normalized, '/en/')) {
        $stripped = substr($normalized, 3);
        return $stripped === '' ? '/' : normalizeLocalePath($stripped);
    }

    return $normalized;
}

/**
 * Localize a path and query string together, including legacy script URLs.
 */
function localizePathAndQuery(string $path, string $query, string $targetLocale): string
{
    $targetLocale = $targetLocale === 'es' ? 'es' : 'en';
    $normalizedPath = normalizeLocalePath($path);
    $queryMap = [];
    if ($query !== '') {
        parse_str($query, $queryMap);
        if (!is_array($queryMap)) {
            $queryMap = [];
        }
    }

    foreach (localeRoutes() as $routeKey => $route) {
        $scriptPath = normalizeLocalePath((string) ($route['script'] ?? ''));
        if ($scriptPath !== '' && $scriptPath === $normalizedPath) {
            $localizedPath = routeUrl((string) $routeKey, $targetLocale);
            $queryString = http_build_query($queryMap);
            return $queryString !== '' ? ($localizedPath . '?' . $queryString) : $localizedPath;
        }
    }

    if ($normalizedPath === '/beach.php') {
        $slug = trim((string) ($queryMap['slug'] ?? ''));
        if ($slug !== '') {
            unset($queryMap['slug']);
            $localizedPath = routeUrl('beach_detail', $targetLocale, ['slug' => $slug]);
            $queryString = http_build_query($queryMap);
            return $queryString !== '' ? ($localizedPath . '?' . $queryString) : $localizedPath;
        }
        return routeUrl('home', $targetLocale);
    }

    if ($normalizedPath === '/municipality.php') {
        $municipality = trim((string) ($queryMap['m'] ?? ''));
        if ($municipality !== '') {
            unset($queryMap['m']);
            $localizedPath = routeUrl('municipality', $targetLocale, ['municipality' => $municipality]);
            $queryString = http_build_query($queryMap);
            return $queryString !== '' ? ($localizedPath . '?' . $queryString) : $localizedPath;
        }
        return routeUrl('home', $targetLocale);
    }

    $localizedPath = localizePath($normalizedPath, $targetLocale);
    $queryString = http_build_query($queryMap);
    return $queryString !== '' ? ($localizedPath . '?' . $queryString) : $localizedPath;
}

/**
 * Get localized URL (path + query) for current request.
 */
function getLocalizedUrlForCurrentRequest(string $targetLocale): string
{
    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
    $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '/');
    $query = (string) (parse_url($requestUri, PHP_URL_QUERY) ?? '');
    return localizePathAndQuery($path, $query, $targetLocale);
}

/**
 * Whether a path resolves to an indexable route.
 */
function isIndexableLocalePath(string $path): bool
{
    $match = localeRouteMatch($path);
    if (!is_array($match)) {
        return false;
    }
    return (bool) ($match['indexable'] ?? true);
}

/**
 * Resolve a localized path to an internal public script and query args.
 */
function resolvePublicScriptFromLocalizedPath(string $path): ?array
{
    $match = localeRouteMatch($path);
    if (!is_array($match)) {
        return null;
    }

    $routeKey = (string) $match['route_key'];
    $params = (array) ($match['params'] ?? []);
    $routes = localeRoutes();

    if (isset($routes[$routeKey])) {
        $script = (string) ($routes[$routeKey]['script'] ?? '');
        if ($script !== '') {
            return ['script' => $script, 'query' => []];
        }
    }

    if ($routeKey === 'beach_detail') {
        return [
            'script' => '/beach.php',
            'query' => ['slug' => (string) ($params['slug'] ?? '')],
        ];
    }

    if ($routeKey === 'municipality') {
        return [
            'script' => '/municipality.php',
            'query' => ['m' => (string) ($params['municipality'] ?? '')],
        ];
    }

    return null;
}

/**
 * Route list for sitemap generation.
 */
function sitemapLocaleRoutes(): array
{
    $rows = [];
    foreach (localeRoutes() as $route) {
        if (!($route['indexable'] ?? false)) {
            continue;
        }
        if (!isset($route['changefreq'], $route['priority'])) {
            continue;
        }
        $rows[] = [
            'en' => normalizeLocalePath((string) $route['en']),
            'es' => normalizeLocalePath((string) $route['es']),
            'changefreq' => (string) $route['changefreq'],
            'priority' => (string) $route['priority'],
        ];
    }
    return $rows;
}
