<?php
/**
 * Admin - User Management
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/db.php';
require_once APP_ROOT . '/inc/account.php';
require_once APP_ROOT . '/inc/audit_log.php';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once APP_ROOT . '/inc/session.php';
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    require_once APP_ROOT . '/inc/admin.php';
    requireAdmin();

    if (!validateCsrf($_POST['csrf_token'] ?? '')) {
        redirect('/admin/users?error=csrf');
    }

    $userId = trim((string) ($_POST['user_id'] ?? ''));
    $action = trim((string) ($_POST['action'] ?? ''));

    if ($userId && $action) {
        $targetUser = queryOne(
            'SELECT id, email, is_admin FROM users WHERE id = :id',
            [':id' => $userId]
        );

        if (!$targetUser) {
            redirect('/admin/users?error=missing');
        }

        if ($action === 'toggle_admin') {
            if ($userId === (string) ($_SESSION['user_id'] ?? '')) {
                redirect('/admin/users?error=self_admin');
            }

            $isCurrentlyAdmin = (int) ($targetUser['is_admin'] ?? 0) === 1;
            if ($isCurrentlyAdmin) {
                $adminCount = (int) (queryOne('SELECT COUNT(*) AS count FROM users WHERE is_admin = 1')['count'] ?? 0);
                if ($adminCount <= 1) {
                    redirect('/admin/users?error=last_admin');
                }
            }

            $newIsAdmin = $isCurrentlyAdmin ? 0 : 1;
            $result = execute(
                'UPDATE users SET is_admin = :is_admin WHERE id = :id',
                [':is_admin' => $newIsAdmin, ':id' => $userId]
            );

            if (!$result) {
                redirect('/admin/users?error=save_failed');
            }

            auditLogRecord('user.role_change', [
                'actor_user_id' => (string) ($_SESSION['user_id'] ?? ''),
                'target_type' => 'user',
                'target_id' => $userId,
                'target_email_hash' => auditLogHashValue((string) ($targetUser['email'] ?? '')),
                'metadata' => [
                    'from_is_admin' => $isCurrentlyAdmin,
                    'to_is_admin' => (bool) $newIsAdmin,
                ],
            ]);
        } elseif ($action === 'delete') {
            if ($userId === (string) ($_SESSION['user_id'] ?? '')) {
                redirect('/admin/users?error=self_delete');
            }

            $result = deleteUserAccount(
                $userId,
                (string) ($_SESSION['user_id'] ?? ''),
                ['reason' => 'admin_delete', 'self_service' => false]
            );

            if (!$result['success']) {
                redirect('/admin/users?error=delete_failed');
            }
        }

        header('Location: /admin/users?updated=1');
        exit;
    }
}

$pageTitle = 'Users';
$pageSubtitle = 'Manage user accounts';

include __DIR__ . '/components/header.php';

// Get users
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (name LIKE :search OR email LIKE :search)';
    $params[':search'] = "%$search%";
}

$users = query("
    SELECT u.*,
           (SELECT COUNT(*) FROM beach_reviews WHERE user_id = u.id) as review_count,
           (SELECT COUNT(*) FROM user_favorites WHERE user_id = u.id) as favorite_count
    FROM users u
    WHERE $where
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
", $params);

$total = queryOne("SELECT COUNT(*) as count FROM users WHERE $where", $params)['count'] ?? 0;
$totalPages = ceil($total / $limit);
?>

<!-- Search -->
<div class="bg-white rounded-xl shadow-sm p-4 mb-6">
    <form method="GET" class="flex items-center gap-4">
        <input type="text" name="search" value="<?= h($search) ?>"
               placeholder="Search by name or email..."
               class="flex-1 max-w-md px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
        <button type="submit" class="bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-lg font-medium">Search</button>
        <?php if ($search): ?>
        <a href="/admin/users" class="text-gray-500 hover:text-gray-700">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if (isset($_GET['updated'])): ?>
<div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-6">User updated successfully!</div>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
<div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
    <?php
    $errorMessages = [
        'csrf' => 'Your session expired. Please retry the action.',
        'missing' => 'That user no longer exists.',
        'self_admin' => 'You cannot change your own admin role here.',
        'self_delete' => 'Use the account deletion flow from your profile to delete your own account.',
        'last_admin' => 'You cannot remove the last remaining admin.',
        'save_failed' => 'Failed to update the user role.',
        'delete_failed' => 'Failed to delete the user account.',
    ];
    echo h($errorMessages[$_GET['error']] ?? 'The requested action could not be completed.');
    ?>
</div>
<?php endif; ?>

<!-- Users Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-200">
            <tr>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">User</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Joined</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Activity</th>
                <th class="text-left px-6 py-3 text-xs font-medium text-gray-500 uppercase">Role</th>
                <th class="text-right px-6 py-3 text-xs font-medium text-gray-500 uppercase">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            <?php foreach ($users as $user): ?>
            <tr class="hover:bg-gray-50">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <?php if (!empty($user['avatar_url'])): ?>
                        <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-10 h-10 rounded-full">
                        <?php else: ?>
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-medium">
                            <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <p class="font-medium text-gray-900"><?= h($user['name'] ?? 'No name') ?></p>
                            <p class="text-sm text-gray-500"><?= h($user['email']) ?></p>
                        </div>
                        <?php if ($user['google_id']): ?>
                        <span class="text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded">Google</span>
                        <?php endif; ?>
                    </div>
                </td>
                <td class="px-6 py-4 text-gray-600 text-sm">
                    <?= $user['created_at'] ? date('M j, Y', strtotime($user['created_at'])) : '—' ?>
                </td>
                <td class="px-6 py-4 text-sm">
                    <span class="text-gray-600"><?= $user['review_count'] ?> reviews</span>
                    <span class="text-gray-400 mx-1">•</span>
                    <span class="text-gray-600"><?= $user['favorite_count'] ?> favorites</span>
                </td>
                <td class="px-6 py-4">
                    <?php if ($user['is_admin']): ?>
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-700">
                        Admin
                    </span>
                    <?php else: ?>
                    <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                        User
                    </span>
                    <?php endif; ?>
                </td>
                <td class="px-6 py-4 text-right">
                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                    <form method="POST" class="inline">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= h($user['id']) ?>">
                        <input type="hidden" name="action" value="toggle_admin">
                        <button type="submit" class="text-blue-600 hover:text-blue-700 text-sm mr-3">
                            <?= $user['is_admin'] ? 'Remove Admin' : 'Make Admin' ?>
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($user['id'] !== $_SESSION['user_id']): ?>
                    <form method="POST" class="inline" data-action-confirm="Are you sure you want to delete this user? This will also delete all their reviews and favorites." data-action="submitParentForm" data-action-args='["__this__"]' data-on="submit">
                        <?= csrfField() ?>
                        <input type="hidden" name="user_id" value="<?= h($user['id']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <button type="submit" class="text-red-600 hover:text-red-700 text-sm">Delete</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <p class="text-sm text-gray-500">Showing <?= $offset + 1 ?>-<?= min($offset + $limit, $total) ?> of <?= $total ?> users</p>
        <div class="flex gap-2">
            <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1 border rounded hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/components/footer.php'; ?>
