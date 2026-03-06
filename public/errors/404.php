<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';

http_response_code(404);
$appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
$currentLang = getCurrentLanguage();
?>
<!doctype html>
<html lang="<?= htmlspecialchars(getHtmlLang(), ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars(__('errors.page_not_found_title'), ENT_QUOTES, 'UTF-8') ?> | <?= htmlspecialchars($appName, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body { margin: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; background: #0f172a; color: #e2e8f0; }
        main { min-height: 100vh; display: grid; place-items: center; padding: 24px; }
        .card { max-width: 560px; width: 100%; background: #111827; border: 1px solid #334155; border-radius: 16px; padding: 32px; text-align: center; }
        h1 { margin: 0 0 12px; font-size: 2rem; }
        p { margin: 0 0 20px; color: #94a3b8; }
        a { display: inline-block; background: #facc15; color: #0f172a; text-decoration: none; padding: 12px 18px; border-radius: 10px; font-weight: 600; }
    </style>
</head>
<body>
<main>
    <section class="card">
        <h1><?= htmlspecialchars(__('errors.page_not_found_title'), ENT_QUOTES, 'UTF-8') ?></h1>
        <p><?= htmlspecialchars(__('errors.page_not_found_message'), ENT_QUOTES, 'UTF-8') ?></p>
        <a href="<?= htmlspecialchars(routeUrl('home', $currentLang), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(__('errors.back_home'), ENT_QUOTES, 'UTF-8') ?></a>
    </section>
</main>
</body>
</html>
