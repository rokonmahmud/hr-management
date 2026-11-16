<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'employee' || empty($_SESSION['employee_id'])) {
    header("Location: ../index.php");
    exit;
}

include_once("../config/db.php");
/** @var mysqli $conn */

$employeeId = (int) $_SESSION['employee_id'];
$errorMessage = '';

if (empty($_SESSION['attendance_csrf_token'])) {
    $_SESSION['attendance_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['attendance_csrf_token'], $token)) {
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'clock_in') {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $conn->prepare(
            "INSERT INTO attendance
                (employee_id, attendance_date, clock_in_time, clock_in_ip_address, is_late, status)
             VALUES (?, CURDATE(), CURRENT_TIMESTAMP, ?, TIME(CURRENT_TIMESTAMP) > '09:00:00', 'Present')"
        );
        $stmt->bind_param("is", $employeeId, $ipAddress);
        $stmt->execute();
        header("Location: attendance.php");
        exit;
    } elseif (($_POST['action'] ?? '') === 'clock_out') {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '';
        $stmt = $conn->prepare(
            "UPDATE attendance
             SET clock_out_time = CURRENT_TIMESTAMP,
                 clock_out_ip_address = ?,
                 total_duration = TIMEDIFF(CURRENT_TIMESTAMP, clock_in_time),
                 status = 'Completed'
             WHERE employee_id = ? AND attendance_date = CURDATE() AND clock_out_time IS NULL"
        );
        $stmt->bind_param("si", $ipAddress, $employeeId);
        $stmt->execute();
        if ($stmt->affected_rows !== 1) {
            $errorMessage = 'You have not clocked in today, or your attendance is already complete.';
        } else {
            header("Location: attendance.php");
            exit;
        }
    } else {
        $errorMessage = 'Invalid attendance action.';
    }
}

$todayStmt = $conn->prepare(
    "SELECT * FROM attendance WHERE employee_id = ? AND attendance_date = CURDATE() LIMIT 1"
);
$todayStmt->bind_param("i", $employeeId);
$todayStmt->execute();
$todayAttendance = $todayStmt->get_result()->fetch_assoc();

$historyStmt = $conn->prepare(
    "SELECT attendance_date, clock_in_time, clock_out_time, total_duration, is_late, status
     FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 30"
);
$historyStmt->bind_param("i", $employeeId);
$historyStmt->execute();
$history = $historyStmt->get_result();

function attendanceTimeLabel($value)
{
    return $value ? date('g:i A', strtotime($value)) : '—';
}

function attendanceDurationLabel($value)
{
    if (!$value) {
        return '—';
    }
    $parts = explode(':', $value);
    return sprintf('%d h %02d m', (int) $parts[0], (int) $parts[1]);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>My Attendance | PeopleDesk</title>
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
            <?php $pageTitle = 'Attendance'; ?>
            <?php include_once('includes/topbar.php'); ?>
            <div class="dashboard-content">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">My Attendance</h1>
                        <p class="text-secondary mb-0">Clock in and out, and review your recent attendance.</p>
                    </div>
                </div>

                <?php if ($errorMessage !== '') { ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>

                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-body d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 p-4">
                        <div>
                            <div class="text-secondary small mb-1"><?php echo date('l, F j, Y'); ?></div>
                            <?php if (!$todayAttendance) { ?>
                                <h2 class="h5 fw-bold mb-1">You have not clocked in today</h2>
                                <p class="text-secondary mb-0">Your clock-in time and IP address will be recorded.</p>
                            <?php } elseif (empty($todayAttendance['clock_out_time'])) { ?>
                                <h2 class="h5 fw-bold mb-1">You are currently clocked in</h2>
                                <p class="text-secondary mb-0">
                                    Clocked in at <?php echo htmlspecialchars(attendanceTimeLabel($todayAttendance['clock_in_time']), ENT_QUOTES, 'UTF-8'); ?>
                                    <?php if ((int) $todayAttendance['is_late'] === 1) { ?>
                                        <span class="badge rounded-pill bg-warning-subtle text-warning-emphasis ms-1">Late</span>
                                    <?php } ?>
                                </p>
                            <?php } else { ?>
                                <h2 class="h5 fw-bold mb-1">Attendance complete for today</h2>
                                <p class="text-secondary mb-0">
                                    <?php echo htmlspecialchars(attendanceTimeLabel($todayAttendance['clock_in_time']), ENT_QUOTES, 'UTF-8'); ?>
                                    – <?php echo htmlspecialchars(attendanceTimeLabel($todayAttendance['clock_out_time']), ENT_QUOTES, 'UTF-8'); ?>
                                </p>
                            <?php } ?>
                        </div>
                        <?php if (!$todayAttendance || empty($todayAttendance['clock_out_time'])) { ?>
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['attendance_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="action" value="<?php echo $todayAttendance ? 'clock_out' : 'clock_in'; ?>">
                                <button class="btn <?php echo $todayAttendance ? 'btn-outline-danger' : 'btn-success'; ?> px-4" type="submit">
                                    <?php echo $todayAttendance ? 'Clock out' : 'Clock in'; ?>
                                </button>
                            </form>
                        <?php } ?>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-4 py-3">
                        <h2 class="h6 fw-bold mb-0">Recent attendance</h2>
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">Last 30 days</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-semibold small">Date</th>
                                    <th class="text-secondary fw-semibold small">Clock in</th>
                                    <th class="text-secondary fw-semibold small">Clock out</th>
                                    <th class="text-secondary fw-semibold small">Duration</th>
                                    <th class="text-secondary fw-semibold small">Is late</th>
                                    <th class="pe-4 text-secondary fw-semibold small">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($history->num_rows > 0) { ?>
                                    <?php while ($row = $history->fetch_assoc()) { ?>
                                        <tr>
                                            <td class="ps-4"><?php echo htmlspecialchars(date('M j, Y', strtotime($row['attendance_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(attendanceTimeLabel($row['clock_in_time']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(attendanceTimeLabel($row['clock_out_time']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo htmlspecialchars(attendanceDurationLabel($row['total_duration']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td><?php echo (int) $row['is_late'] === 1 ? 'Yes' : 'No'; ?></td>
                                            <td class="pe-4">
                                                <span class="badge rounded-pill <?php echo $row['status'] === 'Completed' ? 'bg-success-subtle text-success-emphasis' : 'bg-primary-subtle text-primary-emphasis'; ?>">
                                                    <?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="6" class="text-center text-secondary py-5">No attendance records yet.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php include_once("includes/footer.php"); ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
