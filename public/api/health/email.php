<?php
/**
 * Email provider health probe.
 *
 * Returns JSON with configuration and Plunk connectivity status
 * without sending an email.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/email_provider_plunk.php';

header('Content-Type: application/json');

$provider = 'plunk';
$secretKey = (string) env('PLUNK_SECRET_KEY', '');
$publicKey = (string) env('PLUNK_PUBLIC_KEY', '');
$baseUrl = plunkBaseUrl();

$checks = [
    'provider' => $provider,
    'configured' => [
        'secret_key' => $secretKey !== '',
        'public_key' => $publicKey !== '',
        'base_url' => $baseUrl,
    ],
    'api' => [
        'reachable' => false,
        'authenticated' => false,
        'status_code' => 0,
        'note' => '',
    ],
];

$healthy = true;

if ($secretKey === '' || $publicKey === '') {
    $healthy = false;
    $checks['api']['note'] = 'Missing Plunk API key configuration';
} else {
    // Probe with intentionally invalid send payload to verify network + auth
    // without delivering an email.
    $probe = plunkRequest('POST', '/v1/send', [
        'health_probe' => true,
    ], false);

    $checks['api']['status_code'] = (int) ($probe['status_code'] ?? 0);

    if ($checks['api']['status_code'] > 0) {
        $checks['api']['reachable'] = true;
    }

    if (($probe['ok'] ?? false) === true) {
        $checks['api']['authenticated'] = true;
        $checks['api']['note'] = 'Plunk API reachable/authenticated';
    } else {
        $status = (int) ($probe['status_code'] ?? 0);

        if ($status === 422) {
            // Expected validation failure indicates auth + endpoint are working.
            $checks['api']['authenticated'] = true;
            $checks['api']['note'] = 'Plunk API reachable/authenticated (validation probe)';
        } elseif ($status === 401 || $status === 403) {
            $healthy = false;
            $checks['api']['note'] = 'Plunk authentication failed';
        } else {
            $healthy = false;
            $checks['api']['note'] = (string) ($probe['error'] ?? 'Plunk connectivity probe failed');
        }
    }
}

http_response_code($healthy ? 200 : 503);
jsonResponse([
    'ok' => $healthy,
    'checks' => $checks,
    'timestamp' => gmdate('c'),
], $healthy ? 200 : 503);
