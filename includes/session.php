<?php
// includes/session.php
// This file handles session management for the social media site

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session parameters
    $session_name = 'social_media_session';
    $secure = false; // Set to true if using HTTPS
    $httponly = true;
    
    // Set the session name
    session_name($session_name);
    
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 86400, // 24 hours
        'path' => '/',
        'domain' => '',
        'secure' => $secure,
        'httponly' => $httponly,
        'samesite' => 'Lax'
    ]);
    
    // Start the session
    session_start();
    
    // Regenerate session ID periodically to prevent session fixation
    if (!isset($_SESSION['last_regeneration'])) {
        regenerateSessionId();
    } else {
        // Regenerate session ID every 30 minutes
        $interval = 30 * 60;
        if (time() - $_SESSION['last_regeneration'] > $interval) {
            regenerateSessionId();
        }
    }
}

/**
 * Regenerates the session ID
 */
function regenerateSessionId() {
    // Update regeneration time
    $_SESSION['last_regeneration'] = time();
    
    // Regenerate session ID
    session_regenerate_id(true);
}

/**
 * Creates a session for a logged-in user
 * 
 * @param array $user User data from database
 * @return void
 */
function createUserSession($user) {
    // Store basic user information in session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    
    // Set a token for CSRF protection
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

/**
 * Validates if the current session is active and valid
 * 
 * @return boolean True if session is valid, false otherwise
 */
function validateSession() {
    // Check if user is logged in
    if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        return false;
    }
    
    // Check if session has not expired (24 hours)
    $session_duration = 24 * 60 * 60; // 24 hours
    if (time() - $_SESSION['login_time'] > $session_duration) {
        destroySession();
        return false;
    }
    
    // Check if user still exists in database (optional, can be expensive)
    // This can be uncommented if you want to validate against DB on every request
    /*
    global $db;
    $user = $db->fetch("SELECT id FROM users WHERE id = ?", [$_SESSION['user_id']]);
    if (!$user) {
        destroySession();
        return false;
    }
    */
    
    return true;
}

/**
 * Destroys the current session
 * 
 * @return void
 */
function destroySession() {
    // Unset all session variables
    $_SESSION = [];
    
    // Get session cookie parameters
    $params = session_get_cookie_params();
    
    // Delete the session cookie
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
    
    // Destroy the session
    session_destroy();
}

/**
 * Generates a CSRF token input field for forms
 * 
 * @return string HTML input field with CSRF token
 */
function csrfTokenField() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    
    return '<input type="hidden" name="csrf_token" value="' . $_SESSION['csrf_token'] . '">';
}

/**
 * Validates a CSRF token from a form submission
 * 
 * @param string $token The token to validate
 * @return boolean True if token is valid, false otherwise
 */
function validateCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    
    return true;
}

/**
 * Sets a flash message to be displayed on the next page load
 * 
 * @param string $type The type of message (success, error, warning, info)
 * @param string $message The message text
 * @return void
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Gets and clears the flash message
 * 
 * @return array|null Flash message array or null if no message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    
    return null;
}

/**
 * Displays flash messages in the view
 * 
 * @return string HTML for flash message or empty string
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    
    if (!$flash) {
        return '';
    }
    
    $alertClass = 'alert-info';
    
    switch ($flash['type']) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'error':
            $alertClass = 'alert-danger';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
    }
    
    return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . htmlspecialchars($flash['message']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

/**
 * Handles guest session with cookies
 */
function handleGuestSession() {
    // Check if guest preferences cookie exists
    if (!isset($_COOKIE['user_preferences'])) {
        // Set default preferences
        $defaultPreferences = [
            'theme' => 'light',
            'language' => 'en',
            'notification' => 'off',
            'ignored_posts' => []
        ];
        
        setGuestCookie($defaultPreferences);
    }
}

