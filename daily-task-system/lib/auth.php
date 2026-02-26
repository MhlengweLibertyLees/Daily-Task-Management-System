<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log in a user
function login_user($user) {
    session_regenerate_id(true); // Prevent session fixation
    $_SESSION['uid']  = $user['id'];
    $_SESSION['name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
}

// Log out a user
function logout_user() {
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

// Check if user is logged in
function is_logged_in() {
    return !empty($_SESSION['uid']);
}

// Get current user ID
function current_user_id() {
    return $_SESSION['uid'] ?? null;
}

// Get current user role
function current_user_role() {
    return $_SESSION['role'] ?? null;
}
