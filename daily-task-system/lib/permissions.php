<?php
require_once __DIR__ . '/auth.php';

// Require login for a page
function require_login() {
    if (!is_logged_in()) {
        header('Location: /daily-task-system/public/login.php');
        exit;
    }
}

// Require a specific role
function require_role($role) {
    require_login();
    if (current_user_role() !== $role) {
        http_response_code(403);
        exit('Access denied.');
    }
}

// Require one of multiple roles
function require_any_role(array $roles) {
    require_login();
    if (!in_array(current_user_role(), $roles)) {
        http_response_code(403);
        exit('Access denied.');
    }
}
