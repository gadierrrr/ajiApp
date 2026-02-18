<?php
/**
 * Analytics (Umami) health probe.
 *
 * GET  /api/health/analytics
 * GET  /api/health/analytics?page_probe=1&network_probe=1
 * POST /api/health/analytics?client_probe=1
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';

function truthyQueryParam(string $key, bool $default = false): bool {
    if (!isset($_GET[$key])) {
        return $default;
    }
    $value = strtolower(trim((string) $_GET[$key]));
    return in_array($value, ['1', 'true', 'yes', 'on'], true);
}

function parseHttpStatusCode(array $headers): int {
    if (empty($headers)) {
        return 0;
    }

    $statusLine = (string) $headers[0];
    if (preg_match('/\s(\d{3})(?:\s|$)/', $statusLine, $matches) !== 1) {
        return 0;
    }

    return (int) $matches[1];
}

/**
 * @return array{ok:bool,status_code:int,error:string,latency_ms:int,body:string}
 */
function probeUrl(string $url, int $timeoutSeconds = 5, bool $includeBody = false): array {
    $start = microtime(true);
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => $timeoutSeconds,
            'ignore_errors' => true,
            'header' => "User-Agent: beach-finder-analytics-health/1.0\r\nAccept: text/html,application/json\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    $latencyMs = (int) round((microtime(true) - $start) * 1000);
    $headers = $http_response_header ?? [];
    $statusCode = parseHttpStatusCode($headers);
    $error = '';
    if ($body === false) {
        $error = (string) (error_get_last()['message'] ?? 'Request failed');
    }

    return [
        'ok' => $statusCode >= 200 && $statusCode < 400,
        'status_code' => $statusCode,
        'error' => $error,
        'latency_ms' => $latencyMs,
        'body' => ($includeBody && is_string($body)) ? $body : '',
    ];
}

/**
 * @return array{umami_tag_present:bool,umami_script_src:string,umami_website_id:string,analytics_wrapper_present:bool}
 */
function extractAnalyticsTags(string $html): array {
    $result = [
        'umami_tag_present' => false,
        'umami_script_src' => '',
        'umami_website_id' => '',
        'analytics_wrapper_present' => false,
    ];

    if ($html === '') {
        return $result;
    }

    $previousErrors = libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $loaded = @$dom->loadHTML($html);
    libxml_clear_errors();
    libxml_use_internal_errors($previousErrors);

    if ($loaded !== true) {
        return $result;
    }

    $scripts = $dom->getElementsByTagName('script');
    foreach ($scripts as $script) {
        $src = trim((string) $script->getAttribute('src'));
        if ($src !== '' && str_contains($src, '/assets/js/analytics.js')) {
            $result['analytics_wrapper_present'] = true;
        }

        $websiteId = trim((string) $script->getAttribute('data-website-id'));
        if ($websiteId === '') {
            continue;
        }

        $result['umami_tag_present'] = true;
        $result['umami_website_id'] = $websiteId;
        $result['umami_script_src'] = $src;
    }

    return $result;
}

function handleClientProbe(string $probePath): void {
    $raw = file_get_contents('php://input');
    if (($raw === false || $raw === '') && PHP_SAPI === 'cli') {
        $raw = stream_get_contents(STDIN);
    }
    $payload = json_decode($raw ?: '{}', true);
    if (!is_array($payload)) {
        jsonResponse([
            'ok' => false,
            'error' => 'Invalid JSON body',
        ], 400);
    }

    $eventNameRaw = (string) ($payload['event_name'] ?? '');
    $eventNameSanitized = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $eventNameRaw) ?? '';
    $eventName = substr($eventNameSanitized, 0, 64);

    $pathRaw = trim((string) ($payload['path'] ?? ''));
    $path = substr($pathRaw, 0, 200);
    $umamiAvailable = (bool) ($payload['umami_available'] ?? false);

    $record = [
        'last_seen_at' => gmdate('c'),
        'event_name' => $eventName !== '' ? $eventName : 'unknown',
        'path' => $path,
        'umami_available' => $umamiAvailable,
        'app_env' => appEnv(),
        'remote_addr' => substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64),
        'user_agent' => substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 200),
    ];

    $ok = @file_put_contents($probePath, json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
    if ($ok === false) {
        jsonResponse([
            'ok' => false,
            'error' => 'Failed to persist client probe',
        ], 500);
    }

    jsonResponse([
        'ok' => true,
        'accepted' => true,
    ], 202);
}

header('Content-Type: application/json');

$clientProbePath = APP_ROOT . '/data/analytics-client-probe.json';
$requestMethod = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));

if ($requestMethod === 'POST' && truthyQueryParam('client_probe', false)) {
    handleClientProbe($clientProbePath);
}

$appEnv = appEnv();
$umamiEnabled = envBool('UMAMI_ENABLED', false);
$umamiWebsiteId = trim((string) env('UMAMI_WEBSITE_ID', ''));
$umamiScriptUrl = trim((string) env('UMAMI_SCRIPT_URL', 'https://cloud.umami.is/script.js'));
$umamiDomainsRaw = trim((string) env('UMAMI_DOMAINS', ''));
$umamiDomains = array_values(array_filter(array_map('trim', explode(',', $umamiDomainsRaw)), static fn($d) => $d !== ''));
$umamiScriptHost = (string) (parse_url($umamiScriptUrl, PHP_URL_HOST) ?? '');

