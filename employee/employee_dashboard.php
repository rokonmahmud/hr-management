<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'employee' || empty($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit;
}

include_once("../config/db.php");
/** @var mysqli $conn */

$employeeId = (int) $_SESSION['employee_id'];
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');
$today = date('Y-m-d');

$attendanceStmt = $conn->prepare(
    "SELECT COUNT(*) AS present_days
     FROM attendance
     WHERE employee_id = ? AND attendance_date BETWEEN ? AND ?"
);
$attendanceStmt->bind_param("iss", $employeeId, $monthStart, $today);
$attendanceStmt->execute();
$presentDays = (int) $attendanceStmt->get_result()->fetch_assoc()['present_days'];
$elapsedDays = 0;
for ($day = strtotime($monthStart); $day <= strtotime($today); $day = strtotime('+1 day', $day)) {
    if ((int) date('N', $day) <= 5) {
        $elapsedDays++;
    }
}
$attendancePercent = $elapsedDays > 0 ? min(100, (int) round(($presentDays / $elapsedDays) * 100)) : 0;

$leaveStmt = $conn->prepare(
    "SELECT COALESCE(SUM(total_days), 0) AS used_days
     FROM leave_requests
     WHERE employee_id = ? AND status = 'Approved' AND start_date >= ?"
);
$yearStart = date('Y-01-01');
$leaveStmt->bind_param("is", $employeeId, $yearStart);
$leaveStmt->execute();
$leaveUsed = (int) $leaveStmt->get_result()->fetch_assoc()['used_days'];
$leaveBalance = max(0, 12 - $leaveUsed);

$payrollStmt = $conn->prepare(
    "SELECT salary, status, paid_at FROM payroll
     WHERE employee_id = ? AND payroll_month = ? LIMIT 1"
);
$payrollMonth = date('Y-m-01');
$payrollStmt->bind_param("is", $employeeId, $payrollMonth);
$payrollStmt->execute();
$payroll = $payrollStmt->get_result()->fetch_assoc();

$profileStmt = $conn->prepare(
    "SELECT name, email, phone, department, designation, joining_date, birth_date, profile_picture
     FROM employees WHERE employee_id = ? LIMIT 1"
);
$profileStmt->bind_param("i", $employeeId);
$profileStmt->execute();
$profile = $profileStmt->get_result()->fetch_assoc();
$profileFields = 0;
$profileFieldCount = 8;
foreach ($profile ?: [] as $value) {
    if (trim((string) $value) !== '') {
        $profileFields++;
    }
}
$profilePercent = $profile ? (int) round(($profileFields / $profileFieldCount) * 100) : 0;
$todayMonthDay = date('m-d');
$isBirthday = !empty($profile['birth_date']) &&
    date('m-d', strtotime($profile['birth_date'])) === $todayMonthDay;

$birthdayDates = [];
$birthdayOffsets = [];
for ($dayOffset = 0; $dayOffset < 3; $dayOffset++) {
    $birthdayDate = new DateTimeImmutable('today +' . $dayOffset . ' days');
    $monthDay = $birthdayDate->format('m-d');
    $birthdayDates[] = $monthDay;
    $birthdayOffsets[$monthDay] = $dayOffset;
}
$birthdayDateOne = $birthdayDates[0];
$birthdayDateTwo = $birthdayDates[1];
$birthdayDateThree = $birthdayDates[2];
$birthdayStmt = $conn->prepare(
    "SELECT name, department, birth_date
     FROM employees
     WHERE role = 'employee' AND employee_id <> ? AND birth_date IS NOT NULL
       AND DATE_FORMAT(birth_date, '%m-%d') IN (?, ?, ?)"
);
$birthdayStmt->bind_param(
    "isss",
    $employeeId,
    $birthdayDateOne,
    $birthdayDateTwo,
    $birthdayDateThree
);
$birthdayStmt->execute();
$birthdayEmployees = $birthdayStmt->get_result()->fetch_all(MYSQLI_ASSOC);
foreach ($birthdayEmployees as &$birthdayEmployee) {
    $birthdayEmployee['days_until'] = $birthdayOffsets[date('m-d', strtotime($birthdayEmployee['birth_date']))];
}
unset($birthdayEmployee);
usort($birthdayEmployees, static function ($first, $second) {
    return $first['days_until'] <=> $second['days_until'] ?: strcmp($first['name'], $second['name']);
});

