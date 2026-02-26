<?php
$pageTitle = "Edit User";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';

require_role('admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$targetId = (int)($_GET['id'] ?? 0);
if ($targetId <= 0) {
    header('Location: /daily-task-system/public/admin/users.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id, full_name, email, role, status FROM users WHERE id = ?");
$stmt->execute([$targetId]);
$user = $stmt->fetch();

if (!$user) {
    exit('User not found.');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $actingAdminId = current_user_id();

        // Update status (with self-deactivation protection)
        if (isset($_POST['status'])) {
            $newStatus = $_POST['status'] === 'inactive' ? 'inactive' : 'active';
            if ($targetId === $actingAdminId && $newStatus === 'inactive') {
                $error = 'You cannot deactivate your own account while logged in.';
            } else {
                $upd = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $upd->execute([$newStatus, $targetId]);

                // Audit
                $log = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, target, meta) VALUES (?, ?, ?, ?)");
                $log->execute([
                    $actingAdminId,
                    'update_user_status',
                    'users',
                    json_encode(['user_id' => $targetId, 'status' => $newStatus])
                ]);

                $success = 'User status updated successfully.';
                $user['status'] = $newStatus;
            }
        }

        // Optional password reset
        if (!$error && !empty($_POST['new_password'])) {
            $newPass = $_POST['new_password'];
            if (strlen($newPass) < 8) {
                $error = 'New password must be at least 8 characters.';
            } else {
                $hash = password_hash($newPass, PASSWORD_DEFAULT);
                $pwd = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
                $pwd->execute([$hash, $targetId]);

                // Audit
                $log = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, target, meta) VALUES (?, ?, ?, ?)");
                $log->execute([
                    $actingAdminId,
                    'reset_user_password',
                    'users',
                    json_encode(['user_id' => $targetId])
                ]);

                $success .= ($success ? ' ' : '') . 'Password reset successfully.';
            }
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations for user edit page */
@keyframes slideInEdit {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulseWarning {
    0% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(255, 193, 7, 0); }
    100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0); }
}

@keyframes shakeError {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

/* Page specific styles */
.edit-user-container {
    animation: slideInEdit 0.6s ease-out;
}

.user-profile-card {
    animation: fadeInScale 0.5s ease-out;
    transition: all 0.3s ease;
    border: none;
    border-left: 4px solid transparent;
    overflow: hidden;
}

.user-profile-card.admin {
    border-left-color: #8B0000;
    background: linear-gradient(135deg, #fff, #f8f9fa);
}

.user-profile-card.member {
    border-left-color: #0d6efd;
    background: linear-gradient(135deg, #fff, #f8f9fa);
}

.user-profile-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
}

.user-avatar-large {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B0000, #000);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2rem;
    transition: all 0.3s ease;
}

.user-avatar-large:hover {
    transform: scale(1.05);
}

.status-badge {
    font-size: 0.8rem;
    padding: 0.5em 1em;
    transition: all 0.3s ease;
    border: none;
}

.status-badge:hover {
    transform: scale(1.05);
}

.role-badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
    transition: all 0.3s ease;
}

.role-badge:hover {
    transform: scale(1.05);
}

.btn-edit {
    transition: all 0.3s ease;
    border: none;
    font-weight: 600;
    padding: 0.7rem 1.5rem;
}

.btn-edit:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.form-control-custom {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.form-control-custom:focus {
    border-color: #8B0000;
    box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
    transform: scale(1.02);
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .edit-user-container {
        margin-top: 1rem !important;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }
    
    .btn-edit {
        width: 100%;
        margin-bottom: 0.5rem;
        padding: 0.9rem 1.5rem;
    }
    
    .user-profile-card .row {
        margin: 0;
    }
    
    .user-profile-card .col-md-4 {
        margin-bottom: 1.5rem;
        text-align: center;
    }
    
    .form-section {
        padding: 1rem !important;
    }
}

@media (max-width: 576px) {
    h3 {
        font-size: 1.4rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .alert {
        font-size: 0.85rem;
        padding: 0.8rem;
    }
    
    .badge {
        font-size: 0.7rem;
    }
    
    .user-avatar-large {
        width: 70px;
        height: 70px;
        font-size: 1.8rem;
    }
    
    .form-label {
        font-size: 0.9rem;
    }
}

/* Status colors */
.bg-active { background-color: #198754 !important; }
.bg-inactive { background-color: #6c757d !important; }
.bg-admin { background-color: #8B0000 !important; }
.bg-member { background-color: #0d6efd !important; }

/* Alert animations */
.alert-success {
    animation: fadeInScale 0.5s ease-out;
    border: none;
    border-left: 4px solid #198754;
}

.alert-danger {
    animation: shakeError 0.5s ease-out;
    border: none;
    border-left: 4px solid #dc3545;
}

.alert-warning {
    animation: pulseWarning 2s infinite;
    border: none;
    border-left: 4px solid #ffc107;
}

/* Form sections */
.form-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.form-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

/* Password strength indicator */
.password-strength {
    height: 4px;
    border-radius: 2px;
    margin-top: 0.5rem;
    transition: all 0.3s ease;
}

.strength-weak { background-color: #dc3545; width: 25%; }
.strength-fair { background-color: #fd7e14; width: 50%; }
.strength-good { background-color: #ffc107; width: 75%; }
.strength-strong { background-color: #198754; width: 100%; }

/* Toggle password button */
.btn-toggle-password {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
}

.btn-toggle-password:hover {
    background-color: #8B0000;
    border-color: #8B0000;
    color: white;
    transform: scale(1.05);
}

/* Loading animations */
.loading-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive text */
.responsive-text {
    font-size: clamp(0.8rem, 2vw, 1rem);
}

/* Danger zone styling */
.danger-zone {
    border: 2px solid #dc3545;
    border-radius: 12px;
    padding: 1.5rem;
    background: linear-gradient(135deg, #fff5f5, #ffe6e6);
    animation: pulseWarning 3s infinite;
}

.danger-zone:hover {
    animation: none;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.2);
}

/* Success state for form */
.form-success {
    border-left: 4px solid #198754;
    background: linear-gradient(135deg, #f8fff9, #e8f5e8);
}
</style>

<div class="container mt-3 mt-md-4 edit-user-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 header-actions animate__animated animate__fadeIn">
        <div class="mb-2 mb-md-0">
            <h3 class="fw-bold mb-1">
                <i class="bi bi-pencil-square text-danger me-2"></i> Edit User
            </h3>
            <p class="text-muted mb-0 responsive-text">
                <i class="bi bi-info-circle me-1"></i> Manage user account settings and permissions
            </p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="/daily-task-system/public/admin/users.php" class="btn btn-secondary btn-edit">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> 
                <span class="d-none d-sm-inline">Back to Users</span>
                <span class="d-sm-none">Back</span>
            </a>
        </div>
    </div>

    <!-- User Profile Card -->
    <div class="card shadow-sm border-0 user-profile-card <?= $user['role'] ?> mb-4 animate__animated animate__fadeIn animate__delay-1s">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-12 col-md-4 text-center text-md-start mb-3 mb-md-0">
                    <div class="d-flex flex-column flex-md-row align-items-center">
                        <div class="user-avatar-large me-0 me-md-3 mb-2 mb-md-0">
                            <?= strtoupper(substr($user['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1"><?= htmlspecialchars($user['full_name']) ?></h4>
                            <div class="d-flex flex-wrap justify-content-center justify-content-md-start gap-2">
                                <span class="badge <?= $user['role'] === 'admin' ? 'bg-admin' : 'bg-member' ?> role-badge text-capitalize">
                                    <i class="bi bi-<?= $user['role'] === 'admin' ? 'shield-lock' : 'person' ?>-fill me-1"></i>
                                    <?= htmlspecialchars($user['role']) ?>
                                </span>
                                <span class="badge <?= $user['status'] === 'active' ? 'bg-active' : 'bg-inactive' ?> status-badge">
                                    <i class="bi bi-<?= $user['status'] === 'active' ? 'check-circle' : 'x-circle' ?>-fill me-1"></i>
                                    <?= htmlspecialchars(ucfirst($user['status'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-8">
                    <div class="row g-3">
                        <div class="col-12 col-sm-6">
                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                <i class="bi bi-envelope-fill text-primary me-3 fs-5"></i>
                                <div>
                                    <small class="text-muted d-block">Email Address</small>
                                    <strong class="text-truncate d-block" style="max-width: 200px;">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                <i class="bi bi-person-badge text-success me-3 fs-5"></i>
                                <div>
                                    <small class="text-muted d-block">User ID</small>
                                    <strong>#<?= $user['id'] ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alerts -->
    <?php if ($error): ?>
        <div class="alert alert-danger animate__animated animate__shakeX">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
                <div>
                    <h6 class="alert-heading mb-1">Action Required</h6>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success animate__animated animate__fadeIn">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                <div>
                    <h6 class="alert-heading mb-1">Success!</h6>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="card shadow-sm border-0 form-section animate__animated animate__fadeIn animate__delay-2s <?= $success ? 'form-success' : '' ?>">
        <div class="card-body">
            <h5 class="card-title mb-4">
                <i class="bi bi-sliders text-primary me-2"></i> Account Settings
            </h5>
            
            <form method="post" id="userEditForm" class="row g-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <!-- Status Field -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-flag-fill me-1 text-primary"></i> Account Status
                    </label>
                    <select name="status" class="form-select form-control-custom" id="statusSelect">
                        <option value="active" <?= $user['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $user['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        Active users can log in and use the system.
                    </div>
                </div>

                <!-- Password Reset Field -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-key-fill me-1 text-warning"></i> Reset Password
                    </label>
                    <div class="input-group">
                        <input type="password" 
                               name="new_password" 
                               id="new_password" 
                               class="form-control form-control-custom" 
                               placeholder="Enter new password"
                               aria-describedby="passwordHelp">
                        <button type="button" class="btn btn-outline-secondary btn-toggle-password" id="togglePassword">
                            <i class="bi bi-eye-fill" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <div id="passwordHelp" class="form-text">
                        <i class="bi bi-shield-check me-1"></i>
                        Minimum 8 characters. Leave blank to keep current password.
                    </div>
                    <div class="password-strength mt-2" id="passwordStrength"></div>
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <hr class="my-4">
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <button type="submit" class="btn btn-primary btn-edit flex-fill" id="submitBtn">
                            <i class="bi bi-save-fill me-2"></i> Save Changes
                        </button>
                        <a href="/daily-task-system/public/admin/users.php" class="btn btn-outline-secondary flex-fill">
                            <i class="bi bi-x-circle-fill me-2"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Danger Zone (Optional - for future features) -->
    <div class="card border-0 danger-zone mt-4 animate__animated animate__fadeIn animate__delay-3s">
        <div class="card-body">
            <h5 class="card-title text-danger mb-3">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> Danger Zone
            </h5>
            <p class="text-muted mb-3">
                These actions are irreversible. Please proceed with caution.
            </p>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <button class="btn btn-outline-danger" disabled>
                    <i class="bi bi-person-x-fill me-2"></i> Delete User Account
                </button>
                <button class="btn btn-outline-warning" disabled>
                    <i class="bi bi-arrow-repeat me-2"></i> Reset All Data
                </button>
            </div>
            <small class="text-muted mt-2 d-block">
                <i class="bi bi-info-circle me-1"></i>
                Advanced user management features coming soon.
            </small>
        </div>
    </div>
</div>

<script>
// Password visibility toggle
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordField = document.getElementById('new_password');
    const icon = document.getElementById('togglePasswordIcon');
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    icon.classList.toggle('bi-eye-fill');
    icon.classList.toggle('bi-eye-slash-fill');
});

// Password strength indicator
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('passwordStrength');
    
    if (password.length === 0) {
        strengthBar.className = 'password-strength';
        strengthBar.style.width = '0%';
        return;
    }
    
    let strength = 0;
    if (password.length >= 8) strength += 25;
    if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 25;
    if (password.match(/\d/)) strength += 25;
    if (password.match(/[^a-zA-Z\d]/)) strength += 25;
    
    let strengthClass = 'strength-weak';
    if (strength >= 50) strengthClass = 'strength-fair';
    if (strength >= 75) strengthClass = 'strength-good';
    if (strength >= 100) strengthClass = 'strength-strong';
    
    strengthBar.className = `password-strength ${strengthClass}`;
});

// Form submission handling
document.getElementById('userEditForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat loading-spinner me-2"></i> Saving Changes...';
    submitBtn.disabled = true;
    
    // Re-enable after 3 seconds (in case of error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});

// Status change confirmation for self-deactivation
document.getElementById('statusSelect').addEventListener('change', function() {
    const currentUserId = <?= $targetId ?>;
    const actingAdminId = <?= current_user_id() ?>;
    const newStatus = this.value;
    
    if (currentUserId === actingAdminId && newStatus === 'inactive') {
        if (!confirm('⚠️ WARNING: You are about to deactivate your own account!\n\nThis will immediately log you out and prevent future logins.\n\nAre you absolutely sure?')) {
            this.value = 'active';
        }
    }
});

// Add interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to cards
    const cards = document.querySelectorAll('.user-profile-card, .form-section');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Auto-hide success messages after 5 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.classList.add('animate__animated', 'animate__fadeOut');
            setTimeout(() => {
                successAlert.remove();
            }, 500);
        }, 5000);
    }

    // Add focus effects to form controls
    const formControls = document.querySelectorAll('.form-control-custom');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('animate__animated', 'animate__pulse');
        });
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate__animated', 'animate__pulse');
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('userEditForm').dispatchEvent(new Event('submit'));
    }
    
    // Escape key to go back
    if (e.key === 'Escape') {
        window.location.href = '/daily-task-system/public/admin/users.php';
    }
});
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>