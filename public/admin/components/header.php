<?php
/**
 * Admin Header Component
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

require_once APP_ROOT . '/inc/session.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
require_once APP_ROOT . '/inc/security_headers.php';
require_once APP_ROOT . '/inc/admin.php';

// Require admin access
requireAdmin();

$user = currentUser();
$appName = $_ENV['APP_NAME'] ?? 'AJI';

// Current page for active nav
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? h($pageTitle) . ' - Admin | ' : 'Admin | ' ?><?= h($appName) ?></title>

    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="<?= h(csrfToken()) ?>">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com" <?= cspNonceAttr() ?>></script>

    <!-- HTMX -->
    <script src="https://unpkg.com/htmx.org@1.9.10" <?= cspNonceAttr() ?>></script>

    <!-- CSP event delegation -->
    <script src="/assets/js/csp-bindings.js" <?= cspNonceAttr() ?>></script>

    <!-- Tom Select -->
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js" <?= cspNonceAttr() ?>></script>

    <style>
        .admin-sidebar { width: 250px; }
        @media (max-width: 768px) {
            .admin-sidebar { display: none; }
            .admin-sidebar.open { display: flex; position: fixed; z-index: 50; }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="admin-sidebar bg-gray-900 text-white flex flex-col">
            <!-- Logo -->
            <div class="p-4 border-b border-gray-800">
                <a href="/admin/" class="flex items-center gap-2">
                    <span class="text-2xl">🏖️</span>
                    <div>
                        <span class="font-bold text-lg">AJI</span>
                        <span class="text-xs text-gray-400 block">Admin Panel</span>
                    </div>
                </a>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 p-4 space-y-1">
                <a href="/admin/"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $currentPage === 'index' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="/admin/beaches"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $currentPage === 'beaches' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Beaches
                </a>

                <a href="/admin/reviews"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $currentPage === 'reviews' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Reviews
                    <?php
                    $pendingReviews = queryOne('SELECT COUNT(*) as count FROM beach_reviews WHERE status = "pending"')['count'] ?? 0;
                    if ($pendingReviews > 0):
                    ?>
                    <span class="ml-auto bg-yellow-500 text-xs px-2 py-0.5 rounded-full"><?= $pendingReviews ?></span>
                    <?php endif; ?>
                </a>

                <a href="/admin/users"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $currentPage === 'users' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Users
                </a>

                <a href="/admin/emails"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $currentPage === 'emails' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Email Templates
                </a>

                <a href="/admin/place-id-audit"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $currentPage === 'place-id-audit' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Place ID Audit
                </a>

                <a href="/admin/referrals"
                   class="flex items-center gap-3 px-3 py-2 rounded-lg <?= $currentPage === 'referrals' ? 'bg-blue-600 text-white' : 'text-gray-300 hover:bg-gray-800' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5M10.172 13.828a4 4 0 010-5.656l3-3a4 4 0 015.656 5.656l-1.5 1.5"/>
                    </svg>
                    Referrals & CMS
                </a>

                <div class="pt-4 mt-4 border-t border-gray-800">
                    <a href="/"
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-gray-300 hover:bg-gray-800">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                        </svg>
                        View Site
                    </a>
                </div>
            </nav>

            <!-- User -->
            <div class="p-4 border-t border-gray-800">
                <div class="flex items-center gap-3">
                    <?php if (!empty($user['avatar_url'])): ?>
                    <img src="<?= h($user['avatar_url']) ?>" alt="" class="w-8 h-8 rounded-full">
                    <?php else: ?>
                    <div class="w-8 h-8 rounded-full bg-gray-700 flex items-center justify-center text-sm">
                        <?= strtoupper(substr($user['name'] ?? $user['email'], 0, 1)) ?>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium truncate"><?= h($user['name'] ?? 'Admin') ?></p>
                        <p class="text-xs text-gray-400 truncate"><?= h($user['email']) ?></p>
                    </div>
                    <a href="/logout" class="text-gray-400 hover:text-white" title="Logout">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Top Bar -->
            <header class="bg-white shadow-sm border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900"><?= h($pageTitle ?? 'Dashboard') ?></h1>
                        <?php if (isset($pageSubtitle)): ?>
                        <p class="text-sm text-gray-500 mt-1"><?= h($pageSubtitle) ?></p>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($pageActions)): ?>
                    <div class="flex items-center gap-3">
                        <?= $pageActions ?>
                    </div>
                    <?php endif; ?>
                </div>
            </header>

            <!-- Page Content -->
            <main class="flex-1 p-6">
