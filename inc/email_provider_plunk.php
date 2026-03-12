<?php
// inc/email_provider_plunk.php - Plunk API client helpers

if (defined('EMAIL_PROVIDER_PLUNK_INCLUDED')) {
    return;
}
define('EMAIL_PROVIDER_PLUNK_INCLUDED', true);

require_once __DIR__ . '/env.php';

function plunkBaseUrl(): string {
    return rtrim((string) env('PLUNK_BASE_URL', 'https://next-api.useplunk.com'), '/');
}

function plunkSecretKey(): string {
    return (string) env('PLUNK_SECRET_KEY', '');
}

function plunkPublicKey(): string {
    return (string) env('PLUNK_PUBLIC_KEY', '');
}

function plunkRequest(string $method, string $path, ?array $payload = null, bool $usePublicKey = false): array {
    $method = strtoupper($method);
    $baseUrl = plunkBaseUrl();
    $url = $baseUrl . '/' . ltrim($path, '/');

    $key = $usePublicKey ? plunkPublicKey() : plunkSecretKey();
    if ($key === '') {
        return [
            'ok' => false,
            'status_code' => 0,
            'body' => null,
            'error' => 'Missing Plunk API key',
        ];
    }

    $headers = [
        'Authorization: Bearer ' . $key,
        'Content-Type: application/json',
        'Accept: application/json',
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

    if ($payload !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }

    $response = curl_exec($ch);
    $curlErr = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false) {
        return [
            'ok' => false,
            'status_code' => $status,
            'body' => null,
            'error' => $curlErr !== '' ? $curlErr : 'Unknown cURL error',
        ];
    }

    $decoded = json_decode((string) $response, true);
    $body = is_array($decoded) ? $decoded : ['raw' => (string) $response];
    $ok = $status >= 200 && $status < 300;

    $errorMessage = null;
    if (!$ok) {
        $candidate = $body['error'] ?? $body['message'] ?? 'Plunk request failed';
        if (is_array($candidate)) {
            $errorMessage = json_encode($candidate, JSON_UNESCAPED_SLASHES);
        } else {
            $errorMessage = (string) $candidate;
        }
    }

    return [
        'ok' => $ok,
        'status_code' => $status,
        'body' => $body,
        'error' => $errorMessage,
    ];
}

function plunkSendEmail(string $to, string $subject, string $html, array $options = []): array {
    $fromAddress = (string) ($options['from_address'] ?? '');
    $fromName = (string) ($options['from_name'] ?? (env('APP_NAME', 'AJI') ?? 'AJI'));

    if ($fromAddress === '') {
        $appUrl = (string) env('APP_URL', 'https://puertoricobeachfinder.com');
        $domain = parse_url($appUrl, PHP_URL_HOST) ?: 'puertoricobeachfinder.com';
        $fromAddress = 'noreply@' . $domain;
    }

    $payload = [
        'to' => $to,
        'subject' => $subject,
        'body' => $html,
        'type' => 'html',
        'from' => $fromAddress,
        'name' => $fromName,
    ];

    $res = plunkRequest('POST', '/v1/send', $payload, false);

    $messageId = null;
    if (is_array($res['body'])) {
        $messageId = $res['body']['id']
            ?? $res['body']['message_id']
            ?? $res['body']['messageId']
            ?? null;
    }

    return [
        'ok' => (bool) $res['ok'],
        'provider' => 'plunk',
        'message_id' => $messageId,
        'status_code' => (int) $res['status_code'],
        'error_code' => (string) ($res['body']['code'] ?? ''),
        'error_message' => $res['error'],
        'raw' => $res['body'],
    ];
}

function plunkTrackEvent(string $eventName, array $properties = [], ?string $email = null): bool {
    $payload = [
        'event' => $eventName,
        'data' => $properties,
    ];
    if ($email !== null && $email !== '') {
        $payload['email'] = $email;
    }

    $res = plunkRequest('POST', '/v1/track', $payload, true);
    return (bool) $res['ok'];
}

function plunkUpsertContact(string $email, array $attributes = [], ?bool $subscribed = null): bool {
    $payload = [
        'email' => $email,
    ];

    if (!empty($attributes)) {
        $payload['subscribed'] = $subscribed;
        $payload['data'] = $attributes;
    } elseif ($subscribed !== null) {
        $payload['subscribed'] = $subscribed;
    }

    $res = plunkRequest('POST', '/v1/contacts', $payload, false);
    return (bool) $res['ok'];
}
