<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/permissions.php';

require_role('admin');

// Get filters from query string
$filterDate   = $_GET['date']   ?? '';
$filterUser   = $_GET['user']   ?? '';
$filterStatus = $_GET['status'] ?? '';

$where = [];
$params = [];

if ($filterDate !== '') {
    $where[] = "task_date = ?";
    $params[] = $filterDate;
}
if ($filterUser !== '') {
    $where[] = "user_id = ?";
    $params[] = $filterUser;
}
if ($filterStatus !== '') {
    $where[] = "status = ?";
    $params[] = $filterStatus;
}

$whereSQL = $where ? "WHERE " . implode(" AND ", $where) : "";

// Fetch tasks
$sql = "SELECT dt.task_date, u.full_name, dt.summary, dt.details, dt.blockers, dt.hours, dt.status, dt.created_at, dt.updated_at
        FROM daily_tasks dt
        JOIN users u ON dt.user_id = u.id
        $whereSQL
        ORDER BY dt.task_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Log the export
$logStmt = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, target, meta) VALUES (?, ?, ?, ?)");
$logStmt->execute([
    current_user_id(),
    'export_csv',
    'daily_tasks',
    json_encode(['filters' => $_GET])
]);

// Output CSV headers
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="tasks_export_' . date('Ymd_His') . '.csv"');

// Open output stream
$output = fopen('php://output', 'w');

// CSV header row
fputcsv($output, ['Date', 'User', 'Summary', 'Details', 'Blockers', 'Hours', 'Status', 'Created At', 'Updated At']);

// Data rows
foreach ($tasks as $task) {
    fputcsv($output, $task);
}

fclose($output);
exit;
