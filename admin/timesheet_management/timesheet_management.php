<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

include_once("../../config/db.php");
/** @var mysqli $conn */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$errorMessage = '';
$notice = $_SESSION['admin_timesheet_notice'] ?? '';
unset($_SESSION['admin_timesheet_notice']);

if (empty($_SESSION['admin_timesheet_csrf_token'])) {
    $_SESSION['admin_timesheet_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['admin_timesheet_csrf_token'], $token)) {
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } elseif (in_array($action, ['approve', 'reject', 'delete'], true)) {
        $id = filter_var($_POST['timesheet_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            $errorMessage = 'Select a valid timesheet.';
        } elseif ($action === 'delete') {
            $stmt = $conn->prepare("DELETE FROM timesheets WHERE timesheet_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['admin_timesheet_notice'] = 'Timesheet deleted.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } else {
            $status = $action === 'approve' ? 'Approved' : 'Rejected';
            $remarks = trim($_POST['admin_remarks'] ?? '');
            $remarks = $remarks === '' ? null : $remarks;
            $stmt = $conn->prepare("UPDATE timesheets SET status = ?, admin_remarks = ? WHERE timesheet_id = ?");
            $stmt->bind_param("ssi", $status, $remarks, $id);
            $stmt->execute();
            $_SESSION['admin_timesheet_notice'] = 'Timesheet ' . strtolower($status) . '.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $errorMessage = 'Invalid timesheet action.';
    }
}

$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'approved', 'rejected'], true)) $filter = 'all';
$employeeFilter = filter_var($_GET['employee'] ?? null, FILTER_VALIDATE_INT);
$employeeFilter = $employeeFilter ?: null;

$sql = "SELECT t.*, e.name AS employee_name, e.department, e.designation
        FROM timesheets t JOIN employees e ON e.employee_id = t.employee_id";
$conditions = [];
$types = '';
$params = [];
if ($filter !== 'all') { $conditions[] = "LOWER(t.status) = ?"; $types .= 's'; $params[] = $filter; }
if ($employeeFilter) { $conditions[] = "t.employee_id = ?"; $types .= 'i'; $params[] = $employeeFilter; }
if ($conditions) $sql .= " WHERE " . implode(" AND ", $conditions);
$sql .= " ORDER BY t.work_date DESC, t.timesheet_id DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$timesheets = $stmt->get_result();

