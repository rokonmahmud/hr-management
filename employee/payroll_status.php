<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'employee' || empty($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit;
}

include_once("../config/db.php");
/** @var mysqli $conn */

function payrollStatusEscape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

$employeeId = (int) $_SESSION['employee_id'];
$monthInput = $_GET['month'] ?? date('Y-m');
$month = is_string($monthInput) && preg_match('/^\d{4}-\d{2}$/', $monthInput)
    ? DateTimeImmutable::createFromFormat('!Y-m', $monthInput)
    : false;
if (!$month || $month->format('Y-m') !== $monthInput) {
    $month = new DateTimeImmutable(date('Y-m-01'));
}
$monthValue = $month->format('Y-m-01');

$employeeStmt = $conn->prepare(
    "SELECT e.name, e.department, e.designation, e.basic_salary,
            p.salary, p.status, p.paid_at
     FROM employees e
     LEFT JOIN payroll p
       ON p.employee_id = e.employee_id AND p.payroll_month = ?
     WHERE e.employee_id = ? AND e.role = 'employee'
     LIMIT 1"
);
$employeeStmt->bind_param("si", $monthValue, $employeeId);
$employeeStmt->execute();
$employee = $employeeStmt->get_result()->fetch_assoc();

if (!$employee) {
    http_response_code(404);
    exit('Employee payroll record is not available.');
}

$historyStmt = $conn->prepare(
    "SELECT payroll_month, salary, status, paid_at
     FROM payroll
     WHERE employee_id = ?
     ORDER BY payroll_month DESC"
);
$historyStmt->bind_param("i", $employeeId);
$historyStmt->execute();
$paymentHistory = $historyStmt->get_result()->fetch_all(MYSQLI_ASSOC);

$status = $employee['status'] ?? 'unpaid';
$salary = $employee['salary'] !== null ? (float) $employee['salary'] : (float) $employee['basic_salary'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>My Payroll | PeopleDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <div class="app-shell">
        <?php include_once('includes/sidebar.php'); ?>
        <main class="main-content">
            <?php $pageTitle = 'My Payroll'; include_once('includes/topbar.php'); ?>
            <div class="dashboard-content">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">My Payroll</h1>
                        <p class="text-secondary mb-0">Check your salary and payment status.</p>
                    </div>
                    <form method="get" class="d-flex align-items-end gap-2">
                        <div>
                            <label class="form-label small fw-semibold mb-1" for="payrollMonth">Month</label>
                            <input class="form-control" type="month" id="payrollMonth" name="month" value="<?php echo payrollStatusEscape($month->format('Y-m')); ?>">
                        </div>
                        <button class="btn btn-outline-secondary" type="submit">Show</button>
                    </form>
                </div>

                <section class="row g-3 mb-4" aria-label="Selected month payroll status">
                    <div class="col-12 col-md-6">
                        <article class="card stat-card h-100">
                            <span class="stat-label"><?php echo payrollStatusEscape($month->format('F Y')); ?> salary</span>
                            <div class="stat-value-row"><strong>Tk <?php echo number_format($salary, 2); ?></strong></div>
                            <span class="stat-footnote"><?php echo payrollStatusEscape($employee['designation']); ?> · <?php echo payrollStatusEscape($employee['department']); ?></span>
                        </article>
                    </div>
                    <div class="col-12 col-md-6">
                        <article class="card stat-card h-100">
                            <span class="stat-label">Payment status</span>
                            <div class="stat-value-row">
                                <strong><?php echo payrollStatusEscape(ucfirst($status)); ?></strong>
                                <span class="badge rounded-pill <?php echo $status === 'paid' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'; ?>"><?php echo $status === 'paid' ? 'Paid' : 'Unpaid'; ?></span>
                            </div>
                            <span class="stat-footnote"><?php echo $employee['paid_at'] ? 'Paid on ' . payrollStatusEscape(date('M j, Y', strtotime($employee['paid_at']))) : 'Payment has not been recorded for this month.'; ?></span>
                        </article>
                    </div>
                </section>

                <section class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom px-4 py-3">
                        <h2 class="h5 fw-bold mb-1">Payment history</h2>
                        <p class="small text-secondary mb-0">Your recorded monthly payroll payments.</p>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light"><tr>
                                <th class="ps-4 py-3 text-secondary fw-semibold small">Month</th>
                                <th class="text-secondary fw-semibold small">Salary</th>
                                <th class="text-secondary fw-semibold small">Status</th>
                                <th class="text-secondary fw-semibold small">Paid on</th>
                            </tr></thead>
                            <tbody>
                                <?php if ($paymentHistory) { foreach ($paymentHistory as $payment) { ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold"><?php echo payrollStatusEscape(date('F Y', strtotime($payment['payroll_month']))); ?></td>
                                        <td class="text-nowrap">Tk <?php echo number_format((float) $payment['salary'], 2); ?></td>
                                        <td><span class="badge rounded-pill <?php echo $payment['status'] === 'paid' ? 'bg-success-subtle text-success-emphasis' : 'bg-warning-subtle text-warning-emphasis'; ?>"><?php echo payrollStatusEscape(ucfirst($payment['status'])); ?></span></td>
                                        <td><?php echo $payment['paid_at'] ? payrollStatusEscape(date('M j, Y', strtotime($payment['paid_at']))) : '—'; ?></td>
                                    </tr>
                                <?php } } else { ?>
                                    <tr><td colspan="4" class="text-center text-secondary py-5">No payment history yet.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <?php include_once('includes/footer.php'); ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
