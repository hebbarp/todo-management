<?php
/**
 * Authentication System for Admin Panel
 * Secure login system for accessing analytics and registration data
 */

session_start();

// Configuration - Change these credentials
define('ADMIN_USERNAME', 'admin');
define('ADMIN_PASSWORD', 'VibeTa$king2025!'); // Strong password with special chars
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

/**
 * Check if user is authenticated
 */
function isAuthenticated() {
    if (!isset($_SESSION['authenticated']) || $_SESSION['authenticated'] !== true) {
        return false;
    }
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        logout();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    return true;
}

/**
 * Authenticate user credentials
 */
function authenticate($username, $password) {
    // Simple but secure authentication
    if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
        $_SESSION['authenticated'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['login_time'] = time();
        $_SESSION['last_activity'] = time();
        
        // Log successful login
        logActivity("Successful login for user: $username");
        return true;
    }
    
    // Log failed login attempt
    logActivity("Failed login attempt for user: $username from IP: " . $_SERVER['REMOTE_ADDR']);
    return false;
}

/**
 * Logout user
 */
function logout() {
    if (isset($_SESSION['username'])) {
        logActivity("User logged out: " . $_SESSION['username']);
    }
    
    session_destroy();
    session_start();
}

/**
 * Require authentication - redirect to login if not authenticated
 */
function requireAuth() {
    if (!isAuthenticated()) {
        header('Location: login.php');
        exit();
    }
}

/**
 * Log authentication activities
 */
function logActivity($message) {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] $message\n";
    file_put_contents(__DIR__ . '/auth.log', $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get user info
 */
function getCurrentUser() {
    if (isAuthenticated()) {
        return [
            'username' => $_SESSION['username'],
            'login_time' => $_SESSION['login_time'],
            'last_activity' => $_SESSION['last_activity']
        ];
    }
    return null;
}

/**
 * Rate limiting for login attempts
 */
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_file = __DIR__ . '/login_attempts.json';
    
    // Load existing attempts
    $attempts = [];
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true) ?: [];
    }
    
    // Clean old attempts (older than 15 minutes)
    $current_time = time();
    foreach ($attempts as $attempt_ip => $data) {
        if ($current_time - $data['last_attempt'] > 900) { // 15 minutes
            unset($attempts[$attempt_ip]);
        }
    }
    
    // Check current IP attempts
    if (isset($attempts[$ip])) {
        if ($attempts[$ip]['count'] >= 5) { // Max 5 attempts per 15 minutes
            return false;
        }
    }
    
    return true;
}

/**
 * Record failed login attempt
 */
function recordFailedAttempt() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_file = __DIR__ . '/login_attempts.json';
    
    $attempts = [];
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true) ?: [];
    }
    
    if (!isset($attempts[$ip])) {
        $attempts[$ip] = ['count' => 0, 'last_attempt' => 0];
    }
    
    $attempts[$ip]['count']++;
    $attempts[$ip]['last_attempt'] = time();
    
    file_put_contents($attempts_file, json_encode($attempts), LOCK_EX);
}

/**
 * Clear failed attempts for IP (on successful login)
 */
function clearFailedAttempts() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts_file = __DIR__ . '/login_attempts.json';
    
    if (file_exists($attempts_file)) {
        $attempts = json_decode(file_get_contents($attempts_file), true) ?: [];
        if (isset($attempts[$ip])) {
            unset($attempts[$ip]);
            file_put_contents($attempts_file, json_encode($attempts), LOCK_EX);
        }
    }
}
?>