<?php
// public/admin/task_view.php
$pageTitle = "Task Details";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';

require_role('admin');

// CSRF setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error   = '';
$success = '';

$taskId = (int)($_GET['id'] ?? 0);
if ($taskId <= 0) {
    header('Location: /daily-task-system/public/admin/tasks.php');
    exit;
}

// Handle Lock action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lock_task'])) {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = 'Invalid form submission. Please try again.';
    } else {
        try {
            // Update status to locked
            $upd = $pdo->prepare("
                UPDATE daily_tasks 
                SET status = 'locked', updated_at = NOW() 
                WHERE id = ?
            ");
            $upd->execute([$taskId]);

            // Audit log
            $log = $pdo->prepare("
                INSERT INTO audit_logs (actor_user_id, action, target, meta) 
                VALUES (?, ?, ?, ?)
            ");
            $log->execute([
                current_user_id(),
                'lock_task',
                'daily_tasks',
                json_encode(['task_id' => $taskId, 'status' => 'locked'])
            ]);

            $success = 'Task locked successfully.';
        } catch (PDOException $e) {
            $error = 'Error locking task.';
        }
    }
}

// Fetch (or re-fetch) task with user info
$sql = "
    SELECT dt.id,
           dt.user_id,
           dt.task_date,
           dt.summary,
           dt.details,
           dt.blockers,
           dt.hours,
           dt.status,
           dt.created_at,
           dt.updated_at,
           u.full_name,
           u.email
    FROM daily_tasks dt
    JOIN users u ON dt.user_id = u.id
    WHERE dt.id = ?
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$taskId]);
$task = $stmt->fetch();

if (!$task) {
    http_response_code(404);
    exit('Task not found.');
}

