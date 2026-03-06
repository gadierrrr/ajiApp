<?php
/**
 * Common application bootstrap
 */

if (defined('BOOTSTRAP_PHP_INCLUDED')) {
    return;
}
define('BOOTSTRAP_PHP_INCLUDED', true);

if (!defined('APP_ROOT')) {
    $root = realpath(__DIR__ . '/..');
    define('APP_ROOT', $root !== false ? $root : dirname(__DIR__));
}

if (!defined('PUBLIC_ROOT')) {
    define('PUBLIC_ROOT', APP_ROOT . '/public');
}

require_once __DIR__ . '/env.php';
loadEnvFile();

require_once __DIR__ . '/error-handler.php';
registerErrorHandlers();

validateEnvironment();

require_once __DIR__ . '/request_security.php';
enforceDefaultApiRateLimit();

error_reporting(E_ALL);
ini_set('display_errors', appDebug() ? '1' : '0');
ini_set('log_errors', '1');
