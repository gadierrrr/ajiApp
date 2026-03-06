<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/referrals.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    try {
        @session_start();
    } catch (Throwable $e) {
        // Click logging still works without an authenticated session.
    }
}

$campaignSlug = trim((string) ($_GET['c'] ?? ''));
if ($campaignSlug === '') {
    http_response_code(400);
    echo 'Missing referral campaign.';
    exit;
}

$context = [
    'page_type' => trim((string) ($_GET['page_type'] ?? '')),
    'page_slug' => trim((string) ($_GET['page_slug'] ?? '')),
    'placement' => trim((string) ($_GET['placement'] ?? '')),
    'locale' => trim((string) ($_GET['locale'] ?? '')),
    'block_slug' => trim((string) ($_GET['block_slug'] ?? '')),
];

$result = referralResolveRedirect($campaignSlug, $context);
if (!($result['ok'] ?? false)) {
    http_response_code((int) ($result['status'] ?? 404));
    echo h((string) ($result['message'] ?? 'Referral not found'));
    exit;
}

// Sanitize target URL to prevent header injection (newlines, null bytes)
$targetUrl = (string) $result['target_url'];
$targetUrl = str_replace(["\r", "\n", "\0"], '', $targetUrl);

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Location: ' . $targetUrl, true, 302);
exit;
