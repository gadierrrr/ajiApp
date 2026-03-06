<?php
declare(strict_types=1);

/**
 * Locale routing regression checks.
 *
 * Usage:
 *   php scripts/test-locale-routing.php
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_ROOT . '/inc/locale_routes.php';

$failures = [];

$assertSame = static function (string $label, string $actual, string $expected) use (&$failures): void {
    if ($actual !== $expected) {
        $failures[] = sprintf('%s: expected "%s", got "%s"', $label, $expected, $actual);
    }
};

$assertRoute = static function (string $label, string $path, string $expectedScript, array $expectedQuery = []) use (&$failures): void {
    $resolved = resolvePublicScriptFromLocalizedPath($path);
    if (!is_array($resolved)) {
        $failures[] = sprintf('%s: expected route resolution, got null', $label);
        return;
    }
    $script = (string) ($resolved['script'] ?? '');
    $query = (array) ($resolved['query'] ?? []);
    if ($script !== $expectedScript || $query !== $expectedQuery) {
        $failures[] = sprintf(
            '%s: expected %s %s, got %s %s',
            $label,
            $expectedScript,
            json_encode($expectedQuery, JSON_UNESCAPED_SLASHES),
            $script,
            json_encode($query, JSON_UNESCAPED_SLASHES)
        );
    }
};

// Legacy script URL localization.
$assertSame(
    'legacy beach script to es',
    localizePathAndQuery('/beach.php', 'slug=flamenco-beach&view=map', 'es'),
    '/es/playa/flamenco-beach?view=map'
);
$assertSame(
    'legacy municipality script to es',
    localizePathAndQuery('/municipality.php', 'm=san-juan', 'es'),
    '/es/playas-en-san-juan'
);
$assertSame(
    'legacy profile script to es',
    localizePathAndQuery('/profile.php', 'tab=favorites', 'es'),
    '/es/perfil?tab=favorites'
);

// Extensionless and trailing-slash normalization.
$assertSame(
    'best beaches trailing slash to es',
    localizePathAndQuery('/best-beaches/', '', 'es'),
    '/es/mejores-playas'
);
$assertSame(
    'es guide index to en',
    localizePathAndQuery('/es/guias/', '', 'en'),
    '/guides/'
);

// Reverse localization for Spanish paths.
$assertSame(
    'es beach detail to en',
    localizePathAndQuery('/es/playa/flamenco-beach', '', 'en'),
    '/beach/flamenco-beach'
);
$assertSame(
    'es municipality to en',
    localizePathAndQuery('/es/playas-en-rincon', '', 'en'),
    '/beaches-in-rincon'
);

// Route resolution used by dev-router.
$assertRoute(
    'resolve es collection',
    '/es/mejores-playas',
    '/best-beaches.php'
);
$assertRoute(
    'resolve es beach detail',
    '/es/playa/flamenco-beach',
    '/beach.php',
    ['slug' => 'flamenco-beach']
);
$assertRoute(
    'resolve es municipality',
    '/es/playas-en-san-juan',
    '/municipality.php',
    ['m' => 'san-juan']
);

// Route map invariants: round-trip and script resolution for all static mapped paths.
foreach (localeRoutes() as $routeKey => $route) {
    $enPath = normalizeLocalePath((string) $route['en']);
    $esPath = normalizeLocalePath((string) $route['es']);

    $assertSame(
        'round-trip en->es->en ' . $routeKey,
        localizePathAndQuery(localizePathAndQuery($enPath, '', 'es'), '', 'en'),
        $enPath
    );
    $assertSame(
        'round-trip es->en->es ' . $routeKey,
        localizePathAndQuery(localizePathAndQuery($esPath, '', 'en'), '', 'es'),
        $esPath
    );

    if (isset($route['script']) && (string) $route['script'] !== '') {
        $assertRoute(
            'resolve mapped en path ' . $routeKey,
            $enPath,
            (string) $route['script']
        );
        $assertRoute(
            'resolve mapped es path ' . $routeKey,
            $esPath,
            (string) $route['script']
        );
    }
}

// Unknown paths should not be indexable by default.
if (isIndexableLocalePath('/definitely-not-a-real-route')) {
    $failures[] = 'unknown paths must be noindex by default';
}

// Keep Nginx localized route config in sync with route map.
$nginxConfigPath = APP_ROOT . '/deploy/nginx/beach-finder.conf';
if (is_file($nginxConfigPath)) {
    $nginxConfig = (string) file_get_contents($nginxConfigPath);
    foreach (localeRoutes() as $routeKey => $route) {
        $esPath = normalizeLocalePath((string) ($route['es'] ?? ''));
        if ($esPath === '' || !str_starts_with($esPath, '/es')) {
            continue;
        }
        // Dynamic routes are handled via regex locations and are not in localeRoutes().
        if (str_contains($esPath, '{')) {
            continue;
        }

        $candidateA = 'location = ' . $esPath;
        $candidateB = '';
        if ($esPath !== '/' && str_ends_with($esPath, '/')) {
            $candidateB = 'location = ' . rtrim($esPath, '/');
        }
        $hasMatch = str_contains($nginxConfig, $candidateA)
            || ($candidateB !== '' && str_contains($nginxConfig, $candidateB));
        if (!$hasMatch) {
            $failures[] = sprintf('nginx config missing static ES route location for %s (%s)', $routeKey, $esPath);
        }
    }
}

// Malformed slash-heavy inputs should normalize safely.
$assertSame(
    'normalize leading double slash es route',
    normalizeLocalePath('//es//mejores-playas//'),
    '/es/mejores-playas'
);
$assertSame(
    'normalize leading double slash en route',
    normalizeLocalePath('//best-beaches///'),
    '/best-beaches'
);
$assertSame(
    'localize malformed slash-heavy input',
    localizePathAndQuery('//es//playa//flamenco//', '', 'en'),
    '/beach/flamenco'
);

if ($failures !== []) {
    fwrite(STDERR, "Locale routing regression checks failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, ' - ' . $failure . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "Locale routing regression checks passed.\n");
