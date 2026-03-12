<?php
declare(strict_types=1);

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/locale_routes.php';
require_once APP_ROOT . '/inc/i18n.php';

$lang = getCurrentLanguage();
$pageTitle = $lang === 'es' ? 'Términos de Servicio' : 'Terms of Service';
$pageDescription = $lang === 'es' ? 'Términos de Servicio de AJI.' : 'Terms of Service for AJI.';
$pageTheme = 'light';

$pageShellMode = 'start';
include APP_ROOT . '/components/page-shell.php';

if ($lang === 'es') {
    include APP_ROOT . '/components/legal/terms-es.php';
} else {
    include APP_ROOT . '/components/legal/terms-en.php';
}

$pageShellMode = 'end';
include APP_ROOT . '/components/page-shell.php';
?>
