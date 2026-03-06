<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/guide_cms.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
if ($slug === '' && isset($_SERVER['REQUEST_URI'])) {
    $path = (string) (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '');
    if (preg_match('#^/guides/([a-z0-9-]+)$#', $path, $matches)) {
        $slug = $matches[1];
    }
}

if ($slug === '' || !preg_match('/^[a-z0-9-]+$/', $slug)) {
    http_response_code(404);
    include APP_ROOT . '/public/errors/404.php';
    exit;
}

if ($slug === 'cms-router' || $slug === 'index') {
    redirect('/guides/');
}

$lang = function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'en';
$lang = $lang === 'es' ? 'es' : 'en';

if (guideCmsRenderBySlug($slug, $lang)) {
    exit;
}

// Fallback to legacy static template if CMS article doesn't exist yet.
$fallback = APP_ROOT . '/public/guides/' . $slug . '.php';
$real = realpath($fallback);
$guidesRoot = realpath(APP_ROOT . '/public/guides');

if (is_string($real) && is_string($guidesRoot)
    && str_starts_with($real, $guidesRoot . DIRECTORY_SEPARATOR)
    && is_file($real)
    && basename($real) !== 'cms-router.php') {
    ob_start();
    require $real;
    echo (string) ob_get_clean();
    exit;
}

http_response_code(404);
include APP_ROOT . '/public/errors/404.php';
exit;
