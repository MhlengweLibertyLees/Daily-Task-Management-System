<?php
$pageTitle = "Edit Task";
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
$taskId = (int)($_GET['id'] ?? 0);

if ($taskId <= 0) {
    header('Location: /daily-task-system/public/tasks/my.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM daily_tasks WHERE id = ? AND user_id = ?");
$stmt->execute([$taskId, $userId]);
$task = $stmt->fetch();

if (!$task) {
    exit('Task not found.');
}
if ($task['status'] === 'locked') {
    exit('This task is locked and cannot be edited.');
}

$error = '';
$success = '';

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
                $upd = $pdo->prepare("UPDATE daily_tasks
                                      SET summary = ?, details = ?, blockers = ?, hours = ?, status = 'edited'
                                      WHERE id = ? AND user_id = ?");
                $upd->execute([$summary, $details, $blockers, $hours, $taskId, $userId]);
                $success = 'Task updated successfully!';

                // Refresh data
                $stmt = $pdo->prepare("SELECT * FROM daily_tasks WHERE id = ? AND user_id = ?");
                $stmt->execute([$taskId, $userId]);
                $task = $stmt->fetch();
            }
        }
    }
}

require_once __DIR__ . '/../../views/header.php';
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<div class="container mt-4">
  <h3 class="mb-3">
    <i class="bi bi-pencil-square text-danger me-2"></i>
    Edit Task for <?= htmlspecialchars($task['task_date']) ?>
  </h3>
  <hr>

  <?php if ($error): ?>
    <div class="alert alert-danger">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
    </div>
  <?php elseif ($success): ?>
    <div class="alert alert-success">
      <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
    </div>
  <?php endif; ?>

  <form method="post" class="row g-3" onsubmit="return validateEditForm()">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

    <div class="col-12">
      <label class="form-label"><i class="bi bi-card-text me-1 text-secondary"></i> Summary</label>
      <input type="text" name="summary" class="form-control" value="<?= htmlspecialchars($task['summary']) ?>" required>
    </div>

    <div class="col-12">
      <label class="form-label"><i class="bi bi-journal-text me-1 text-secondary"></i> Details</label>
      <textarea name="details" class="form-control" rows="4" required><?= htmlspecialchars($task['details']) ?></textarea>
    </div>

    <div class="col-12">
      <label class="form-label"><i class="bi bi-exclamation-octagon-fill me-1 text-secondary"></i> Blockers</label>
      <textarea name="blockers" class="form-control" rows="2"><?= htmlspecialchars($task['blockers']) ?></textarea>
    </div>

    <div class="col-md-4">
      <label class="form-label"><i class="bi bi-clock-fill me-1 text-secondary"></i> Hours Worked</label>
      <input type="number" step="0.25" min="0" max="24" name="hours" class="form-control" value="<?= htmlspecialchars($task['hours']) ?>">
    </div>

    <div class="col-12">
      <button type="submit" class="btn btn-success">
        <i class="bi bi-save-fill me-1"></i> Update Task
      </button>
      <a href="/daily-task-system/public/tasks/my.php" class="btn btn-secondary">
        <i class="bi bi-arrow-left-circle-fill me-1"></i> Back
      </a>
    </div>
  </form>
</div>

<script>
function validateEditForm() {
  const summary = document.querySelector('[name="summary"]').value.trim();
  const details = document.querySelector('[name="details"]').value.trim();
  if (!summary || !details) {
    alert('Summary and details are required.');
    return false;
  }
  return true;
}
</script>

<?php require_once __DIR__ . '/../../views/footer.php'; ?>
