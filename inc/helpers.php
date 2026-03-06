<?php
// inc/helpers.php - Utility functions
//
// IMPORTANT: This file should be included with require_once to prevent
// duplicate function declarations. All display label functions live here.

// Include guard to prevent duplicate declarations
if (defined('HELPERS_PHP_INCLUDED')) {
    return;
}
define('HELPERS_PHP_INCLUDED', true);

function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function redirect($url) {
    header("Location: $url");
    exit;
}

function sanitizeInternalRedirect($value, $fallback = '/') {
    if (!is_string($value)) {
        return $fallback;
    }

    $value = trim($value);
    if ($value === '') {
        return $fallback;
    }

    // Reject control characters and encoded line breaks.
    if (preg_match('/[\x00-\x1F\x7F]/', $value) || preg_match('/%0d|%0a/i', $value)) {
        return $fallback;
    }

    // Only allow local absolute paths.
    if (!str_starts_with($value, '/')) {
        return $fallback;
    }

    if (str_starts_with($value, '//')) {
        return $fallback;
    }

    if (strpos($value, '\\') !== false) {
        return $fallback;
    }

    return $value;
}

function redirectInternal($value, $fallback = '/') {
    redirect(sanitizeInternalRedirect($value, $fallback));
}

function normalizeHostForUrl($host) {
    if (!is_string($host) || $host === '') {
        return null;
    }

    $host = trim($host);
    if ($host === '') {
        return null;
    }

    // Proxies can send a comma-separated host list; use the first hop.
    $host = trim(explode(',', $host)[0]);
    $host = preg_replace('#^https?://#i', '', $host);
    $host = rtrim($host, '/');

    if ($host === '' || !preg_match('/^[a-z0-9.-]+(?::\d{1,5})?$/i', $host)) {
        return null;
    }

    return strtolower($host);
}

function isAllowedCanonicalHost(string $host): bool {
    $hostWithoutPort = explode(':', $host)[0];

    if (in_array($hostWithoutPort, ['localhost', '127.0.0.1', '::1'], true)) {
        return true;
    }

    if ($hostWithoutPort === 'puertoricobeachfinder.com' || $hostWithoutPort === 'www.puertoricobeachfinder.com') {
        return true;
    }

    $envUrl = $_ENV['APP_URL'] ?? '';
    $envHost = '';
    if (is_string($envUrl) && $envUrl !== '') {
        $parsed = parse_url($envUrl);
        $envHost = normalizeHostForUrl($parsed['host'] ?? '');
    }

    if ($envHost && $hostWithoutPort === explode(':', $envHost)[0]) {
        return true;
    }

    return false;
}

function getRequestScheme(): string {
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';
    if (is_string($forwardedProto) && $forwardedProto !== '') {
        $proto = strtolower(trim(explode(',', $forwardedProto)[0]));
        if ($proto === 'http' || $proto === 'https') {
            return $proto;
        }
    }

    $https = $_SERVER['HTTPS'] ?? '';
    if (!empty($https) && $https !== 'off') {
        return 'https';
    }

    if ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443) {
        return 'https';
    }

    return 'http';
}

function getPublicBaseUrl(): string {
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $envUrl = rtrim((string) ($_ENV['APP_URL'] ?? ''), '/');
    $envParsed = $envUrl !== '' ? parse_url($envUrl) : null;
    $envHost = normalizeHostForUrl($envParsed['host'] ?? '');

    if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
        $requestHost = normalizeHostForUrl($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '')));
        if ($requestHost && isAllowedCanonicalHost($requestHost)) {
            $cached = getRequestScheme() . '://' . $requestHost;
            return $cached;
        }
    }

    if ($envHost) {
        $scheme = $envParsed['scheme'] ?? 'https';
        $port = isset($envParsed['port']) ? ':' . (int) $envParsed['port'] : '';
        $cached = strtolower($scheme) . '://' . explode(':', $envHost)[0] . $port;
        return $cached;
    }

    $cached = 'https://www.puertoricobeachfinder.com';
    return $cached;
}

function absoluteUrl($pathOrUrl): string {
    if (!is_string($pathOrUrl) || $pathOrUrl === '') {
        return getPublicBaseUrl() . '/';
    }

    if (preg_match('#^https?://#i', $pathOrUrl)) {
        return $pathOrUrl;
    }

    return getPublicBaseUrl() . '/' . ltrim($pathOrUrl, '/');
}

function currentUser() {
    if (!isset($_SESSION['user_id'])) return null;
    require_once __DIR__ . '/db.php';
    return queryOne('SELECT * FROM users WHERE id = :id', [':id' => $_SESSION['user_id']]);
}

function isAuthenticated() {
    return session_status() === PHP_SESSION_ACTIVE && isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!isAuthenticated()) {
        redirect("/login.php?redirect=" . urlencode(sanitizeInternalRedirect($_SERVER["REQUEST_URI"] ?? '/')));
    }

    // Check session timeout (30 minutes of inactivity)
    if (isset($_SESSION["LAST_ACTIVITY"]) && (time() - $_SESSION["LAST_ACTIVITY"] > 1800)) {
        session_unset();
        session_destroy();
        redirect("/login.php?timeout=1&redirect=" . urlencode(sanitizeInternalRedirect($_SERVER["REQUEST_URI"] ?? '/')));
    }

    // Validate session fingerprint (prevent session hijacking)
    $currentFingerprint = hash("sha256", ($_SERVER["REMOTE_ADDR"] ?? "") . ($_SERVER["HTTP_USER_AGENT"] ?? ""));
    if (isset($_SESSION["SESSION_FINGERPRINT"]) && $_SESSION["SESSION_FINGERPRINT"] !== $currentFingerprint) {
        session_unset();
        session_destroy();
        redirect("/login.php?error=session_invalid");
    }

    // Update last activity time
    $_SESSION["LAST_ACTIVITY"] = time();
}

function slugify($str) {
    $str = strtolower(trim($str));
    $str = preg_replace('/[^a-z0-9-]/', '-', $str);
    return trim(preg_replace('/-+/', '-', $str), '-');
}

function isHtmx() {
    return isset($_SERVER['HTTP_HX_REQUEST']);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function csrfToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) return '';
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateToken(32);
    }
    return $_SESSION['csrf_token'];
}