require_once __DIR__ . '/../../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations for task view */
@keyframes slideInTask {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulseLock {
    0% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(220, 53, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(220, 53, 69, 0); }
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

@keyframes highlightChange {
    0% { background-color: transparent; }
    50% { background-color: rgba(25, 135, 84, 0.1); }
    100% { background-color: transparent; }
}

/* Page specific styles */
.task-view-container {
    animation: slideInTask 0.6s ease-out;
}

.task-card {
    animation: fadeInScale 0.5s ease-out;
    transition: all 0.3s ease;
    border: none;
    border-left: 4px solid transparent;
    overflow: hidden;
}

.task-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
}

.task-card.locked {
    border-left-color: #dc3545;
    background: linear-gradient(135deg, #fff, #f8f9fa);
}

.task-card.edited {
    border-left-color: #ffc107;
    background: linear-gradient(135deg, #fff, #f8f9fa);
}

.task-card.submitted {
    border-left-color: #198754;
    background: linear-gradient(135deg, #fff, #f8f9fa);
}

.status-badge {
    font-size: 0.8rem;
    padding: 0.5em 1em;
    transition: all 0.3s ease;
    border: none;
}

.status-badge:hover {
    transform: scale(1.08);
}

.btn-task-action {
    transition: all 0.3s ease;
    border: none;
    font-weight: 600;
    padding: 0.6rem 1.2rem;
}

.btn-task-action:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.btn-lock {
    animation: pulseLock 2s infinite;
}

.info-card {
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.content-card {
    transition: all 0.3s ease;
    border: none;
    min-height: 200px;
}

.content-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important;
}

.user-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #8B0000, #000);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1.5rem;
    transition: all 0.3s ease;
}

.user-avatar:hover {
    transform: scale(1.05);
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .task-view-container {
        margin-top: 1rem !important;
    }
    
    .header-actions {
        flex-direction: column;
        gap: 1rem;
        align-items: flex-start !important;
    }
    
    .btn-task-action {
        width: 100%;
        margin-bottom: 0.5rem;
        padding: 0.8rem 1.5rem;
    }
    
    .info-card .row {
        margin: 0;
    }
    
    .info-card .col-md-6 {
        margin-bottom: 1rem;
    }
    
    .content-card {
        min-height: 150px;
    }
    
    .user-avatar {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
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
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.775rem;
    }
    
    .content-text {
        font-size: 0.9rem;
    }
}

/* Status colors */
.bg-locked { background-color: #dc3545 !important; }
.bg-edited { background-color: #ffc107 !important; color: #000 !important; }
.bg-submitted { background-color: #198754 !important; }

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

@keyframes shakeError {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Content styling */
.content-section {
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.content-section:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.content-text {
    white-space: pre-wrap;
    line-height: 1.6;
    font-size: 0.95rem;
}

.empty-content {
    color: #6c757d;
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

/* Metadata styling */
.meta-item {
    display: flex;
    align-items: center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #f0f0f0;
    transition: all 0.3s ease;
}

.meta-item:hover {
    background-color: rgba(139, 0, 0, 0.05);
    transform: translateX(5px);
}

.meta-item:last-child {
    border-bottom: none;
}

.meta-icon {
    width: 32px;
    height: 32px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.meta-icon.primary { background: linear-gradient(135deg, #0d6efd, #0b5ed7); color: white; }
.meta-icon.success { background: linear-gradient(135deg, #198754, #157347); color: white; }
.meta-icon.warning { background: linear-gradient(135deg, #ffc107, #ffb507); color: #000; }
.meta-icon.danger { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
.meta-icon.info { background: linear-gradient(135deg, #6c757d, #5a6268); color: white; }

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

/* Highlight animation for updates */
.highlight-update {
    animation: highlightChange 2s ease-in-out;
}

/* Print styles */
@media print {
    .btn-task-action,
    .alert {
        display: none !important;
    }
    
    .task-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<div class="container mt-3 mt-md-4 task-view-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 header-actions animate__animated animate__fadeIn">
        <div class="mb-2 mb-md-0">
            <h3 class="fw-bold mb-1">
                <i class="bi bi-eye-fill text-danger me-2"></i> Task Details
            </h3>
            <p class="text-muted mb-0 responsive-text">
                <i class="bi bi-info-circle me-1"></i> View and manage task submission
            </p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2 w-100 w-md-auto">
            <a href="/daily-task-system/public/admin/tasks.php" class="btn btn-secondary btn-task-action">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> 
                <span class="d-none d-sm-inline">Back to Tasks</span>
                <span class="d-sm-none">Back</span>
            </a>

            <?php if ($task['status'] !== 'locked'): ?>
                <form method="post" class="d-inline w-100 w-sm-auto">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" name="lock_task" class="btn btn-danger btn-task-action btn-lock w-100">
                        <i class="bi bi-lock-fill me-1"></i> 
                        <span class="d-none d-sm-inline">Lock Task</span>
                        <span class="d-sm-none">Lock</span>
                    </button>
                </form>
            <?php else: ?>
                <span class="badge bg-locked status-badge align-self-center p-3">
                    <i class="bi bi-lock-fill me-1"></i> Task Locked
                </span>
            <?php endif; ?>
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
        <div class="alert alert-success animate__animated animate__fadeIn highlight-update">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill me-3 fs-4"></i>
                <div>
                    <h6 class="alert-heading mb-1">Success!</h6>
                    <?= htmlspecialchars($success) ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Main Task Card -->
    <div class="card shadow-sm border-0 task-card <?= $task['status'] ?> mb-4 animate__animated animate__fadeIn animate__delay-1s">
        <div class="card-body">
            <!-- User Info Row -->
            <div class="row align-items-center mb-4">
                <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center">
                        <div class="user-avatar me-3">
                            <?= strtoupper(substr($task['full_name'], 0, 1)) ?>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($task['full_name']) ?></h5>
                            <p class="text-muted mb-0">
                                <i class="bi bi-envelope-fill me-1"></i>
                                <?= htmlspecialchars($task['email']) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-6 mt-3 mt-md-0">
                    <div class="d-flex flex-wrap justify-content-md-end gap-2">
                        <span class="badge <?= $task['status'] === 'locked' ? 'bg-locked' : ($task['status'] === 'edited' ? 'bg-edited' : 'bg-submitted') ?> status-badge">
                            <i class="bi bi-<?= $task['status'] === 'locked' ? 'lock' : ($task['status'] === 'edited' ? 'pencil' : 'check-circle') ?>-fill me-1"></i>
                            <?= ucfirst($task['status']) ?>
                        </span>
                        <span class="badge bg-primary status-badge">
                            <i class="bi bi-clock-fill me-1"></i>
                            <?= htmlspecialchars((string)$task['hours']) ?> hours
                        </span>
                    </div>
                </div>
            </div>

            <!-- Task Metadata -->
            <div class="row g-3 mb-4">
                <div class="col-12">
                    <div class="card info-card">
                        <div class="card-body">
                            <h6 class="card-title text-muted mb-3">
                                <i class="bi bi-info-circle-fill me-2"></i> Task Information
                            </h6>
                            <div class="row g-2">
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-item">
                                        <div class="meta-icon primary">
                                            <i class="bi bi-calendar-event-fill"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Task Date</small>
                                            <strong><?= htmlspecialchars($task['task_date']) ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-item">
                                        <div class="meta-icon success">
                                            <i class="bi bi-upload"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Submitted</small>
                                            <strong><?= htmlspecialchars($task['created_at']) ?></strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12 col-sm-6 col-md-4">
                                    <div class="meta-item">
                                        <div class="meta-icon info">
                                            <i class="bi bi-arrow-repeat"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Last Updated</small>
                                            <strong><?= htmlspecialchars($task['updated_at'] ?? '-') ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Task Content Sections -->
            <div class="row g-4">
                <!-- Summary Section -->
                <div class="col-12 col-lg-6">
                    <div class="card content-card h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-card-text me-2 text-primary"></i> Summary
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="content-section h-100">
                                <div class="content-text">
                                    <?= nl2br(htmlspecialchars($task['summary'])) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Section -->
                <div class="col-12 col-lg-6">
                    <div class="card content-card h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-journal-text me-2 text-success"></i> Details
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="content-section h-100">
                                <div class="content-text">
                                    <?= $task['details'] ? htmlspecialchars($task['details']) : '<div class="empty-content"><i class="bi bi-info-circle me-2"></i>No details provided</div>' ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Blockers Section -->
                <div class="col-12">
                    <div class="card content-card">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <h6 class="text-muted mb-3">
                                <i class="bi bi-exclamation-octagon-fill me-2 text-danger"></i> Blockers & Challenges
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="content-section">
                                <div class="content-text">
                                    <?= $task['blockers'] !== null && $task['blockers'] !== '' 
                                       ? htmlspecialchars($task['blockers']) 
                                       : '<div class="empty-content"><i class="bi bi-check-circle me-2"></i>No blockers reported</div>' 
                                    ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card border-0 bg-light mt-4 animate__animated animate__fadeIn animate__delay-2s">
        <div class="card-body">
            <h6 class="text-muted mb-3">
                <i class="bi bi-lightning-fill me-2"></i> Quick Actions
            </h6>
            <div class="d-flex flex-column flex-sm-row gap-2">
                <a href="/daily-task-system/public/admin/tasks.php?tab=all&user_all=<?= $task['user_id'] ?>" 
                   class="btn btn-outline-primary btn-task-action flex-fill">
                    <i class="bi bi-list-ul me-2"></i> View All Tasks by This User
                </a>
                <a href="/daily-task-system/public/admin/tasks.php?tab=all&date=<?= $task['task_date'] ?>" 
                   class="btn btn-outline-success btn-task-action flex-fill">
                    <i class="bi bi-calendar-check me-2"></i> View All Tasks from This Date
                </a>
                <button class="btn btn-outline-info btn-task-action flex-fill" onclick="window.print()">
                    <i class="bi bi-printer me-2"></i> Print Task Details
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Add interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to cards
    const cards = document.querySelectorAll('.task-card, .info-card, .content-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add loading state to lock button
    const lockForm = document.querySelector('form[method="post"]');
    if (lockForm) {
        lockForm.addEventListener('submit', function(e) {
            if (!confirm('🔒 Lock this task?\n\nOnce locked, no further edits will be allowed.\nThis action cannot be undone.')) {
                e.preventDefault();
                return;
            }
            
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat loading-spinner me-2"></i> Locking...';
                submitBtn.disabled = true;
                
                // Re-enable after 5 seconds (in case of error)
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 5000);
            }
        });
    }

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

    // Add click effects to meta items
    const metaItems = document.querySelectorAll('.meta-item');
    metaItems.forEach(item => {
        item.addEventListener('click', function() {
            this.classList.add('animate__animated', 'animate__pulse');
            setTimeout(() => {
                this.classList.remove('animate__animated', 'animate__pulse');
            }, 1000);
        });
    });

    // Add copy functionality to content sections
    const contentSections = document.querySelectorAll('.content-section');
    contentSections.forEach(section => {
        section.addEventListener('click', function() {
            const text = this.querySelector('.content-text').textContent;
            if (text && !text.includes('No ') && !text.includes('—')) {
                this.classList.add('animate__animated', 'animate__flash');
                setTimeout(() => {
                    this.classList.remove('animate__animated', 'animate__flash');
                }, 1000);
            }
        });
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to go back
    if (e.key === 'Escape') {
        window.location.href = '/daily-task-system/public/admin/tasks.php';
    }
    
    // Ctrl + P to print
    if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
        e.preventDefault();
        window.print();
    }
});

// Viewport detection for mobile optimizations
function isMobileDevice() {
    return window.innerWidth <= 768;
}

// Adjust content height for mobile
if (isMobileDevice()) {
    document.addEventListener('DOMContentLoaded', function() {
        const contentCards = document.querySelectorAll('.content-card');
        contentCards.forEach(card => {
            card.style.minHeight = 'auto';
        });
    });
}
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>