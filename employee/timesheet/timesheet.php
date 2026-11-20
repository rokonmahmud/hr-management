<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'employee' || empty($_SESSION['employee_id'])) {
    header("Location: ../../index.php");
    exit;
}

include_once("../../config/db.php");
/** @var mysqli $conn */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$employeeId = (int) $_SESSION['employee_id'];
$errorMessage = '';
$notice = $_SESSION['timesheet_notice'] ?? '';
unset($_SESSION['timesheet_notice']);

if (empty($_SESSION['timesheet_csrf_token'])) {
    $_SESSION['timesheet_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['timesheet_csrf_token'], $token)) {
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $workDate = $_POST['work_date'] ?? '';
        $project = trim($_POST['project_name'] ?? '');
        $task = trim($_POST['task_description'] ?? '');
        $hours = filter_var($_POST['hours_worked'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($action === 'delete') {
            $timesheetId = filter_var($_POST['timesheet_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$timesheetId) {
                $errorMessage = 'Select a valid timesheet.';
            } else {
                $stmt = $conn->prepare("DELETE FROM timesheets WHERE timesheet_id = ? AND employee_id = ?");
                $stmt->bind_param("ii", $timesheetId, $employeeId);
                $stmt->execute();
                $_SESSION['timesheet_notice'] = 'Timesheet deleted.';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } elseif (in_array($action, ['create', 'update'], true)) {
            $timesheetId = filter_var($_POST['timesheet_id'] ?? null, FILTER_VALIDATE_INT);
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', is_string($workDate) ? $workDate : '');
            if (!$date || $date->format('Y-m-d') !== $workDate || $project === '' || $task === '' ||
                $hours === false || $hours < 0.1 || $hours > 24) {
                $errorMessage = 'Enter a valid date, project, task, and hours (0.1–24).';
            } elseif ($action === 'update' && !$timesheetId) {
                $errorMessage = 'Select a valid timesheet.';
            } else {
                if ($action === 'create') {
                    $stmt = $conn->prepare(
                        "INSERT INTO timesheets (employee_id, work_date, project_name, task_description, hours_worked)
                         VALUES (?, ?, ?, ?, ?)
                         ON DUPLICATE KEY UPDATE project_name = VALUES(project_name),
                                                 task_description = VALUES(task_description),
                                                 hours_worked = VALUES(hours_worked),
                                                 status = 'Pending',
                                                 admin_remarks = NULL"
                    );
                    $stmt->bind_param("isssd", $employeeId, $workDate, $project, $task, $hours);
                } else {
                    $stmt = $conn->prepare(
                        "UPDATE timesheets
                         SET work_date = ?, project_name = ?, task_description = ?, hours_worked = ?,
                             status = 'Pending', admin_remarks = NULL
                         WHERE timesheet_id = ? AND employee_id = ?"
                    );
                    $stmt->bind_param("sssdii", $workDate, $project, $task, $hours, $timesheetId, $employeeId);
                }
                $stmt->execute();
                $_SESSION['timesheet_notice'] = $action === 'create' ? 'Timesheet submitted.' : 'Timesheet updated.';
                header("Location: " . $_SERVER['PHP_SELF']);
                exit;
            }
        } else {
            $errorMessage = 'Invalid timesheet action.';
        }
    }
}

$monthInput = $_GET['month'] ?? date('Y-m');
$month = DateTimeImmutable::createFromFormat('!Y-m', $monthInput);
if (!$month || $month->format('Y-m') !== $monthInput) {
    $month = new DateTimeImmutable(date('Y-m-01'));
}
$monthStart = $month->format('Y-m-01');
$monthEnd = $month->modify('last day of this month')->format('Y-m-d');

$stmt = $conn->prepare(
    "SELECT * FROM timesheets
     WHERE employee_id = ? AND work_date BETWEEN ? AND ?
     ORDER BY work_date DESC"
);
$stmt->bind_param("iss", $employeeId, $monthStart, $monthEnd);
$stmt->execute();
$timesheets = $stmt->get_result();

$summaryStmt = $conn->prepare(
    "SELECT COUNT(*) AS total,
            SUM(hours_worked) AS hours,
            SUM(status = 'Approved') AS approved,
            SUM(status = 'Pending') AS pending
     FROM timesheets WHERE employee_id = ? AND work_date BETWEEN ? AND ?"
);
$summaryStmt->bind_param("iss", $employeeId, $monthStart, $monthEnd);
$summaryStmt->execute();
$summary = $summaryStmt->get_result()->fetch_assoc();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Timesheet | PeopleDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin.css">
</head>
<body>
<div class="app-shell">
    <?php include_once('../includes/sidebar.php'); ?>
    <main class="main-content">
        <?php $pageTitle = 'My Timesheet'; include_once('../includes/topbar.php'); ?>
        <div class="dashboard-content">
            <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                <div><h1 class="h2 fw-bold mb-1">My Timesheet</h1><p class="text-secondary mb-0">Submit and update your daily work.</p></div>
                <button class="btn btn-success px-4" type="button" data-bs-toggle="modal" data-bs-target="#timesheetModal">+ Add timesheet</button>
            </div>
            <?php if ($errorMessage) { ?><div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div><?php } ?>
            <?php if ($notice) { ?><div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div><?php } ?>

            <div class="row g-3 mb-4">
                <div class="col-md-4"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="text-secondary small">Hours this month</div><div class="h4 fw-bold mb-0"><?php echo number_format((float)$summary['hours'], 1); ?> h</div></div></div></div>
                <div class="col-md-4"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="text-secondary small">Approved entries</div><div class="h4 fw-bold mb-0"><?php echo (int)$summary['approved']; ?></div></div></div></div>
                <div class="col-md-4"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="text-secondary small">Pending entries</div><div class="h4 fw-bold mb-0"><?php echo (int)$summary['pending']; ?></div></div></div></div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-4 py-3">
                    <h2 class="h6 fw-bold mb-0">Timesheet entries</h2>
                    <form class="d-flex" method="get"><input class="form-control form-control-sm" type="month" name="month" value="<?php echo $month->format('Y-m'); ?>" onchange="this.form.submit()"></form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th class="ps-4">Date</th><th>Project</th><th>Task</th><th>Hours</th><th>Status</th><th>Remarks</th><th class="pe-4 text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php if ($timesheets->num_rows) { while ($row = $timesheets->fetch_assoc()) { ?>
                            <tr>
                                <td class="ps-4 text-nowrap"><?php echo date('M d, Y', strtotime($row['work_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['task_description']); ?></td>
                                <td class="fw-semibold"><?php echo number_format((float)$row['hours_worked'], 1); ?> h</td>
                                <td><span class="badge rounded-pill <?php echo $row['status'] === 'Approved' ? 'bg-success-subtle text-success-emphasis' : ($row['status'] === 'Rejected' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-warning-subtle text-warning-emphasis'); ?>"><?php echo $row['status']; ?></span></td>
                                <td class="text-secondary"><?php echo htmlspecialchars($row['admin_remarks'] ?: '—'); ?></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-outline-primary edit-timesheet" type="button"
                                        data-id="<?php echo $row['timesheet_id']; ?>" data-date="<?php echo $row['work_date']; ?>"
                                        data-project="<?php echo htmlspecialchars($row['project_name']); ?>"
                                        data-task="<?php echo htmlspecialchars($row['task_description']); ?>" data-hours="<?php echo $row['hours_worked']; ?>">Edit</button>
                                    <form class="d-inline" method="post" onsubmit="return confirm('Delete this timesheet?')">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['timesheet_csrf_token']); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="timesheet_id" value="<?php echo $row['timesheet_id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } } else { ?><tr><td colspan="7" class="text-center text-secondary py-5">No timesheets found for this month.</td></tr><?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include_once("../includes/footer.php"); ?>
    </main>
</div>

<div class="modal fade" id="timesheetModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post" class="needs-validation">
        <div class="modal-header"><h2 class="modal-title fs-5" id="timesheetModalTitle">Add timesheet</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['timesheet_csrf_token']); ?>">
            <input type="hidden" name="action" id="timesheetAction" value="create"><input type="hidden" name="timesheet_id" id="timesheetId">
            <div class="mb-3"><label class="form-label" for="workDate">Work date</label><input class="form-control" type="date" id="workDate" name="work_date" value="<?php echo date('Y-m-d'); ?>" required></div>
            <div class="mb-3"><label class="form-label" for="projectName">Project</label><input class="form-control" id="projectName" name="project_name" maxlength="150" required></div>
            <div class="mb-3"><label class="form-label" for="taskDescription">Task description</label><textarea class="form-control" id="taskDescription" name="task_description" maxlength="500" rows="3" required></textarea></div>
            <div class="mb-3"><label class="form-label" for="hoursWorked">Hours worked</label><input class="form-control" type="number" id="hoursWorked" name="hours_worked" min="0.1" max="24" step="0.1" required></div>
        </div>
        <div class="modal-footer"><button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancel</button><button class="btn btn-success" type="submit">Save timesheet</button></div>
    </form>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.edit-timesheet').forEach(button => button.addEventListener('click', () => {
    document.getElementById('timesheetModalTitle').textContent = 'Edit timesheet';
    document.getElementById('timesheetAction').value = 'update';
    document.getElementById('timesheetId').value = button.dataset.id;
    document.getElementById('workDate').value = button.dataset.date;
    document.getElementById('projectName').value = button.dataset.project;
    document.getElementById('taskDescription').value = button.dataset.task;
    document.getElementById('hoursWorked').value = button.dataset.hours;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('timesheetModal')).show();
}));
document.getElementById('timesheetModal').addEventListener('hidden.bs.modal', () => {
    document.getElementById('timesheetModalTitle').textContent = 'Add timesheet';
    document.getElementById('timesheetAction').value = 'create';
    document.getElementById('timesheetId').value = '';
});
</script>
</body>
</html>
@@
                    $stmt = $conn->prepare(