function validateCsrf($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function csrfField() {
    return '<input type="hidden" name="csrf_token" value="' . h(csrfToken()) . '">';
}

function detectImageSignature(string $path): ?array {
    if ($path === '' || !is_file($path)) {
        return null;
    }

    if (function_exists('exif_imagetype')) {
        $type = @exif_imagetype($path);
        if ($type !== false) {
            return [
                'type' => (int) $type,
                'mime' => image_type_to_mime_type($type),
            ];
        }
    }

    $imageInfo = @getimagesize($path);
    if (is_array($imageInfo) && isset($imageInfo[2])) {
        $type = (int) $imageInfo[2];
        $mime = (string) ($imageInfo['mime'] ?? image_type_to_mime_type($type));

        return [
            'type' => $type,
            'mime' => $mime,
        ];
    }

    return null;
}

// Beach-specific helpers

function getBeachUrl($beach) {
    return '/beach/' . h($beach['slug']);
}

function getBeachDetailUrl($beach) {
    return '/beach/' . urlencode($beach['slug']);
}

function getDirectionsUrl($beach) {
    $name = urlencode($beach['name']);

    // Use beach name + place_id for accurate destination display
    // When destination is a name (not coordinates), Google Maps uses place_id to resolve the exact location
    if (!empty($beach['place_id'])) {
        return "https://www.google.com/maps/dir/?api=1&destination={$name}&destination_place_id={$beach['place_id']}";
    }

    // Fallback: use name + Puerto Rico (Google will search for it)
    $destination = urlencode($beach['name'] . ', Puerto Rico');
    return "https://www.google.com/maps/dir/?api=1&destination={$destination}";
}

function getShareText($beach) {
    if (function_exists('__')) {
        return __('js.share_check_out', [
            'name' => $beach['name'],
            'municipality' => $beach['municipality']
        ]);
    }
    return "Check out " . $beach['name'] . " in " . $beach['municipality'] . ", Puerto Rico!";
}

function getConditionClass($value, $type) {
    if (!$value) return 'bg-gray-100 text-gray-600';

    $classes = [
        'sargassum' => [
            'none' => 'bg-green-100 text-green-700',
            'light' => 'bg-yellow-100 text-yellow-700',
            'moderate' => 'bg-orange-100 text-orange-700',
            'heavy' => 'bg-red-100 text-red-700',
        ],
        'surf' => [
            'calm' => 'bg-blue-100 text-blue-700',
            'small' => 'bg-cyan-100 text-cyan-700',
            'medium' => 'bg-indigo-100 text-indigo-700',
            'large' => 'bg-purple-100 text-purple-700',
        ],
        'wind' => [
            'calm' => 'bg-teal-100 text-teal-700',
            'light' => 'bg-emerald-100 text-emerald-700',
            'moderate' => 'bg-amber-100 text-amber-700',
            'strong' => 'bg-rose-100 text-rose-700',
        ],
    ];

    return $classes[$type][$value] ?? 'bg-gray-100 text-gray-600';
}

/**
 * Get dark theme CSS class for condition badges
 */
function getConditionClassDark($value, $type = null) {
    if (!$value) return 'bg-white/10 text-gray-400 border-white/10';

    $colors = [
        'low' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'moderate' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        'high' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'very_high' => 'bg-red-500/20 text-red-400 border-red-500/30',
        'none' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'flat' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'calm' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'light' => 'bg-green-500/20 text-green-400 border-green-500/30',
        'small' => 'bg-blue-500/20 text-blue-400 border-blue-500/30',
        'medium' => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        'large' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'heavy' => 'bg-red-500/20 text-red-400 border-red-500/30',
        'strong' => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        'gusty' => 'bg-red-500/20 text-red-400 border-red-500/30',
    ];

    return $colors[strtolower($value)] ?? 'bg-white/10 text-gray-400 border-white/10';
}

/**
 * Get compact CSS class for condition indicator dots on beach cards
 * Returns color based on condition severity (good/moderate/warning/danger)
 */
function getConditionDotClass($value) {
    if (!$value) return 'text-gray-500';

    // Map conditions to severity colors
    $colors = [
        // Good conditions (green)
        'none' => 'text-green-400',
        'flat' => 'text-green-400',
        'calm' => 'text-green-400',
        'light' => 'text-green-400',
        'low' => 'text-green-400',
        // Neutral/informational (blue)
        'small' => 'text-blue-400',
        // Moderate conditions (yellow)
        'moderate' => 'text-yellow-400',
        'medium' => 'text-yellow-400',
        // Warning conditions (orange)
        'high' => 'text-orange-400',
        'large' => 'text-orange-400',
        'strong' => 'text-orange-400',
        // Danger conditions (red)
        'very_high' => 'text-red-400',
        'heavy' => 'text-red-400',
        'gusty' => 'text-red-400',
    ];

    return $colors[strtolower($value)] ?? 'text-gray-500';
}

// ========================================
// Swim Difficulty Helpers
// ========================================

/**
 * Get descriptive label for swim difficulty level
 */
function getSwimDifficultyLabel($level) {
    // Try i18n lookup first (when i18n system is loaded)
    if (function_exists('__')) {
        $keys = [
            1 => 'beach.swim_very_easy',
            2 => 'beach.swim_easy',
            3 => 'beach.swim_moderate',
            4 => 'beach.swim_challenging',
            5 => 'beach.swim_experts_only',
        ];
        if (isset($keys[$level])) {
            $translated = __($keys[$level]);
            if ($translated !== $keys[$level]) {
                return $translated;
            }
        }
    }
    // Hardcoded English fallback
    $labels = [
        1 => 'Very Easy',
        2 => 'Easy',
        3 => 'Moderate',
        4 => 'Challenging',
        5 => 'Experts Only'
    ];
    return $labels[$level] ?? 'Unknown';
}

/**
 * Get light theme CSS class for swim difficulty badge
 */
function getSwimDifficultyClass($level) {
    $classes = [
        1 => 'bg-green-50 text-green-700',
        2 => 'bg-green-50 text-green-700',
        3 => 'bg-yellow-50 text-yellow-700',
        4 => 'bg-orange-50 text-orange-700',
        5 => 'bg-red-50 text-red-700'
    ];
    return $classes[$level] ?? 'bg-gray-50 text-gray-700';
}

/**
 * Get dark theme CSS class for swim difficulty badge
 */
function getSwimDifficultyClassDark($level) {
    $classes = [
        1 => 'bg-green-500/20 text-green-400 border-green-500/30',
        2 => 'bg-green-500/20 text-green-400 border-green-500/30',
        3 => 'bg-yellow-500/20 text-yellow-400 border-yellow-500/30',
        4 => 'bg-orange-500/20 text-orange-400 border-orange-500/30',
        5 => 'bg-red-500/20 text-red-400 border-red-500/30'
    ];
    return $classes[$level] ?? 'bg-white/10 text-gray-400 border-white/10';
}

function formatDistance($meters) {
    if ($meters < 1000) {
        return round($meters) . 'm';
    }
    return round($meters / 1000, 1) . 'km';
}

function timeAgo($datetime) {
    if (!$datetime) return function_exists('__') ? __('time.never') : 'Never';

    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;

    $useI18n = function_exists('__');

    if ($diff < 60) {
        return $useI18n ? __('time.just_now') : 'Just now';
    } elseif ($diff < 3600) {
        $mins = (int)floor($diff / 60);
        if ($useI18n) {
            return $mins === 1 ? __('time.minute_ago') : __('time.minutes_ago', ['count' => $mins]);
        }
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = (int)floor($diff / 3600);
        if ($useI18n) {
            return $hours === 1 ? __('time.hour_ago') : __('time.hours_ago', ['count' => $hours]);
        }
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = (int)floor($diff / 86400);
        if ($useI18n) {
            return $days === 1 ? __('time.day_ago') : __('time.days_ago', ['count' => $days]);
        }
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 2592000) {
        $weeks = (int)floor($diff / 604800);
        if ($useI18n) {
            return $weeks === 1 ? __('time.week_ago') : __('time.weeks_ago', ['count' => $weeks]);
        }
        return $weeks . ' week' . ($weeks > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 31536000) {
        $months = (int)floor($diff / 2592000);
        if ($useI18n) {
            return $months === 1 ? __('time.month_ago') : __('time.months_ago', ['count' => $months]);
        }
        return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
    } else {
        $years = (int)floor($diff / 31536000);
        if ($useI18n) {
            return $years === 1 ? __('time.year_ago') : __('time.years_ago', ['count' => $years]);
        }
        return $years . ' year' . ($years > 1 ? 's' : '') . ' ago';
    }
}

function getThumbnailUrl($imagePath) {
    if (empty($imagePath)) {
        return '/images/beaches/placeholder-beach.webp';
    }

    // Extract filename without extension
    $pathInfo = pathinfo($imagePath);
    $baseName = $pathInfo['filename'];

    // Build thumbnail path
    $thumbnailPath = '/images/thumbnails/' . $baseName . '.webp';
    $thumbnailFile = $_SERVER['DOCUMENT_ROOT'] . $thumbnailPath;

    // Return thumbnail if it exists, otherwise original
    if (file_exists($thumbnailFile)) {
        return $thumbnailPath;
    }

    return $imagePath;
}

/**
 * Get WebP image path if available, with fallback to original
 * @param string $imagePath Original image path (e.g., /images/beaches/foo.jpg)
 * @return array ['webp' => webp path or null, 'fallback' => original path]
 */
function getWebPImage($imagePath) {
    if (empty($imagePath)) {
        return [
            'webp' => null,
            'fallback' => '/images/beaches/placeholder-beach.webp'
        ];
    }

    $pathInfo = pathinfo($imagePath);
    $dir = $pathInfo['dirname'];
    $baseName = $pathInfo['filename'];
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

    // Check for WebP version in same directory
    $webpPath = $dir . '/' . $baseName . '.webp';
    $webpExists = file_exists($docRoot . $webpPath);

    return [
        'webp' => $webpExists ? $webpPath : null,
        'fallback' => $imagePath
    ];
}

/**
 * Render a <picture> element with WebP and fallback
 * @param string $imagePath Original image path
 * @param string $alt Alt text for the image
 * @param string $class CSS classes
 * @param string $loading Loading attribute (lazy or eager)
 * @param array $attrs Additional attributes as key => value
 * @return string HTML picture element
 */
function renderWebPPicture($imagePath, $alt = '', $class = '', $loading = 'lazy', $attrs = []) {
    $images = getWebPImage($imagePath);
    $fallback = h($images['fallback']);
    $altText = h($alt);
    $classAttr = $class ? ' class="' . h($class) . '"' : '';
    $loadingAttr = $loading ? ' loading="' . h($loading) . '"' : '';

    // Build additional attributes string
    $extraAttrs = '';
    foreach ($attrs as $key => $value) {
        $extraAttrs .= ' ' . h($key) . '="' . h($value) . '"';
    }

    // If WebP exists, use picture element
    if ($images['webp']) {
        $webp = h($images['webp']);
        return <<<HTML
<picture>
    <source srcset="{$webp}" type="image/webp">
    <img src="{$fallback}" alt="{$altText}"{$classAttr}{$loadingAttr}{$extraAttrs}>
</picture>
HTML;
    }

    // No WebP, just return img
    return '<img src="' . $fallback . '" alt="' . $altText . '"' . $classAttr . $loadingAttr . $extraAttrs . '>';
}

/**
 * Get image attributes for use in templates (allows more flexibility than renderWebPPicture)
 * @param string $imagePath Original image path
 * @return array ['src' => fallback, 'webp_src' => webp or null, 'has_webp' => bool]
 */
function getImageAttrs($imagePath) {
    $images = getWebPImage($imagePath);
    return [
        'src' => $images['fallback'],
        'webp_src' => $images['webp'],
        'has_webp' => $images['webp'] !== null
    ];
}

/**
 * Get responsive image attributes (srcset, sizes, src)
 * Returns array with 'src', 'srcset', and 'sizes' keys
 *
 * @param string $imagePath - Original image path
 * @param string $sizes - Sizes attribute (default for card grid)
 * @return array
 */
function getResponsiveImageAttrs($imagePath, $sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw') {
    if (empty($imagePath)) {
        return [
            'src' => '/images/beaches/placeholder-beach.webp',
            'srcset' => '',
            'sizes' => ''
        ];
    }

    $pathInfo = pathinfo($imagePath);
    $baseName = $pathInfo['filename'];
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';

    // Check for different sized thumbnails
    $thumbnailSizes = [
        'sm' => ['width' => 400, 'suffix' => '-400w'],
        'md' => ['width' => 800, 'suffix' => '-800w'],
        'lg' => ['width' => 1200, 'suffix' => '-1200w'],
    ];

    $srcsetParts = [];
    $defaultSrc = $imagePath;

    // Check for sized thumbnails first
    foreach ($thumbnailSizes as $size => $config) {
        $sizedPath = '/images/thumbnails/' . $baseName . $config['suffix'] . '.webp';
        if (file_exists($docRoot . $sizedPath)) {
            $srcsetParts[] = $sizedPath . ' ' . $config['width'] . 'w';
            if ($size === 'sm') {
                $defaultSrc = $sizedPath;
            }
        }
    }

    // If no sized thumbnails, check for standard thumbnail
    if (empty($srcsetParts)) {
        $thumbnailPath = '/images/thumbnails/' . $baseName . '.webp';
        if (file_exists($docRoot . $thumbnailPath)) {
            // Use thumbnail for smaller screens (assume ~400w), original for larger
            $srcsetParts[] = $thumbnailPath . ' 400w';
            $srcsetParts[] = $imagePath . ' 800w';
            $defaultSrc = $thumbnailPath;
        }
    }

    // If still no srcset, just return the original image
    if (empty($srcsetParts)) {
        return [
            'src' => $imagePath,
            'srcset' => '',
            'sizes' => ''
        ];
    }

    return [
        'src' => $defaultSrc,
        'srcset' => implode(', ', $srcsetParts),
        'sizes' => $sizes
    ];
}

/**
 * Generate descriptive alt text for beach images (SEO optimized)
 * Format: "{Beach Name} in {Municipality}, Puerto Rico - {description}"
 *
 * @param array $beach Beach data array with name, municipality, tags
 * @param string $context Optional context like 'aerial view', 'sunset', 'snorkeling'
 * @return string SEO-friendly alt text
 */
function getBeachImageAlt($beach, $context = '') {
    $name = $beach['name'] ?? 'Beach';
    $municipality = $beach['municipality'] ?? 'Puerto Rico';

    // Build base alt text
    $alt = "{$name} in {$municipality}, Puerto Rico";

    // Add descriptive context based on tags or provided context
    if (!empty($context)) {
        $alt .= " - {$context}";
    } elseif (!empty($beach['tags'])) {
        $tags = is_array($beach['tags']) ? $beach['tags'] : [];

        // Priority order for most visual/descriptive tags
        $visualTags = [
            'calm-waters' => 'showing calm turquoise waters',
            'white-sand' => 'featuring white sand beach',
            'snorkeling' => 'with clear waters for snorkeling',
            'surfing' => 'with surfing waves',
            'secluded' => 'secluded tropical beach',
            'family-friendly' => 'family-friendly beach',
            'scenic' => 'scenic coastal view',
            'coral-reef' => 'with coral reefs',
            'swimming' => 'ideal for swimming'
        ];

        foreach ($visualTags as $tag => $description) {
            if (in_array($tag, $tags)) {
                $alt .= " - {$description}";
                break;
            }
        }
    }

    return $alt;
}

// ========================================
// Score Badge Helpers (Nomads-style)
// ========================================

/**
 * Get descriptive label for a rating score
 */
function getScoreLabel($rating) {
    if ($rating === null) return '';
    if ($rating >= 4.8) return function_exists('__') ? __('beach.score_exceptional') : 'Exceptional';
    if ($rating >= 4.5) return function_exists('__') ? __('beach.score_excellent') : 'Excellent';
    if ($rating >= 4.0) return function_exists('__') ? __('beach.score_very_good') : 'Very Good';
    if ($rating >= 3.5) return function_exists('__') ? __('beach.score_good') : 'Good';
    if ($rating >= 3.0) return function_exists('__') ? __('beach.score_average') : 'Average';
    return function_exists('__') ? __('beach.score_below_avg') : 'Below Avg';
}

/**
 * Get CSS class for score badge based on rating
 */
function getScoreBadgeClass($rating) {
    if ($rating === null) return 'score-average';
    if ($rating >= 4.5) return 'score-exceptional';
    if ($rating >= 4.0) return 'score-excellent';
    if ($rating >= 3.5) return 'score-good';
    return 'score-average';
}

/**
 * Format distance for display with smart units
 */
function formatDistanceDisplay($meters) {
    if ($meters === null) return null;
    if ($meters < 1000) {
        return round($meters) . 'm';
    } elseif ($meters < 10000) {
        return number_format($meters / 1000, 1) . 'km';
    }
    return round($meters / 1000) . 'km';
}

// ========================================
// Conditions Meter Helpers
// ========================================

/**
 * Get percentage for surf condition meter
 */
function getSurfPercentage($surf) {
    $map = ['calm' => 20, 'small' => 40, 'medium' => 70, 'large' => 100];
    return $map[$surf] ?? 0;
}

/**
 * Get percentage for sargassum condition meter
 */
function getSargassumPercentage($sargassum) {
    $map = ['none' => 5, 'light' => 33, 'moderate' => 66, 'heavy' => 100];
    return $map[$sargassum] ?? 0;
}

/**
 * Get percentage for wind condition meter
 */
function getWindPercentage($wind) {
    $map = ['calm' => 20, 'light' => 40, 'moderate' => 70, 'strong' => 100];
    return $map[$wind] ?? 0;
}

/**
 * Get human-readable label for condition value
 */
function getConditionLabel($type, $value) {
    if (!$value) return 'Unknown';
    if (function_exists('__')) {
        $key = "conditions.{$type}.{$value}";
        $translated = __($key);
        if ($translated !== $key) {
            return $translated;
        }
    }
    return ucfirst($value);
}

// ========================================
// Tag Helpers
// ========================================

/**
 * Get emoji for a tag
 */
function getTagEmoji($tag) {
    $emojis = [
        'calm-waters' => '🌊',
        'surfing' => '🏄',
        'snorkeling' => '🤿',
        'family-friendly' => '👨‍👩‍👧‍👦',
        'accessible' => '♿',
        'secluded' => '🏝️',
        'popular' => '⭐',
        'scenic' => '📸',
        'swimming' => '🏊',
        'diving' => '🐠',
        'fishing' => '🎣',
        'camping' => '⛺',
    ];
    return $emojis[$tag] ?? '🏖️';
}

/**
 * Get display label for a tag
 */
function getTagLabel($tag) {
    // Try i18n lookup first (when i18n system is loaded)
    if (function_exists('__')) {
        $translated = __('tags.' . $tag);
        if ($translated !== 'tags.' . $tag) {
            return $translated;
        }
    }
    // Hardcoded English fallback for admin/CLI contexts
    $labels = [
        'calm-waters' => 'Calm Waters',
        'surfing' => 'Surfing',
        'snorkeling' => 'Snorkeling',
        'family-friendly' => 'Family Friendly',
        'accessible' => 'Accessible',
        'secluded' => 'Secluded',
        'popular' => 'Popular',
        'scenic' => 'Scenic',
        'swimming' => 'Swimming',
        'diving' => 'Diving',
        'fishing' => 'Fishing',
        'camping' => 'Camping',
    ];
    return $labels[$tag] ?? ucwords(str_replace('-', ' ', $tag));
}

/**
 * Get display label for an amenity
 */
function getAmenityLabel($amenity) {
    // Try i18n lookup first (when i18n system is loaded)
    if (function_exists('__')) {
        $translated = __('amenities.' . $amenity);
        if ($translated !== 'amenities.' . $amenity) {
            return $translated;
        }
    }
    // Hardcoded English fallback for admin/CLI contexts
    $labels = [
        'restrooms' => 'Restrooms',
        'showers' => 'Showers',
        'lifeguard' => 'Lifeguard',
        'parking' => 'Parking',
        'food' => 'Food & Drinks',
        'equipment-rental' => 'Equipment Rental',
        'accessibility' => 'Wheelchair Accessible',
        'picnic-areas' => 'Picnic Areas',
        'shade-structures' => 'Shade/Umbrellas',
        'water-sports' => 'Water Sports',
    ];
    return $labels[$amenity] ?? ucwords(str_replace('-', ' ', $amenity));
}

/**
 * Get display label for parking difficulty
 */
function getParkingLabel($difficulty) {
    if (function_exists('__')) {
        $translated = __('parking.' . $difficulty);
        if ($translated !== 'parking.' . $difficulty) {
            return $translated;
        }
    }
    $labels = [
        'easy' => 'Easy Parking',
        'moderate' => 'Moderate',
        'difficult' => 'Difficult',
        'very-difficult' => 'Very Difficult',
    ];
    return $labels[$difficulty] ?? ucwords(str_replace('-', ' ', $difficulty));
}

/**
 * Get display label for a content section type
 */
function getContentSectionLabel($sectionType) {
    if (function_exists('__')) {
        $translated = __('content_sections.' . $sectionType);
        if ($translated !== 'content_sections.' . $sectionType) {
            return $translated;
        }
    }
    $labels = [
        'history' => 'History & Background',
        'best_time' => 'Best Time to Visit',
        'getting_there' => 'Getting There',
        'what_to_bring' => 'What to Bring',
        'nearby' => 'Nearby Attractions',
        'local_tips' => 'Local Tips',
    ];
    return $labels[$sectionType] ?? ucwords(str_replace('_', ' ', $sectionType));
}

// ========================================
// Beach Badge Helpers
// ========================================

/**
 * Get auto-generated badges for a beach based on its attributes
 */
function getBeachBadges($beach) {
    $badges = [];
    $tags = $beach['tags'] ?? [];
    $rating = $beach['google_rating'] ?? 0;
    $reviewCount = $beach['google_review_count'] ?? 0;

    // Top Rated: High rating with many reviews
    if ($rating >= 4.7 && $reviewCount >= 50) {
        $badges[] = 'top-rated';
    }

    // Family Pick: Family-friendly + calm waters
    if (in_array('family-friendly', $tags) && in_array('calm-waters', $tags)) {
        $badges[] = 'family-pick';
    }

    // Hidden Gem: Secluded + high rating + low review count
    if (in_array('secluded', $tags) && $rating >= 4.5 && $reviewCount < 100) {
        $badges[] = 'hidden-gem';
    }

    // Surfer's Favorite: Has surfing tag
    if (in_array('surfing', $tags) && $rating >= 4.0) {
        $badges[] = 'surfer-fave';
    }

    // Instagram Worthy: Scenic with good rating
    if (in_array('scenic', $tags) && $rating >= 4.3) {
        $badges[] = 'instagram-worthy';
    }

    // Return max 2 badges
    return array_slice($badges, 0, 2);
}

/**
 * Get badge display info
 */
function getBadgeInfo($badgeKey) {
    $badges = [
        'top-rated' => ['emoji' => '🏆', 'label' => function_exists('__') ? __('badges.top_rated') : 'Top Rated', 'color' => 'gold'],
        'family-pick' => ['emoji' => '👨‍👩‍👧', 'label' => function_exists('__') ? __('badges.family_pick') : 'Family Pick', 'color' => 'purple'],
        'hidden-gem' => ['emoji' => '💎', 'label' => function_exists('__') ? __('badges.hidden_gem') : 'Hidden Gem', 'color' => 'blue'],
        'surfer-fave' => ['emoji' => '🏄', 'label' => function_exists('__') ? __('badges.surfers_fave') : "Surfer's Fave", 'color' => 'cyan'],
        'instagram-worthy' => ['emoji' => '📸', 'label' => function_exists('__') ? __('badges.insta_worthy') : 'Insta-Worthy', 'color' => 'pink'],
        'local-secret' => ['emoji' => '🤫', 'label' => function_exists('__') ? __('badges.local_secret') : 'Local Secret', 'color' => 'green'],
    ];
    return $badges[$badgeKey] ?? null;
}

/**
 * Render a beach badge as HTML
 */
function renderBeachBadge($badgeKey) {
    $badge = getBadgeInfo($badgeKey);
    if (!$badge) return '';

    $colorClass = 'beach-badge-' . $badge['color'];

    return '<span class="beach-badge ' . $colorClass . '">'
         . '<span>' . $badge['emoji'] . '</span>'
         . '<span>' . h($badge['label']) . '</span>'
         . '</span>';
}

// ========================================
// Parking Difficulty Helpers
// ========================================

/**
 * Get parking difficulty label
 */
function getParkingDifficultyLabel($difficulty) {
    if ($difficulty !== null && function_exists('__')) {
        $key = str_replace('-', '_', $difficulty);
        $translated = __('parking.' . $key);
        if ($translated !== 'parking.' . $key) {
            return $translated;
        }
    }
    $labels = [
        'easy' => 'Easy Parking',
        'moderate' => 'Moderate',
        'difficult' => 'Difficult',
        'very-difficult' => 'Very Difficult'
    ];
    return $labels[$difficulty] ?? ucwords(str_replace('-', ' ', $difficulty ?? ''));
}

/**
 * Get parking difficulty description
 */
function getParkingDifficultyDescription($difficulty) {
    if ($difficulty !== null && function_exists('__')) {
        $key = str_replace('-', '_', $difficulty);
        $translated = __('parking_desc.' . $key);
        if ($translated !== 'parking_desc.' . $key) {
            return $translated;
        }
    }
    $descriptions = [
        'easy' => 'Plenty of parking available, rarely fills up',
        'moderate' => 'Usually find parking, may fill on weekends',
        'difficult' => 'Limited spots, arrive early on busy days',
        'very-difficult' => 'Very limited parking, consider alternate transport'
    ];
    return $descriptions[$difficulty] ?? '';
}

/**
 * Get parking difficulty CSS classes
 */
function getParkingDifficultyClass($difficulty) {
    $classes = [
        'easy' => 'bg-green-100 text-green-700',
        'moderate' => 'bg-yellow-100 text-yellow-700',
        'difficult' => 'bg-orange-100 text-orange-700',
        'very-difficult' => 'bg-red-100 text-red-700'
    ];
    return $classes[$difficulty] ?? 'bg-gray-100 text-gray-600';
}

/**
 * Get parking difficulty icon
 */
function getParkingDifficultyIcon($difficulty) {
    $icons = [
        'easy' => 'circle-check',
        'moderate' => 'circle-minus',
        'difficult' => 'circle-alert',
        'very-difficult' => 'circle-x'
    ];
    return $icons[$difficulty] ?? 'car';
}

// ========================================
// Sunrise/Sunset Helpers
// ========================================

/**
 * Calculate sunrise and sunset times for a given location and date
 * Uses standard solar calculations
 */
function getSunTimes($lat, $lng, $date = null) {
    $date = $date ?? date('Y-m-d');
    $timestamp = strtotime($date);

    // Puerto Rico timezone (AST - Atlantic Standard Time, no DST)
    $timezone = new DateTimeZone('America/Puerto_Rico');

    $sunInfo = date_sun_info($timestamp, $lat, $lng);
    $sunrise = $sunInfo['sunrise'];
    $sunset = $sunInfo['sunset'];

    if ($sunrise === false || $sunset === false) {
        return null;
    }

    $sunriseDateTime = new DateTime('@' . $sunrise);
    $sunriseDateTime->setTimezone($timezone);

    $sunsetDateTime = new DateTime('@' . $sunset);
    $sunsetDateTime->setTimezone($timezone);

    return [
        'sunrise' => $sunriseDateTime->format('g:i A'),
        'sunset' => $sunsetDateTime->format('g:i A'),
        'sunrise_timestamp' => $sunrise,
        'sunset_timestamp' => $sunset
    ];
}

/**
 * Get "Best For" summary based on beach tags
 */
function getBestForSummary($tags, $maxItems = 3) {
    if (empty($tags)) return [];

    // Priority order for "Best For" display
    $priority = [
        'family-friendly' => function_exists('__') ? __('best_for_labels.families') : 'Families',
        'surfing' => function_exists('__') ? __('best_for_labels.surfing') : 'Surfing',
        'snorkeling' => function_exists('__') ? __('best_for_labels.snorkeling') : 'Snorkeling',
        'diving' => function_exists('__') ? __('best_for_labels.diving') : 'Diving',
        'swimming' => function_exists('__') ? __('best_for_labels.swimming') : 'Swimming',
        'calm-waters' => function_exists('__') ? __('best_for_labels.relaxing') : 'Relaxing',
        'scenic' => function_exists('__') ? __('best_for_labels.photography') : 'Photography',
        'secluded' => function_exists('__') ? __('best_for_labels.quiet_escape') : 'Quiet Escape',
        'fishing' => function_exists('__') ? __('best_for_labels.fishing') : 'Fishing',
        'camping' => function_exists('__') ? __('best_for_labels.camping') : 'Camping'
    ];

    $bestFor = [];
    foreach ($priority as $tag => $label) {
        if (in_array($tag, $tags)) {
            $bestFor[] = $label;
            if (count($bestFor) >= $maxItems) break;
        }
    }

    return $bestFor;
}

// ========================================
// Similar Beaches Helper
// ========================================

/**
 * Find beaches similar to a given beach based on shared tags
 * @param string $beachId The beach to find similar beaches for
 * @param array $beachTags Tags of the current beach
 * @param int $limit Maximum number of similar beaches to return
 * @return array Array of similar beaches with similarity scores
 */
function getSimilarBeaches($beachId, $beachTags, $limit = 4) {
    if (empty($beachTags)) return [];

    require_once __DIR__ . '/db.php';

    // Build query to find beaches with overlapping tags
    $placeholders = [];
    $params = [':beach_id' => $beachId];
    foreach ($beachTags as $i => $tag) {
        $placeholders[] = ':tag' . $i;
        $params[':tag' . $i] = $tag;
    }

    $sql = "
        SELECT
            b.id, b.slug, b.name, b.municipality, b.cover_image,
            b.google_rating, b.google_review_count,
            COUNT(DISTINCT bt.tag) as shared_tags
        FROM beaches b
        INNER JOIN beach_tags bt ON b.id = bt.beach_id
        WHERE b.id != :beach_id
          AND b.publish_status = 'published'
          AND bt.tag IN (" . implode(',', $placeholders) . ")
        GROUP BY b.id
        ORDER BY shared_tags DESC, b.google_rating DESC
        LIMIT " . intval($limit);

    $similarBeaches = query($sql, $params);

    // Attach tags to similar beaches
    if (!empty($similarBeaches)) {
        attachBeachMetadata($similarBeaches);
    }

    return $similarBeaches;
}

/**
 * Get trending beaches based on recent favorites and views
 * @param int $limit Maximum number of beaches to return
 * @param int $days Number of days to look back
 * @return array Array of trending beaches
 */
function getTrendingBeaches($limit = 6, $days = 7) {
    require_once __DIR__ . '/db.php';

    $sql = "
        SELECT
            b.*,
            COUNT(DISTINCT uf.id) as recent_favorites
        FROM beaches b
        LEFT JOIN user_favorites uf ON b.id = uf.beach_id
            AND uf.created_at >= datetime('now', '-' || :days || ' days')
        WHERE b.publish_status = 'published'
        GROUP BY b.id
        ORDER BY recent_favorites DESC, b.google_rating DESC
        LIMIT :limit
    ";

    $beaches = query($sql, [':days' => $days, ':limit' => $limit]);

    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    return $beaches;
}

/**
 * Format view count for display (e.g., 1500 -> "1.5k")
 * @param int $count The view count
 * @return string Formatted view count
 */
function formatViewCount($count) {
    if ($count >= 1000) {
        return number_format($count / 1000, 1) . 'k';
    }
    return (string)$count;
}

/**
 * Get hidden gem beaches (high rating, low review count, secluded tag)
 * @param int $limit Maximum number of beaches to return
 * @return array Array of hidden gem beaches
 */
function getHiddenGems($limit = 6) {
    require_once __DIR__ . '/db.php';

    $sql = "
        SELECT b.*
        FROM beaches b
        LEFT JOIN beach_tags bt ON b.id = bt.beach_id AND bt.tag = 'secluded'
        WHERE b.publish_status = 'published'
          AND b.google_rating >= 4.3
          AND (b.google_review_count < 100 OR bt.tag IS NOT NULL)
        ORDER BY
            CASE WHEN bt.tag IS NOT NULL THEN 1 ELSE 2 END,
            b.google_rating DESC
        LIMIT :limit
    ";

    $beaches = query($sql, [':limit' => $limit]);

    if (!empty($beaches)) {
        attachBeachMetadata($beaches);
    }

    return $beaches;
}

// ========================================
// Hero Section Helpers
// ========================================

/**
 * Get beach counts by tag for hero filter cards
 * @return array Associative array of tag => count
 */
function getBeachCountsByTag() {
    require_once __DIR__ . '/db.php';

    $sql = "
        SELECT bt.tag, COUNT(DISTINCT bt.beach_id) as count
        FROM beach_tags bt
        INNER JOIN beaches b ON bt.beach_id = b.id
        WHERE b.publish_status = 'published'
        GROUP BY bt.tag
    ";

    $results = query($sql, []);
    $counts = [];
    foreach ($results as $row) {
        $counts[$row['tag']] = (int)$row['count'];
    }
    return $counts;
}

/**
 * Get popular beaches for hero quick links
 * @param int $limit Maximum number of beaches
 * @return array Array of popular beaches with name and slug
 */
function getPopularBeaches($limit = 4) {
    require_once __DIR__ . '/db.php';

    $sql = "
        SELECT b.name, b.slug, b.municipality
        FROM beaches b
        WHERE b.publish_status = 'published'
          AND b.google_rating >= 4.5
          AND b.google_review_count >= 100
        ORDER BY b.google_review_count DESC
        LIMIT :limit
    ";

    return query($sql, [':limit' => $limit]);
}

/**
 * Get aggregated site statistics for social proof
 * @return array Stats including total beaches, total reviews, avg rating
 */
function getSiteStats() {
    require_once __DIR__ . '/db.php';

    $sql = "
        SELECT
            COUNT(*) as total_beaches,
            SUM(google_review_count) as total_reviews,
            AVG(google_rating) as avg_rating
        FROM beaches
        WHERE publish_status = 'published'
          AND google_rating IS NOT NULL
    ";

    $stats = queryOne($sql, []);

    // Get total user count
    $userSql = "SELECT COUNT(*) as total_users FROM users";
    $userStats = queryOne($userSql, []);

    return [
        'total_beaches' => (int)($stats['total_beaches'] ?? 0),
        'total_reviews' => (int)($stats['total_reviews'] ?? 0),
        'avg_rating' => round((float)($stats['avg_rating'] ?? 0), 1),
        'total_users' => (int)($userStats['total_users'] ?? 0)
    ];
}

/**
 * Get SVG icon for a tag (for hero cards)
 * @param string $tag The tag identifier
 * @return string SVG path content
 */
function getTagIconSvg($tag) {
    $icons = [
        'surfing' => '<path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'snorkeling' => '<circle cx="6" cy="15" r="4"/><circle cx="18" cy="15" r="4"/><path d="M14 15a2 2 0 0 0-2-2 2 2 0 0 0-2 2"/><path d="M2.5 13 5 7c.7-1.3 1.4-2 3-2"/><path d="M21.5 13 19 7c-.7-1.3-1.5-2-3-2"/>',
        'family-friendly' => '<circle cx="12" cy="5" r="3"/><path d="M12 8v4"/><path d="m8 14 4 4 4-4"/><path d="M5 19h14"/><circle cx="7" cy="11" r="2"/><circle cx="17" cy="11" r="2"/>',
        'secluded' => '<path d="M10 10v.2A3 3 0 0 1 8.9 16v0H5v0h0a3 3 0 0 1-1-5.8V10a3 3 0 0 1 6 0Z"/><path d="M7 16v6"/><path d="M13 19v3"/><path d="M12 19h8.3a1 1 0 0 0 .7-1.7L18 14h.3a1 1 0 0 0 .7-1.7L16 9h.2a1 1 0 0 0 .8-1.7L13 3l-1.4 1.5"/>',
        'calm-waters' => '<path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/>',
        'swimming' => '<circle cx="12" cy="5" r="3"/><path d="M4 22c0-5 8-5 8-10"/><path d="M20 22c0-5-8-5-8-10"/>',
        'diving' => '<path d="M2 12h20"/><path d="M12 2v20"/><circle cx="12" cy="12" r="4"/>',
        'scenic' => '<path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3z"/><circle cx="12" cy="13" r="3"/>',
        'fishing' => '<path d="M3 14a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h18a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/><path d="M12 14v-4"/><path d="m8 6 4 4 4-4"/>',
        'camping' => '<path d="M3 20h18"/><path d="M12 4v16"/><path d="m4 20 8-14 8 14"/>',
    ];
    return $icons[$tag] ?? '<circle cx="12" cy="12" r="10"/>';
}

// ========================================
// Explorer Level Helpers
// ========================================

/**
 * Get explorer level info for a user
 * @param string $level The explorer level (newcomer, explorer, guide, expert, legend)
 * @return array Level info including label, icon, color, and thresholds
 */
function getExplorerLevelInfo($level) {
    $levels = [
        'newcomer' => [
            'label' => function_exists('__') ? __('explorer.newcomer') : 'Newcomer',
            'icon' => '⭐',
            'color' => 'amber',
            'colorClass' => 'text-amber-400 bg-amber-500/20 border-amber-500/30',
            'min_beaches' => 0,
            'max_beaches' => 2,
            'next_level' => 'explorer',
            'rank' => 1
        ],
        'explorer' => [
            'label' => function_exists('__') ? __('explorer.explorer') : 'Explorer',
            'icon' => '⭐⭐',
            'color' => 'gray',
            'colorClass' => 'text-gray-300 bg-white/10 border-white/20',
            'min_beaches' => 3,
            'max_beaches' => 10,
            'next_level' => 'guide',
            'rank' => 2
        ],
        'guide' => [
            'label' => function_exists('__') ? __('explorer.guide') : 'Guide',
            'icon' => '⭐⭐⭐',
            'color' => 'yellow',
            'colorClass' => 'text-yellow-400 bg-yellow-500/20 border-yellow-500/30',
            'min_beaches' => 11,
            'max_beaches' => 25,
            'next_level' => 'expert',
            'rank' => 3
        ],
        'expert' => [
            'label' => function_exists('__') ? __('explorer.expert') : 'Expert',
            'icon' => '⭐⭐⭐⭐',
            'color' => 'cyan',
            'colorClass' => 'text-cyan-400 bg-cyan-500/20 border-cyan-500/30',
            'min_beaches' => 26,
            'max_beaches' => 50,
            'next_level' => 'legend',
            'rank' => 4
        ],
        'legend' => [
            'label' => function_exists('__') ? __('explorer.legend') : 'Legend',
            'icon' => '👑',
            'color' => 'purple',
            'colorClass' => 'text-purple-400 bg-purple-500/20 border-purple-500/30',
            'min_beaches' => 51,
            'max_beaches' => null,
            'next_level' => null,
            'rank' => 5
        ]
    ];

    return $levels[$level] ?? $levels['newcomer'];
}

/**
 * Calculate progress to next explorer level
 * @param int $beachesVisited Number of beaches the user has visited
 * @param string $currentLevel Current explorer level
 * @return array Progress info with percentage and beaches needed
 */
function getExplorerProgress($beachesVisited, $currentLevel) {
    $levelInfo = getExplorerLevelInfo($currentLevel);

    // If already at max level
    if ($levelInfo['next_level'] === null) {
        return [
            'percentage' => 100,
            'beaches_needed' => 0,
            'next_level' => null,
            'message' => function_exists('__') ? __('explorer.highest_level') : 'You\'ve reached the highest level!'
        ];
    }

    $nextLevelInfo = getExplorerLevelInfo($levelInfo['next_level']);
    $minForNext = $nextLevelInfo['min_beaches'];
    $minForCurrent = $levelInfo['min_beaches'];

    $rangeSize = $minForNext - $minForCurrent;
    $progress = $beachesVisited - $minForCurrent;
    $percentage = min(100, max(0, ($progress / $rangeSize) * 100));
    $beachesNeeded = max(0, $minForNext - $beachesVisited);

    return [
        'percentage' => round($percentage),
        'beaches_needed' => $beachesNeeded,
        'next_level' => $levelInfo['next_level'],
        'next_level_info' => $nextLevelInfo,
        'message' => (function() use ($beachesNeeded, $nextLevelInfo) {
            if (function_exists('__')) {
                $parts = explode('|', __('explorer.beaches_to_next', ['count' => $beachesNeeded, 'level' => $nextLevelInfo['label']]));
                return $beachesNeeded === 1 ? $parts[0] : ($parts[1] ?? $parts[0]);
            }
            return $beachesNeeded . ' more beach' . ($beachesNeeded !== 1 ? 'es' : '') . ' to ' . $nextLevelInfo['label'];
        })()
    ];
}

/**
 * Update user's explorer level based on beaches visited
 * @param string $userId User ID
 */
function updateUserExplorerLevel($userId) {
    require_once __DIR__ . '/db.php';

    // Count unique beaches from check-ins
    $result = queryOne(
        'SELECT COUNT(DISTINCT beach_id) as count FROM beach_checkins WHERE user_id = :user_id',
        [':user_id' => $userId]
    );
    $beachesVisited = (int)($result['count'] ?? 0);

    // Determine level
    $level = 'newcomer';
    if ($beachesVisited >= 51) $level = 'legend';
    elseif ($beachesVisited >= 26) $level = 'expert';
    elseif ($beachesVisited >= 11) $level = 'guide';
    elseif ($beachesVisited >= 3) $level = 'explorer';

    // Update user
    execute(
        'UPDATE users SET explorer_level = :level, total_beaches_visited = :count WHERE id = :id',
        [':level' => $level, ':count' => $beachesVisited, ':id' => $userId]
    );

    return ['level' => $level, 'beaches_visited' => $beachesVisited];
}

// ========================================
// Admin Beach Image Helpers
// ========================================

/**
 * Get optimized beach image URL for a specific size
 *
 * For beaches with admin-uploaded images (stored in beach_images table),
 * returns the WebP URL for the requested size. For legacy beaches using
 * cover_image URLs, returns the original URL.
 *
 * @param array $beach Beach data with 'id' and 'cover_image'
 * @param string $size Size variant: 'original', 'large', 'medium', 'thumb', 'placeholder'
 * @return string Image URL
 */
function getBeachImageUrl($beach, $size = 'medium') {
    $coverImage = $beach['cover_image'] ?? '';

    // Check if this is an admin-uploaded image (in /uploads/admin/beaches/)
    if (strpos($coverImage, '/uploads/admin/beaches/') === 0) {
        // Extract base filename (without size suffix and extension)
        $filename = basename($coverImage);

        // Remove any existing size suffix and extension
        $baseName = preg_replace('/(_\d+|_placeholder)?\.webp$/', '', $filename);

        // Build URL for requested size
        $suffix = match($size) {
            'original' => '',
            'large' => '_1200',
            'medium' => '_800',
            'thumb' => '_400',
            'placeholder' => '_placeholder',
            default => '_800'
        };

        return '/uploads/admin/beaches/' . $baseName . $suffix . '.webp';
    }

    // Legacy image - return as-is
    return $coverImage ?: '/images/beaches/placeholder-beach.webp';
}

/**
 * Get srcset attribute for responsive beach images
 *
 * Returns a srcset string for use with responsive images. For admin-uploaded
 * images, includes all available sizes. For legacy images, returns empty string.
 *
 * @param array $beach Beach data with 'id' and 'cover_image'
 * @return string Srcset attribute value
 */
function getBeachImageSrcset($beach) {
    $coverImage = $beach['cover_image'] ?? '';

    // Only generate srcset for admin-uploaded images
    if (strpos($coverImage, '/uploads/admin/beaches/') !== 0) {
        return '';
    }

    // Extract base filename
    $filename = basename($coverImage);
    $baseName = preg_replace('/(_\d+|_placeholder)?\.webp$/', '', $filename);
    $basePath = '/uploads/admin/beaches/' . $baseName;

    $srcset = [
        $basePath . '_400.webp 400w',
        $basePath . '_800.webp 800w',
        $basePath . '_1200.webp 1200w',
        $basePath . '.webp 2400w',
    ];

    return implode(', ', $srcset);
}

/**
 * Render a responsive beach image element with srcset and sizes
 *
 * @param array $beach Beach data
 * @param string $alt Alt text
 * @param string $class CSS classes
 * @param string $sizes Sizes attribute (default for card grid)
 * @param string $loading Loading strategy ('lazy' or 'eager')
 * @return string HTML img element
 */
function renderBeachImage($beach, $alt = '', $class = '', $sizes = '(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw', $loading = 'lazy') {
    $src = h(getBeachImageUrl($beach, 'medium'));
    $srcset = getBeachImageSrcset($beach);
    $altText = h($alt ?: ($beach['name'] ?? 'Beach'));
    $classAttr = $class ? ' class="' . h($class) . '"' : '';
    $loadingAttr = $loading ? ' loading="' . h($loading) . '"' : '';

    if ($srcset) {
        $srcsetAttr = ' srcset="' . h($srcset) . '"';
        $sizesAttr = ' sizes="' . h($sizes) . '"';
        return '<img src="' . $src . '"' . $srcsetAttr . $sizesAttr . ' alt="' . $altText . '"' . $classAttr . $loadingAttr . '>';
    }

    return '<img src="' . $src . '" alt="' . $altText . '"' . $classAttr . $loadingAttr . '>';
}

/**
 * Check if a beach has admin-uploaded images
 *
 * @param string $beachId Beach ID
 * @return bool True if beach has images in beach_images table
 */
function beachHasAdminImages($beachId) {
    require_once __DIR__ . '/db.php';

    $result = queryOne(
        'SELECT COUNT(*) as count FROM beach_images WHERE beach_id = :beach_id',
        [':beach_id' => $beachId]
    );

    return ((int)($result['count'] ?? 0)) > 0;
}

// ========================================
// Schema Markup Helper Functions
// ========================================

/**
 * Get image dimensions with in-memory caching
 * Returns width and height for an image path, or defaults to 1200x900
 *
 * @param string $imagePath Image path (relative or absolute URL)
 * @return array ['width' => int, 'height' => int]
 */
function getImageDimensions($imagePath) {
    static $cache = [];

    // Return cached value if available
    if (isset($cache[$imagePath])) {
        return $cache[$imagePath];
    }

    // Default dimensions
    $default = ['width' => 1200, 'height' => 900];

    if (empty($imagePath)) {
        return $default;
    }

    // Build full file path
    $docRoot = $_SERVER['DOCUMENT_ROOT'] ?? '';
    $fullPath = $imagePath;

    // If relative path, prepend document root
    if (strpos($imagePath, 'http') !== 0 && strpos($imagePath, '/') === 0) {
        $fullPath = $docRoot . $imagePath;
    }

    // If HTTP URL, can't get dimensions easily - return default
    if (strpos($imagePath, 'http') === 0) {
        $cache[$imagePath] = $default;
        return $default;
    }

    // Try to get image size
    if (file_exists($fullPath)) {
        $size = @getimagesize($fullPath);
        if ($size !== false && isset($size[0]) && isset($size[1])) {
            $dimensions = ['width' => $size[0], 'height' => $size[1]];
            $cache[$imagePath] = $dimensions;
            return $dimensions;
        }
    }

    // Fallback to default
    $cache[$imagePath] = $default;
    return $default;
}

/**
 * Parse JSON string of external URLs and validate
 * Returns array of valid URLs
 *
 * @param string|null $jsonUrls JSON-encoded array of URLs
 * @return array Valid URLs
 */
function parseExternalUrls($jsonUrls) {
    if (empty($jsonUrls)) {
        return [];
    }

    // Decode JSON
    $urls = json_decode($jsonUrls, true);
    if (!is_array($urls)) {
        return [];
    }

    // Filter and validate URLs
    $validUrls = [];
    foreach ($urls as $url) {
        if (is_string($url) && filter_var($url, FILTER_VALIDATE_URL)) {
            $validUrls[] = $url;
        }
    }

    return $validUrls;
}

/**
 * Build sameAs array from external_urls field + auto-generate Google Maps URL
 * Used for schema.org sameAs property to link beach to external references
 *
 * @param array $beach Beach data with 'external_urls' and 'place_id'
 * @return array Array of valid URLs
 */
function buildSameAsLinks(array $beach) {
    $sameAs = [];

    // Parse external URLs from JSON field
    if (!empty($beach['external_urls'])) {
        $externalUrls = parseExternalUrls($beach['external_urls']);
        $sameAs = array_merge($sameAs, $externalUrls);
    }

    // Add Google Maps URL if place_id exists
    if (!empty($beach['place_id'])) {
        $placeId = urlencode($beach['place_id']);
        $sameAs[] = "https://www.google.com/maps/place/?q=place_id:{$placeId}";
    }

    return array_unique($sameAs);
}

// ========================================
// Internal Linking Helper Functions
// ========================================

/**
 * Get related guide pages based on beach tags
 * Maps beach characteristics to relevant planning guides
 *
 * @param array $beachTags Array of tag strings from beach
 * @param int $limit Maximum number of guides to return (default 3)
 * @return array Array of guide objects with 'title', 'url', 'icon'
 */
function getRelatedGuides($beachTags = [], $limit = 3) {
    // Guide mapping: tag => guide data
    $guideMap = [
        'surfing' => [
            'title' => function_exists('__') ? __('related_guides.surfing') : 'Puerto Rico Surfing Guide',
            'url' => '/guides/surfing-guide',
            'icon' => 'waves',
            'priority' => 10
        ],
        'snorkeling' => [
            'title' => function_exists('__') ? __('related_guides.snorkeling') : 'Snorkeling in Puerto Rico',
            'url' => '/guides/snorkeling-guide',
            'icon' => 'fish',
            'priority' => 10
        ],
        'family' => [
            'title' => function_exists('__') ? __('related_guides.family') : 'Family Beach Vacation Planning',
            'url' => '/guides/family-beach-vacation-planning',
            'icon' => 'users',
            'priority' => 9
        ],
        'photography' => [
            'title' => function_exists('__') ? __('related_guides.photography') : 'Beach Photography Tips',
            'url' => '/guides/beach-photography-tips',
            'icon' => 'camera',
            'priority' => 8
        ],
        'secluded' => [
            'title' => function_exists('__') ? __('related_guides.getting_there') : 'Getting to Puerto Rico Beaches',
            'url' => '/guides/getting-to-puerto-rico-beaches',
            'icon' => 'map-pin',
            'priority' => 7
        ],
        'remote' => [
            'title' => function_exists('__') ? __('related_guides.getting_there') : 'Getting to Puerto Rico Beaches',
            'url' => '/guides/getting-to-puerto-rico-beaches',
            'icon' => 'map-pin',
            'priority' => 7
        ],
        'wild' => [
            'title' => function_exists('__') ? __('related_guides.safety') : 'Beach Safety Tips',
            'url' => '/guides/beach-safety-tips',
            'icon' => 'shield',
            'priority' => 8
        ],
        'camping' => [
            'title' => function_exists('__') ? __('related_guides.packing') : 'Beach Packing List',
            'url' => '/guides/beach-packing-list',
            'icon' => 'backpack',
            'priority' => 6
        ]
    ];

    // Universal guides (always relevant, lower priority)
    $universalGuides = [
        [
            'title' => function_exists('__') ? __('related_guides.best_time') : 'Best Time to Visit Puerto Rico Beaches',
            'url' => '/guides/best-time-visit-puerto-rico-beaches',
            'icon' => 'calendar',
            'priority' => 5
        ],
        [
            'title' => function_exists('__') ? __('related_guides.packing') : 'Beach Packing List',
            'url' => '/guides/beach-packing-list',
            'icon' => 'backpack',
            'priority' => 4
        ],
        [
            'title' => function_exists('__') ? __('related_guides.safety') : 'Beach Safety Tips',
            'url' => '/guides/beach-safety-tips',
            'icon' => 'shield',
            'priority' => 3
        ]
    ];

    $relatedGuides = [];
    $usedUrls = []; // Track to avoid duplicates

    // Match tag-specific guides
    foreach ($beachTags as $tag) {
        if (isset($guideMap[$tag])) {
            $guide = $guideMap[$tag];
            if (!in_array($guide['url'], $usedUrls)) {
                $relatedGuides[] = $guide;
                $usedUrls[] = $guide['url'];
            }
        }
    }

    // Fill with universal guides if needed
    foreach ($universalGuides as $guide) {
        if (count($relatedGuides) >= $limit) {
            break;
        }
        if (!in_array($guide['url'], $usedUrls)) {
            $relatedGuides[] = $guide;
            $usedUrls[] = $guide['url'];
        }
    }

    // Sort by priority (descending)
    usort($relatedGuides, function($a, $b) {
        return ($b['priority'] ?? 0) - ($a['priority'] ?? 0);
    });

    // Limit results
    return array_slice($relatedGuides, 0, $limit);
}

/**
 * Sanitize HTML content to allow only safe tags for beach content sections.
 */
function sanitizeContentHtml(string $html): string {
    return strip_tags($html, '<p><ul><ol><li><strong><em><h3><br>');
}

/**
 * Get translated access label for a beach.
 */
function getAccessLabelTranslated(string $label): string {
    if (!$label) return '';
    if (!function_exists('getCurrentLanguage') || getCurrentLanguage() !== 'es') return $label;

    static $map = [
        '10-min walk' => 'caminata de 10 min',
        '4×4 track & hike' => 'camino 4×4 y caminata',
        'Easy Access' => 'Acceso fácil',
        'boardwalk access' => 'acceso por paseo tablado',
        'boat access' => 'acceso en bote',
        'difficult hike' => 'caminata difícil',
        'dirt pullouts & short paths' => 'desvíos de tierra y caminos cortos',
        'dirt road & short walk' => 'camino de tierra y caminata corta',
        'dirt track & walk' => 'camino de tierra y caminata',
        'guided trail access' => 'acceso por sendero guiado',
        'hike via reserve trail' => 'caminata por sendero de reserva',
        'hike via trail' => 'caminata por sendero',
        'hike via tunnel trail' => 'caminata por sendero del túnel',
        'moderate hike' => 'caminata moderada',
        'road & parking' => 'carretera y estacionamiento',
        'road & short trail' => 'carretera y sendero corto',
        'road & short walk' => 'carretera y caminata corta',
        'roadside access' => 'acceso desde la carretera',
        'rough road & short walk' => 'camino rústico y caminata corta',
        'shore swim' => 'nado desde la orilla',
        'shore/boat entry' => 'entrada por orilla/bote',
        'short hike via streambed' => 'caminata corta por cauce',
        'short path' => 'camino corto',
        'short path from pullout' => 'camino corto desde desvío',
        'short stairs/path' => 'escaleras/camino corto',
        'short trail' => 'sendero corto',
        'short trail from parking' => 'sendero corto desde estacionamiento',
        'short trail from roadside' => 'sendero corto desde la carretera',
        'short walk' => 'caminata corta',
        'short walk from lot' => 'caminata corta desde el lote',
        'short walk from parking' => 'caminata corta desde estacionamiento',
        'short walk from track' => 'caminata corta desde camino',
        'steep hike-in' => 'caminata empinada',
        'steep trail from road' => 'sendero empinado desde carretera',
        'street parking & beach access' => 'estacionamiento en calle y acceso a playa',
        'street parking & beach entries' => 'estacionamiento en calle y entradas a playa',
        'street parking & beach path' => 'estacionamiento en calle y camino a playa',
        'street parking & beach paths' => 'estacionamiento en calle y caminos a playa',
        'street parking & short walk' => 'estacionamiento en calle y caminata corta',
        'street parking & walk' => 'estacionamiento en calle y caminata',
        'trail access' => 'acceso por sendero',
        'viewpoint; informal paths' => 'mirador; caminos informales',
    ];

    return $map[$label] ?? $label;
}

/**
 * Strip accented characters for URL slug generation.
 */
function stripAccents(string $str): string {
    $map = [
        'á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n','ü'=>'u',
        'Á'=>'A','É'=>'E','Í'=>'I','Ó'=>'O','Ú'=>'U','Ñ'=>'N','Ü'=>'U',
    ];
    return strtr($str, $map);
}
