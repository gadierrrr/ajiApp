<?php
/**
 * Self-service account deletion endpoint.
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
session_cache_limiter('');
session_start();

require_once APP_ROOT . '/inc/helpers.php';
require_once APP_ROOT . '/inc/account.php';
require_once APP_ROOT . '/inc/auth.php';

function accountDeleteRedirect(string $tab, string $error = ''): void
{
    $validTabs = ['favorites', 'reviews', 'photos', 'checkins'];
    if (!in_array($tab, $validTabs, true)) {
        $tab = 'favorites';
    }

    $params = ['tab' => $tab];
    if ($error !== '') {
        $params['delete_error'] = $error;
    }

    redirect('/profile?' . http_build_query($params));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    accountDeleteRedirect('favorites', 'method');
}

requireAuth();

$tab = trim((string) ($_POST['redirect_tab'] ?? 'favorites'));
$validTabs = ['favorites', 'reviews', 'photos', 'checkins'];
if (!in_array($tab, $validTabs, true)) {
    $tab = 'favorites';
}

if (!validateCsrf($_POST['csrf_token'] ?? '')) {
    accountDeleteRedirect($tab, 'csrf');
}

$user = currentUser();
if (!is_array($user) || empty($user['id'])) {
    logout();
    redirect('/login');
}

$expectedEmail = strtolower(trim((string) ($user['email'] ?? '')));
$submittedEmail = strtolower(trim((string) ($_POST['confirm_email'] ?? '')));
if ($expectedEmail === '' || $submittedEmail === '' || !hash_equals($expectedEmail, $submittedEmail)) {
    accountDeleteRedirect($tab, 'email');
}

$confirmPhrase = trim((string) ($_POST['confirm_phrase'] ?? ''));
if (!hash_equals('DELETE', $confirmPhrase)) {
    accountDeleteRedirect($tab, 'phrase');
}

$result = deleteUserAccount(
    (string) $user['id'],
    (string) $user['id'],
    [
        'reason' => 'self_service_delete',
        'self_service' => true,
    ]
);

if (!($result['success'] ?? false)) {
    $errorCode = trim((string) ($result['code'] ?? ''));
    if (!in_array($errorCode, ['last_admin'], true)) {
        $errorCode = 'failed';
    }

    accountDeleteRedirect($tab, $errorCode);
}

logout();
redirect('/login?account_deleted=1');
