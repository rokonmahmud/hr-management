<?php
session_start();
/** @var mysqli $conn */

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once("../config/db.php");
$totalEmployee = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM employees WHERE role IN ('employee','hr')"
)->fetch_assoc()['total'];

$presentToday = (int) $conn->query(
    "SELECT COUNT(DISTINCT employee_id) AS total FROM attendance WHERE attendance_date = CURDATE()"
)->fetch_assoc()['total'];
$presentPercent = $totalEmployee > 0 ? (int) round(($presentToday / $totalEmployee) * 100) : 0;

$onLeaveToday = (int) $conn->query(
    "SELECT COUNT(DISTINCT employee_id) AS total
     FROM leave_requests
     WHERE status = 'Approved' AND CURDATE() BETWEEN start_date AND end_date"
)->fetch_assoc()['total'];

$monthValue = date('Y-m-01');
$payrollStmt = $conn->prepare(
    "SELECT COUNT(*) AS total, COALESCE(SUM(salary), 0) AS amount
     FROM payroll WHERE payroll_month = ?"
);
$payrollStmt->bind_param("s", $monthValue);
$payrollStmt->execute();
$payrollSummary = $payrollStmt->get_result()->fetch_assoc();
$payrollTotal = (int) $payrollSummary['total'];
$payrollAmount = (float) $payrollSummary['amount'];

$pendingLeaveCount = (int) $conn->query(
    "SELECT COUNT(*) AS total FROM leave_requests WHERE status = 'Pending'"
)->fetch_assoc()['total'];
$newEmployees = $conn->query(
    "SELECT name, email, department, designation, joining_date
     FROM employees
     WHERE role IN ('employee','hr')
     ORDER BY joining_date DESC, employee_id DESC
     LIMIT 3"
);
$pendingLeaves = $conn->query(
    "SELECT lr.*, e.name AS employee_name
     FROM leave_requests lr
     JOIN employees e ON e.employee_id = lr.employee_id
     WHERE lr.status = 'Pending'
     ORDER BY lr.leave_id DESC
     LIMIT 3"
);
$departmentRows = $conn->query(
     "SELECT department, COUNT(*) AS total
      FROM employees
      WHERE role IN ('employee','hr')
      GROUP BY department
      ORDER BY total DESC, department"
)->fetch_all(MYSQLI_ASSOC);
$departmentMax = count($departmentRows) > 0 ? (int) $departmentRows[0]['total'] : 1;

$weekStart = date('Y-m-d', strtotime('monday this week'));
$weeklyAttendance = [];
for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
     $date = date('Y-m-d', strtotime($weekStart . ' +' . $dayOffset . ' days'));
     $stmt = $conn->prepare(
         "SELECT COUNT(DISTINCT employee_id) AS present
          FROM attendance WHERE attendance_date = ?"
     );
     $stmt->bind_param("s", $date);
     $stmt->execute();
     $present = (int) $stmt->get_result()->fetch_assoc()['present'];
     $weeklyAttendance[] = [
         'label' => date('D', strtotime($date)),
         'present' => $totalEmployee > 0 ? (int) round(($present / $totalEmployee) * 100) : 0,
     ];
}

