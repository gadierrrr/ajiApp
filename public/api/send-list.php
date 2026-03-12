<?php
/**
 * API: Send a beach list to email (no account required).
 *
 * POST /api/send-list.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/email.php';
require_once APP_ROOT . '/inc/rate_limiter.php';
require_once APP_ROOT . '/inc/constants.php';
require_once APP_ROOT . '/inc/collection_query.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(['success' => false, 'error' => 'Method not allowed'], 405);
}

// Honeypot
if (!empty($_POST['website'] ?? '')) {
    jsonResponse(['success' => true]);
}

$email = trim((string)($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['success' => false, 'error' => 'Please enter a valid email address.'], 400);
}

$contextType = trim((string)($_POST['context_type'] ?? ''));
$contextKey = trim((string)($_POST['context_key'] ?? ''));
$filtersQuery = (string)($_POST['filters_query'] ?? '');
$pagePath = sanitizeInternalRedirect((string)($_POST['page_path'] ?? '/'), '/');

if (!in_array($contextType, ['municipality', 'collection'], true) || $contextKey === '') {
    jsonResponse(['success' => false, 'error' => 'Invalid request.'], 400);
}

// Rate limiting
$rateLimiter = new RateLimiter(getDB());
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$ipLimit = $rateLimiter->check($ip, 'send_list_ip', 10, 60);
if (!$ipLimit['allowed']) {
    jsonResponse(['success' => false, 'error' => 'Too many requests. Please try again later.'], 429);
}
$emailLimit = $rateLimiter->check(strtolower($email), 'send_list_email', 3, 60);
if (!$emailLimit['allowed']) {
    jsonResponse(['success' => false, 'error' => 'Too many requests for this email. Please try again later.'], 429);
}

// Parse filter params from query string (if present)
$filters = [];
if ($filtersQuery !== '') {
    parse_str(ltrim($filtersQuery, '?'), $filters);
}

// Build beach list
$beaches = [];
$title = 'Your Puerto Rico Beach List';
$subtitle = '';

if ($contextType === 'municipality') {
    $municipalitySlug = $contextKey;
    $municipality = ucwords(str_replace('-', ' ', $municipalitySlug));
    if (!isValidMunicipality($municipality)) {
        jsonResponse(['success' => false, 'error' => 'Unknown municipality.'], 400);
    }

    $title = "Beaches in {$municipality}, Puerto Rico";
    $subtitle = "Here are beaches in {$municipality} with Google Maps directions links.";

    $beaches = query("
        SELECT b.*
        FROM beaches b
        WHERE b.municipality = :municipality
        AND b.publish_status = 'published'
        ORDER BY
            CASE WHEN b.google_rating IS NOT NULL THEN 1 ELSE 2 END,
            b.google_rating DESC,
            b.name ASC
    ", [':municipality' => $municipality]) ?: [];

    attachBeachMetadata($beaches);
} else {
    $collectionKey = $contextKey;
    if (!isValidCollectionKey($collectionKey)) {
        jsonResponse(['success' => false, 'error' => 'Unknown collection.'], 400);
    }

    $collectionData = fetchCollectionBeaches($collectionKey, $filters);
    $collection = $collectionData['collection'] ?? [];
    $title = (string)($collection['title'] ?? 'Puerto Rico Beach List');
    $subtitle = (string)($collection['subtitle'] ?? 'Here are your requested beaches with directions links.');
    $beaches = $collectionData['beaches'] ?? [];
}

if (empty($beaches)) {
    jsonResponse(['success' => false, 'error' => 'No beaches found for this request.'], 404);
}

// Cap email size
$beaches = array_slice($beaches, 0, 40);

$appUrl = getPublicBaseUrl();
$pageUrl = absoluteUrl($pagePath . ($filtersQuery !== '' ? ('?' . ltrim($filtersQuery, '?')) : ''));

// Build email HTML (fallback if template missing)
$itemsHtml = '';
foreach ($beaches as $beach) {
    $name = h($beach['name'] ?? 'Beach');
    $muni = h($beach['municipality'] ?? '');
    $detailUrl = absoluteUrl('/beach/' . ($beach['slug'] ?? ''));
    $directionsUrl = h(getDirectionsUrl($beach));
    $itemsHtml .= "<li style=\"margin: 0 0 10px;\"><strong>{$name}</strong> <span style=\"color:#64748b;\">({$muni})</span><br>"
        . "<a href=\"{$detailUrl}\" style=\"color:#fbbf24;text-decoration:none;\">View details</a> · "
        . "<a href=\"{$directionsUrl}\" style=\"color:#fbbf24;text-decoration:none;\">Directions</a></li>";
}

$html = <<<HTML
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#1e293b;border-radius:16px;overflow:hidden;border:1px solid #334155;">
      <div style="padding:20px 24px;background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);color:#0f172a;">
        <h1 style="margin:0;font-size:20px;">{$title}</h1>
        <p style="margin:8px 0 0;opacity:.85;">{$subtitle}</p>
      </div>
      <div style="padding:22px 24px;color:#e2e8f0;">
        <p style="margin:0 0 14px;color:#94a3b8;">Open this page again anytime:</p>
        <p style="margin:0 0 18px;"><a href="{$pageUrl}" style="color:#fbbf24;text-decoration:none;">{$pageUrl}</a></p>
        <ol style="margin:0;padding-left:18px;line-height:1.4;">{$itemsHtml}</ol>
        <p style="margin:18px 0 0;color:#64748b;font-size:12px;">Sent by <a href="{$appUrl}" style="color:#fbbf24;text-decoration:none;">AJI</a>.</p>
      </div>
    </div>
  </div>
</body>
</html>
HTML;

$sent = sendTemplateEmail('list-send', $email, [
    'title' => $title,
    'subtitle' => $subtitle,
    'page_url' => $pageUrl,
    'items_html' => $itemsHtml,
]);

if (!$sent) {
    $sent = sendEmail($email, $title, $html, [
        'template_slug' => 'list-send',
        'category' => 'non_critical',
    ]);
}

// Record request for auditing/rate limiting (best-effort).
execute("
    INSERT INTO lead_requests (id, email, context_type, context_key, filters_query, page_path, ip_hash, requested_at)
    VALUES (:id, :email, :context_type, :context_key, :filters_query, :page_path, :ip_hash, datetime('now'))
", [
    ':id' => uuid(),
    ':email' => strtolower($email),
    ':context_type' => $contextType,
    ':context_key' => $contextKey,
    ':filters_query' => $filtersQuery,
    ':page_path' => $pagePath,
    ':ip_hash' => hash('sha256', $ip),
]);

if (!$sent) {
    jsonResponse(['success' => false, 'error' => 'Email sending is not configured.'], 500);
}

jsonResponse(['success' => true]);
