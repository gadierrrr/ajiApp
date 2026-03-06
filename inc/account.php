<?php
/**
 * Account lifecycle helpers.
 */

if (defined('ACCOUNT_HELPERS_INCLUDED')) {
    return;
}
define('ACCOUNT_HELPERS_INCLUDED', true);

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/audit_log.php';

function accountFailure(string $error, string $code): array
{
    return [
        'success' => false,
        'error' => $error,
        'code' => $code,
    ];
}

function accountTableExists(string $table): bool
{
    static $cache = [];

    if (!preg_match('/^[a-z_]+$/', $table)) {
        return false;
    }

    if (array_key_exists($table, $cache)) {
        return $cache[$table];
    }

    $row = queryOne(
        "SELECT name FROM sqlite_master WHERE type = 'table' AND name = :name",
        [':name' => $table]
    );

    $cache[$table] = is_array($row) && !empty($row['name']);
    return $cache[$table];
}

function accountDeleteRowsIfTableExists(string $table, string $whereSql, array $params): void
{
    if (!accountTableExists($table)) {
        return;
    }

    if (!execute("DELETE FROM {$table} WHERE {$whereSql}", $params)) {
        throw new RuntimeException("Failed deleting rows from {$table}");
    }
}

function accountIsLastRemainingAdmin(string $userId): bool
{
    $userId = trim($userId);
    if ($userId === '' || !accountTableExists('users')) {
        return false;
    }

    $user = queryOne(
        'SELECT is_admin FROM users WHERE id = :id LIMIT 1',
        [':id' => $userId]
    );

    if (!is_array($user) || (int) ($user['is_admin'] ?? 0) !== 1) {
        return false;
    }

    $countRow = queryOne('SELECT COUNT(*) AS count FROM users WHERE is_admin = 1');
    return (int) ($countRow['count'] ?? 0) <= 1;
}

function accountCollectUserPhotoFilenames(string $userId): array
{
    if (!accountTableExists('beach_photos')) {
        return [];
    }

    $rows = query(
        'SELECT filename FROM beach_photos WHERE user_id = :user_id',
        [':user_id' => $userId]
    ) ?: [];

    $filenames = [];
    foreach ($rows as $row) {
        $filename = trim((string) ($row['filename'] ?? ''));
        if ($filename === '' || basename($filename) !== $filename) {
            continue;
        }

        $filenames[$filename] = true;
    }

    return array_keys($filenames);
}

function accountDeletePhotoFiles(array $filenames): array
{
    $warnings = [];
    if ($filenames === []) {
        return $warnings;
    }

    $baseDir = APP_ROOT . '/uploads/photos/';
    $thumbDir = $baseDir . 'thumbs/';

    foreach ($filenames as $filename) {
        $paths = [$baseDir . $filename, $thumbDir . $filename];
        foreach ($paths as $path) {
            if (!is_file($path)) {
                continue;
            }

            if (!@unlink($path)) {
                $warnings[] = $path;
            }
        }
    }

    return $warnings;
}

function deleteUserAccount(string $userId, ?string $actorUserId = null, array $options = []): array
{
    $userId = trim($userId);
    $actorUserId = trim((string) ($actorUserId ?? ''));

    if ($userId === '') {
        return accountFailure('Missing user ID', 'missing_user_id');
    }

    $user = queryOne('SELECT * FROM users WHERE id = :id', [':id' => $userId]);
    if (!is_array($user) || empty($user['id'])) {
        return accountFailure('User not found', 'user_not_found');
    }

    if (accountIsLastRemainingAdmin($userId) && empty($options['allow_last_admin_delete'])) {
        return accountFailure('Cannot delete the last remaining admin', 'last_admin');
    }

    $email = strtolower(trim((string) ($user['email'] ?? '')));
    $emailHash = $email !== '' ? hash('sha256', $email) : '';
    $photoFilenames = accountCollectUserPhotoFilenames($userId);
    $db = getDB();

    if (!$db->exec('BEGIN IMMEDIATE')) {
        return accountFailure('Could not start deletion transaction', 'transaction_start_failed');
    }

    try {
        auditLogRecord('user.delete', [
            'actor_user_id' => $actorUserId !== '' ? $actorUserId : $userId,
            'target_type' => 'user',
            'target_id' => $userId,
            'target_email_hash' => $emailHash,
            'metadata' => [
                'reason' => trim((string) ($options['reason'] ?? 'account_delete')),
                'self_service' => !empty($options['self_service']),
            ],
        ]);

        $userParam = [':user_id' => $userId];

        accountDeleteRowsIfTableExists('review_helpful_votes', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('review_votes', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('review_responses', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('referral_clicks', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('quiz_results', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('beach_checkins', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('user_preferences', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('user_favorites', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('beach_lists', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('beach_photos', 'user_id = :user_id', $userParam);
        accountDeleteRowsIfTableExists('beach_reviews', 'user_id = :user_id', $userParam);

        if ($email !== '') {
            accountDeleteRowsIfTableExists('magic_links', 'email = :email', [':email' => $email]);
        }

        if ($emailHash !== '') {
            if (accountTableExists('email_events') && accountTableExists('email_messages')) {
                if (!execute(
                    'DELETE FROM email_events
                     WHERE email_message_id IN (
                         SELECT id FROM email_messages WHERE to_email_hash = :email_hash
                     )',
                    [':email_hash' => $emailHash]
                )) {
                    throw new RuntimeException('Failed deleting email event history');
                }
            }

            accountDeleteRowsIfTableExists('email_messages', 'to_email_hash = :email_hash', [':email_hash' => $emailHash]);
            accountDeleteRowsIfTableExists('email_contacts', 'email_hash = :email_hash', [':email_hash' => $emailHash]);
        }

        if (!execute('DELETE FROM users WHERE id = :id', [':id' => $userId])) {
            throw new RuntimeException('Failed deleting user record');
        }

        $stillExists = queryOne('SELECT id FROM users WHERE id = :id', [':id' => $userId]);
        if ($stillExists) {
            throw new RuntimeException('User record still exists after delete');
        }

        if (!$db->exec('COMMIT')) {
            throw new RuntimeException('Failed committing deletion transaction');
        }
    } catch (Throwable $e) {
        $db->exec('ROLLBACK');
        return accountFailure($e->getMessage(), 'delete_failed');
    }

    return [
        'success' => true,
        'user' => $user,
        'file_warnings' => accountDeletePhotoFiles($photoFilenames),
    ];
}
