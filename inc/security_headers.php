<?php
/**
 * Security Headers
 * Include at the top of all public-facing pages
 */

if (!function_exists('cspHostSourceFromUrl')) {
    function cspHostSourceFromUrl(string $url): ?string {
        $parsed = parse_url($url);
        if (!is_array($parsed)) {
            return null;
        }

        $host = isset($parsed['host']) ? trim((string) $parsed['host']) : '';
        if ($host === '') {
            return null;
        }

        $port = isset($parsed['port']) ? (int) $parsed['port'] : null;
        if ($port !== null && $port > 0) {
            return $host . ':' . $port;
        }

        return $host;
    }
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Content Security Policy - includes optional Umami host from runtime config.
$scriptSources = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'cdn.tailwindcss.com', 'unpkg.com', 'cdn.jsdelivr.net', 'cloud.umami.is'];
$styleSources = ["'self'", "'unsafe-inline'", 'cdn.tailwindcss.com', 'unpkg.com', 'cdn.jsdelivr.net', 'fonts.googleapis.com'];
$imgSources = ["'self'", 'data:', 'blob:', 'https://*.cartocdn.com', 'https://*.basemaps.cartocdn.com'];
$fontSources = ["'self'", 'data:', 'fonts.gstatic.com'];
$connectSources = ["'self'", 'https://*.cartocdn.com', 'https://*.basemaps.cartocdn.com', 'unpkg.com', 'cdn.jsdelivr.net', 'cloud.umami.is', 'api-gateway.umami.dev', 'https://next-api.useplunk.com'];
$workerSources = ["'self'", 'blob:'];

$umamiEnabled = function_exists('envBool') ? envBool('UMAMI_ENABLED', false) : false;
$umamiScriptUrl = function_exists('env')
    ? (string) (env('UMAMI_SCRIPT_URL', 'https://cloud.umami.is/script.js') ?? 'https://cloud.umami.is/script.js')
    : 'https://cloud.umami.is/script.js';

if ($umamiEnabled && $umamiScriptUrl !== '') {
    $umamiScriptHost = cspHostSourceFromUrl($umamiScriptUrl);
    if (is_string($umamiScriptHost) && $umamiScriptHost !== '') {
        $scriptSources[] = $umamiScriptHost;
        $connectSources[] = $umamiScriptHost;
    }
}

$scriptSources = array_values(array_unique($scriptSources));
$styleSources = array_values(array_unique($styleSources));
$imgSources = array_values(array_unique($imgSources));
$fontSources = array_values(array_unique($fontSources));
$connectSources = array_values(array_unique($connectSources));
$workerSources = array_values(array_unique($workerSources));

$csp = "default-src 'self'; "
    . 'script-src ' . implode(' ', $scriptSources) . '; '
    . 'style-src ' . implode(' ', $styleSources) . '; '
    . 'img-src ' . implode(' ', $imgSources) . '; '
    . 'font-src ' . implode(' ', $fontSources) . '; '
    . 'connect-src ' . implode(' ', $connectSources) . '; '
    . 'worker-src ' . implode(' ', $workerSources) . ';';

header('Content-Security-Policy: ' . $csp);

// Performance Headers
header('X-DNS-Prefetch-Control: on');

// Cache HTML pages for 5 minutes (browser) with stale-while-revalidate
if (!headers_sent()) {
    $isApiRequest = strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false;
    $isAuthPage = strpos($_SERVER['REQUEST_URI'] ?? '', '/login') !== false ||
                  strpos($_SERVER['REQUEST_URI'] ?? '', '/logout') !== false ||
                  strpos($_SERVER['REQUEST_URI'] ?? '', '/auth/') !== false;

    if ($isApiRequest) {
        // API responses: short cache, allow revalidation
        header('Cache-Control: public, max-age=60, stale-while-revalidate=300');
    } elseif ($isAuthPage) {
        // Auth pages: no cache
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    } else {
        // Regular pages: moderate cache with revalidation
        header('Cache-Control: public, max-age=300, stale-while-revalidate=600');
    }
}
