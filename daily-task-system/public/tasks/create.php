<?php
$pageTitle = "Submit Today's Task";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';

require_role('member');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$userId = current_user_id();
$today  = date('Y-m-d');

$error = '';
$success = '';

// Prevent duplicate submission for today
$check = $pdo->prepare("SELECT id FROM daily_tasks WHERE user_id = ? AND task_date = ?");
$check->execute([$userId, $today]);
if ($check->fetch()) {
    header('Location: /daily-task-system/public/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form token. Please try again.';
    } else {
        $summary  = trim($_POST['summary']  ?? '');
        $details  = trim($_POST['details']  ?? '');
        $blockers = trim($_POST['blockers'] ?? '');
        $hoursRaw = $_POST['hours'] ?? null;

        $hours = null;
        if ($hoursRaw !== null && $hoursRaw !== '') {
            if (!is_numeric($hoursRaw)) {
                $error = 'Hours must be a number.';
            } else {
                $hours = (float)$hoursRaw;
                if ($hours < 0 || $hours > 24) {
                    $error = 'Hours must be between 0 and 24.';
                }
            }
        }

        if (!$error) {
            if ($summary === '' || $details === '') {
                $error = 'Summary and details are required.';
            } else {
                $ins = $pdo->prepare("INSERT INTO daily_tasks (user_id, task_date, summary, details, blockers, hours)
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $ins->execute([$userId, $today, $summary, $details, $blockers, $hours]);
                $success = 'Task submitted successfully!';
            }
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations for task creation */
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

@keyframes pulseSubmit {
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

@keyframes typewriter {
    from { width: 0; }
    to { width: 100%; }
}

/* Page specific styles */
.create-task-container {
    animation: slideInCreate 0.6s ease-out;
}

.creation-header {
    animation: fadeInScale 0.5s ease-out;
    background: linear-gradient(135deg, #8B0000, #000);
    color: white;
    border: none;
    overflow: hidden;
}

.task-form-card {
    animation: fadeInScale 0.5s ease-out;
    transition: all 0.3s ease;
    border: none;
    border-left: 4px solid #198754;
    overflow: hidden;
}

.task-form-card:hover {
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

.btn-submit-task {
    animation: pulseSubmit 2s infinite;
}

.form-control-create {
    transition: all 0.3s ease;
    border: 2px solid #e9ecef;
    padding: 0.75rem 1rem;
    font-size: 0.95rem;
}

.form-control-create:focus {
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
    
    .create-task-container {
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
    
    .form-section-create .col-md-4 {
        margin-bottom: 1rem;
    }
    
    .character-counter {
        font-size: 0.8rem;
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
    
    .form-label {
        font-size: 0.9rem;
    }
    
    .form-control-create {
        padding: 0.65rem 0.85rem;
        font-size: 0.9rem;
    }
    
    .tips-card .col-md-4 {
        margin-bottom: 1rem;
    }
}

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

/* Character counters */
.character-counter {
    font-size: 0.85rem;
    transition: all 0.3s ease;
}

.counter-warning {
    color: #fd7e14;
    font-weight: 600;
}

.counter-danger {
    color: #dc3545;
    font-weight: bold;
}

.counter-success {
    color: #198754;
    font-weight: 600;
}

/* Loading animations */
.loading-spinner {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Form group enhancements */
.form-group-enhanced {
    transition: all 0.3s ease;
    margin-bottom: 1.5rem;
}

.form-group-enhanced:focus-within {
    transform: translateY(-2px);
}

/* Tips and guidance */
.tip-card {
    background: linear-gradient(135deg, #e3f2fd, #bbdefb);
    border: none;
    border-left: 4px solid #2196f3;
    transition: all 0.3s ease;
}

.tip-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(33, 150, 243, 0.2);
}

.tip-icon {
    font-size: 1.5rem;
    margin-bottom: 0.5rem;
    color: #2196f3;
}

/* Success state */
.success-state {
    animation: fadeInScale 0.6s ease-out;
    text-align: center;
    padding: 3rem 1rem;
}

.success-icon {
    font-size: 4rem;
    color: #198754;
    margin-bottom: 1rem;
    animation: pulseSubmit 2s infinite;
}

/* Responsive text */
.responsive-text {
    font-size: clamp(0.8rem, 2vw, 1rem);
}

/* Typewriter effect for headers */
.typewriter {
    overflow: hidden;
    border-right: 2px solid #8B0000;
    white-space: nowrap;
    animation: typewriter 2s steps(40, end);
}

/* Form validation states */
.is-valid {
    border-color: #198754 !important;
}

.is-invalid {
    border-color: #dc3545 !important;
}

/* Today's date highlight */
.today-date {
    background: linear-gradient(135deg, #198754, #0d6e33);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
}

/* Required field indicators */
.required-field::after {
    content: " *";
    color: #dc3545;
}
</style>

<div class="container mt-3 mt-md-4 create-task-container">
    <!-- Header Section -->
    <div class="card shadow-sm border-0 creation-header mb-4 animate__animated animate__fadeIn">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-12 col-md-8">
                    <h3 class="fw-bold mb-2 text-white">
                        <i class="bi bi-pencil-square me-2"></i> Submit Today's Task
                    </h3>
                    <p class="text-white mb-0 responsive-text opacity-75">
                        <i class="bi bi-info-circle me-1"></i> Document your daily progress and achievements
                    </p>
                </div>
                <div class="col-12 col-md-4 text-center text-md-end mt-3 mt-md-0">
                    <div class="today-date d-inline-block">
                        <i class="bi bi-calendar-check-fill me-2"></i>
                        <?= date('l, F j, Y') ?>
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
                    <h6 class="alert-heading mb-1">Please Check Your Input</h6>
                    <?= htmlspecialchars($error) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <!-- Success State -->
        <div class="card shadow-sm border-0 task-form-card animate__animated animate__fadeIn">
            <div class="card-body success-state">
                <div class="success-icon">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <h3 class="text-success mb-3">Task Submitted Successfully!</h3>
                <p class="text-muted mb-4">Your daily task has been recorded and is now visible in your task history.</p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                    <a href="/daily-task-system/public/dashboard.php" class="btn btn-success btn-create">
                        <i class="bi bi-house-door-fill me-2"></i> Back to Dashboard
                    </a>
                    <a href="/daily-task-system/public/tasks/my.php" class="btn btn-outline-primary btn-create">
                        <i class="bi bi-list-check me-2"></i> View My Tasks
                    </a>
                    <button onclick="window.location.reload()" class="btn btn-outline-secondary btn-create">
                        <i class="bi bi-plus-circle me-2"></i> Submit Another
                    </button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Creation Form -->
        <div class="card shadow-sm border-0 task-form-card mb-4 animate__animated animate__fadeIn animate__delay-1s">
            <div class="card-body">
                <h5 class="card-title mb-4">
                    <i class="bi bi-journal-text text-primary me-2"></i> Task Information
                </h5>
                
                <form method="post" id="taskCreateForm" class="row g-4">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

                    <!-- Date Field -->
                    <div class="col-12 col-md-4">
                        <div class="form-group-enhanced">
                            <label class="form-label fw-semibold required-field">
                                <i class="bi bi-calendar-event-fill me-1 text-primary"></i> Task Date
                            </label>
                            <input type="text" 
                                   class="form-control form-control-create bg-light" 
                                   value="<?= $today ?>" 
                                   readonly
                                   style="cursor: not-allowed;">
                            <div class="form-text">
                                <i class="bi bi-info-circle me-1"></i>
                                Today's date is automatically selected
                            </div>
                        </div>
                    </div>

                    <!-- Hours Field -->
                    <div class="col-12 col-md-4">
                        <div class="form-group-enhanced">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-clock-fill me-1 text-warning"></i> Hours Worked
                            </label>
                            <input type="number" 
                                   step="0.25" 
                                   min="0" 
                                   max="24" 
                                   name="hours" 
                                   class="form-control form-control-create" 
                                   placeholder="e.g., 7.5"
                                   id="hoursInput">
                            <div class="form-text">
                                <i class="bi bi-calculator me-1"></i>
                                Enter hours worked (0-24, decimals allowed)
                            </div>
                            <div class="character-counter mt-1" id="hoursCounter"></div>
                        </div>
                    </div>

                    <!-- Summary Field -->
                    <div class="col-12">
                        <div class="form-group-enhanced">
                            <label class="form-label fw-semibold required-field">
                                <i class="bi bi-card-text me-1 text-success"></i> Task Summary
                            </label>
                            <input type="text" 
                                   name="summary" 
                                   class="form-control form-control-create" 
                                   placeholder="Brief, descriptive summary of your main accomplishment today..."
                                   required
                                   maxlength="255"
                                   id="summaryInput">
                            <div class="form-text">
                                <i class="bi bi-lightbulb me-1"></i>
                                Keep it concise but descriptive (max 255 characters)
                            </div>
                            <div class="character-counter mt-1" id="summaryCounter">
                                <span class="counter-success">0</span>/255 characters
                            </div>
                        </div>
                    </div>

                    <!-- Details Field -->
                    <div class="col-12">
                        <div class="form-group-enhanced">
                            <label class="form-label fw-semibold required-field">
                                <i class="bi bi-journal-text me-1 text-info"></i> Detailed Description
                            </label>
                            <textarea name="details" 
                                      class="form-control form-control-create" 
                                      rows="5" 
                                      placeholder="Provide detailed information about your work today. Include specific tasks completed, methods used, and any relevant context..."
                                      required
                                      maxlength="2000"
                                      id="detailsInput"></textarea>
                            <div class="form-text">
                                <i class="bi bi-list-check me-1"></i>
                                Be specific about what you accomplished and how
                            </div>
                            <div class="character-counter mt-1" id="detailsCounter">
                                <span class="counter-success">0</span>/2000 characters
                            </div>
                        </div>
                    </div>

                    <!-- Blockers Field -->
                    <div class="col-12">
                        <div class="form-group-enhanced">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-exclamation-octagon-fill me-1 text-danger"></i> Blockers & Challenges
                            </label>
                            <textarea name="blockers" 
                                      class="form-control form-control-create" 
                                      rows="3" 
                                      placeholder="Any issues, blockers, or challenges you encountered? What support do you need?"
                                      maxlength="1000"
                                      id="blockersInput"></textarea>
                            <div class="form-text">
                                <i class="bi bi-shield-exclamation me-1"></i>
                                Optional - document any obstacles for future reference
                            </div>
                            <div class="character-counter mt-1" id="blockersCounter">
                                <span class="counter-success">0</span>/1000 characters
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="col-12">
                        <hr class="my-4">
                        <div class="d-flex flex-column flex-sm-row gap-3">
                            <button type="submit" class="btn btn-success btn-create btn-submit-task flex-fill" id="submitBtn">
                                <i class="bi bi-send-fill me-2"></i> Submit Today's Task
                            </button>
                            <a href="/daily-task-system/public/dashboard.php" class="btn btn-outline-secondary flex-fill">
                                <i class="bi bi-x-circle-fill me-2"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tips & Best Practices -->
        <div class="card border-0 bg-light mt-4 animate__animated animate__fadeIn animate__delay-2s">
            <div class="card-body">
                <h6 class="text-muted mb-3">
                    <i class="bi bi-lightbulb-fill me-2 text-warning"></i> Tips for Great Task Submissions
                </h6>
                <div class="row tips-card">
                    <div class="col-12 col-md-4 mb-3">
                        <div class="card tip-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-check-circle-fill tip-icon"></i>
                                <h6 class="fw-bold">Be Specific</h6>
                                <p class="small text-muted mb-0">Include details about what you accomplished and how you did it.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <div class="card tip-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-clock-fill tip-icon"></i>
                                <h6 class="fw-bold">Track Time</h6>
                                <p class="small text-muted mb-0">Accurate hours help with project planning and resource allocation.</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-12 col-md-4 mb-3">
                        <div class="card tip-card h-100">
                            <div class="card-body text-center">
                                <i class="bi bi-flag-fill tip-icon"></i>
                                <h6 class="fw-bold">Report Blockers</h6>
                                <p class="small text-muted mb-0">Document challenges early so your team can provide support.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
// Character counters
function setupCharacterCounter(inputId, counterId, maxLength) {
    const input = document.getElementById(inputId);
    const counter = document.getElementById(counterId);
    const span = counter.querySelector('span');
    
    input.addEventListener('input', function() {
        const length = this.value.length;
        span.textContent = length;
        
        // Update counter color based on usage
        if (length === 0) {
            span.className = 'counter-success';
        } else if (length > maxLength * 0.8) {
            span.className = 'counter-danger';
        } else if (length > maxLength * 0.6) {
            span.className = 'counter-warning';
        } else {
            span.className = 'counter-success';
        }
        
        // Validate length
        if (length > maxLength) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
        }
    });
}

// Hours input validation
document.getElementById('hoursInput').addEventListener('input', function() {
    const value = parseFloat(this.value) || 0;
    const counter = document.getElementById('hoursCounter');
    
    if (value < 0 || value > 24) {
        this.classList.add('is-invalid');
        counter.innerHTML = '<span class="counter-danger">Please enter a value between 0 and 24</span>';
    } else if (value === 0) {
        this.classList.remove('is-invalid');
        counter.innerHTML = '<span class="counter-warning">No hours recorded</span>';
    } else {
        this.classList.remove('is-invalid');
        counter.innerHTML = `<span class="counter-success">${value} hours recorded</span>`;
    }
});

// Initialize character counters
setupCharacterCounter('summaryInput', 'summaryCounter', 255);
setupCharacterCounter('detailsInput', 'detailsCounter', 2000);
setupCharacterCounter('blockersInput', 'blockersCounter', 1000);

// Form submission handling
document.getElementById('taskCreateForm').addEventListener('submit', function(e) {
    const summary = document.getElementById('summaryInput').value.trim();
    const details = document.getElementById('detailsInput').value.trim();
    const hours = document.getElementById('hoursInput').value;
    
    // Basic validation
    if (!summary || !details) {
        e.preventDefault();
        showValidationError('Summary and details are required fields.');
        return false;
    }
    
    if (summary.length > 255) {
        e.preventDefault();
        showValidationError('Summary must be 255 characters or less.');
        return false;
    }
    
    if (details.length > 2000) {
        e.preventDefault();
        showValidationError('Details must be 2000 characters or less.');
        return false;
    }
    
    const hoursValue = parseFloat(hours);
    if (hours && (hoursValue < 0 || hoursValue > 24)) {
        e.preventDefault();
        showValidationError('Hours must be between 0 and 24.');
        return false;
    }
    
    // Show loading state
    const submitBtn = document.getElementById('submitBtn');
    const originalText = submitBtn.innerHTML;
    
    submitBtn.innerHTML = '<i class="bi bi-arrow-repeat loading-spinner me-2"></i> Submitting Task...';
    submitBtn.disabled = true;
    
    // Re-enable after 5 seconds (in case of error)
    setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    }, 5000);
});

function showValidationError(message) {
    // Create temporary error alert
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger animate__animated animate__shakeX';
    alertDiv.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
            <div>
                <h6 class="alert-heading mb-1">Please Check Your Input</h6>
                ${message}
            </div>
        </div>
    `;
    
    // Insert after the form
    const formCard = document.querySelector('.task-form-card .card-body');
    formCard.insertBefore(alertDiv, formCard.firstChild);
    
    // Remove after 5 seconds
    setTimeout(() => {
        alertDiv.classList.add('animate__animated', 'animate__fadeOut');
        setTimeout(() => {
            alertDiv.remove();
        }, 500);
    }, 5000);
    
    // Scroll to error
    alertDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

// Add interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add focus effects to form controls
    const formControls = document.querySelectorAll('.form-control-create');
    formControls.forEach(control => {
        control.addEventListener('focus', function() {
            this.parentElement.classList.add('animate__animated', 'animate__pulse');
        });
        control.addEventListener('blur', function() {
            this.parentElement.classList.remove('animate__animated', 'animate__pulse');
        });
    });
    
    // Auto-focus on summary field
    const summaryInput = document.getElementById('summaryInput');
    if (summaryInput) {
        setTimeout(() => {
            summaryInput.focus();
        }, 500);
    }
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl + Enter to submit
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('taskCreateForm').dispatchEvent(new Event('submit'));
    }
    
    // Escape key to cancel
    if (e.key === 'Escape') {
        window.location.href = '/daily-task-system/public/dashboard.php';
    }
});

// Auto-save draft (local storage)
function autoSaveDraft() {
    const formData = {
        summary: document.getElementById('summaryInput').value,
        details: document.getElementById('detailsInput').value,
        blockers: document.getElementById('blockersInput').value,
        hours: document.getElementById('hoursInput').value,
        timestamp: new Date().getTime()
    };
    
    localStorage.setItem('taskDraft', JSON.stringify(formData));
}

function loadDraft() {
    const draft = localStorage.getItem('taskDraft');
    if (draft) {
        const formData = JSON.parse(draft);
        const oneHourAgo = new Date().getTime() - (60 * 60 * 1000);
        
        // Only load draft if it's less than 1 hour old
        if (formData.timestamp > oneHourAgo) {
            if (confirm('We found a previously saved draft. Would you like to load it?')) {
                document.getElementById('summaryInput').value = formData.summary || '';
                document.getElementById('detailsInput').value = formData.details || '';
                document.getElementById('blockersInput').value = formData.blockers || '';
                document.getElementById('hoursInput').value = formData.hours || '';
                
                // Trigger character counters
                document.getElementById('summaryInput').dispatchEvent(new Event('input'));
                document.getElementById('detailsInput').dispatchEvent(new Event('input'));
                document.getElementById('blockersInput').dispatchEvent(new Event('input'));
                document.getElementById('hoursInput').dispatchEvent(new Event('input'));
            }
        }
        
        // Clear old draft
        localStorage.removeItem('taskDraft');
    }
}

// Set up auto-save
const formInputs = document.querySelectorAll('#taskCreateForm input, #taskCreateForm textarea');
formInputs.forEach(input => {
    input.addEventListener('input', autoSaveDraft);
});

// Load draft on page load
document.addEventListener('DOMContentLoaded', loadDraft);

// Clear draft on successful submission
document.getElementById('taskCreateForm').addEventListener('submit', function() {
    localStorage.removeItem('taskDraft');
});
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>