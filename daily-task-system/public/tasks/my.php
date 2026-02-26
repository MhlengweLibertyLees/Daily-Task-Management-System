<?php
$pageTitle = "My Tasks";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';

require_role('member');

$userId = current_user_id();

$stmt = $pdo->prepare("
    SELECT id, task_date, summary, details, blockers, status, hours, created_at, updated_at
    FROM daily_tasks
    WHERE user_id = ?
    ORDER BY task_date DESC
");
$stmt->execute([$userId]);
$tasks = $stmt->fetchAll();

require_once __DIR__ . '/../../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations for my tasks page */
@keyframes slideInTasks {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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

@keyframes pulseCreate {
    0% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(25, 135, 84, 0); }
    100% { box-shadow: 0 0 0 0 rgba(25, 135, 84, 0); }
}

@keyframes tableRowAppear {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Page specific styles */
.my-tasks-container {
    animation: slideInTasks 0.6s ease-out;
}

.tasks-header-card {
    animation: fadeInScale 0.5s ease-out;
    background: linear-gradient(135deg, #8B0000, #000);
    color: white;
    border: none;
    overflow: hidden;
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
    font-size: 0.75rem;
    padding: 0.4em 0.8em;
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

.btn-create-task {
    animation: pulseCreate 2s infinite;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .my-tasks-container {
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
    
    .table-responsive {
        font-size: 0.8rem;
        border: 1px solid #dee2e6;
        margin: 0 -10px;
    }
    
    .table th, .table td {
        padding: 0.5rem;
        white-space: nowrap;
    }
    
    .task-card-mobile {
        margin-bottom: 1rem;
        animation: fadeInScale 0.5s ease-out;
    }
    
    .task-info-mobile {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .stats-card {
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
        font-size: 0.65rem;
    }
    
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.775rem;
    }
    
    .task-date {
        font-size: 0.9rem;
    }
}

/* Enhanced table styles */
.table-hover-animate tr {
    transition: all 0.3s ease;
    animation: tableRowAppear 0.5s ease-out;
}

.table-hover-animate tr:hover {
    background-color: rgba(139, 0, 0, 0.05) !important;
    transform: scale(1.01);
}

/* Status colors */
.bg-locked { background-color: #dc3545 !important; }
.bg-edited { background-color: #ffc107 !important; color: #000 !important; }
.bg-submitted { background-color: #198754 !important; }

/* Stats cards */
.stats-card {
    background: linear-gradient(135deg, #8B0000, #000);
    color: white;
    border-radius: 12px;
    padding: 1.5rem;
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
}

.stats-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.stats-label {
    opacity: 0.9;
    font-size: 0.9rem;
}

/* Empty state styling */
.empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

/* Content styling */
.content-preview {
    display: -webkit-box;
    
    -webkit-box-orient: vertical;
    overflow: hidden;
    line-height: 1.4;
}

.task-date {
    font-weight: 600;
    color: #495057;
}

/* Action buttons */
.action-buttons {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
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

/* Custom scrollbar */
.table-responsive::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-responsive::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb {
    background: #8B0000;
    border-radius: 4px;
}

.table-responsive::-webkit-scrollbar-thumb:hover {
    background: #660000;
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

<div class="container mt-3 mt-md-4 my-tasks-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 header-actions animate__animated animate__fadeIn">
        <div class="mb-2 mb-md-0">
            <h3 class="fw-bold mb-1">
                <i class="bi bi-list-check text-danger me-2"></i> My Task History
            </h3>
            <p class="text-muted mb-0 responsive-text">
                <i class="bi bi-info-circle me-1"></i> View and manage your daily task submissions
            </p>
        </div>
        <div class="d-flex flex-column flex-sm-row gap-2">
            <a href="/daily-task-system/public/tasks/create.php" class="btn btn-success btn-task-action btn-create-task">
                <i class="bi bi-plus-circle-fill me-1"></i> 
                <span class="d-none d-sm-inline">Create New Task</span>
                <span class="d-sm-none">New Task</span>
            </a>
            <a href="/daily-task-system/public/dashboard.php" class="btn btn-secondary btn-task-action">
                <i class="bi bi-arrow-left-circle-fill me-1"></i> 
                <span class="d-none d-sm-inline">Back to Dashboard</span>
                <span class="d-sm-none">Dashboard</span>
            </a>
        </div>
    </div>

    <!-- Task Statistics -->
    <?php if (!empty($tasks)): ?>
        <?php
        $totalTasks = count($tasks);
        $lockedTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'locked'));
        $editedTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'edited'));
        $submittedTasks = count(array_filter($tasks, fn($t) => $t['status'] === 'submitted'));
        ?>
        <div class="row mb-4 animate__animated animate__fadeIn animate__delay-1s">
            <div class="col-6 col-md-3">
                <div class="stats-card text-center">
                    <div class="stats-number"><?= $totalTasks ?></div>
                    <div class="stats-label">
                        <i class="bi bi-list-check me-1"></i> Total Tasks
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #198754, #0d6e33);">
                    <div class="stats-number"><?= $submittedTasks ?></div>
                    <div class="stats-label">
                        <i class="bi bi-check-circle-fill me-1"></i> Submitted
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #ffc107, #e0a800);">
                    <div class="stats-number"><?= $editedTasks ?></div>
                    <div class="stats-label">
                        <i class="bi bi-pencil-fill me-1"></i> Edited
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stats-card text-center" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                    <div class="stats-number"><?= $lockedTasks ?></div>
                    <div class="stats-label">
                        <i class="bi bi-lock-fill me-1"></i> Locked
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <?php if (empty($tasks)): ?>
        <!-- Empty State -->
        <div class="card shadow-sm border-0 task-card animate__animated animate__fadeIn">
            <div class="card-body text-center empty-state">
                <i class="bi bi-inbox text-muted"></i>
                <h4 class="text-muted mb-3">No Tasks Yet</h4>
                <p class="text-muted mb-4">Start tracking your daily progress by creating your first task.</p>
                <div class="d-flex flex-column flex-sm-row justify-content-center gap-2">
                    <a href="/daily-task-system/public/tasks/create.php" class="btn btn-success btn-lg btn-create-task">
                        <i class="bi bi-plus-circle-fill me-2"></i> Create Your First Task
                    </a>
                    <a href="/daily-task-system/public/dashboard.php" class="btn btn-outline-secondary btn-lg">
                        <i class="bi bi-arrow-left-circle-fill me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Desktop Table View -->
        <div class="d-none d-md-block">
            <div class="table-responsive shadow-sm rounded-3 animate__animated animate__fadeIn">
                <table class="table table-striped table-bordered align-middle table-hover-animate m-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="fw-semibold"><i class="bi bi-calendar-event-fill me-1"></i> Date</th>
                            <th class="fw-semibold"><i class="bi bi-card-text me-1"></i> Summary</th>
                            <th class="fw-semibold"><i class="bi bi-flag-fill me-1"></i> Status</th>
                            <th class="fw-semibold"><i class="bi bi-clock-fill me-1"></i> Hours</th>
                            <th class="fw-semibold"><i class="bi bi-upload me-1"></i> Submitted</th>
                            <th class="fw-semibold"><i class="bi bi-arrow-repeat me-1"></i> Updated</th>
                            <th class="fw-semibold"><i class="bi bi-gear-fill me-1"></i> Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tasks as $index => $task): ?>
                            <tr class="animate__animated animate__fadeIn" style="animation-delay: <?= $index * 0.1 ?>s;">
                                <td class="task-date"><?= htmlspecialchars($task['task_date']) ?></td>
                                <td>
                                    <span class="content-preview" title="<?= htmlspecialchars($task['summary']) ?>">
                                        <?= htmlspecialchars($task['summary']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($task['status'] === 'locked'): ?>
                                        <span class="badge bg-locked status-badge">
                                            <i class="bi bi-lock-fill me-1"></i> Locked
                                        </span>
                                    <?php elseif ($task['status'] === 'edited'): ?>
                                        <span class="badge bg-edited status-badge">
                                            <i class="bi bi-pencil-fill me-1"></i> Edited
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-submitted status-badge">
                                            <i class="bi bi-check-circle-fill me-1"></i> Submitted
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($task['hours']) ?></td>
                                <td class="small"><?= htmlspecialchars($task['created_at']) ?></td>
                                <td class="small"><?= htmlspecialchars($task['updated_at'] ?? '-') ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <button 
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-task-action"
                                            data-bs-toggle="modal"
                                            data-bs-target="#taskViewModal"
                                            data-id="<?= (int)$task['id'] ?>"
                                            data-date="<?= htmlspecialchars($task['task_date']) ?>"
                                            data-summary="<?= htmlspecialchars($task['summary']) ?>"
                                            data-details="<?= htmlspecialchars($task['details']) ?>"
                                            data-blockers="<?= htmlspecialchars($task['blockers']) ?>"
                                            data-status="<?= htmlspecialchars($task['status']) ?>"
                                            data-hours="<?= htmlspecialchars((string)$task['hours']) ?>"
                                            data-created="<?= htmlspecialchars($task['created_at']) ?>"
                                            data-updated="<?= htmlspecialchars($task['updated_at'] ?? '-') ?>"
                                        >
                                            <i class="bi bi-eye-fill me-1"></i> View
                                        </button>

                                        <?php if ($task['status'] !== 'locked'): ?>
                                            <a 
                                                href="/daily-task-system/public/tasks/edit.php?id=<?= $task['id'] ?>" 
                                                class="btn btn-sm btn-warning btn-task-action"
                                            >
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-locked align-self-center">
                                                <i class="bi bi-lock-fill me-1"></i> Locked
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mobile Card View -->
        <div class="d-md-none">
            <div class="row">
                <?php foreach ($tasks as $index => $task): ?>
                    <div class="col-12 mb-3">
                        <div class="card task-card-mobile <?= $task['status'] ?>" style="animation-delay: <?= $index * 0.1 ?>s;">
                            <div class="card-body">
                                <div class="task-info-mobile">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="task-date fw-bold mb-0"><?= htmlspecialchars($task['task_date']) ?></h6>
                                        <div class="d-flex gap-1">
                                            <?php if ($task['status'] === 'locked'): ?>
                                                <span class="badge bg-locked status-badge">
                                                    <i class="bi bi-lock-fill"></i>
                                                </span>
                                            <?php elseif ($task['status'] === 'edited'): ?>
                                                <span class="badge bg-edited status-badge">
                                                    <i class="bi bi-pencil-fill"></i>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-submitted status-badge">
                                                    <i class="bi bi-check-circle-fill"></i>
                                                </span>
                                            <?php endif; ?>
                                            <span class="badge bg-primary">
                                                <i class="bi bi-clock-fill me-1"></i><?= htmlspecialchars($task['hours']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="content-preview mb-2">
                                        <?= htmlspecialchars($task['summary']) ?>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                                        <span>
                                            <i class="bi bi-upload me-1"></i>
                                            <?= date('M j, g:i A', strtotime($task['created_at'])) ?>
                                        </span>
                                        <?php if ($task['updated_at']): ?>
                                            <span>
                                                <i class="bi bi-arrow-repeat me-1"></i>
                                                <?= date('M j, g:i A', strtotime($task['updated_at'])) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <button 
                                            type="button"
                                            class="btn btn-sm btn-outline-primary btn-task-action flex-fill"
                                            data-bs-toggle="modal"
                                            data-bs-target="#taskViewModal"
                                            data-id="<?= (int)$task['id'] ?>"
                                            data-date="<?= htmlspecialchars($task['task_date']) ?>"
                                            data-summary="<?= htmlspecialchars($task['summary']) ?>"
                                            data-details="<?= htmlspecialchars($task['details']) ?>"
                                            data-blockers="<?= htmlspecialchars($task['blockers']) ?>"
                                            data-status="<?= htmlspecialchars($task['status']) ?>"
                                            data-hours="<?= htmlspecialchars((string)$task['hours']) ?>"
                                            data-created="<?= htmlspecialchars($task['created_at']) ?>"
                                            data-updated="<?= htmlspecialchars($task['updated_at'] ?? '-') ?>"
                                        >
                                            <i class="bi bi-eye-fill me-1"></i> View
                                        </button>

                                        <?php if ($task['status'] !== 'locked'): ?>
                                            <a 
                                                href="/daily-task-system/public/tasks/edit.php?id=<?= $task['id'] ?>" 
                                                class="btn btn-sm btn-warning btn-task-action flex-fill"
                                            >
                                                <i class="bi bi-pencil-square me-1"></i> Edit
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-locked align-self-center p-2 text-center flex-fill">
                                                <i class="bi bi-lock-fill me-1"></i> Locked
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- View Task Modal -->
<div class="modal fade" id="taskViewModal" tabindex="-1" aria-labelledby="taskViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content animate__animated animate__fadeIn">
            <div class="modal-header" style="background: linear-gradient(135deg, #000000, #8B0000);">
                <h5 class="modal-title text-white" id="taskViewModalLabel">
                    <i class="bi bi-eye-fill me-2"></i> Task Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-calendar-event-fill text-primary me-2"></i>
                            <strong class="me-2">Date:</strong> 
                            <span id="vDate" class="fw-medium"></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-flag-fill text-warning me-2"></i>
                            <strong class="me-2">Status:</strong> 
                            <span id="vStatus"></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-clock-fill text-info me-2"></i>
                            <strong class="me-2">Hours:</strong> 
                            <span id="vHours" class="fw-medium"></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-upload text-secondary me-2"></i>
                            <strong class="me-2">Submitted:</strong> 
                            <span id="vCreated" class="small"></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-arrow-repeat text-secondary me-2"></i>
                            <strong class="me-2">Last Updated:</strong> 
                            <span id="vUpdated" class="small"></span>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="mb-3">
                    <strong class="d-flex align-items-center mb-2">
                        <i class="bi bi-card-text text-primary me-2"></i> Summary
                    </strong>
                    <div id="vSummary" class="border rounded p-3 bg-light fw-medium"></div>
                </div>
                <div class="mb-3">
                    <strong class="d-flex align-items-center mb-2">
                        <i class="bi bi-journal-text text-success me-2"></i> Details
                    </strong>
                    <div id="vDetails" class="border rounded p-3" style="white-space: pre-wrap; min-height: 100px;"></div>
                </div>
                <div>
                    <strong class="d-flex align-items-center mb-2">
                        <i class="bi bi-exclamation-octagon-fill text-danger me-2"></i> Blockers
                    </strong>
                    <div id="vBlockers" class="border rounded p-3" style="white-space: pre-wrap; min-height: 80px;"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Populate user view modal
const userModal = document.getElementById('taskViewModal');
userModal.addEventListener('show.bs.modal', event => {
    const btn      = event.relatedTarget;
    const date     = btn.getAttribute('data-date')     || '';
    const summary  = btn.getAttribute('data-summary')  || '';
    const details  = btn.getAttribute('data-details')  || '';
    const blockers = btn.getAttribute('data-blockers') || '';
    const status   = btn.getAttribute('data-status')   || '';
    const hours    = btn.getAttribute('data-hours')    || '';
    const created  = btn.getAttribute('data-created')  || '';
    const updated  = btn.getAttribute('data-updated')  || '';

    // Status badge
    let badge = '';
    if (status === 'locked') {
        badge = '<span class="badge bg-locked status-badge"><i class="bi bi-lock-fill me-1"></i> Locked</span>';
    } else if (status === 'edited') {
        badge = '<span class="badge bg-edited status-badge"><i class="bi bi-pencil-fill me-1"></i> Edited</span>';
    } else {
        badge = '<span class="badge bg-submitted status-badge"><i class="bi bi-check-circle-fill me-1"></i> Submitted</span>';
    }

    document.getElementById('vDate').textContent    = date;
    document.getElementById('vStatus').innerHTML    = badge;
    document.getElementById('vHours').textContent   = hours;
    document.getElementById('vCreated').textContent = new Date(created).toLocaleString();
    document.getElementById('vUpdated').textContent = updated ? new Date(updated).toLocaleString() : '-';
    document.getElementById('vSummary').textContent = summary;
    document.getElementById('vDetails').textContent = details || 'No details provided';
    document.getElementById('vBlockers').textContent= blockers || 'No blockers reported';
});

// Add interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to task cards
    const taskCards = document.querySelectorAll('.task-card-mobile, .task-card');
    taskCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn-task-action');
    buttons.forEach(button => {
        if (button.href && !button.href.includes('javascript:')) {
            button.addEventListener('click', function(e) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-arrow-repeat loading-spinner me-2"></i> Loading...';
                this.disabled = true;
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);
            });
        }
    });

    // Add scroll animations for table rows
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all animated elements
    document.querySelectorAll('.animate__animated').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Escape key to go back
    if (e.key === 'Escape') {
        window.location.href = '/daily-task-system/public/dashboard.php';
    }
    
    // N key to create new task
    if (e.key === 'n' && !e.ctrlKey && !e.metaKey) {
        e.preventDefault();
        window.location.href = '/daily-task-system/public/tasks/create.php';
    }
});

// Quick search functionality
function searchTasks() {
    const searchTerm = document.getElementById('taskSearch').value.toLowerCase();
    const taskRows = document.querySelectorAll('.table-hover-animate tr, .task-card-mobile');
    
    taskRows.forEach(row => {
        const text = row.textContent.toLowerCase();
        if (text.includes(searchTerm)) {
            row.style.display = '';
            row.classList.add('animate__animated', 'animate__fadeIn');
        } else {
            row.style.display = 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>