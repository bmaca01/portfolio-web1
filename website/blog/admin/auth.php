<?php
/**
 * Admin Authentication Helper
 * Web 1.0 Portfolio Site - Blog Admin
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../../includes/db-config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is authenticated, redirect to login if not
 */
function admin_check_auth() {
    if (!admin_is_logged_in()) {
        header('Location: login.php');
        exit;
    }

    // Validate session fingerprint (prevents session hijacking)
    if (!admin_validate_fingerprint()) {
        admin_logout();
        header('Location: login.php?error=session');
        exit;
    }

    // Check session timeout
    if (isset($_SESSION['admin_last_activity'])) {
        if (time() - $_SESSION['admin_last_activity'] > ADMIN_SESSION_TIMEOUT) {
            admin_logout();
            header('Location: login.php?timeout=1');
            exit;
        }
    }

    // Update last activity time
    $_SESSION['admin_last_activity'] = time();
}

/**
 * Check if user is logged in
 *
 * @return bool
 */
function admin_is_logged_in() {
    return isset($_SESSION['admin_user_id']) && isset($_SESSION['admin_username']);
}

/**
 * Attempt to log in user
 *
 * @param mysqli $mysqli Database connection
 * @param string $username
 * @param string $password
 * @return array ['success' => bool, 'message' => string]
 */
function admin_login($mysqli, $username, $password) {
    // Check rate limiting
    if (admin_is_rate_limited()) {
        $remaining = admin_get_lockout_remaining();
        return [
            'success' => false,
            'message' => "Too many failed attempts. Try again in $remaining seconds."
        ];
    }

    // Get user from database
    $stmt = $mysqli->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        // Record failed attempt
        admin_record_failed_attempt();
        return [
            'success' => false,
            'message' => 'Invalid username or password'
        ];
    }

    // Clear failed attempts
    admin_clear_failed_attempts();

    // Regenerate session ID for security
    session_regenerate_id(true);

    // Set session variables
    $_SESSION['admin_user_id'] = $user['id'];
    $_SESSION['admin_username'] = $user['username'];
    $_SESSION['admin_last_activity'] = time();
    $_SESSION['admin_fingerprint'] = admin_generate_fingerprint();

    // Update last login time
    $stmt = $mysqli->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
    $stmt->bind_param('i', $user['id']);
    $stmt->execute();

    return [
        'success' => true,
        'message' => 'Login successful'
    ];
}

/**
 * Log out user
 */
function admin_logout() {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }

    session_destroy();
}

/**
 * Generate CSRF token
 *
 * @return string
 */
function admin_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 *
 * @param string $token Token from form
 * @return bool
 */
function admin_validate_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || $token === null || $token === '') {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], (string) $token);
}

/**
 * Output CSRF token as hidden form field
 */
function admin_csrf_field() {
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(admin_csrf_token()) . '">';
}

/**
 * Check if request is rate limited
 *
 * @return bool
 */
function admin_is_rate_limited() {
    if (!isset($_SESSION['login_attempts'])) {
        return false;
    }

    $attempts = $_SESSION['login_attempts'];
    if ($attempts['count'] >= ADMIN_MAX_LOGIN_ATTEMPTS) {
        if (time() - $attempts['first_attempt'] < ADMIN_LOCKOUT_TIME) {
            return true;
        }
        // Lockout expired, clear attempts
        admin_clear_failed_attempts();
    }

    return false;
}

/**
 * Get remaining lockout time in seconds
 *
 * @return int
 */
function admin_get_lockout_remaining() {
    if (!isset($_SESSION['login_attempts'])) {
        return 0;
    }
    $elapsed = time() - $_SESSION['login_attempts']['first_attempt'];
    return max(0, ADMIN_LOCKOUT_TIME - $elapsed);
}

/**
 * Record a failed login attempt
 */
function admin_record_failed_attempt() {
    if (!isset($_SESSION['login_attempts'])) {
        $_SESSION['login_attempts'] = [
            'count' => 0,
            'first_attempt' => time()
        ];
    }
    $_SESSION['login_attempts']['count']++;
}

/**
 * Clear failed login attempts
 */
function admin_clear_failed_attempts() {
    unset($_SESSION['login_attempts']);
}

/**
 * Get current admin user info
 *
 * @return array|null
 */
function admin_get_current_user() {
    if (!admin_is_logged_in()) {
        return null;
    }
    return [
        'id' => $_SESSION['admin_user_id'],
        'username' => $_SESSION['admin_username']
    ];
}

/**
 * Generate session fingerprint based on client info
 *
 * @return string
 */
function admin_generate_fingerprint() {
    $components = [
        $_SERVER['HTTP_USER_AGENT'] ?? '',
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
    ];
    return hash('sha256', implode('|', $components));
}

/**
 * Validate session fingerprint
 *
 * @return bool
 */
function admin_validate_fingerprint() {
    if (!isset($_SESSION['admin_fingerprint'])) {
        return true; // No fingerprint set (legacy session)
    }
    return hash_equals($_SESSION['admin_fingerprint'], admin_generate_fingerprint());
}

/**
 * Check session timeout
 *
 * @return bool True if session has timed out
 */
function admin_check_session_timeout() {
    if (!isset($_SESSION['admin_last_activity'])) {
        return false;
    }
    return (time() - $_SESSION['admin_last_activity']) > ADMIN_SESSION_TIMEOUT;
}

/**
 * Update last activity timestamp
 */
function admin_update_activity() {
    $_SESSION['admin_last_activity'] = time();
}
