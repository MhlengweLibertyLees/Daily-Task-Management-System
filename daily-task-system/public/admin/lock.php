<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../lib/permissions.php';

require_role('admin');

$date = $_GET['date'] ?? null;

if (!$date) {
    exit('No date provided.');
}

// Lock all tasks for that date
$stmt = $pdo->prepare("UPDATE daily_tasks SET status = 'locked' WHERE task_date = ?");
$stmt->execute([$date]);

// Log the action
$logStmt = $pdo->prepare("INSERT INTO audit_logs (actor_user_id, action, target, meta) VALUES (?, ?, ?, ?)");
$logStmt->execute([
    current_user_id(),
    'lock_tasks',
    'daily_tasks',
    json_encode(['date' => $date])
]);

header('Location: /daily-task-system/public/admin/tasks.php?date=' . urlencode($date));
exit;
