<?php
/**
 * Security Headers
 * Include at the top of all public-facing pages
 */

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

// Content Security Policy - allows MapLibre, Tailwind CDN, HTMX, Tom Select, Umami Analytics
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.tailwindcss.com unpkg.com cdn.jsdelivr.net cloud.umami.is; style-src 'self' 'unsafe-inline' cdn.tailwindcss.com unpkg.com cdn.jsdelivr.net fonts.googleapis.com; img-src 'self' data: blob: https://*.cartocdn.com https://*.basemaps.cartocdn.com; font-src 'self' data: fonts.gstatic.com; connect-src 'self' https://*.cartocdn.com https://*.basemaps.cartocdn.com unpkg.com cdn.jsdelivr.net cloud.umami.is api-gateway.umami.dev https://next-api.useplunk.com; worker-src 'self' blob:");

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