?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Admin dashboard | PeopleDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <div class="app-shell">
        <?php 
            include_once('includes/sidebar.php');
        ?>

        <main class="main-content">
            <?php 
                include_once('includes/topbar.php');
            ?> 

            <div class="dashboard-content">
                <section class="welcome-row">
                    <div>
                        <span class="welcome-kicker"><?php echo strtoupper(date('l, F j, Y')); ?></span>
                        <h1>Good morning, <?php echo $_SESSION['name']; ?> <span aria-hidden="true">✦</span></h1>
                        <p>Here’s what’s happening with your team today.</p>
                    </div>
                    <a class="btn btn-primary add-employee-button" href="#employees">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Add employee
                    </a>
                </section>

                <section class="row g-3 stat-grid" aria-label="HR overview">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon people-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.2a3.2 3.2 0 0 1 0 6.1M17 14a5.2 5.2 0 0 1 3.5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Total employees</span>
                            <div class="stat-value-row"><strong><?php echo $totalEmployee ?></strong><span class="trend positive">↗ 8.2%</span></div>
                            <span class="stat-footnote">vs. last month</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon attendance-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Present today</span>
                            <div class="stat-value-row"><strong><?php echo $presentToday; ?></strong><span class="trend positive"><?php echo $presentPercent; ?>%</span></div>
                            <span class="stat-footnote">of your team is at work today</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon leave-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5h7l4.5 4.5v12a1.5 1.5 0 0 1-1.5 1.5H7A2.5 2.5 0 0 1 4.5 19V6A2.5 2.5 0 0 1 7 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 4v5h5M8 13h8M8 16.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">On leave</span>
                            <div class="stat-value-row"><strong><?php echo $onLeaveToday; ?></strong><span class="trend neutral">Today</span></div>
                            <span class="stat-footnote">approved leaves currently active</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon payroll-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9.5h17M8 14h3M16 13v.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Payroll this month</span>
                            <div class="stat-value-row"><strong class="currency-value">৳ <?php echo number_format($payrollAmount, 0); ?></strong><span class="trend neutral"><?php echo date('F'); ?></span></div>
                            <span class="stat-footnote">for <?php echo $payrollTotal; ?> payroll records</span>
                        </article>
                    </div>
                </section>

                <section class="row g-3 dashboard-row">
                    <div class="col-12 col-xl-8" id="attendance">
                        <article class="card dashboard-card attendance-card h-100">
                            <div class="card-heading">
                                <div><h2>Attendance overview</h2><p>A look at your team's attendance this week.</p></div>
                                <button class="btn btn-sm dropdown-button" type="button">This week <span aria-hidden="true">⌄</span></button>
                            </div>
                            <div class="chart-legend">
                                <span><i class="legend-dot present-dot"></i>Present</span>
                                <span><i class="legend-dot absent-dot"></i>Absent</span>
                                <span><i class="legend-dot leave-dot"></i>On leave</span>
                            </div>
                            <div class="attendance-chart" role="img" aria-label="Bar chart showing attendance Monday through Sunday, with the highest attendance on Friday">
                                <div class="chart-grid-lines"><span>50</span><span>40</span><span>30</span><span>20</span><span>10</span></div>
                                <div class="chart-columns">
                                    <?php foreach ($weeklyAttendance as $day) { ?>
                                        <div class="chart-column">
                                            <div class="bar-stack <?php echo in_array($day['label'], ['Sat','Sun']) ? 'weekend-bars' : ''; ?>">
                                                <i class="bar-present" style="height: <?php echo $day['present']; ?>%"></i>
                                            </div>
                                            <span><?php echo $day['label']; ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-xl-4" id="departments">
                        <article class="card dashboard-card department-card h-100">
                            <div class="card-heading">
                                <div><h2>Departments</h2><p>People across your teams.</p></div>
                                <a class="text-action" href="#departments">View all</a>
                            </div>
                            <div class="department-list">
                                <?php if (count($departmentRows) > 0) { ?>
                                    <?php foreach ($departmentRows as $department) {
                                        $departmentName = $department['department'] ?: 'Not assigned';
                                        $departmentSymbol = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $departmentName), 0, 1));
                                        $departmentPercent = (int) round(((int) $department['total'] / $departmentMax) * 100);
                                    ?>
                                        <div class="department-item">
                                            <span class="department-symbol people-symbol"><?php echo htmlspecialchars($departmentSymbol); ?></span>
                                            <span class="department-name"><?php echo htmlspecialchars($departmentName); ?><small>Team members</small></span>
                                            <span class="department-total"><?php echo (int) $department['total']; ?></span>
                                            <span class="department-track"><i class="people-bar" style="width: <?php echo $departmentPercent; ?>%"></i></span>
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p class="text-secondary text-center py-4 mb-0">No departments found.</p>
                                <?php } ?>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="row g-3 dashboard-row">
                    <div class="col-12 col-xl-7" id="employees">
                        <article class="card dashboard-card employee-card h-100">
                            <div class="card-heading">
                                <div><h2>New employees</h2><p>Welcome the newest members of your team.</p></div>
                                <a class="text-action" href="#employees">View all employees <span aria-hidden="true">→</span></a>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle employee-table mb-0">
                                    <thead><tr><th scope="col">EMPLOYEE</th><th scope="col">DEPARTMENT</th><th scope="col">JOINED</th><th scope="col">STATUS</th></tr></thead>
                                    <tbody>
                                        <?php if ($newEmployees->num_rows > 0) { ?>
                                            <?php while ($employee = $newEmployees->fetch_assoc()) {
                                                $initials = strtoupper(substr(preg_replace('/\s+/', '', $employee['name']), 0, 2));
                                            ?>
                                                <tr>
                                                    <td><div class="employee-cell"><span class="employee-avatar avatar-lavender"><?php echo htmlspecialchars($initials); ?></span><span><strong><?php echo htmlspecialchars($employee['name']); ?></strong><small><?php echo htmlspecialchars($employee['designation']); ?></small></span></div></td>
                                                    <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($employee['joining_date'])); ?></td>
                                                    <td><span class="status-badge status-active">Active</span></td>
                                                </tr>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <tr><td colspan="4" class="text-center text-secondary py-4">No employees found.</td></tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-xl-5" id="leave-requests">
                        <article class="card dashboard-card leave-card h-100">
                            <div class="card-heading">
                                <div><h2>Leave requests <span class="pending-count"><?php echo $pendingLeaveCount; ?></span></h2><p>Requests waiting for your review.</p></div>
                                <a class="text-action" href="/hr-management/admin/leave_management/leave_management.php">View all</a>
                            </div>
                            <div class="leave-list">
                                <?php if ($pendingLeaves->num_rows > 0) { ?>
                                    <?php while ($leave = $pendingLeaves->fetch_assoc()) {
                                        $initials = strtoupper(substr(preg_replace('/\s+/', '', $leave['employee_name']), 0, 2));
                                    ?>
                                        <div class="leave-item">
                                            <span class="employee-avatar avatar-peach"><?php echo htmlspecialchars($initials); ?></span>
                                            <span class="leave-person"><strong><?php echo htmlspecialchars($leave['employee_name']); ?></strong><small><?php echo htmlspecialchars(ucfirst($leave['leave_type'])); ?> leave · <?php echo date('M d', strtotime($leave['start_date'])); ?>–<?php echo date('M d', strtotime($leave['end_date'])); ?></small></span>
                                            <span class="leave-duration"><?php echo (int) $leave['total_days']; ?> days</span>
                                        </div>
                                    <?php } ?>
                                <?php } else { ?>
                                    <p class="text-secondary text-center py-4 mb-0">No pending leave requests.</p>
                                <?php } ?>
                            </div>
                            <a class="review-link" href="/hr-management/admin/leave_management/leave_management.php">Review all requests <span aria-hidden="true">→</span></a>
                        </article>
                    </div>
                </section>

                <?php include_once("includes/footer.php"); ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('#adminSidebar .nav-link[href^="#"]').forEach((link) => {
            link.addEventListener('click', () => {
                if (window.matchMedia('(max-width: 991.98px)').matches) {
                    bootstrap.Offcanvas.getInstance(document.getElementById('adminSidebar'))?.hide();
                }
            });
        });
    </script>
</body>
</html>
