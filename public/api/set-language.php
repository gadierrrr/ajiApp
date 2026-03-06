<?php
/**
 * Set Language API
 * Changes the user's language preference
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_start();
require_once APP_ROOT . '/inc/i18n.php';
require_once APP_ROOT . '/inc/locale_routes.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$lang = $_POST['lang'] ?? $_GET['lang'] ?? '';
$redirect = (string) ($_POST['redirect'] ?? $_GET['redirect'] ?? '');

if (!in_array($lang, SUPPORTED_LANGUAGES, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid language']);
    exit;
}

setLanguage($lang);

// Build a safe internal redirect URL.
$redirectPath = '/';
$redirectQuery = '';
$queryMap = [];
if ($redirect !== '') {
    $redirectValue = trim($redirect);

    if (preg_match('#^https?://#i', $redirectValue) === 1) {
        $parsedPath = (string) (parse_url($redirectValue, PHP_URL_PATH) ?? '/');
        $redirectQuery = (string) (parse_url($redirectValue, PHP_URL_QUERY) ?? '');
    } else {
        $fragmentParts = explode('#', $redirectValue, 2);
        $pathAndQuery = $fragmentParts[0];
        $queryParts = explode('?', $pathAndQuery, 2);
        $parsedPath = $queryParts[0] !== '' ? $queryParts[0] : '/';
        $redirectQuery = $queryParts[1] ?? '';
    }

    if ($parsedPath === '' || $parsedPath[0] !== '/') {
        $parsedPath = '/';
    }

    $redirectPath = $parsedPath;
    if ($redirectQuery !== '') {
        parse_str($redirectQuery, $queryMap);
        if (!is_array($queryMap)) {
            $queryMap = [];
        }
    }
}

$normalizedRedirectPath = normalizeLocalePath($redirectPath);
$routeMatch = localeRouteMatch($normalizedRedirectPath);
$isLegacyBeach = $normalizedRedirectPath === '/beach.php' && trim((string) ($queryMap['slug'] ?? '')) !== '';
$isLegacyMunicipality = $normalizedRedirectPath === '/municipality.php' && trim((string) ($queryMap['m'] ?? '')) !== '';
$isScriptMapped = false;
if (!$routeMatch) {
    foreach (localeRoutes() as $routeDef) {
        $scriptPath = normalizeLocalePath((string) ($routeDef['script'] ?? ''));
        if ($scriptPath !== '' && $scriptPath === $normalizedRedirectPath) {
            $isScriptMapped = true;
            break;
        }
    }
}
$isKnownPath = is_array($routeMatch) || $isLegacyBeach || $isLegacyMunicipality || $isScriptMapped;

$redirectUrl = $isKnownPath
    ? localizePathAndQuery($redirectPath, $redirectQuery, $lang)
    : routeUrl('home', $lang);

echo json_encode([
    'success' => true,
    'language' => $lang,
    'name' => getLanguageName($lang),
    'redirect_url' => $redirectUrl,
]);
