<?php
/**
 * Shared request-security guardrails.
 */

if (defined('REQUEST_SECURITY_INCLUDED')) {
    return;
}
define('REQUEST_SECURITY_INCLUDED', true);

function requestSecurityClientIp(): string
{
    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));

    if (in_array($remoteAddr, ['127.0.0.1', '::1'], true) && $forwardedFor !== '') {
        $candidate = trim(explode(',', $forwardedFor)[0]);
        if ($candidate !== '') {
            return $candidate;
        }
    }

    return $remoteAddr !== '' ? $remoteAddr : 'unknown';
}

function requestSecurityApiLimitConfig(string $path): array
{
    if (str_starts_with($path, '/api/webhooks/')) {
        return ['max_attempts' => 300, 'window_minutes' => 60];
    }

    if (str_starts_with($path, '/api/health/')) {
        return ['max_attempts' => 30, 'window_minutes' => 60];
    }

    return ['max_attempts' => 100, 'window_minutes' => 60];
}

function requestSecurityDenyRateLimited(int $retryAfterSeconds): void
{
    if (!headers_sent()) {
        http_response_code(429);
        header('Content-Type: application/json');
        header('Retry-After: ' . $retryAfterSeconds);
        header('Cache-Control: no-store');
    }

    echo json_encode([
        'success' => false,
        'error' => 'Too many requests. Please try again later.',
    ]);
    exit;
}

function enforceDefaultApiRateLimit(): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') {
        return;
    }

    $requestUri = (string) ($_SERVER['REQUEST_URI'] ?? '');
    $path = (string) (parse_url($requestUri, PHP_URL_PATH) ?? '');
    if (!str_starts_with($path, '/api/')) {
        return;
    }

    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/rate_limiter.php';

    $config = requestSecurityApiLimitConfig($path);
    $action = 'global_api:' . $path . ':' . strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

    $limiter = new RateLimiter(getDB());
    $result = $limiter->check(
        requestSecurityClientIp(),
        $action,
        (int) ($config['max_attempts'] ?? 100),
        (int) ($config['window_minutes'] ?? 60)
    );

    if (($result['allowed'] ?? false) !== true) {
        requestSecurityDenyRateLimited(((int) ($config['window_minutes'] ?? 60)) * 60);
    }
}
