<?php
// public/admin/tasks.php
$pageTitle = "Task Management System";
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';
require_role('admin');

// Tab selection
$activeTab = $_GET['tab'] ?? 'weekly';

// ========== WEEKLY VIEW LOGIC ==========
$filterWeek = $_GET['week'] ?? date('Y-m-d');
$filterUser = (int)($_GET['user'] ?? 0);

// Calculate Monday→Friday of that week
$ts = strtotime($filterWeek);
$mondayTs = strtotime('monday this week', $ts);
$weekDays = [];
for ($i = 0; $i < 5; $i++) {
    $weekDays[] = date('Y-m-d', $mondayTs + $i * 86400);
}

// Load members (filtered or all)
$userSql = "
  SELECT id, full_name
  FROM users
  WHERE role = 'member'
    AND status = 'active'
";
if ($filterUser) {
    $userSql .= " AND id = " . $filterUser;
}
$userSql .= " ORDER BY full_name";
$memberStmt = $pdo->query($userSql);
$members = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch tasks for weekly view
$weeklyTasks = [];
if ($members) {
    $memberIds   = array_column($members, 'id');
    $dPH = implode(',', array_fill(0, count($weekDays), '?'));
    $uPH = implode(',', array_fill(0, count($memberIds), '?'));
    $sql = "
      SELECT id, user_id, task_date, summary, details, blockers,
             hours, status, created_at, updated_at
      FROM daily_tasks
      WHERE task_date IN ($dPH)
        AND user_id   IN ($uPH)
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([...$weekDays, ...$memberIds]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $weeklyTasks[$r['user_id']][$r['task_date']] = $r;
    }
}

// All members for dropdown
$allMembers = $pdo
  ->query("SELECT id, full_name FROM users WHERE role='member' AND status='active' ORDER BY full_name")
  ->fetchAll(PDO::FETCH_ASSOC);

// ========== ALL TASKS LOGIC ==========
// Filters
$filterDate   = $_GET['date']   ?? '';
$filterUserAll   = $_GET['user_all']   ?? '';
$filterStatus = $_GET['status'] ?? '';

// Pagination
$limit  = max(5, (int)($_GET['limit'] ?? 10));
$page   = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

// WHERE clause for all tasks
$where  = [];
$params = [];

if ($filterDate !== '') {
    $where[] = "dt.task_date = ?";
    $params[] = $filterDate;
}
if ($filterUserAll !== '') {
    $where[] = "dt.user_id = ?";
    $params[] = $filterUserAll;
}
if ($filterStatus !== '') {
    $where[] = "dt.status = ?";
    $params[] = $filterStatus;
}

$whereSQL = $where ? ("WHERE " . implode(" AND ", $where)) : "";

// Count all tasks
$countSql = "SELECT COUNT(*)
             FROM daily_tasks dt
             JOIN users u ON dt.user_id = u.id
             $whereSQL";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalTasks = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalTasks / $limit));

// Data for all tasks
$dataSql = "SELECT dt.id, dt.task_date, dt.summary, dt.details, dt.blockers, dt.hours, dt.status, dt.created_at, dt.updated_at,
                   u.full_name
            FROM daily_tasks dt
            JOIN users u ON dt.user_id = u.id
            $whereSQL
            ORDER BY dt.task_date DESC, dt.created_at DESC
            LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($dataSql);
$stmt->execute($params);
$allTasks = $stmt->fetchAll();

// Users for all tasks dropdown
$userStmt = $pdo->query("SELECT id, full_name FROM users WHERE status = 'active' ORDER BY full_name");
$allUsers = $userStmt->fetchAll();

require_once __DIR__ . '/../../views/header.php';
?>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/animate.css@4.1.1/animate.min.css" rel="stylesheet">

<style>
/* Custom animations for tasks page */
@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translateY(-30px);
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
.tasks-container {
    animation: slideInFromTop 0.6s ease-out;
}

.filter-card {
    animation: fadeInScale 0.5s ease-out;
    transition: all 0.3s ease;
    border: none;
    overflow: hidden;
}

.filter-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 20px rgba(0,0,0,0.1) !important;
}

.tab-content {
    animation: fadeInScale 0.4s ease-out;
}

.table-hover-animate tr {
    transition: all 0.3s ease;
    animation: tableRowAppear 0.5s ease-out;
}

.table-hover-animate tr:hover {
    background-color: rgba(139, 0, 0, 0.05) !important;
    transform: scale(1.01);
}