$netPay = $payroll ? (float) $payroll['salary'] : 0;
$payrollStatus = $payroll['status'] ?? 'Unpaid';
$payrollFootnote = $payrollStatus === 'paid'
    ? 'Paid on ' . date('M d', strtotime($payroll['paid_at']))
    : 'For ' . date('F Y');

$weekStart = date('Y-m-d', strtotime('monday this week'));
$weeklyHours = [];
for ($dayOffset = 0; $dayOffset < 7; $dayOffset++) {
    $date = date('Y-m-d', strtotime($weekStart . ' +' . $dayOffset . ' days'));
    $hoursStmt = $conn->prepare(
        "SELECT COALESCE(SUM(hours_worked), 0) AS hours
         FROM timesheets WHERE employee_id = ? AND work_date = ?"
    );
    $hoursStmt->bind_param("is", $employeeId, $date);
    $hoursStmt->execute();
    $hours = (float) $hoursStmt->get_result()->fetch_assoc()['hours'];
    $weeklyHours[] = ['label' => date('D', strtotime($date)), 'height' => min(100, (int) round(($hours / 8) * 100))];
}

$pendingTimesheetsStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM timesheets WHERE employee_id = ? AND status = 'Pending'"
);
$pendingTimesheetsStmt->bind_param("i", $employeeId);
$pendingTimesheetsStmt->execute();
$pendingTimesheets = (int) $pendingTimesheetsStmt->get_result()->fetch_assoc()['total'];

