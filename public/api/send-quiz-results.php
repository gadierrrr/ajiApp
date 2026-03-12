<?php
/**
 * API: Email quiz results (no account required).
 *
 * POST /api/send-quiz-results.php
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/email.php';
require_once APP_ROOT . '/inc/rate_limiter.php';

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

$token = trim((string)($_POST['results_token'] ?? ''));
if ($token === '') {
    jsonResponse(['success' => false, 'error' => 'Missing results token.'], 400);
}

// Rate limiting
$rateLimiter = new RateLimiter(getDB());
$ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
$ipLimit = $rateLimiter->check($ip, 'send_quiz_results_ip', 10, 60);
if (!$ipLimit['allowed']) {
    jsonResponse(['success' => false, 'error' => 'Too many requests. Please try again later.'], 429);
}
$emailLimit = $rateLimiter->check(strtolower($email), 'send_quiz_results_email', 3, 60);
if (!$emailLimit['allowed']) {
    jsonResponse(['success' => false, 'error' => 'Too many requests for this email. Please try again later.'], 429);
}

$row = queryOne('SELECT * FROM quiz_results WHERE token = :token', [':token' => $token]);
if (!$row) {
    jsonResponse(['success' => false, 'error' => 'Quiz results not found.'], 404);
}

$matches = json_decode((string)($row['matched_beaches'] ?? '[]'), true);
if (!is_array($matches) || empty($matches)) {
    jsonResponse(['success' => false, 'error' => 'No results found to send.'], 404);
}

$appUrl = getPublicBaseUrl();
$resultsUrl = absoluteUrl('/quiz-results?token=' . urlencode($token));

$itemsHtml = '';
$i = 0;
foreach ($matches as $m) {
    if (!is_array($m)) continue;
    $i++;
    $name = h((string)($m['name'] ?? 'Beach'));
    $muni = h((string)($m['municipality'] ?? ''));
    $slug = (string)($m['slug'] ?? '');
    $score = (int)($m['score'] ?? 0);
    $detailUrl = $slug !== '' ? absoluteUrl('/beach/' . $slug) : $resultsUrl;
    $itemsHtml .= "<li style=\"margin: 0 0 10px;\"><strong>{$name}</strong> <span style=\"color:#64748b;\">({$muni})</span> <span style=\"color:#fbbf24;\">{$score}%</span><br>"
        . "<a href=\"{$detailUrl}\" style=\"color:#fbbf24;text-decoration:none;\">View details</a></li>";
    if ($i >= 8) break;
}

$subject = 'Your Puerto Rico Beach Matches';
$html = <<<HTML
<!doctype html>
<html>
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#0f172a;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Arial,sans-serif;">
  <div style="max-width:640px;margin:0 auto;padding:24px;">
    <div style="background:#1e293b;border-radius:16px;overflow:hidden;border:1px solid #334155;">
      <div style="padding:20px 24px;background:linear-gradient(135deg,#fbbf24 0%,#f59e0b 100%);color:#0f172a;">
        <h1 style="margin:0;font-size:20px;">{$subject}</h1>
        <p style="margin:8px 0 0;opacity:.85;">Open the full list + map links here:</p>
      </div>
      <div style="padding:22px 24px;color:#e2e8f0;">
        <p style="margin:0 0 18px;"><a href="{$resultsUrl}" style="color:#fbbf24;text-decoration:none;\">{$resultsUrl}</a></p>
        <ol style="margin:0;padding-left:18px;line-height:1.4;">{$itemsHtml}</ol>
        <p style="margin:18px 0 0;color:#64748b;font-size:12px;">Sent by <a href="{$appUrl}" style="color:#fbbf24;text-decoration:none;\">AJI</a>.</p>
      </div>
    </div>
  </div>
</body>
</html>
HTML;

$sent = sendTemplateEmail('quiz-results', $email, [
    'results_url' => $resultsUrl,
    'items_html' => $itemsHtml,
]);

if (!$sent) {
    $sent = sendEmail($email, $subject, $html, [
        'template_slug' => 'quiz-results',
        'category' => 'non_critical',
    ]);
}

if (!$sent) {
    jsonResponse(['success' => false, 'error' => 'Email sending is not configured.'], 500);
}

jsonResponse(['success' => true]);
