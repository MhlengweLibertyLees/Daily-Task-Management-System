<?php
require_once __DIR__ . '/../lib/auth.php';

logout_user();
header('Location: /daily-task-system/public/login.php');
exit;