$pendingLeaveStmt = $conn->prepare(
    "SELECT COUNT(*) AS total FROM leave_requests WHERE employee_id = ? AND status = 'Pending'"
);
$pendingLeaveStmt->bind_param("i", $employeeId);
$pendingLeaveStmt->execute();
$pendingLeaves = (int) $pendingLeaveStmt->get_result()->fetch_assoc()['total'];
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
                <?php if ($isBirthday) { ?>
                    <section class="card border-0 shadow-sm rounded-4 mb-4 bg-success-subtle">
                        <div class="card-body d-flex align-items-center gap-3 p-4">
                            <span class="fs-1" aria-hidden="true">🎂</span>
                            <div>
                                <h2 class="h4 fw-bold mb-1">Happy Birthday, <?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?>!</h2>
                                <p class="mb-0 text-secondary">Wishing you a wonderful day and a great year ahead.</p>
                            </div>
                        </div>
                    </section>
                <?php } ?>
                <?php if (count($birthdayEmployees) > 0) { ?>
                    <section class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h2 class="h5 fw-bold mb-3">🎉 Upcoming birthdays</h2>
                            <div class="d-flex flex-wrap gap-3">
                                <?php foreach ($birthdayEmployees as $birthdayEmployee) { ?>
                                    <div class="d-flex align-items-center gap-2 rounded-3 bg-light px-3 py-2">
                                        <span class="employee-avatar avatar-peach"><?php echo htmlspecialchars(strtoupper(substr(preg_replace('/\s+/', '', $birthdayEmployee['name']), 0, 2)), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <span>
                                            <strong class="d-block"><?php echo htmlspecialchars($birthdayEmployee['name'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                            <small class="text-secondary"><?php echo htmlspecialchars($birthdayEmployee['department'], ENT_QUOTES, 'UTF-8'); ?></small>
                                        </span>
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis">
                                            <?php echo $birthdayEmployee['days_until'] === 0 ? 'Today' : ($birthdayEmployee['days_until'] === 1 ? 'Tomorrow' : 'In 2 days'); ?>
                                        </span>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </section>
                <?php } ?>
                <section class="welcome-row">
                    <div>
                        <span class="welcome-kicker">WELCOME BACK</span>
                        <h1>Good morning, <?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">✦</span></h1>
                        <p>Your day-to-day overview is ready.</p>
                    </div>
                    <a class="btn btn-primary add-employee-button" href="timesheet/timesheet.php">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Submit timesheet
                    </a>
                </section>

                <section class="row g-3 stat-grid" aria-label="Employee overview">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon attendance-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Attendance</span>
                            <div class="stat-value-row"><strong><?php echo $attendancePercent; ?>%</strong><span class="trend positive"><?php echo $presentDays; ?> days</span></div>
                            <span class="stat-footnote">This month</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon leave-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5h7l4.5 4.5v12a1.5 1.5 0 0 1-1.5 1.5H7A2.5 2.5 0 0 1 4.5 19V6A2.5 2.5 0 0 1 7 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 4v5h5M8 13h8M8 16.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Leave balance</span>
                            <div class="stat-value-row"><strong><?php echo $leaveBalance; ?></strong><span class="trend neutral">Days</span></div>
                            <span class="stat-footnote"><?php echo $leaveUsed; ?> days used this year</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon payroll-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9.5h17M7.5 14h3M16 13v.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Net pay</span>
                            <div class="stat-value-row"><strong class="currency-value">৳ <?php echo number_format($netPay, 0); ?></strong><span class="trend <?php echo strtolower($payrollStatus) === 'paid' ? 'positive' : 'neutral'; ?>"><?php echo ucfirst($payrollStatus); ?></span></div>
                            <span class="stat-footnote"><?php echo htmlspecialchars($payrollFootnote); ?></span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon people-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Profile status</span>
                            <div class="stat-value-row"><strong><?php echo $profilePercent; ?>%</strong><span class="trend positive">Complete</span></div>
                            <span class="stat-footnote"><?php echo max(0, $profileFieldCount - $profileFields); ?> fields remaining</span>
                        </article>
                    </div>
                </section>

                <section class="row g-3 dashboard-row">
                    <div class="col-12 col-xl-8">
                        <article class="card dashboard-card h-100">
                            <div class="card-heading">
                                <div>
                                    <h2>My schedule</h2>
                                    <p>Upcoming meetings and focus blocks.</p>
                                </div>
                                <button class="btn btn-sm dropdown-button" type="button">This week <span aria-hidden="true">⌄</span></button>
                            </div>
                            <div class="attendance-chart" role="img" aria-label="Weekly working hours chart">
                                <div class="chart-grid-lines"><span>40</span><span>30</span><span>20</span><span>10</span></div>
                                <div class="chart-columns">
                                    <?php foreach ($weeklyHours as $day) { ?>
                                        <div class="chart-column">
                                            <div class="bar-stack <?php echo in_array($day['label'], ['Sat','Sun']) ? 'weekend-bars' : ''; ?>">
                                                <i class="bar-present" style="height: <?php echo $day['height']; ?>%"></i>
                                            </div>
                                            <span><?php echo $day['label']; ?></span>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="col-12 col-xl-4">
                        <article class="card dashboard-card h-100">
                            <div class="card-heading">
                                <div>
                                    <h2>Priority tasks</h2>
                                    <p>Finish the essentials.</p>
                                </div>
                            </div>
                            <div class="leave-list" style="margin-top: 18px;">
                                <div class="leave-item">
                                    <span class="employee-avatar avatar-mint">TS</span>
                                    <div class="leave-person">
                                        <strong>Timesheet entries</strong>
                                        <small>Waiting for admin approval</small>
                                    </div>
                                    <span class="pending-count"><?php echo $pendingTimesheets; ?></span>
                                </div>
                                <div class="leave-item">
                                    <span class="employee-avatar avatar-peach">LV</span>
                                    <div class="leave-person">
                                        <strong>Leave requests</strong>
                                        <small>Pending your approval result</small>
                                    </div>
                                    <span class="pending-count"><?php echo $pendingLeaves; ?></span>
                                </div>
                                <div class="leave-item">
                                    <span class="employee-avatar avatar-blue">AT</span>
                                    <div class="leave-person">
                                        <strong>Attendance this month</strong>
                                        <small><?php echo $presentDays; ?> working days recorded</small>
                                    </div>
                                    <span class="pending-count"><?php echo $attendancePercent; ?>%</span>
                                </div>
                            </div>
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
