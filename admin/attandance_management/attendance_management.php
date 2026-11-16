<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

include_once("../../config/db.php");
/** @var mysqli $conn */

$errorMessage = '';

if (empty($_SESSION['attendance_csrf_token'])) {
    $_SESSION['attendance_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['attendance_csrf_token'], $token)) {
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $attendanceId = filter_var($_POST['attendance_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$attendanceId || $attendanceId < 1) {
            $errorMessage = 'Select a valid attendance record.';
        } else {
            $stmt = $conn->prepare("DELETE FROM attendance WHERE attendance_id = ?");
            $stmt->bind_param("i", $attendanceId);
            $stmt->execute();
            $_SESSION['attendance_notice'] = 'Attendance record deleted.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } elseif (($_POST['action'] ?? '') === 'update') {
        $attendanceId = filter_var($_POST['attendance_id'] ?? null, FILTER_VALIDATE_INT);
        $clockInInput = is_string($_POST['clock_in_time'] ?? null) ? $_POST['clock_in_time'] : '';
        $clockOutInput = is_string($_POST['clock_out_time'] ?? null) ? $_POST['clock_out_time'] : '';
        $clockIn = DateTime::createFromFormat('Y-m-d\TH:i', $clockInInput);
        $clockOut = $clockOutInput === '' ? null : DateTime::createFromFormat('Y-m-d\TH:i', $clockOutInput);

        if (!$attendanceId || $attendanceId < 1 || !$clockIn ||
            $clockIn->format('Y-m-d\TH:i') !== $clockInInput ||
            ($clockOutInput !== '' && (!$clockOut || $clockOut->format('Y-m-d\TH:i') !== $clockOutInput))) {
            $errorMessage = 'Enter a valid clock-in and clock-out time.';
        } elseif ($clockOut && $clockOut < $clockIn) {
            $errorMessage = 'Clock-out must be later than clock-in.';
        } else {
            $attendanceDate = $clockIn->format('Y-m-d');
            $clockInValue = $clockIn->format('Y-m-d H:i:s');
            $clockOutValue = $clockOut ? $clockOut->format('Y-m-d H:i:s') : null;
            $stmt = $conn->prepare(
                "UPDATE attendance
                 SET attendance_date = ?,
                     clock_in_time = ?,
                     clock_out_time = ?,
                     total_duration = CASE WHEN ? IS NULL THEN NULL ELSE TIMEDIFF(?, ?) END,
                     is_late = TIME(?) > '09:00:00',
                     status = CASE WHEN ? IS NULL THEN 'Present' ELSE 'Completed' END
                 WHERE attendance_id = ?"
            );
            $stmt->bind_param(
                "ssssssssi",
                $attendanceDate,
                $clockInValue,
                $clockOutValue,
                $clockOutValue,
                $clockOutValue,
                $clockInValue,
                $clockInValue,
                $clockOutValue,
                $attendanceId
            );
            $stmt->execute();
            $_SESSION['attendance_notice'] = 'Attendance record updated.';
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        }
    } else {
        $errorMessage = 'Invalid attendance action.';
    }
}

$notice = $_SESSION['attendance_notice'] ?? '';
unset($_SESSION['attendance_notice']);

$attendanceResult = $conn->query(
    "SELECT a.*, e.name AS employee_name, e.designation
     FROM attendance a
     JOIN employees e ON e.employee_id = a.employee_id
     ORDER BY a.attendance_date DESC, a.clock_in_time DESC"
);

function attendanceTimeInputValue($value)
{
    return $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
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
    <title>Attendance Management | PeopleDesk</title>
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
            <?php $pageTitle = 'Attendance'; ?>
            <?php include_once('../includes/topbar.php'); ?>
            <div class="dashboard-content">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Attendance Management</h1>
                        <p class="text-secondary mb-0">Review and manage employee clock-in and clock-out records.</p>
                    </div>
                </div>

                <?php if ($errorMessage !== '') { ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <?php if ($notice !== '') { ?>
                    <div class="alert alert-success" role="status"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-4 py-3">
                            <h2 class="h6 fw-bold mb-0">
                                Attendance records
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2 ms-2"><?php echo $attendanceResult->num_rows; ?> records</span>
                            </h2>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-semibold small">Employees</th>
                                    <th class="text-secondary fw-semibold small">Date</th>
                                    <th class="text-secondary fw-semibold small">Clock In Time</th>
                                    <th class="text-secondary fw-semibold small">Clock Out Time</th>
                                    <th class="text-secondary fw-semibold small">Clock In IP Address</th>
                                    <th class="text-secondary fw-semibold small">Clock Out IP Address</th>
                                    <th class="text-secondary fw-semibold small">Total Duration</th>
                                    <th class="text-secondary fw-semibold small">Is Late</th>
                                    <th class="text-secondary fw-semibold small">Status</th>
                                    <th class="text-end pe-4 text-secondary fw-semibold small">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($attendanceResult->num_rows > 0) { ?>
                                    <?php while ($row = $attendanceResult->fetch_assoc()) { ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-semibold"><?php echo htmlspecialchars($row['employee_name'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                <div class="small text-secondary"><?php echo htmlspecialchars($row['designation'], ENT_QUOTES, 'UTF-8'); ?></div>
                                            </td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars(date('M j, Y', strtotime($row['attendance_date'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars(date('g:i A', strtotime($row['clock_in_time'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-nowrap"><?php echo $row['clock_out_time'] ? htmlspecialchars(date('g:i A', strtotime($row['clock_out_time'])), ENT_QUOTES, 'UTF-8') : '—'; ?></td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars($row['clock_in_ip_address'], ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars($row['clock_out_ip_address'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td class="text-nowrap"><?php echo htmlspecialchars(attendanceDurationLabel($row['total_duration']), ENT_QUOTES, 'UTF-8'); ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo (int) $row['is_late'] === 1 ? 'bg-warning-subtle text-warning-emphasis' : 'bg-success-subtle text-success-emphasis'; ?>">
                                                    <?php echo (int) $row['is_late'] === 1 ? 'Yes' : 'No'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo $row['status'] === 'Completed' ? 'bg-success-subtle text-success-emphasis' : 'bg-primary-subtle text-primary-emphasis'; ?>">
                                                    <?php echo htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8'); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4 text-nowrap">
                                                <button class="btn btn-sm btn-outline-success" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#editAttendanceModal"
                                                        data-attendance-id="<?php echo (int) $row['attendance_id']; ?>"
                                                        data-employee-name="<?php echo htmlspecialchars($row['employee_name'], ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-clock-in="<?php echo htmlspecialchars(attendanceTimeInputValue($row['clock_in_time']), ENT_QUOTES, 'UTF-8'); ?>"
                                                        data-clock-out="<?php echo htmlspecialchars(attendanceTimeInputValue($row['clock_out_time']), ENT_QUOTES, 'UTF-8'); ?>">Edit</button>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Delete this attendance record?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['attendance_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="attendance_id" value="<?php echo (int) $row['attendance_id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr><td colspan="10" class="text-center text-secondary py-5">No attendance records found for this date.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="modal fade" id="editAttendanceModal" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <form method="post" class="modal-content border-0 rounded-4 shadow">
                                <div class="modal-header border-0 pb-0">
                                    <h2 class="modal-title h5 fw-bold">Edit attendance</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-secondary" id="editAttendanceEmployee">Update clock times. Late status and duration are recalculated.</p>
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['attendance_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="attendance_id" id="editAttendanceId">
                                    <label class="form-label small fw-semibold" for="editAttendanceClockIn">Clock in</label>
                                    <input class="form-control mb-3" type="datetime-local" id="editAttendanceClockIn" name="clock_in_time" required>
                                    <label class="form-label small fw-semibold" for="editAttendanceClockOut">Clock out <span class="text-secondary fw-normal">(leave blank if not clocked out)</span></label>
                                    <input class="form-control" type="datetime-local" id="editAttendanceClockOut" name="clock_out_time">
                                </div>
                                <div class="modal-footer border-0 pt-0">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-success">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php include_once('../includes/footer.php'); ?>
            </div>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('editAttendanceModal').addEventListener('show.bs.modal', (event) => {
            const button = event.relatedTarget;
            document.getElementById('editAttendanceId').value = button.dataset.attendanceId;
            document.getElementById('editAttendanceEmployee').textContent =
                'Update clock times for ' + button.dataset.employeeName + '. Late status and duration are recalculated.';
            document.getElementById('editAttendanceClockIn').value = button.dataset.clockIn;
            document.getElementById('editAttendanceClockOut').value = button.dataset.clockOut;
        });
    </script>
</body>
</html>
