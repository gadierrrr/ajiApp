<?php
/**
 * Secure Session Configuration
 * MUST be included before any session_start() call
 */

require_once __DIR__ . '/bootstrap.php';

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
           (($_SERVER['SERVER_PORT'] ?? null) == 443);

$currentEnv = appEnv();
$secureCookie = $currentEnv === 'prod' || $currentEnv === 'staging' ? true : $isHttps;

ini_set('session.cookie_httponly', '1');          // Prevent JavaScript access
ini_set('session.cookie_secure', $secureCookie ? '1' : '0');
ini_set('session.cookie_samesite', 'Lax');        // CSRF mitigation
ini_set('session.use_strict_mode', '1');          // Reject uninitialized session IDs
ini_set('session.use_only_cookies', '1');         // No URL-based sessions
ini_set('session.name', 'AJI_APP_SESSION');  // Custom session name
ini_set('session.gc_maxlifetime', '1800');        // 30 minutes server-side
ini_set('session.cookie_lifetime', '0');          // Expire on browser close
