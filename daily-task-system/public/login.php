<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/auth.php';

// If already logged in, go to dashboard
if (is_logged_in()) {
    header('Location: /daily-task-system/public/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, full_name, email, role, password_hash, status 
                               FROM users 
                               WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && $user['status'] === 'active' && password_verify($password, $user['password_hash'])) {
            login_user($user);
            header('Location: /daily-task-system/public/dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password, or account inactive.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Daily Task System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #000000, #8B0000);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border-radius: 12px;
            overflow: hidden;
            background-color: #fff;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 10px;
        }
        .brand-name {
            font-weight: 700;
            font-size: 1.3rem;
            color: #8B0000;
        }
        .input-group-text {
            background-color: #f8f9fa;
            border-right: none;
        }
        .form-control {
            border-left: none;
        }
        .form-control:focus {
            box-shadow: none;
            border-color: #8B0000;
        }
        .btn-primary {
            background-color: #8B0000;
            border-color: #8B0000;
        }
        .btn-primary:hover {
            background-color: #a30000;
            border-color: #a30000;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow login-card">
                <div class="card-body p-4">
                    <div class="text-center">
                        <img src="/daily-task-system/public/assets/img/logo.PNG" alt="Company Logo" class="logo img-fluid">
                        <div class="brand-name">Daily Task System</div>
                    </div>
                    <hr>
                    <h5 class="text-center mb-4">Sign In to Continue</h5>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php endif; ?>
                    <form method="post" novalidate>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-envelope-fill"></i></span>
                                <input type="email" name="email" id="email" 
                                       class="form-control" placeholder="you@example.com" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                                <input type="password" name="password" id="password" 
                                       class="form-control" placeholder="Enter your password" required>
                            </div>
                        </div>
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary btn-lg">Login</button>
                        </div>
                    </form>
                </div>
                <div class="card-footer text-center text-muted small">
                    &copy; <?= date('Y') ?> Daily Task System. All rights reserved.
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
