<?php
$pageTitle = "Dashboard";
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../lib/permissions.php';

require_login();

$role   = current_user_role();
$userId = current_user_id();
$today  = date('Y-m-d');

// For members: check today's task
$memberTask = null;
if ($role === 'member') {
    $stmt = $pdo->prepare("
        SELECT id, status 
        FROM daily_tasks 
        WHERE user_id = ? 
          AND task_date = ?
    ");
    $stmt->execute([$userId, $today]);
    $memberTask = $stmt->fetch();
}

// For admins: build Mon–Fri grid and fetch submissions
$weekDays = [];
$start    = strtotime('monday this week');
for ($i = 0; $i < 5; $i++) {
    $weekDays[] = date('Y-m-d', $start + $i * 86400);
}
$members = [];
$submissions = [];

// Only for admin
if ($role === 'admin') {
    // Fetch active members
    $mStmt = $pdo->query("
        SELECT id, full_name 
        FROM users 
        WHERE role = 'member' 
          AND status = 'active' 
        ORDER BY full_name
    ");
    $members = $mStmt->fetchAll();

    if ($members) {
        // Fetch all tasks this week for those members
        $placeholders = implode(',', array_fill(0, count($weekDays), '?'));
        $sql = "
            SELECT id, user_id, task_date, status
            FROM daily_tasks
            WHERE task_date IN ($placeholders)
              AND user_id IN (" . implode(',', array_map(fn($m)=> (int)$m['id'], $members)) . ")
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($weekDays);
        $rows = $stmt->fetchAll();

        // Map submissions[user_id][date] = ['id'=>…, 'status'=>…]
        foreach ($rows as $r) {
            $submissions[$r['user_id']][$r['task_date']] = [
                'id'     => $r['id'],
                'status' => $r['status']
            ];
        }
    }
}

require_once __DIR__ . '/../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulseGlow {
    0% { box-shadow: 0 0 0 0 rgba(139, 0, 0, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(139, 0, 0, 0); }
    100% { box-shadow: 0 0 0 0 rgba(139, 0, 0, 0); }
}

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Dashboard specific styles */
.dashboard-card {
    animation: fadeInUp 0.6s ease-out;
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.35em 0.65em;
    transition: all 0.3s ease;
}

.status-badge:hover {
    transform: scale(1.05);
}

.btn-dashboard {
    transition: all 0.3s ease;
    border: none;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
}

.btn-dashboard:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.welcome-icon {
    animation: pulseGlow 2s infinite;
    border-radius: 50%;
    padding: 10px;
}

.week-grid {
    animation: slideInRight 0.6s ease-out;
}

.table-hover-row tr {
    transition: all 0.3s ease;
}

.table-hover-row tr:hover {
    background-color: rgba(139, 0, 0, 0.05);
    transform: scale(1.01);
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .container {
        padding-left: 10px;
        padding-right: 10px;
    }
    
    .dashboard-card .card-body {
        padding: 1.25rem;
    }
    
    .btn-dashboard {
        width: 100%;
        margin-bottom: 0.5rem;
        padding: 1rem 1.5rem;
    }
    
    .btn-group-mobile {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.875rem;
        border: 1px solid #dee2e6;
    }
    
    .table th, .table td {
        padding: 0.5rem;
        white-space: nowrap;
    }
    
    .mobile-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
}

@media (max-width: 576px) {
    .card-body {
        padding: 1rem;
    }
    
    h2 {
        font-size: 1.5rem;
    }
    
    .alert {
        font-size: 0.9rem;
        padding: 0.75rem;
    }
    
    .badge {
        font-size: 0.7rem;
    }
}

/* Loading animation */
.loading-skeleton {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: loading 1.5s infinite;
}

@keyframes loading {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}

/* Status colors with better contrast */
.bg-submitted { background-color: #198754 !important; }
.bg-edited { background-color: #ffc107 !important; color: #000 !important; }
.bg-locked { background-color: #dc3545 !important; }
.bg-pending { background-color: #6c757d !important; }

/* Smooth transitions for all interactive elements */
a, button, .btn {
    transition: all 0.3s ease !important;
}

/* Custom scrollbar for table */
.table-responsive::-webkit-scrollbar {
    height: 8px;
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
</style>

<div class="container mt-3 mt-md-4">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            
            <!-- Welcome Card -->
            <div class="card shadow-lg border-0 rounded-4 mb-4 dashboard-card">
                <div class="card-body text-center p-3 p-md-4">
                    <img 
                        src="/daily-task-system/public/assets/img/logo.PNG" 
                        alt="Company Logo" 
                        class="img-fluid mb-3 welcome-icon" 
                        style="max-width: 80px;"
                    >
                    <h2 class="fw-bold mb-1 animate__animated animate__fadeIn">
                        <i class="bi bi-house-door-fill text-danger me-2"></i>
                        Welcome, <?= htmlspecialchars($_SESSION['name']) ?>!
                    </h2>
                    <p class="text-muted mb-3 mb-md-4 animate__animated animate__fadeIn animate__delay-1s">
                        <i class="bi bi-calendar-event-fill text-secondary me-1"></i>
                        Today is <?= date('l, j F Y') ?>
                    </p>

                    <?php if ($role === 'admin'): ?>
                        <div class="alert alert-primary shadow-sm animate__animated animate__fadeIn animate__delay-2s">
                            <i class="bi bi-shield-lock-fill me-2"></i>
                            <strong>Admin Access:</strong> Monitor submissions Monday–Friday.
                        </div>
                        <div class="d-flex flex-column flex-md-row justify-content-center gap-2 mt-3 btn-group-mobile">
                            <a 
                                href="/daily-task-system/public/admin/tasks.php" 
                                class="btn btn-lg btn-primary btn-dashboard"
                            >
                                <i class="bi bi-card-checklist me-2"></i> View All Tasks
                            </a>
                            <a 
                                href="/daily-task-system/public/admin/users.php" 
                                class="btn btn-lg btn-secondary btn-dashboard"
                            >
                                <i class="bi bi-people-fill me-2"></i> Manage Users
                            </a>
                            <?php if (in_array($today, $weekDays)): ?>
                                <a 
                                    href="/daily-task-system/public/admin/lock.php?date=<?= $today ?>" 
                                    class="btn btn-lg btn-danger btn-dashboard"
                                    onclick="return confirm('Lock all submissions for <?= $today ?>?');"
                                >
                                    <i class="bi bi-lock-fill me-2"></i> Lock Today
                                </a>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($role === 'member'): ?>
                        <div class="alert alert-info shadow-sm animate__animated animate__fadeIn animate__delay-2s">
                            <i class="bi bi-person-badge-fill me-2"></i>
                            <strong>Member Access:</strong> Submit and manage your daily tasks.
                        </div>

                        <?php if ($memberTask): ?>
                            <div class="alert alert-success animate__animated animate__fadeIn animate__delay-2s">
                                <i class="bi bi-check-circle-fill me-2"></i>
                                You submitted today's task 
                                <strong>(Status: <?= htmlspecialchars($memberTask['status']) ?>)</strong>.
                            </div>
                            <div class="d-flex justify-content-center">
                                <a 
                                    href="/daily-task-system/public/tasks/my.php" 
                                    class="btn btn-lg btn-outline-primary btn-dashboard"
                                >
                                    <i class="bi bi-folder2-open me-2"></i> View My Tasks
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning animate__animated animate__fadeIn animate__delay-2s">
                                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                You have not submitted today's task.
                            </div>
                            <div class="d-flex justify-content-center">
                                <a 
                                    href="/daily-task-system/public/tasks/create.php" 
                                    class="btn btn-lg btn-success btn-dashboard animate__animated animate__pulse animate__infinite"
                                    style="animation-duration: 2s;"
                                >
                                    <i class="bi bi-pencil-square me-2"></i> Submit Today's Task
                                </a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center text-muted small">
                    &copy; <?= date('Y') ?> Daily Task System. All rights reserved.
                </div>
            </div>

            <?php if ($role === 'admin' && $members): ?>
                <!-- Weekly Submission Table -->
                <div class="card shadow-sm border-0 mb-4 week-grid">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="bi bi-list-check me-2"></i> 
                        <div>
                            Weekly Submission Status
                            <span class="text-muted small d-none d-md-inline">(Monday → Friday)</span>
                            <span class="text-muted small d-md-none">(Mon-Fri)</span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered m-0 table-hover-row">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">User</th>
                                        <?php foreach ($weekDays as $day): ?>
                                            <th class="text-center fw-semibold">
                                                <span class="d-none d-md-block"><?= date('D<br>j M', strtotime($day)) ?></span>
                                                <span class="d-md-none small"><?= date('D j', strtotime($day)) ?></span>
                                            </th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($members as $index => $m): ?>
                                        <tr class="animate__animated animate__fadeIn" style="animation-delay: <?= $index * 0.1 ?>s;">
                                            <td class="fw-medium"><?= htmlspecialchars($m['full_name']) ?></td>
                                            <?php foreach ($weekDays as $day): 
                                                $cell = $submissions[$m['id']][$day] ?? null;
                                                if ($cell):
                                                    $st = $cell['status'];
                                                    $id = $cell['id'];
                                                    if ($st === 'locked'):
                                                        $badge = 'bg-locked'; $icon = 'bi-lock-fill';   $txt = 'Locked';
                                                    elseif ($st === 'edited'):
                                                        $badge = 'bg-edited'; $icon = 'bi-pencil-fill'; $txt = 'Edited';
                                                    else:
                                                        $badge = 'bg-submitted'; $icon = 'bi-check-circle-fill'; $txt = 'Submitted';
                                                    endif;
                                            ?>
                                                <td class="text-center">
                                                    <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-1">
                                                        <span class="badge <?= $badge ?> status-badge">
                                                            <i class="<?= $icon ?> me-1"></i>
                                                            <span class="d-none d-md-inline"><?= $txt ?></span>
                                                            <span class="d-md-none"><?= substr($txt, 0, 1) ?></span>
                                                        </span>
                                                        <a 
                                                            href="/daily-task-system/public/admin/task_view.php?id=<?= $id ?>" 
                                                            class="btn btn-outline-secondary btn-sm" 
                                                            title="View details"
                                                        >
                                                            <i class="bi bi-eye-fill"></i>
                                                        </a>
                                                    </div>
                                                </td>
                                            <?php else: ?>
                                                <td class="text-center">
                                                    <span class="badge bg-pending status-badge">
                                                        <i class="bi bi-x-circle-fill me-1"></i> 
                                                        <span class="d-none d-md-inline">Not Submitted</span>
                                                        <span class="d-md-none">None</span>
                                                    </span>
                                                </td>
                                            <?php endif; endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Add interactive animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to cards
    const cards = document.querySelectorAll('.dashboard-card');
    cards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-5px)';
        });
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });

    // Add loading states to buttons
    const buttons = document.querySelectorAll('.btn-dashboard');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!this.href.includes('javascript:')) {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Loading...';
                this.disabled = true;
                
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 2000);
            }
        });
    });

    // Add scroll animations
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

    // Observe all cards and table rows for scroll animations
    document.querySelectorAll('.dashboard-card, .week-grid').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });
});

// Mobile menu handler
function toggleMobileMenu() {
    const sidebar = document.querySelector('.sidebar-custom');
    if (sidebar) {
        sidebar.classList.toggle('mobile-show');
    }
}
</script>

<?php require_once __DIR__ . '/../views/footer.php'; ?>