.status-badge {
    font-size: 0.75rem;
    padding: 0.4em 0.7em;
    transition: all 0.3s ease;
    border: none;
}

.status-badge:hover {
    transform: scale(1.08);
}

.btn-task {
    transition: all 0.3s ease;
    border: none;
    font-weight: 600;
    padding: 0.6rem 1.2rem;
}

.btn-task:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.week-day-header {
    background: linear-gradient(135deg, #343a40, #495057) !important;
    transition: all 0.3s ease;
}

.week-day-header:hover {
    background: linear-gradient(135deg, #495057, #5a6268) !important;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .container {
        padding-left: 8px;
        padding-right: 8px;
    }
    
    .tasks-container {
        margin-top: 1rem !important;
    }
    
    .filter-card .row {
        margin: 0;
    }
    
    .filter-card .col-md-4,
    .filter-card .col-md-3,
    .filter-card .col-md-2 {
        margin-bottom: 1rem;
        padding: 0 8px;
    }
    
    .btn-task {
        width: 100%;
        margin-bottom: 0.5rem;
        padding: 0.8rem 1.5rem;
    }
    
    .btn-group-mobile {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .table-responsive {
        font-size: 0.8rem;
        border: 1px solid #dee2e6;
        margin: 0 -8px;
    }
    
    .table th, .table td {
        padding: 0.4rem;
        white-space: nowrap;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.9rem;
        padding: 0.5rem 0.75rem;
    }
    
    .pagination {
        flex-wrap: wrap;
        justify-content: center;
    }
    
    .page-item {
        margin-bottom: 0.25rem;
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
        padding: 0.7rem;
    }
    
    .badge {
        font-size: 0.65rem;
    }
    
    .modal-dialog {
        margin: 0.5rem;
    }
    
    .nav-tabs {
        flex-direction: column;
    }
    
    .nav-tabs .nav-item {
        width: 100%;
        text-align: center;
    }
}

/* Enhanced table styles */
.weekly-table th {
    position: sticky;
    top: 0;
    z-index: 10;
}

.task-cell {
    min-height: 80px;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.task-cell:hover {
    border-left-color: #8B0000;
    background-color: #f8f9fa !important;
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

/* Loading animations */
.loading-pulse {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% { opacity: 0.6; }
    50% { opacity: 1; }
    100% { opacity: 0.6; }
}

/* Status colors */
.bg-submitted { background-color: #198754 !important; }
.bg-edited { background-color: #ffc107 !important; color: #000 !important; }
.bg-locked { background-color: #dc3545 !important; }
.bg-pending { background-color: #6c757d !important; }

/* Tab animations */
.tab-pane {
    animation: fadeInScale 0.4s ease-out;
}

.nav-tabs .nav-link {
    transition: all 0.3s ease;
    border: none;
    margin-bottom: 0;
}

.nav-tabs .nav-link:hover {
    transform: translateY(-2px);
    border-bottom: 3px solid #8B0000;
}

.nav-tabs .nav-link.active {
    border-bottom: 3px solid #8B0000;
    font-weight: bold;
}

/* Modal enhancements */
.modal-content {
    border: none;
    border-radius: 12px;
    overflow: hidden;
}

.modal-header {
    background: linear-gradient(135deg, #000000, #8B0000) !important;
}

/* Responsive text */
.responsive-text {
    font-size: clamp(0.8rem, 2vw, 1rem);
}

/* Export button glow */
.btn-export {
    animation: pulseGlow 2s infinite;
}

@keyframes pulseGlow {
    0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
    70% { box-shadow: 0 0 0 8px rgba(40, 167, 69, 0); }
    100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}
</style>

<div class="container mt-3 mt-md-4 tasks-container">
    <!-- Header Section -->
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4 animate__animated animate__fadeIn">
        <div class="mb-2 mb-md-0">
            <h3 class="fw-bold mb-1">
                <i class="bi bi-list-check text-danger me-2"></i> Task Management System
            </h3>
            <p class="text-muted mb-0 responsive-text">
                <i class="bi bi-info-circle me-1"></i> Manage and monitor daily task submissions
            </p>
        </div>
        <a href="/daily-task-system/public/dashboard.php" class="btn btn-secondary btn-task">
            <i class="bi bi-arrow-left-circle-fill me-1"></i> 
            <span class="d-none d-sm-inline">Back to Dashboard</span>
            <span class="d-sm-none">Dashboard</span>
        </a>
    </div>

    <!-- Tabs Navigation -->
    <ul class="nav nav-tabs mb-4 animate__animated animate__fadeIn animate__delay-1s" id="tasksTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'weekly' ? 'active' : '' ?>" id="weekly-tab" data-bs-toggle="tab" 
                    data-bs-target="#weekly" type="button" role="tab" aria-controls="weekly" aria-selected="true">
                <i class="bi bi-calendar-week me-1 d-none d-sm-inline"></i>
                <span class="d-none d-md-inline">Weekly View</span>
                <span class="d-md-none">Weekly</span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= $activeTab === 'all' ? 'active' : '' ?>" id="all-tab" data-bs-toggle="tab" 
                    data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="false">
                <i class="bi bi-list-task me-1 d-none d-sm-inline"></i>
                <span class="d-none d-md-inline">All Tasks</span>
                <span class="d-md-none">All</span>
            </button>
        </li>
    </ul>

    <!-- Tabs Content -->
    <div class="tab-content animate__animated animate__fadeIn animate__delay-1s" id="tasksTabContent">
        
        <!-- Weekly View Tab -->
        <div class="tab-pane fade <?= $activeTab === 'weekly' ? 'show active' : '' ?>" id="weekly" role="tabpanel" aria-labelledby="weekly-tab">
            <!-- Filter Bar for Weekly View -->
            <div class="card shadow-sm border-0 filter-card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <input type="hidden" name="tab" value="weekly">
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-calendar-event-fill text-secondary me-1"></i> Week Of
                            </label>
                            <input
                                type="date"
                                name="week"
                                value="<?= htmlspecialchars($filterWeek) ?>"
                                class="form-control"
                            >
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-person-fill text-secondary me-1"></i> Member
                            </label>
                            <select name="user" class="form-select">
                                <option value="0">-- All Members --</option>
                                <?php foreach ($allMembers as $m): ?>
                                    <option value="<?= $m['id'] ?>"
                                        <?= $filterUser === (int)$m['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <div class="d-flex flex-column flex-md-row gap-2">
                                <button type="submit" class="btn btn-primary btn-task flex-fill">
                                    <i class="bi bi-funnel-fill me-1"></i> Apply Filters
                                </button>
                                <a
                                    href="/daily-task-system/lib/export.php?type=weekly&week=<?= urlencode($filterWeek) ?>&user=<?= $filterUser ?>"
                                    class="btn btn-success btn-task flex-fill btn-export"
                                >
                                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> 
                                    <span class="d-none d-sm-inline">Export CSV</span>
                                    <span class="d-sm-none">Export</span>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <?php if (empty($members)): ?>
                <div class="alert alert-info animate__animated animate__fadeIn">
                    <i class="bi bi-info-circle-fill me-2"></i>No active members found.
                </div>
            <?php else: ?>
                <div class="table-responsive shadow-sm rounded-3">
                    <table class="table table-bordered table-hover-animate m-0 weekly-table">
                        <thead class="table-dark">
                            <tr>
                                <th class="fw-semibold">Member</th>
                                <?php foreach ($weekDays as $day): ?>
                                    <th class="text-center week-day-header">
                                        <div class="d-none d-md-block">
                                            <?= date('D<br>j M', strtotime($day)) ?>
                                            <br>
                                            <a
                                                href="/daily-task-system/public/admin/lock.php?date=<?= $day ?>"
                                                class="text-white small"
                                                onclick="return confirm('Lock tasks for <?= $day ?>?');"
                                            >
                                                <i class="bi bi-lock-fill"></i>
                                            </a>
                                        </div>
                                        <div class="d-md-none small">
                                            <?= date('D j', strtotime($day)) ?>
                                            <br>
                                            <a
                                                href="/daily-task-system/public/admin/lock.php?date=<?= $day ?>"
                                                class="text-white"
                                                onclick="return confirm('Lock tasks for <?= $day ?>?');"
                                            >
                                                <i class="bi bi-lock-fill"></i>
                                            </a>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $index => $m): ?>
                                <tr class="animate__animated animate__fadeIn" style="animation-delay: <?= $index * 0.1 ?>s;">
                                    <td class="fw-medium"><?= htmlspecialchars($m['full_name']) ?></td>
                                    <?php foreach ($weekDays as $day):
                                        $c = $weeklyTasks[$m['id']][$day] ?? null;
                                        if ($c):
                                            // Badge logic
                                            switch ($c['status']) {
                                                case 'locked':
                                                    $cls = 'bg-locked'; $ic = 'lock-fill';   $txt = 'Locked'; break;
                                                case 'edited':
                                                    $cls = 'bg-edited'; $ic = 'pencil-fill'; $txt = 'Edited'; break;
                                                default:
                                                    $cls = 'bg-submitted'; $ic = 'check-circle-fill'; $txt = 'Submitted'; break;
                                            }
                                    ?>
                                        <td class="text-center align-middle task-cell">
                                            <div class="d-flex flex-column flex-md-row align-items-center justify-content-center gap-1">
                                                <span class="badge <?= $cls ?> status-badge">
                                                    <i class="bi bi-<?= $ic ?> me-1"></i>
                                                    <span class="d-none d-lg-inline"><?= $txt ?></span>
                                                    <span class="d-lg-none"><?= substr($txt, 0, 1) ?></span>
                                                </span>
                                                <!-- View button -->
                                                <button
                                                    type="button"
                                                    class="btn btn-sm btn-outline-primary"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#taskViewModal"
                                                    data-id="<?= $c['id'] ?>"
                                                    data-user="<?= htmlspecialchars($m['full_name']) ?>"
                                                    data-date="<?= $day ?>"
                                                    data-status="<?= htmlspecialchars($c['status']) ?>"
                                                    data-hours="<?= htmlspecialchars($c['hours']) ?>"
                                                    data-created="<?= htmlspecialchars($c['created_at']) ?>"
                                                    data-updated="<?= htmlspecialchars($c['updated_at'] ?? '-') ?>"
                                                    data-summary="<?= htmlspecialchars($c['summary']) ?>"
                                                    data-details="<?= htmlspecialchars($c['details']) ?>"
                                                    data-blockers="<?= htmlspecialchars($c['blockers']) ?>"
                                                >
                                                    <i class="bi bi-eye-fill"></i>
                                                </button>
                                            </div>
                                        </td>
                                    <?php else: ?>
                                        <td class="text-center align-middle task-cell">
                                            <span class="badge bg-pending status-badge">
                                                <i class="bi bi-x-circle-fill me-1"></i> 
                                                <span class="d-none d-lg-inline">Not Submitted</span>
                                                <span class="d-lg-none">None</span>
                                            </span>
                                        </td>
                                    <?php endif; endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- All Tasks Tab -->
        <div class="tab-pane fade <?= $activeTab === 'all' ? 'show active' : '' ?>" id="all" role="tabpanel" aria-labelledby="all-tab">
            <!-- Filters for All Tasks -->
            <div class="card shadow-sm border-0 filter-card mb-4">
                <div class="card-body">
                    <form method="get" class="row g-3 align-items-end">
                        <input type="hidden" name="tab" value="all">
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-calendar-event-fill me-1 text-secondary"></i> Date
                            </label>
                            <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>" class="form-control">
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-person-fill me-1 text-secondary"></i> User
                            </label>
                            <select name="user_all" class="form-select">
                                <option value="">-- All Users --</option>
                                <?php foreach ($allUsers as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= ($filterUserAll == $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['full_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-flag-fill me-1 text-secondary"></i> Status
                            </label>
                            <select name="status" class="form-select">
                                <option value="">-- All Statuses --</option>
                                <option value="submitted" <?= $filterStatus === 'submitted' ? 'selected' : '' ?>>Submitted</option>
                                <option value="edited"    <?= $filterStatus === 'edited'    ? 'selected' : '' ?>>Edited</option>
                                <option value="locked"    <?= $filterStatus === 'locked'    ? 'selected' : '' ?>>Locked</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-list-ol me-1 text-secondary"></i> Per Page
                            </label>
                            <select name="limit" class="form-select">
                                <?php foreach ([10, 20, 50, 100] as $opt): ?>
                                    <option value="<?= $opt ?>" <?= ((int)$opt === (int)$limit) ? 'selected' : '' ?>><?= $opt ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-2">
                            <div class="d-flex flex-column gap-2">
                                <button type="submit" class="btn btn-primary btn-task">
                                    <i class="bi bi-funnel-fill me-1"></i> Filter
                                </button>
                                <?php if ($filterDate): ?>
                                    <a
                                        href="/daily-task-system/public/admin/lock.php?date=<?= urlencode($filterDate) ?>"
                                        class="btn btn-danger btn-task"
                                        onclick="return confirm('Lock all tasks for <?= htmlspecialchars($filterDate) ?>? This prevents further edits.');"
                                    >
                                        <i class="bi bi-lock-fill me-1"></i> Lock Tasks
                                    </a>
                                <?php endif; ?>
                                <a
                                    href="/daily-task-system/lib/export.php?type=all&<?= http_build_query($_GET) ?>"
                                    class="btn btn-success btn-task btn-export"
                                >
                                    <i class="bi bi-file-earmark-spreadsheet-fill me-1"></i> Export
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="alert alert-info animate__animated animate__fadeIn">
                <i class="bi bi-info-circle-fill me-1"></i>
                Showing page <?= (int)$page ?> of <?= (int)$totalPages ?> • Total: <?= (int)$totalTasks ?> tasks
            </div>

            <?php if (empty($allTasks)): ?>
                <div class="alert alert-warning mt-3 animate__animated animate__fadeIn">
                    <i class="bi bi-exclamation-circle-fill me-2"></i>No tasks found for the selected filters.
                </div>
            <?php else: ?>
                <div class="table-responsive mt-3 shadow-sm rounded-3">
                    <table class="table table-striped table-bordered align-middle table-hover-animate m-0">
                        <thead class="table-dark">
                            <tr>
                                <th><i class="bi bi-calendar-event-fill me-1"></i> Date</th>
                                <th><i class="bi bi-person-fill me-1"></i> User</th>
                                <th><i class="bi bi-card-text me-1"></i> Summary</th>
                                <th><i class="bi bi-flag-fill me-1"></i> Status</th>
                                <th><i class="bi bi-clock-fill me-1"></i> Hours</th>
                                <th class="d-none d-lg-table-cell"><i class="bi bi-upload me-1"></i> Submitted</th>
                                <th class="d-none d-md-table-cell"><i class="bi bi-arrow-repeat me-1"></i> Updated</th>
                                <th><i class="bi bi-eye-fill me-1"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($allTasks as $index => $t): ?>
                            <tr class="animate__animated animate__fadeIn" style="animation-delay: <?= $index * 0.05 ?>s;">
                                <td class="fw-medium"><?= htmlspecialchars($t['task_date']) ?></td>
                                <td><?= htmlspecialchars($t['full_name']) ?></td>
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 200px;" 
                                          title="<?= htmlspecialchars($t['summary']) ?>">
                                        <?= htmlspecialchars($t['summary']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($t['status'] === 'locked'): ?>
                                        <span class="badge bg-locked status-badge">
                                            <i class="bi bi-lock-fill me-1"></i> Locked
                                        </span>
                                    <?php elseif ($t['status'] === 'edited'): ?>
                                        <span class="badge bg-edited status-badge">
                                            <i class="bi bi-pencil-fill me-1"></i> Edited
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-submitted status-badge">
                                            <i class="bi bi-check-circle-fill me-1"></i> Submitted
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($t['hours']) ?></td>
                                <td class="d-none d-lg-table-cell"><?= htmlspecialchars($t['created_at']) ?></td>
                                <td class="d-none d-md-table-cell"><?= htmlspecialchars($t['updated_at'] ?? '-') ?></td>
                                <td>
                                    <button
                                        type="button"
                                        class="btn btn-sm btn-outline-primary btn-task"
                                        data-bs-toggle="modal"
                                        data-bs-target="#taskViewModal"
                                        data-id="<?= (int)$t['id'] ?>"
                                        data-date="<?= htmlspecialchars($t['task_date']) ?>"
                                        data-user="<?= htmlspecialchars($t['full_name']) ?>"
                                        data-summary="<?= htmlspecialchars($t['summary']) ?>"
                                        data-details="<?= htmlspecialchars($t['details']) ?>"
                                        data-blockers="<?= htmlspecialchars($t['blockers']) ?>"
                                        data-hours="<?= htmlspecialchars((string)$t['hours']) ?>"
                                        data-status="<?= htmlspecialchars($t['status']) ?>"
                                        data-created="<?= htmlspecialchars($t['created_at']) ?>"
                                        data-updated="<?= htmlspecialchars($t['updated_at'] ?? '-') ?>"
                                    >
                                        <i class="bi bi-eye-fill me-1"></i> 
                                        <span class="d-none d-sm-inline">View</span>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav aria-label="Tasks pagination" class="mt-4">
                    <ul class="pagination justify-content-center flex-wrap">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                            <?php
                                $q = $_GET;
                                $q['page'] = $p;
                            ?>
                            <li class="page-item <?= ($p === $page) ? 'active' : '' ?>">
                                <a class="page-link" href="?<?= htmlspecialchars(http_build_query($q)) ?>">
                                    <?= $p ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Task View Modal -->
<div class="modal fade" id="taskViewModal" tabindex="-1" aria-labelledby="taskViewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content animate__animated animate__fadeIn">
            <div class="modal-header" style="background: linear-gradient(135deg,#000,#8B0000);">
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
                            <i class="bi bi-person-fill text-success me-2"></i>
                            <strong class="me-2">User:</strong> 
                            <span id="vUser" class="fw-medium"></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-flag-fill text-warning me-2"></i>
                            <strong class="me-2">Status:</strong> 
                            <span id="vStatus"></span>
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-clock-fill text-info me-2"></i>
                            <strong class="me-2">Hours:</strong> 
                            <span id="vHours" class="fw-medium"></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-upload text-secondary me-2"></i>
                            <strong class="me-2">Submitted:</strong> 
                            <span id="vCreated" class="small"></span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-arrow-repeat text-secondary me-2"></i>
                            <strong class="me-2">Updated:</strong> 
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
                <a id="vOpenPage" href="#" class="btn btn-outline-secondary" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Open in new page
                </a>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Enhanced modal functionality with animations
const taskModal = document.getElementById('taskViewModal');
taskModal.addEventListener('show.bs.modal', event => {
    const button = event.relatedTarget;

    const id       = button.getAttribute('data-id');
    const date     = button.getAttribute('data-date') || '';
    const user     = button.getAttribute('data-user') || '';
    const summary  = button.getAttribute('data-summary') || '';
    const details  = button.getAttribute('data-details') || '';
    const blockers = button.getAttribute('data-blockers') || '';
    const hours    = button.getAttribute('data-hours') || '';
    const status   = button.getAttribute('data-status') || '';
    const created  = button.getAttribute('data-created') || '';
    const updated  = button.getAttribute('data-updated') || '';

    // Status badge with better styling
    let statusBadge = '';
    if (status === 'locked') {
        statusBadge = '<span class="badge bg-locked status-badge"><i class="bi bi-lock-fill me-1"></i> Locked</span>';
    } else if (status === 'edited') {
        statusBadge = '<span class="badge bg-edited status-badge"><i class="bi bi-pencil-fill me-1"></i> Edited</span>';
    } else {
        statusBadge = '<span class="badge bg-submitted status-badge"><i class="bi bi-check-circle-fill me-1"></i> Submitted</span>';
    }

    // Fill fields safely
    document.getElementById('vDate').textContent = date;
    document.getElementById('vUser').textContent = user;
    document.getElementById('vStatus').innerHTML = statusBadge;
    document.getElementById('vHours').textContent = hours;
    document.getElementById('vCreated').textContent = created;
    document.getElementById('vUpdated').textContent = updated;

    document.getElementById('vSummary').textContent = summary;
    document.getElementById('vDetails').textContent = details || 'No details provided';
    document.getElementById('vBlockers').textContent = blockers || 'No blockers reported';

    // Link to a dedicated view page
    const openLink = document.getElementById('vOpenPage');
    openLink.href = '/daily-task-system/public/admin/task_view.php?id=' + encodeURIComponent(id);
});

// Add loading states and animations
document.addEventListener('DOMContentLoaded', function() {
    // Add hover effects to table rows
    const tableRows = document.querySelectorAll('.table-hover-animate tr');
    tableRows.forEach(row => {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
            this.style.transition = 'all 0.3s ease';
        });
        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });

    // Add loading animation to filter buttons
    const filterForms = document.querySelectorAll('form');
    filterForms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn) {
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Loading...';
                submitBtn.disabled = true;
                
                setTimeout(() => {
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                }, 2000);
            }
        });
    });

    // Tab switching animation
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Add animation to the active tab content
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) {
                target.classList.add('animate__animated', 'animate__fadeIn');
            }
        });
    });

    // Auto-remove animation classes after animation completes
    const animatedElements = document.querySelectorAll('.animate__animated');
    animatedElements.forEach(el => {
        el.addEventListener('animationend', function() {
            this.classList.remove('animate__animated', 'animate__fadeIn');
        });
    });
});

// Mobile filter toggle for advanced filters
function toggleMobileFilters() {
    const filters = document.querySelector('.filter-card .row');
    if (filters) {
        filters.classList.toggle('mobile-expanded');
    }
}
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>