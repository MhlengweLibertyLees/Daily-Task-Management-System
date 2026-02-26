<?php
$pageTitle = "Add New User";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';

require_role('admin');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $name  = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role  = ($_POST['role'] ?? 'member') === 'admin' ? 'admin' : 'member';
        $pass  = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $pass === '') {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format.';
        } elseif (strlen($pass) < 8) {
            $error = 'Password must be at least 8 characters.';
        } else {
            // Duplicate email check
            $chk = $pdo->prepare("SELECT 1 FROM users WHERE email = ? LIMIT 1");
            $chk->execute([$email]);
            if ($chk->fetch()) {
                $error = 'A user with that email already exists.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, role, password_hash) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$name, $email, $role, $hash]);
                    $newUserId = (int)$pdo->lastInsertId();

                    // Audit log
                    $log = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, target, meta) VALUES (?, ?, ?, ?)");
                    $log->execute([
                        current_user_id(),
                        'add_user',
                        'users',
                        json_encode(['user_id' => $newUserId, 'email' => $email, 'role' => $role])
                    ]);

                    $success = 'User added successfully!';
                    $_POST = [];
                } catch (PDOException $e) {
                    if (str_contains(strtolower($e->getMessage()), 'duplicate')) {
                        $error = 'A user with that email already exists.';
                    } else {
                        $error = 'Database error while creating user.';
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations for user creation */
@keyframes slideInCreate {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulseSuccess {
    0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
    100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
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
.create-user-container {
    animation: slideInCreate 0.6s ease-out;
}

.creation-card {
    animation: fadeInScale 0.5s ease-out;
    transition: all 0.3s ease;
    border: none;
    border-left: 4px solid #198754;
    overflow: hidden;
}

.creation-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
}

.form-section-create {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 2rem;
    transition: all 0.3s ease;
}

.form-section-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.btn-create {
    transition: all 0.3s ease;
    border: none;
    font-weight: 600;
    padding: 0.7rem 1.5rem;
}

.btn-create:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-create-success {
    animation: pulseSuccess 2s infinite;
}

.form-control-create {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
}

.form-control-create:focus {
    border-color: #8B0000;
    box-shadow: 0 0 0 0.2rem rgba(139, 0, 0, 0.25);
    transform: scale(1.02);
}

.role-badge {
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
    transition: all 0.3s ease;
}

.role-badge:hover {
    transform: scale(1.05);
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .create-user-container {
        margin-top: 1rem !important;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }
    
    .btn-create {
        width: 100%;
        margin-bottom: 0.5rem;
        padding: 0.9rem 1.5rem;
    }
    
    .form-section-create {
        padding: 1.5rem !important;
    }
    
    .form-section-create .row {
        margin: 0;
    }
    
    .form-section-create .col-md-6,
    .form-section-create .col-md-4,
    .form-section-create .col-md-8 {
        margin-bottom: 1rem;
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
    
    .form-label {
        font-size: 0.9rem;
    }
    
    .form-control-create {
        padding: 0.65rem 0.85rem;
    }
}

/* Status colors */
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

/* Role selection styling */
.role-option {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    cursor: pointer;
}

.role-option:hover {
    border-color: #8B0000;
    transform: translateY(-2px);
}

.role-option.selected {
    border-color: #198754;
    background-color: rgba(25, 135, 84, 0.05);
}

.role-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
}

/* Responsive text */
.responsive-text {
    font-size: clamp(0.8rem, 2vw, 1rem);
}

/* Success state for form */
.form-success {
    border-left: 4px solid #198754;
    background: linear-gradient(135deg, #f8fff9, #e8f5e8);
}

/* Feature highlights */
.feature-list {
    list-style: none;
    padding: 0;
}

.feature-list li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
}

.feature-list li:last-child {
    border-bottom: none;
}

.feature-list i {
    width: 20px;
    margin-right: 0.5rem;
}

/* Input group enhancements */
.input-group-create {
    transition: all 0.3s ease;
}

.input-group-create:focus-within {
    transform: scale(1.02);
}
</style>

<div class="container mt-3 mt-md-4 create-user-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 header-actions animate__animated animate__fadeIn">
        <div class="mb-2 mb-md-0">
            <h3 class="fw-bold mb-1">
                <i class="bi bi-person-plus-fill text-success me-2"></i> Add New User
            </h3>
            <p class="text-muted mb-0 responsive-text">
                <i class="bi bi-info-circle me-1"></i> Create a new user account for the system
            </p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="/daily-task-system/public/admin/users.php" class="btn btn-secondary btn-create">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> 
                <span class="d-none d-sm-inline">Back to Users</span>
                <span class="d-sm-none">Back</span>
            </a>
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
                    <div class="mt-2">
                        <a href="/daily-task-system/public/admin/users.php" class="btn btn-sm btn-outline-success me-2">
                            <i class="bi bi-people-fill me-1"></i> View All Users
                        </a>
                        <button onclick="window.location.reload()" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add Another User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Creation Card -->
    <div class="card shadow-sm border-0 creation-card mb-4 animate__animated animate__fadeIn animate__delay-1s">
        <div class="card-body">
            <h5 class="card-title mb-4">
                <i class="bi bi-person-badge text-primary me-2"></i> User Information
            </h5>
            
            <form method="post" id="userCreateForm" class="row g-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                <!-- Full Name Field -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-person-fill me-1 text-primary"></i> Full Name
                    </label>
                    <input type="text" 
                           name="full_name" 
                           class="form-control form-control-create" 
                           required 
                           value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                           placeholder="Enter user's full name"
                           autocomplete="name">
                    <div class="form-text">
                        <i class="bi bi-info-circle me-1"></i>
                        The user's complete name as it should appear in the system.
                    </div>
                </div>

                <!-- Email Field -->
                <div class="col-12 col-md-6">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-envelope-fill me-1 text-warning"></i> Email Address
                    </label>
                    <input type="email" 
                           name="email" 
                           class="form-control form-control-create" 
                           required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="user@company.com"
                           autocomplete="email">
                    <div class="form-text">
                        <i class="bi bi-shield-check me-1"></i>
                        This will be used for login and notifications.
                    </div>
                </div>

                <!-- Role Selection -->
                <div class="col-12">
                    <label class="form-label fw-semibold mb-3">
                        <i class="bi bi-shield-lock-fill me-1 text-danger"></i> User Role
                    </label>
                    <div class="row">
                        <div class="col-12 col-sm-6">
                            <div class="role-option <?= (($_POST['role'] ?? 'member') === 'member') ? 'selected' : '' ?>" 
                                 onclick="selectRole('member')">
                                <div class="text-center">
                                    <i class="bi bi-person-fill role-icon text-primary"></i>
                                    <h6 class="fw-bold">Member</h6>
                                    <p class="text-muted small mb-2">Can submit and view their own daily tasks</p>
                                    <span class="badge bg-member role-badge">Standard Access</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6">
                            <div class="role-option <?= (($_POST['role'] ?? 'member') === 'admin') ? 'selected' : '' ?>" 
                                 onclick="selectRole('admin')">
                                <div class="text-center">
                                    <i class="bi bi-shield-lock-fill role-icon text-danger"></i>
                                    <h6 class="fw-bold">Administrator</h6>
                                    <p class="text-muted small mb-2">Full system access and user management</p>
                                    <span class="badge bg-admin role-badge">Full Access</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="role" id="roleInput" value="<?= htmlspecialchars($_POST['role'] ?? 'member') ?>">
                    <div class="form-text">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Role cannot be changed after creation without editing the user.
                    </div>
                </div>

                <!-- Password Field -->
                <div class="col-12">
                    <label class="form-label fw-semibold">
                        <i class="bi bi-key-fill me-1 text-success"></i> Password
                    </label>
                    <div class="input-group input-group-create">
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="form-control form-control-create" 
                               required
                               placeholder="Create a secure password"
                               autocomplete="new-password">
                        <button type="button" class="btn btn-outline-secondary btn-toggle-password" id="togglePassword">
                            <i class="bi bi-eye-fill" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                    <div class="form-text">
                        <i class="bi bi-shield-lock me-1"></i>
                        Minimum 8 characters. Include letters, numbers, and symbols for better security.
                    </div>
                    <div class="password-strength mt-2" id="passwordStrength"></div>
                </div>

                <!-- Role Features -->
                <div class="col-12">
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="card-title">
                                <i class="bi bi-lightning-fill me-2 text-warning"></i> Role Capabilities
                            </h6>
                            <div class="row">
                                <div class="col-12 col-md-6">
                                    <h6 class="text-primary mb-2">
                                        <i class="bi bi-person-fill me-2"></i> Member Access
                                    </h6>
                                    <ul class="feature-list small">
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Submit daily tasks</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> View own task history</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Update profile information</li>
                                    </ul>
                                </div>
                                <div class="col-12 col-md-6">
                                    <h6 class="text-danger mb-2">
                                        <i class="bi bi-shield-lock-fill me-2"></i> Admin Access
                                    </h6>
                                    <ul class="feature-list small">
                                        <li><i class="bi bi-check-circle-fill text-success"></i> All Member capabilities</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> View all user tasks</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Manage user accounts</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Lock/unlock tasks</li>
                                        <li><i class="bi bi-check-circle-fill text-success"></i> Export system data</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="col-12">
                    <hr class="my-4">
                    <div class="d-flex flex-column flex-sm-row gap-3">
                        <button type="submit" class="btn btn-success btn-create btn-create-success flex-fill" id="submitBtn">
                            <i class="bi bi-person-plus-fill me-2"></i> Create User Account
                        </button>
                        <a href="/daily-task-system/public/admin/users.php" class="btn btn-outline-secondary flex-fill">
                            <i class="bi bi-x-circle-fill me-2"></i> Cancel
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Tips -->
    <div class="card border-0 bg-light mt-4 animate__animated animate__fadeIn animate__delay-2s">
        <div class="card-body">
            <h6 class="text-muted mb-3">
                <i class="bi bi-lightbulb-fill me-2 text-warning"></i> Quick Tips
            </h6>
            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-shield-check text-success me-2 mt-1"></i>
                        <div>
                            <strong>Secure Passwords</strong>
                            <p class="small text-muted mb-0">Use strong, unique passwords for each user account.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-people text-primary me-2 mt-1"></i>
                        <div>
                            <strong>Role Assignment</strong>
                            <p class="small text-muted mb-0">Assign admin roles only to trusted team members.</p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="d-flex align-items-start">
                        <i class="bi bi-envelope-check text-info me-2 mt-1"></i>
                        <div>
                            <strong>Email Verification</strong>
                            <p class="small text-muted mb-0">Ensure email addresses are correct and active.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Password visibility toggle
document.getElementById('togglePassword').addEventListener('click', function () {
    const passwordField = document.getElementById('password');
    const icon = document.getElementById('togglePasswordIcon');
    const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
    passwordField.setAttribute('type', type);
    icon.classList.toggle('bi-eye-fill');
    icon.classList.toggle('bi-eye-slash-fill');
});

// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
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

// Role selection function
function selectRole(role) {
    document.getElementById('roleInput').value = role;
    
    // Update visual selection
    document.querySelectorAll('.role-option').forEach(option => {
        option.classList.remove('selected');
    });
    
    document.querySelector(`.role-option[onclick="selectRole('${role}')"]`).classList.add('selected');
    
    // Add animation
    const selectedOption = document.querySelector(`.role-option[onclick="selectRole('${role}')"]`);
    selectedOption.classList.add('animate__animated', 'animate__pulse');
    setTimeout(() => {
        selectedOption.classList.remove('animate__animated', 'animate__pulse');
    }, 1000);
}

// Form submission handling
document.getElementById('userCreateForm').addEventListener('submit', function(e) {
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    // Show loading state
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat loading-spinner me-2"></i> Creating User...';
    submitBtn.disabled = true;
    
    // Re-enable after 3 seconds (in case of error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 3000);
});

// Add interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to form controls
    const formControls = document.querySelectorAll('.form-control-create');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('animate__animated', 'animate__pulse');
        });
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate__animated', 'animate__pulse');
        });
    });

    // Auto-hide success messages after 8 seconds
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        setTimeout(() => {
            successAlert.classList.add('animate__animated', 'animate__fadeOut');
            setTimeout(() => {
                successAlert.remove();
            }, 500);
        }, 8000);
    }

    // Add character counter for name field
    const nameField = document.querySelector('input[name="full_name"]');
    if (nameField) {
        nameField.addEventListener('input', function() {
            const charCount = this.value.length;
            // You could add a character counter display here if needed
        });
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + S to save
    if ((e.ctrlKey || e.metaKey) && e.key === 's') {
        e.preventDefault();
        document.getElementById('userCreateForm').dispatchEvent(new Event('submit'));
    }
    
    // Escape key to go back
    if (e.key === 'Escape') {
        window.location.href = '/daily-task-system/public/admin/users.php';
    }
});

// Real-time email validation (basic)
document.querySelector('input[name="email"]').addEventListener('blur', function() {
    const email = this.value;
    if (email && !email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        this.classList.add('is-invalid');
    } else {
        this.classList.remove('is-invalid');
    }
});
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>