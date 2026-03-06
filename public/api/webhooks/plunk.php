<?php
/**
 * Plunk webhooks endpoint.
 *
 * Handles delivery lifecycle and suppression events.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/email.php';

header('Content-Type: application/json');

function plunkWebhookNormalizeEnvTag(?string $value): string
{
    $normalized = strtolower(trim((string) $value));
    return in_array($normalized, ['dev', 'staging', 'prod'], true) ? $normalized : '';
}

function plunkWebhookExpectedEnvTag(): string
{
    return plunkWebhookNormalizeEnvTag(env('PLUNK_WEBHOOK_EXPECT_ENV', ''));
}

function plunkWebhookProvidedEnvTag(): string
{
    $candidates = [
        $_GET['env'] ?? null,
        $_SERVER['HTTP_X_WEBHOOK_ENV'] ?? null,
        $_SERVER['HTTP_X_APP_ENV'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        $normalized = plunkWebhookNormalizeEnvTag($candidate);
        if ($normalized !== '') {
            return $normalized;
        }
    }

    return '';
}

function plunkWebhookArrayPath(array $payload, array $path)
{
    $value = $payload;
    foreach ($path as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return null;
        }
        $value = $value[$segment];
    }

    return $value;
}

function plunkWebhookParseBoolish($value): ?bool
{
    if (is_bool($value)) {
        return $value;
    }

    if (is_int($value) || is_float($value)) {
        if ((int) $value === 1) {
            return true;
        }
        if ((int) $value === 0) {
            return false;
        }
    }

    if (!is_string($value)) {
        return null;
    }

    $normalized = strtolower(trim($value));
    return match ($normalized) {
        '1', 'true', 'yes', 'on' => true,
        '0', 'false', 'no', 'off' => false,
        default => null,
    };
}

function plunkWebhookModeFromString($value): string
{
    if (!is_scalar($value)) {
        return '';
    }

    $normalized = strtolower(trim((string) $value));
    if ($normalized === '') {
        return '';
    }

    if (in_array($normalized, ['live', 'prod', 'production'], true)) {
        return 'live';
    }

    if (in_array($normalized, ['test', 'testing', 'sandbox', 'staging', 'dev', 'development'], true)) {
        return 'test';
    }

    return '';
}

function plunkWebhookDetectedMode(array $payload): string
{
    $boolCandidates = [
        [['livemode'], 'livemode'],
        [['live_mode'], 'livemode'],
        [['data', 'livemode'], 'livemode'],
        [['data', 'live_mode'], 'livemode'],
        [['test_mode'], 'test_mode'],
        [['sandbox'], 'test_mode'],
        [['data', 'test_mode'], 'test_mode'],
        [['data', 'sandbox'], 'test_mode'],
    ];

    foreach ($boolCandidates as [$path, $kind]) {
        $parsed = plunkWebhookParseBoolish(plunkWebhookArrayPath($payload, $path));
        if ($parsed === null) {
            continue;
        }

        if ($kind === 'livemode') {
            return $parsed ? 'live' : 'test';
        }

        return $parsed ? 'test' : 'live';
    }

    $stringCandidates = [
        ['mode'],
        ['environment'],
        ['data', 'mode'],
        ['data', 'environment'],
        ['meta', 'mode'],
        ['meta', 'environment'],
    ];

    foreach ($stringCandidates as $path) {
        $mode = plunkWebhookModeFromString(plunkWebhookArrayPath($payload, $path));
        if ($mode !== '') {
            return $mode;
        }
    }

    return '';
}

function plunkWebhookReject(string $error): void
{
    jsonResponse(['success' => false, 'error' => $error], 409);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

$rawBody = file_get_contents('php://input');
if (!is_string($rawBody) || $rawBody === '') {
    jsonResponse(['success' => false, 'error' => 'Empty body'], 400);
}

$secret = (string) env('PLUNK_WEBHOOK_SECRET', '');
if ($secret !== '') {
    $signature = (string) ($_SERVER['HTTP_X_PLUNK_SIGNATURE'] ?? $_SERVER['HTTP_PLUNK_SIGNATURE'] ?? '');
    if ($signature === '') {
        jsonResponse(['success' => false, 'error' => 'Missing signature'], 401);
    }

    $calculated = hash_hmac('sha256', $rawBody, $secret);
    $normalizedSig = str_starts_with($signature, 'sha256=') ? substr($signature, 7) : $signature;

    if (!hash_equals($calculated, $normalizedSig)) {
        jsonResponse(['success' => false, 'error' => 'Invalid signature'], 401);
    }
}

$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    jsonResponse(['success' => false, 'error' => 'Invalid JSON'], 400);
}

$expectedEnvTag = plunkWebhookExpectedEnvTag();
if ($expectedEnvTag !== '') {
    $providedEnvTag = plunkWebhookProvidedEnvTag();
    if ($providedEnvTag === '' || !hash_equals($expectedEnvTag, $providedEnvTag)) {
        plunkWebhookReject('Webhook environment mismatch');
    }
}

$detectedMode = plunkWebhookDetectedMode($payload);
$currentEnv = appEnv();
if ($currentEnv === 'prod' && $detectedMode === 'test') {
    plunkWebhookReject('Test webhook rejected in production');
}
if (in_array($currentEnv, ['dev', 'staging'], true) && $detectedMode === 'live') {
    plunkWebhookReject('Live webhook rejected outside production');
}

$eventType = (string) ($payload['type'] ?? $payload['event'] ?? '');
$providerEventId = (string) ($payload['id'] ?? $payload['event_id'] ?? '');
$data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

$messageId = (string) ($data['message_id'] ?? $payload['message_id'] ?? '');
$email = emailNormalizeAddress((string) ($data['email'] ?? $data['to'] ?? $payload['email'] ?? ''));
$occurredAt = (string) ($payload['created_at'] ?? $payload['occurred_at'] ?? date('c'));

if ($eventType === '') {
    jsonResponse(['success' => false, 'error' => 'Missing event type'], 400);
}

$localMessageId = null;
if ($messageId !== '' && emailTrackingTablesAvailable()) {
    $row = queryOne('SELECT id FROM email_messages WHERE provider_message_id = :provider_message_id', [
        ':provider_message_id' => $messageId,
    ]);
    if (is_array($row) && !empty($row['id'])) {
        $localMessageId = (string) $row['id'];
    }
}

emailRecordEvent($localMessageId, 'webhook_' . $eventType, $payload, $providerEventId !== '' ? $providerEventId : null, $occurredAt);

$statusMap = [
    'delivered' => 'delivered',
    'opened' => 'opened',
    'clicked' => 'clicked',
    'bounced' => 'bounced',
    'bounce' => 'bounced',
    'complained' => 'complained',
    'spam' => 'complained',
    'unsubscribed' => 'unsubscribed',
];

if ($localMessageId !== null && isset($statusMap[$eventType])) {
    emailUpdateMessage($localMessageId, [
        'status' => $statusMap[$eventType],
    ]);
}

if ($email !== '' && in_array($eventType, ['unsubscribed', 'complained', 'spam'], true)) {
    $reason = $eventType === 'unsubscribed' ? 'plunk_unsubscribed' : 'plunk_complaint';
    emailUpsertContactState($email, [
        'unsubscribed' => true,
        'suppressed_reason' => $reason,
    ]);
}

jsonResponse(['success' => true]);
