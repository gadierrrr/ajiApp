<?php
// inc/auth.php - Magic link authentication

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/email.php';
require_once __DIR__ . '/rate_limiter.php';

function sendMagicLink($email) {
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Invalid email address'];
    }

    // Rate limiting - prevent abuse
    $rateLimiter = new RateLimiter(getDB());

    // Check email-based rate limit
    $emailLimit = $rateLimiter->check($email, 'magic_link_email', 3, 60);
    if (!$emailLimit['allowed']) {
        return ['success' => false, 'error' => 'Too many requests. Please try again later.'];
    }

    // Check IP-based rate limit (20 per hour)
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $ipLimit = $rateLimiter->check($ip, 'magic_link_ip', 20, 60);
    if (!$ipLimit['allowed']) {
        return ['success' => false, 'error' => 'Too many requests from your network. Please try again later.'];
    }

    // Create user if doesn't exist - SAME CODE PATH FOR ALL (prevents enumeration)
    $user = queryOne('SELECT * FROM users WHERE email = :email', [':email' => $email]);
    $isNewUser = false;
    if (!$user) {
        $isNewUser = true;
        $userId = uuid();
        $name = explode('@', $email)[0]; // Use email prefix as default name
        execute(
            'INSERT INTO users (id, email, name, created_at) VALUES (:id, :email, :name, datetime("now"))',
            [':id' => $userId, ':email' => $email, ':name' => $name]
        );

        // Send welcome email to new users (async - don't block the flow)
        sendWelcomeEmail($email, $name);
    }

    // Generate token
    $token = generateToken(32);
    $tokenHash = hash('sha256', $token);

    // Store magic link (expires in 15 minutes)
    $linkId = uuid();
    execute(
        'INSERT INTO magic_links (id, email, token, expires_at, created_at) VALUES (:id, :email, :token, datetime("now", "+15 minutes"), datetime("now"))',
        [':id' => $linkId, ':email' => $email, ':token' => $tokenHash]
    );

    // Send email
    $appUrl = $_ENV['APP_URL'] ?? 'http://localhost:8082';
    $appName = $_ENV['APP_NAME'] ?? 'Beach Finder';
    $loginUrl = $appUrl . '/verify?token=' . $token;

    // Try to use database template first
    $emailSent = sendTemplateEmail('magic-link', $email, [
        'login_url' => $loginUrl
    ]);

    // Fallback to simple HTML if template fails
    if (!$emailSent) {
        $subject = 'Login to ' . $appName;
        $html = "
            <h2>Login to {$appName}</h2>
            <p>Click the link below to log in to your account:</p>
            <p><a href=\"{$loginUrl}\">{$loginUrl}</a></p>
            <p>This link expires in 15 minutes.</p>
            <p>If you didn't request this, you can safely ignore this email.</p>
        ";
        $emailSent = sendEmail($email, $subject, $html, [
            'template_slug' => 'magic-link',
            'category' => 'critical_auth',
            'critical' => true,
        ]);
    }

    // Add random delay to prevent timing attacks (100-300ms)
    usleep(rand(100000, 300000));

    if (!$emailSent) {
        error_log("Failed to send magic link to {$email}");
        // Still return success to prevent enumeration
    }

    // Always return same response (prevents enumeration)
    return ['success' => true, 'message' => 'Check your email for the login link!'];
}

function verifyMagicLink($token) {
    $tokenHash = hash('sha256', $token);

    // Find valid magic link
    $link = queryOne(
        'SELECT * FROM magic_links WHERE token = :token AND used = 0 AND expires_at > datetime("now")',
        [':token' => $tokenHash]
    );

    if (!$link) {
        return ['success' => false, 'error' => 'Invalid or expired link'];
    }

    // Mark as used
    execute('UPDATE magic_links SET used = 1 WHERE id = :id', [':id' => $link['id']]);

    // Get user
    $user = queryOne('SELECT * FROM users WHERE email = :email', [':email' => $link['email']]);

    if (!$user) {
        return ['success' => false, 'error' => 'User not found'];
    }

    // Create session
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_name'] = $user['name'];

    // Store session fingerprint
    $_SESSION['CLIENT_IP'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['CLIENT_UA'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['SESSION_FINGERPRINT'] = hash('sha256', $_SESSION['CLIENT_IP'] . $_SESSION['CLIENT_UA']);
    $_SESSION['LAST_ACTIVITY'] = time();

    return ['success' => true, 'user' => $user];
}

function logout() {
    // Clear all session data
    $_SESSION = [];

    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }

    // Destroy session
    session_destroy();
}
