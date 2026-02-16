<?php
// inc/email.php - email delivery service (Plunk-only)

if (defined('EMAIL_PHP_INCLUDED')) {
    return;
}
define('EMAIL_PHP_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/email_provider_plunk.php';

const EMAIL_TEMPLATE_CATEGORY_MAP = [
    'magic-link' => 'critical_auth',
    'welcome' => 'non_critical',
    'list-send' => 'non_critical',
    'quiz-results' => 'non_critical',
];

function getEmailTemplate($slug) {
    return queryOne(
        'SELECT * FROM email_templates WHERE slug = :slug AND is_active = 1',
        [':slug' => $slug]
    );
}

function renderEmailTemplate($template, $variables) {
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', $value ?? '', $template);
    }
    return $template;
}

function emailNormalizeAddress(string $email): string {
    return strtolower(trim($email));
}

function emailHash(string $email): string {
    return hash('sha256', emailNormalizeAddress($email));
}

function emailTrackingTablesAvailable(): bool {
    static $available = null;
    if ($available !== null) {
        return $available;
    }

    $row = queryOne("SELECT name FROM sqlite_master WHERE type='table' AND name='email_messages'");
    $available = is_array($row) && !empty($row['name']);
    return $available;
}

function emailCategoryForSlug(?string $slug, ?string $explicitCategory = null): string {
    if ($explicitCategory !== null && $explicitCategory !== '') {
        return $explicitCategory;
    }

    if ($slug !== null && isset(EMAIL_TEMPLATE_CATEGORY_MAP[$slug])) {
        return EMAIL_TEMPLATE_CATEGORY_MAP[$slug];
    }

    return 'non_critical';
}

function emailIsCriticalCategory(string $category): bool {
    return $category === 'critical_auth';
}

function emailProviderMode(): string {
    return 'plunk';
}

function emailGetFromParts(array $options = []): array {
    $appUrl = (string) env('APP_URL', 'https://puertoricobeachfinder.com');
    $appName = (string) env('APP_NAME', 'Beach Finder');
    $domain = parse_url($appUrl, PHP_URL_HOST) ?: 'puertoricobeachfinder.com';
    if (str_starts_with($domain, 'www.')) {
        $domain = substr($domain, 4);
    }

    return [
        'name' => (string) ($options['from_name'] ?? $appName),
        'address' => (string) ($options['from_address'] ?? ('noreply@' . $domain)),
    ];
}

function emailRecordMessage(array $data): ?string {
    if (!emailTrackingTablesAvailable()) {
        return null;
    }

    $id = uuid();

    execute(
        "INSERT INTO email_messages (
            id, template_slug, category, to_email_hash, provider, provider_message_id,
            status, failure_code, failure_reason, sent_at, created_at, updated_at
        ) VALUES (
            :id, :template_slug, :category, :to_email_hash, :provider, :provider_message_id,
            :status, :failure_code, :failure_reason, :sent_at, datetime('now'), datetime('now')
        )",
        [
            ':id' => $id,
            ':template_slug' => (string) ($data['template_slug'] ?? ''),
            ':category' => (string) ($data['category'] ?? 'non_critical'),
            ':to_email_hash' => (string) ($data['to_email_hash'] ?? ''),
            ':provider' => (string) ($data['provider'] ?? ''),
            ':provider_message_id' => (string) ($data['provider_message_id'] ?? ''),
            ':status' => (string) ($data['status'] ?? 'pending'),
            ':failure_code' => (string) ($data['failure_code'] ?? ''),
            ':failure_reason' => (string) ($data['failure_reason'] ?? ''),
            ':sent_at' => (string) ($data['sent_at'] ?? ''),
        ]
    );

    return $id;
}

function emailUpdateMessage(string $id, array $data): void {
    if (!emailTrackingTablesAvailable() || $id === '') {
        return;
    }

    execute(
        "UPDATE email_messages
         SET provider = COALESCE(NULLIF(:provider, ''), provider),
             provider_message_id = COALESCE(NULLIF(:provider_message_id, ''), provider_message_id),
             status = COALESCE(NULLIF(:status, ''), status),
             failure_code = COALESCE(NULLIF(:failure_code, ''), failure_code),
             failure_reason = COALESCE(NULLIF(:failure_reason, ''), failure_reason),
             sent_at = CASE WHEN :sent_at <> '' THEN :sent_at ELSE sent_at END,
             updated_at = datetime('now')
         WHERE id = :id",
        [
            ':id' => $id,
            ':provider' => (string) ($data['provider'] ?? ''),
            ':provider_message_id' => (string) ($data['provider_message_id'] ?? ''),
            ':status' => (string) ($data['status'] ?? ''),
            ':failure_code' => (string) ($data['failure_code'] ?? ''),
            ':failure_reason' => (string) ($data['failure_reason'] ?? ''),
            ':sent_at' => (string) ($data['sent_at'] ?? ''),
        ]
    );
}

