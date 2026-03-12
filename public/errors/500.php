<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';

http_response_code(500);
$appName = $_ENV['APP_NAME'] ?? 'AJI';
$errorId = $errorId ?? bin2hex(random_bytes(4));
$showDetails = $showDetails ?? false;
$errorMessage = $errorMessage ?? null;
$currentLang = getCurrentLanguage();
?>
<!doctype html>
<html lang="<?= htmlspecialchars(getHtmlLang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(__('errors.server_error_title'), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #0f172a; color: #e2e8f0; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { max-width: 680px; width: 100%; background: #111827; border: 1px solid #334155; border-radius: 16px; padding: 32px; }
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { margin: 0 0 16px; color: #94a3b8; }
        code { display: inline-block; background: #0b1220; border: 1px solid #334155; padding: 2px 8px; border-radius: 8px; color: #f8fafc; }
        pre { margin: 0; padding: 16px; border-radius: 12px; background: #0b1220; border: 1px solid #334155; color: #f8fafc; overflow: auto; }
        a { display: inline-block; margin-top: 20px; background: #facc15; color: #0f172a; text-decoration: none; padding: 12px 18px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <section class="card">
        <h1><?= htmlspecialchars(__('errors.server_error_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars(__('errors.server_error_message'), ENT_QUOTES, 'UTF-8') ?> <code><?= htmlspecialchars($errorId, ENT_QUOTES, 'UTF-8') ?></code>.</p>
        <?php if ($showDetails && $errorMessage): ?>
            <pre><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></pre>
        <?php endif; ?>
        <a href="<?= htmlspecialchars(routeUrl('home', $currentLang), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('errors.back_home'), ENT_QUOTES, 'UTF-8') ?></a>
    </section>
</main>
</body>
</html>
