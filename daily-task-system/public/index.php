<?php
require_once __DIR__ . '/../lib/auth.php';

// If logged in, go to dashboard; otherwise, go to login
if (is_logged_in()) {
    header('Location: /daily-task-system/public/dashboard.php');
} else {
    header('Location: /daily-task-system/public/login.php');
}
exit;
