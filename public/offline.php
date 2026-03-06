<?php
/**
 * Offline Page
 * Shown when user is offline and page isn't cached
 */

require_once $_SERVER['DOCUMENT_ROOT'] . '/../bootstrap.php';

$pageTitle = 'Offline';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Offline | Puerto Rico Beach Finder</title>
    <script src="https://cdn.tailwindcss.com" <?= cspNonceAttr() ?>></script>
    <style>
        .wave {
            animation: wave 2s ease-in-out infinite;
        }
        @keyframes wave {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="text-center max-w-md">
        <div class="text-8xl mb-6 wave">🏖️</div>

        <h1 class="text-3xl font-bold text-gray-900 mb-4">
            You're Offline
        </h1>

        <p class="text-gray-600 mb-8">
            It looks like you've lost your internet connection.
            Don't worry, some beaches may still be available from your cache!
        </p>

        <div class="space-y-4">
            <button data-action="reloadPage"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-lg font-medium transition-colors">
                Try Again
            </button>

            <a href="/"
               class="block w-full bg-gray-200 hover:bg-gray-300 text-gray-700 py-3 px-6 rounded-lg font-medium transition-colors">
                Go to Home (Cached)
            </a>
        </div>

        <div class="mt-8 p-4 bg-blue-50 a11y-on-light-blue rounded-lg">
            <h2 class="font-medium text-blue-900 mb-2">Tip</h2>
            <p class="text-blue-700 text-sm">
                Visit your favorite beaches while online to cache them for offline viewing.
            </p>
        </div>
    </div>

    <script <?= cspNonceAttr() ?>>
        // Check for connection and reload if back online
        window.addEventListener('online', () => {
            window.location.reload();
        });
    </script>
</body>
</html>