$counts = ['total' => 0, 'pending' => 0, 'approved' => 0, 'rejected' => 0];
$countResult = $conn->query("SELECT status, COUNT(*) AS c FROM timesheets GROUP BY status");
while ($row = $countResult->fetch_assoc()) {
    $counts[strtolower($row['status'])] = (int)$row['c'];
    $counts['total'] += (int)$row['c'];
}
$employees = $conn->query("SELECT employee_id, name FROM employees WHERE role IN ('employee','hr') ORDER BY name");
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Timesheet Management | PeopleDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin.css">
</head>
<body>
<div class="app-shell">
    <?php include_once('../includes/sidebar.php'); ?>
    <main class="main-content">
        <?php $pageTitle = 'Timesheet Management'; include_once('../includes/topbar.php'); ?>
        <div class="dashboard-content">
            <div class="mb-4"><h1 class="h2 fw-bold mb-1">Timesheet Management</h1><p class="text-secondary mb-0">Review and approve employee work entries.</p></div>
            <?php if ($errorMessage) { ?><div class="alert alert-danger"><?php echo htmlspecialchars($errorMessage); ?></div><?php } ?>
            <?php if ($notice) { ?><div class="alert alert-success"><?php echo htmlspecialchars($notice); ?></div><?php } ?>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="text-secondary small">Total</div><div class="h4 fw-bold mb-0"><?php echo $counts['total']; ?></div></div></div></div>
                <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="text-secondary small">Pending</div><div class="h4 fw-bold mb-0"><?php echo $counts['pending']; ?></div></div></div></div>
                <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="text-secondary small">Approved</div><div class="h4 fw-bold mb-0"><?php echo $counts['approved']; ?></div></div></div></div>
                <div class="col-6 col-lg-3"><div class="card border-0 shadow-sm rounded-4"><div class="card-body"><div class="text-secondary small">Rejected</div><div class="h4 fw-bold mb-0"><?php echo $counts['rejected']; ?></div></div></div></div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white border-bottom px-4 py-3">
                    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                        <form class="d-flex align-items-center gap-2" method="get">
                            <select class="form-select form-select-sm" name="employee" onchange="this.form.submit()">
                                <option value="">All employees</option>
                                <?php while ($employee = $employees->fetch_assoc()) { ?><option value="<?php echo $employee['employee_id']; ?>" <?php echo $employeeFilter === (int)$employee['employee_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($employee['name']); ?></option><?php } ?>
                            </select>
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter); ?>">
                        </form>
                        <ul class="nav nav-pills small gap-1">
                            <?php foreach (['all'=>'All','pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected'] as $key=>$label) { ?>
                                <li class="nav-item"><a class="nav-link py-1 px-3 <?php echo $filter === $key ? 'active' : 'text-secondary'; ?>" href="?status=<?php echo $key; ?><?php echo $employeeFilter ? '&employee='.$employeeFilter : ''; ?>"><?php echo $label; ?></a></li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th class="ps-4">Entry</th><th>Employee</th><th>Date</th><th>Project</th><th>Task</th><th>Hours</th><th>Status</th><th class="pe-4 text-end">Actions</th></tr></thead>
                        <tbody>
                        <?php if ($timesheets->num_rows) { while ($row = $timesheets->fetch_assoc()) { ?>
                            <tr>
                                <td class="ps-4 fw-bold">#TS-<?php echo str_pad($row['timesheet_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><div class="fw-semibold"><?php echo htmlspecialchars($row['employee_name']); ?></div><div class="small text-secondary"><?php echo htmlspecialchars($row['designation']); ?></div></td>
                                <td class="text-nowrap"><?php echo date('M d, Y', strtotime($row['work_date'])); ?></td>
                                <td><?php echo htmlspecialchars($row['project_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['task_description']); ?></td>
                                <td class="fw-semibold"><?php echo number_format((float)$row['hours_worked'], 1); ?> h</td>
                                <td><span class="badge rounded-pill <?php echo $row['status'] === 'Approved' ? 'bg-success-subtle text-success-emphasis' : ($row['status'] === 'Rejected' ? 'bg-danger-subtle text-danger-emphasis' : 'bg-warning-subtle text-warning-emphasis'); ?>"><?php echo $row['status']; ?></span></td>
                                <td class="pe-4 text-end">
                                    <button class="btn btn-sm btn-outline-primary review-timesheet" type="button" data-id="<?php echo $row['timesheet_id']; ?>" data-remarks="<?php echo htmlspecialchars($row['admin_remarks'] ?: ''); ?>">Review</button>
                                    <form class="d-inline" method="post" onsubmit="return confirm('Delete this timesheet?')"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_timesheet_csrf_token']); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="timesheet_id" value="<?php echo $row['timesheet_id']; ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Delete</button></form>
                                </td>
                            </tr>
                        <?php } } else { ?><tr><td colspan="8" class="text-center text-secondary py-5">No timesheets found.</td></tr><?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php include_once("../includes/footer.php"); ?>
    </main>
</div>
<div class="modal fade" id="reviewModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <form method="post">
        <div class="modal-header"><h2 class="modal-title fs-5">Review timesheet</h2><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['admin_timesheet_csrf_token']); ?>">
            <input type="hidden" name="timesheet_id" id="reviewTimesheetId">
            <label class="form-label" for="reviewRemarks">Remarks</label><textarea class="form-control" id="reviewRemarks" name="admin_remarks" maxlength="250" rows="3"></textarea>
        </div>
        <div class="modal-footer"><button class="btn btn-danger" name="action" value="reject" type="submit">Reject</button><button class="btn btn-success" name="action" value="approve" type="submit">Approve</button></div>
    </form>
</div></div></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.querySelectorAll('.review-timesheet').forEach(button => button.addEventListener('click', () => {
    document.getElementById('reviewTimesheetId').value = button.dataset.id;
    document.getElementById('reviewRemarks').value = button.dataset.remarks;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('reviewModal')).show();
}));
</script>
</body>
</html>