function emailRecordEvent(?string $messageId, string $eventType, array $payload = [], ?string $providerEventId = null, ?string $occurredAt = null): void {
    if (!emailTrackingTablesAvailable()) {
        return;
    }

    $normalizedMessageId = null;
    if (is_string($messageId) && trim($messageId) !== '') {
        $normalizedMessageId = $messageId;
    }

    execute(
        "INSERT OR IGNORE INTO email_events (
            id, email_message_id, event_type, provider_event_id, payload_json, occurred_at, created_at
        ) VALUES (
            :id, :email_message_id, :event_type, :provider_event_id, :payload_json,
            COALESCE(NULLIF(:occurred_at, ''), datetime('now')), datetime('now')
        )",
        [
            ':id' => uuid(),
            ':email_message_id' => $normalizedMessageId,
            ':event_type' => $eventType,
            ':provider_event_id' => (string) ($providerEventId ?? ''),
            ':payload_json' => json_encode($payload, JSON_UNESCAPED_SLASHES),
            ':occurred_at' => (string) ($occurredAt ?? ''),
        ]
    );
}

function emailGetContactState(string $email): ?array {
    if (!emailTrackingTablesAvailable()) {
        return null;
    }

    return queryOne(
        'SELECT * FROM email_contacts WHERE email_hash = :email_hash',
        [':email_hash' => emailHash($email)]
    );
}

function emailUpsertContactState(string $email, array $updates = []): void {
    if (!emailTrackingTablesAvailable()) {
        return;
    }

    $emailHash = emailHash($email);
    $plunkContactId = (string) ($updates['plunk_contact_id'] ?? '');
    $unsubscribed = array_key_exists('unsubscribed', $updates) ? ((int) ((bool) $updates['unsubscribed'])) : 0;
    $reason = (string) ($updates['suppressed_reason'] ?? '');

    execute(
        "INSERT INTO email_contacts (
            email_hash, plunk_contact_id, unsubscribed, suppressed_reason, last_synced_at, updated_at
        ) VALUES (
            :email_hash, :plunk_contact_id, :unsubscribed, :suppressed_reason, datetime('now'), datetime('now')
        )
        ON CONFLICT(email_hash) DO UPDATE SET
            plunk_contact_id = CASE WHEN excluded.plunk_contact_id <> '' THEN excluded.plunk_contact_id ELSE email_contacts.plunk_contact_id END,
            unsubscribed = CASE WHEN :set_unsubscribed = 1 THEN excluded.unsubscribed ELSE email_contacts.unsubscribed END,
            suppressed_reason = CASE WHEN excluded.suppressed_reason <> '' THEN excluded.suppressed_reason ELSE email_contacts.suppressed_reason END,
            last_synced_at = datetime('now'),
            updated_at = datetime('now')",
        [
            ':email_hash' => $emailHash,
            ':plunk_contact_id' => $plunkContactId,
            ':unsubscribed' => $unsubscribed,
            ':suppressed_reason' => $reason,
            ':set_unsubscribed' => array_key_exists('unsubscribed', $updates) ? 1 : 0,
        ]
    );
}

function emailBusinessEventName(?string $templateSlug, string $category): string {
    if ($category === 'admin_test') {
        return 'admin_test_email_sent';
    }

    return match ($templateSlug) {
        'welcome' => 'welcome_email_sent',
        'magic-link' => 'magic_link_email_sent',
        'list-send' => 'list_send_email_sent',
        'quiz-results' => 'quiz_results_email_sent',
        default => 'email_sent',
    };
}

