<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

$lang = getCurrentLanguage();
$pageTitle = $lang === 'es' ? 'Política de Privacidad' : 'Privacy Policy';
$pageDescription = $lang === 'es' ? 'Política de Privacidad de Puerto Rico Beach Finder.' : 'Privacy Policy for Puerto Rico Beach Finder.';
$pageTheme = 'light';

$pageShellMode = 'start';
include APP_ROOT . '/components/page-shell.php';

if ($lang === 'es') {
    include APP_ROOT . '/components/legal/privacy-es.php';
} else {
    include APP_ROOT . '/components/legal/privacy-en.php';
}

$pageShellMode = 'end';
include APP_ROOT . '/components/page-shell.php';
?>