$healthy = true;
$errors = [];

$checks = [
    'config' => [
        'app_env' => $appEnv,
        'enabled' => $umamiEnabled,
        'website_id_configured' => $umamiWebsiteId !== '',
        'script_url' => $umamiScriptUrl,
        'script_host' => $umamiScriptHost,
        'domains' => $umamiDomains,
    ],
    'page_probe' => [
        'ran' => false,
        'url' => '',
        'status_code' => 0,
        'latency_ms' => 0,
        'analytics_wrapper_present' => false,
        'umami_tag_present' => false,
        'umami_website_id_present' => false,
        'umami_script_src' => '',
        'error' => '',
    ],
    'network_probe' => [
        'ran' => false,
        'script_url_reachable' => false,
        'status_code' => 0,
        'latency_ms' => 0,
        'error' => '',
    ],
    'client_probe' => [
        'available' => false,
        'last_seen_at' => null,
        'event_name' => null,
        'path' => null,
        'umami_available' => null,
    ],
];

if ($appEnv === 'prod' && !$umamiEnabled) {
    $healthy = false;
    $errors[] = 'UMAMI_ENABLED is off in production';
}

if ($umamiEnabled && $umamiWebsiteId === '') {
    $healthy = false;
    $errors[] = 'UMAMI_WEBSITE_ID is missing while Umami is enabled';
}

if ($umamiEnabled && $umamiScriptHost === '') {
    $healthy = false;
    $errors[] = 'UMAMI_SCRIPT_URL is invalid';
}

$runPageProbe = truthyQueryParam('page_probe', false);
$runNetworkProbe = truthyQueryParam('network_probe', false);

if ($runPageProbe) {
    $pageProbeUrl = trim((string) ($_GET['page_probe_url'] ?? env('APP_URL', '')));
    if ($pageProbeUrl === '') {
        $checks['page_probe']['ran'] = true;
        $checks['page_probe']['error'] = 'No page probe URL configured';
        $healthy = false;
        $errors[] = 'Page probe URL is empty';
    } else {
        $checks['page_probe']['ran'] = true;
        $checks['page_probe']['url'] = $pageProbeUrl;

        $probe = probeUrl($pageProbeUrl, 8, true);
        $checks['page_probe']['status_code'] = $probe['status_code'];
        $checks['page_probe']['latency_ms'] = $probe['latency_ms'];
        $checks['page_probe']['error'] = $probe['error'];

        if (!$probe['ok']) {
            $healthy = false;
            $errors[] = 'Page probe request failed';
        }

        $tags = extractAnalyticsTags($probe['body']);
        $checks['page_probe']['analytics_wrapper_present'] = $tags['analytics_wrapper_present'];
        $checks['page_probe']['umami_tag_present'] = $tags['umami_tag_present'];
        $checks['page_probe']['umami_website_id_present'] = $tags['umami_website_id'] !== '';
        $checks['page_probe']['umami_script_src'] = $tags['umami_script_src'];

        if ($umamiEnabled && !$tags['umami_tag_present']) {
            $healthy = false;
            $errors[] = 'Umami script tag missing from probed page';
        }

        if ($umamiEnabled && $tags['umami_website_id'] !== '' && $tags['umami_website_id'] !== $umamiWebsiteId) {
            $healthy = false;
            $errors[] = 'Umami website ID mismatch between config and rendered page';
        }
    }
}

if ($runNetworkProbe) {
    $checks['network_probe']['ran'] = true;
    $probe = probeUrl($umamiScriptUrl, 8, false);
    $checks['network_probe']['script_url_reachable'] = $probe['ok'];
    $checks['network_probe']['status_code'] = $probe['status_code'];
    $checks['network_probe']['latency_ms'] = $probe['latency_ms'];
    $checks['network_probe']['error'] = $probe['error'];

    if ($umamiEnabled && !$probe['ok']) {
        $healthy = false;
        $errors[] = 'Umami script URL is not reachable';
    }
}

if (is_file($clientProbePath)) {
    $clientProbeData = json_decode((string) file_get_contents($clientProbePath), true);
    if (is_array($clientProbeData)) {
        $checks['client_probe']['available'] = true;
        $checks['client_probe']['last_seen_at'] = $clientProbeData['last_seen_at'] ?? null;
        $checks['client_probe']['event_name'] = $clientProbeData['event_name'] ?? null;
        $checks['client_probe']['path'] = $clientProbeData['path'] ?? null;
        $checks['client_probe']['umami_available'] = $clientProbeData['umami_available'] ?? null;
    }
}

$statusCode = $healthy ? 200 : 503;
jsonResponse([
    'ok' => $healthy,
    'errors' => $errors,
    'checks' => $checks,
    'timestamp' => gmdate('c'),
], $statusCode);