function emailSendInternal(string $to, string $subject, string $html, array $options = []): bool {
    $email = emailNormalizeAddress($to);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $templateSlug = isset($options['template_slug']) ? (string) $options['template_slug'] : null;
    $category = emailCategoryForSlug($templateSlug, isset($options['category']) ? (string) $options['category'] : null);
    $isCritical = isset($options['critical']) ? (bool) $options['critical'] : emailIsCriticalCategory($category);

    // Keep Plunk contact profile in sync (best effort)
    $contactAttrs = [
        'source' => 'beach_finder',
        'last_email_template' => (string) ($templateSlug ?? ''),
        'last_email_category' => $category,
        'last_email_subject' => mb_substr($subject, 0, 120),
    ];
    plunkUpsertContact($email, $contactAttrs, null);
    emailUpsertContactState($email);

    $contactState = emailGetContactState($email);
    $isUnsubscribed = is_array($contactState) && !empty($contactState['unsubscribed']);

    $messageId = emailRecordMessage([
        'template_slug' => (string) ($templateSlug ?? ''),
        'category' => $category,
        'to_email_hash' => emailHash($email),
        'provider' => emailProviderMode(),
        'status' => 'pending',
    ]);

    $eventProps = [
        'template_slug' => (string) ($templateSlug ?? ''),
        'category' => $category,
        'provider_mode' => emailProviderMode(),
    ];

    emailRecordEvent($messageId, 'email_send_attempt', $eventProps);
    plunkTrackEvent('email_send_attempt', $eventProps, $email);

    if ($isUnsubscribed && !$isCritical) {
        $reason = (string) ($contactState['suppressed_reason'] ?? 'unsubscribed');
        emailUpdateMessage((string) $messageId, [
            'status' => 'suppressed',
            'failure_code' => 'suppressed',
            'failure_reason' => $reason,
        ]);
        emailRecordEvent($messageId, 'email_suppressed', ['reason' => $reason] + $eventProps);
        plunkTrackEvent('email_suppressed', ['reason' => $reason] + $eventProps, $email);
        return true;
    }

    $provider = emailProviderMode();
    $from = emailGetFromParts($options);
    $result = plunkSendEmail($email, $subject, $html, [
        'from_name' => $from['name'],
        'from_address' => $from['address'],
    ]);

    if ($result['ok']) {
        emailUpdateMessage((string) $messageId, [
            'provider' => (string) ($result['provider'] ?? ''),
            'provider_message_id' => (string) ($result['message_id'] ?? ''),
            'status' => 'sent',
            'sent_at' => date('c'),
        ]);

        $successProps = [
            'provider_used' => (string) ($result['provider'] ?? ''),
            'message_id' => (string) ($result['message_id'] ?? ''),
        ] + $eventProps;

        emailRecordEvent($messageId, 'email_sent', $successProps);
        plunkTrackEvent('email_sent', $successProps, $email);
        plunkTrackEvent(emailBusinessEventName($templateSlug, $category), $successProps, $email);
        return true;
    }

    emailUpdateMessage((string) $messageId, [
        'provider' => (string) ($result['provider'] ?? $provider),
        'provider_message_id' => (string) ($result['message_id'] ?? ''),
        'status' => 'failed',
        'failure_code' => (string) ($result['error_code'] ?? ''),
        'failure_reason' => (string) ($result['error_message'] ?? 'Unknown email error'),
    ]);

    $failureProps = [
        'provider_used' => (string) ($result['provider'] ?? $provider),
        'error_code' => (string) ($result['error_code'] ?? ''),
        'error_message' => (string) ($result['error_message'] ?? ''),
    ] + $eventProps;

    emailRecordEvent($messageId, 'email_failed', $failureProps);
    plunkTrackEvent('email_failed', $failureProps, $email);

    return false;
}

function sendTemplateEmail($slug, $to, $variables = []) {
    $template = getEmailTemplate($slug);
    if (!$template) {
        error_log("Email template not found or inactive: {$slug}");
        return false;
    }

    $appUrl = (string) env('APP_URL', 'https://www.puertoricobeachfinder.com');
    $appName = (string) env('APP_NAME', 'Puerto Rico Beach Finder');

    $vars = array_merge([
        'app_url' => $appUrl,
        'app_name' => $appName,
    ], $variables);

    $subject = renderEmailTemplate($template['subject'], $vars);
    $html = renderEmailTemplate($template['html_body'], $vars);

    return emailSendInternal((string) $to, (string) $subject, (string) $html, [
        'template_slug' => (string) $slug,
    ]);
}

function sendEmail($to, $subject, $html, $options = []) {
    return emailSendInternal((string) $to, (string) $subject, (string) $html, is_array($options) ? $options : []);
}

function sendWelcomeEmail($email, $name, $preferences = []) {
    $activityText = '';
    if (!empty($preferences['activities'])) {
        $activities = json_decode((string) $preferences['activities'], true) ?: [];
        if (!empty($activities)) {
            $activityLabels = [
                'swimming' => 'swimming spots',
                'snorkeling' => 'snorkeling paradises',
                'surfing' => 'surf breaks',
                'relaxing' => 'relaxing getaways',
                'family' => 'family-friendly beaches',
                'photography' => 'Instagram-worthy views',
                'hiking' => 'hidden coves',
                'secluded' => 'secluded escapes',
            ];
            $matched = array_intersect_key($activityLabels, array_flip($activities));
            if (!empty($matched)) {
                $activityText = '<p style="margin:0 0 20px;color:#fbbf24;font-size:16px;line-height:1.6;">Based on your preferences, we\'ll help you find the best ' . implode(', ', array_slice($matched, 0, 3)) . ' across Puerto Rico.</p>';
            }
        }
    }

    $sent = sendTemplateEmail('welcome', $email, [
        'name' => $name,
        'email' => $email,
        'activity_text' => $activityText,
    ]);

    if ($sent) {
        return true;
    }

    $appUrl = (string) env('APP_URL', 'https://www.puertoricobeachfinder.com');
    $appName = (string) env('APP_NAME', 'Puerto Rico Beach Finder');

    $subject = 'Welcome to ' . $appName . '!';
    $html = '<!doctype html><html><body style="font-family:Arial,sans-serif;background:#0f172a;color:#e2e8f0;padding:24px">'
        . '<h2>Welcome to ' . h($appName) . '</h2>'
        . '<p>Hey ' . h($name) . '! Thanks for joining our beach community.</p>'
        . $activityText
        . '<p><a href="' . h($appUrl) . '" style="color:#fbbf24">Start Exploring Beaches</a></p>'
        . '</body></html>';

    return sendEmail($email, $subject, $html, [
        'template_slug' => 'welcome',
        'category' => 'non_critical',
    ]);
}
