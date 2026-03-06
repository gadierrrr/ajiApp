<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';
require_once APP_ROOT . '/inc/security_headers.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied | Beach Finder Admin</title>
    <script src="https://cdn.tailwindcss.com" <?= cspNonceAttr() ?>></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="text-8xl mb-6">🚫</div>

        <h1 class="text-3xl font-bold text-gray-900 mb-4">
            Access Denied
        </h1>

        <p class="text-gray-600 mb-8">
            You don't have permission to access the admin area.
            Please contact an administrator if you believe this is an error.
        </p>

        <div class="space-y-4">
            <a href="/"
               class="block w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-medium transition-colors">
                Go to Beach Finder
            </a>

            <a href="/logout"
               class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 px-6 rounded-lg font-medium transition-colors">
                Sign Out
            </a>
        </div>
    </div>
</body>
</html>
