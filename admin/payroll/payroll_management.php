<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

include_once("../../config/db.php");
/** @var mysqli $conn */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function payrollEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$monthInput = $_POST['month'] ?? $_GET['month'] ?? date('Y-m');
$month = is_string($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)
    ? DateTimeImmutable::createFromFormat('!Y-m', $monthInput)
    : false;
if (!$month || $month->format('Y-m') !== $monthInput) {
    $month = new DateTimeImmutable(date('Y-m-01'));
}
$monthValue = $month->format('Y-m-01');
$monthEnd = $month->modify('last day of this month')->format('Y-m-d');
$errorMessage = '';
$notice = $_SESSION['payroll_notice'] ?? '';
unset($_SESSION['payroll_notice']);

if (empty($_SESSION['payroll_csrf_token'])) {
    $_SESSION['payroll_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    if (!is_string($csrfToken) || !hash_equals($_SESSION['payroll_csrf_token'], $csrfToken)) {
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } elseif ($action === 'mark_paid') {
        $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$employeeId || $employeeId < 1) {
            $errorMessage = 'Select a valid employee.';
        } else {
            $paidStmt = $conn->prepare(
                "INSERT INTO payroll (employee_id, payroll_month, salary, status, paid_at)
                 SELECT employee_id, ?, basic_salary, 'paid', NOW()
                 FROM employees
                 WHERE employee_id = ? AND role IN ('employee', 'hr') AND joining_date <= ?
                 ON DUPLICATE KEY UPDATE
                    status = 'paid',
                    paid_at = COALESCE(paid_at, NOW())"
            );
            $paidStmt->bind_param("sis", $monthValue, $employeeId, $monthEnd);
            $paidStmt->execute();
            if ($paidStmt->affected_rows < 1) {
                $errorMessage = 'This employee is not in the selected month or is already paid.';
            } else {
                $_SESSION['payroll_notice'] = 'Salary marked as paid.';
                header("Location: " . $_SERVER['PHP_SELF'] . '?month=' . urlencode($month->format('Y-m')));
                exit;
            }
        }
    } else {
        $errorMessage = 'Invalid payroll action.';
    }
}

$listStmt = $conn->prepare(
    "SELECT e.employee_id, e.name AS employee_name, e.department, e.designation,
            COALESCE(p.salary, e.basic_salary) AS salary,
            COALESCE(p.status, 'unpaid') AS status, p.paid_at
     FROM employees e
     LEFT JOIN payroll p
       ON p.employee_id = e.employee_id AND p.payroll_month = ?
     WHERE e.role IN ('employee', 'hr') AND e.joining_date <= ?
     ORDER BY e.name"
);
$listStmt->bind_param("ss", $monthValue, $monthEnd);
$listStmt->execute();
$payrollRows = $listStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$summary = ['total' => 0, 'paid' => 0, 'unpaid' => 0, 'amount' => 0];
foreach ($payrollRows as $row) {
    $summary['total']++;
    $summary[$row['status'] === 'paid' ? 'paid' : 'unpaid']++;
    $summary['amount'] += (float) $row['salary'];
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Payroll | PeopleDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin.css">
</head>
<body>
    <div class="app-shell">
        <?php include_once('../includes/sidebar.php'); ?>
        <main class="main-content">
            <?php $pageTitle = 'Payroll'; include_once('../includes/topbar.php'); ?>
            <div class="dashboard-content">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Payroll</h1>
                        <p class="text-secondary mb-0">Monthly salary records from employee basic salary.</p>
                    </div>
                    <form method="get" class="d-flex align-items-end gap-2">
                        <div>
                            <label class="form-label small fw-semibold mb-1" for="payrollMonth">Month</label>
                            <input class="form-control" type="month" id="payrollMonth" name="month" value="<?php echo payrollEscape($month->format('Y-m')); ?>">
                        </div>
                        <button class="btn btn-outline-secondary" type="submit">Show</button>
                    </form>
                </div>

                <?php if ($errorMessage !== '') { ?>
                    <div class="alert alert-danger" role="alert"><?php echo payrollEscape($errorMessage); ?></div>
                <?php } ?>
                <?php if ($notice !== '') { ?>
                    <div class="alert alert-success" role="status"><?php echo payrollEscape($notice); ?></div>
                <?php } ?>

                <section class="row g-3 mb-4" aria-label="Monthly payroll summary">
                    <div class="col-12 col-sm-4"><article class="card stat-card h-100"><span class="stat-label">Employees</span><div class="stat-value-row"><strong><?php echo $summary['total']; ?></strong></div><span class="stat-footnote">In this month's payroll</span></article></div>
                    <div class="col-12 col-sm-4"><article class="card stat-card h-100"><span class="stat-label">Paid</span><div class="stat-value-row"><strong><?php echo $summary['paid']; ?></strong></div><span class="stat-footnote">Salary records paid</span></article></div>
                    <div class="col-12 col-sm-4"><article class="card stat-card h-100"><span class="stat-label">Total salary</span><div class="stat-value-row"><strong>Tk <?php echo number_format($summary['amount'], 2); ?></strong></div><span class="stat-footnote"><?php echo $summary['unpaid']; ?> unpaid</span></article></div>
                </section>

                <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 px-4 py-3">
                        <div>
                            <h2 class="h5 fw-bold mb-1"><?php echo payrollEscape($month->format('F Y')); ?> salaries</h2>
                            <p class="small text-secondary mb-0">All employees and their current basic salaries are shown below.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th class="ps-4 py-3 text-secondary fw-semibold small">Employee</th>
                                <th class="text-secondary fw-semibold small">Department</th>
                                <th class="text-secondary fw-semibold small">Salary</th>
                                <th class="text-secondary fw-semibold small">Status</th>
                                <th class="text-end pe-4 text-secondary fw-semibold small">Action</th>
                            </tr></thead>
                            <tbody>
                                <?php if ($payrollRows) { foreach ($payrollRows as $row) { ?>
                                    <tr>
                                        <td class="ps-4"><strong><?php echo payrollEscape($row['employee_name']); ?></strong><small class="d-block text-secondary"><?php echo payrollEscape($row['designation']); ?></small></td>
                                        <td><?php echo payrollEscape($row['department']); ?></td>
                                        <td class="fw-semibold text-nowrap">Tk <?php echo number_format((float) $row['salary'], 2); ?></td>
                                        <td><span class="badge rounded-pill <?php echo $row['status'] === 'paid' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'; ?>"><?php echo payrollEscape(ucfirst($row['status'])); ?></span><?php if ($row['paid_at']) { ?><small class="d-block text-secondary"><?php echo payrollEscape(date('M j, Y', strtotime($row['paid_at']))); ?></small><?php } ?></td>
                                        <td class="text-end pe-4">
                                            <?php if ($row['status'] === 'unpaid') { ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo payrollEscape($_SESSION['payroll_csrf_token']); ?>">
                                                    <input type="hidden" name="month" value="<?php echo payrollEscape($month->format('Y-m')); ?>">
                                                    <input type="hidden" name="action" value="mark_paid">
                                                    <input type="hidden" name="employee_id" value="<?php echo (int) $row['employee_id']; ?>">
                                                    <button class="btn btn-sm btn-outline-success" type="submit">Mark paid</button>
                                                </form>
                                            <?php } else { ?>
                                                <span class="text-secondary small">Complete</span>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="5" class="text-center text-secondary py-5">No employees found for this month.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php include_once('../includes/footer.php'); ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
