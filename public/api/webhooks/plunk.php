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
