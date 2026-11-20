
<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: ../../index.php");
    exit;
}

include_once("../../config/db.php");
/** @var mysqli $conn */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$errorMessage = $_SESSION['employee_error'] ?? '';
$notice = $_SESSION['employee_notice'] ?? '';
unset($_SESSION['employee_error'], $_SESSION['employee_notice']);

if (empty($_SESSION['employee_csrf_token'])) {
    $_SESSION['employee_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    $action = $_POST['action'] ?? '';
    $employeeId = filter_var($_POST['employee_id'] ?? null, FILTER_VALIDATE_INT);

    if (!is_string($token) || !hash_equals($_SESSION['employee_csrf_token'], $token)) {
        $_SESSION['employee_error'] = 'Your session expired. Refresh the page and try again.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ($action !== 'delete' || !$employeeId || $employeeId < 1) {
        $_SESSION['employee_error'] = 'Select a valid employee.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    if ((int) ($_SESSION['employee_id'] ?? 0) === $employeeId) {
        $_SESSION['employee_error'] = 'You cannot delete the account you are currently using.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $selectStmt = $conn->prepare("SELECT user_id, profile_picture FROM employees WHERE employee_id = ?");
    $selectStmt->bind_param("i", $employeeId);
    $selectStmt->execute();
    $employee = $selectStmt->get_result()->fetch_assoc();

    if (!$employee) {
        $_SESSION['employee_error'] = 'Employee not found.';
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    $conn->begin_transaction();
    try {
        $tables = ['attendance', 'leave_requests', 'timesheets', 'payroll'];
        foreach ($tables as $table) {
            $deleteStmt = $conn->prepare("DELETE FROM `$table` WHERE employee_id = ?");
            $deleteStmt->bind_param("i", $employeeId);
            $deleteStmt->execute();
        }

        $deleteEmployeeStmt = $conn->prepare("DELETE FROM employees WHERE employee_id = ?");
        $deleteEmployeeStmt->bind_param("i", $employeeId);
        $deleteEmployeeStmt->execute();

        $userId = (int) ($employee['user_id'] ?? 0);
        if ($userId > 0) {
            $deleteUserStmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $deleteUserStmt->bind_param("i", $userId);
            $deleteUserStmt->execute();
        }

        $conn->commit();

        if (!empty($employee['profile_picture'])) {
            $profilePath = dirname(__DIR__, 2) . '/assets/uploads/profile-pictures/' . basename($employee['profile_picture']);
            if (is_file($profilePath)) {
                unlink($profilePath);
            }
        }

        $_SESSION['employee_notice'] = 'Employee deleted successfully.';
    } catch (mysqli_sql_exception $exception) {
        $conn->rollback();
        $_SESSION['employee_error'] = 'Employee could not be deleted. Please try again.';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
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
    <link rel="stylesheet" href="../../assets/admin.css">
</head>
<body>
    <div class="app-shell">
        <?php 
            include_once('../includes/sidebar.php');
        ?>

        <main class="main-content">
            <?php 
                include_once('../includes/topbar.php');
            ?> 
    <div class="app-shell">
        <!-- Sidebar goes here -->

        <main class="main-content">
            <!-- Topbar goes here -->

            <div class="dashboard-content">
                <?php if ($errorMessage !== '') { ?>
                    <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>
                <?php if ($notice !== '') { ?>
                    <div class="alert alert-success" role="status"><?php echo htmlspecialchars($notice, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php } ?>

                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Employees</h1>
                        <p class="text-secondary mb-0">Everyone who works with your team.</p>
                    </div>
                    <a class="btn btn-success px-4" href="addEmployee.php">+ Add employee</a>
                </div>

                <?php $data = $conn->query("SELECT * FROM employees"); ?>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-4 py-3">
                        <h2 class="h6 fw-bold mb-0">All employees</h2>
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2"><?php echo (int) $data->num_rows; ?> total</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-semibold small">Employee</th>
                                    <th class="text-secondary fw-semibold small">ID</th>
                                    <th class="text-secondary fw-semibold small">Department</th>
                                    <th class="text-secondary fw-semibold small">Designation</th>
                                    <th class="text-secondary fw-semibold small">Status</th>
                                    <th class="text-end pe-4 text-secondary fw-semibold small">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    if($data -> num_rows > 0){?>
                                        <?php foreach($data as $index => $employees){?>
                                            <?php 
                                                $counter = $index + 1;
                                                $employeeName = (string) $employees['name'];
                                                $initialCharacters = preg_split(
                                                    '//u',
                                                    preg_replace('/\s+/u', '', $employeeName),
                                                    -1,
                                                    PREG_SPLIT_NO_EMPTY
                                                );
                                                $initials = $initialCharacters
                                                    ? strtoupper(implode('', array_slice($initialCharacters, 0, 2)))
                                                    : 'EM';
                                                $profilePicture = basename((string) ($employees['profile_picture'] ?? ''));
                                                $profilePictureFile = dirname(__DIR__, 2)
                                                    . '/assets/uploads/profile-pictures/' . $profilePicture;
                                                $hasProfilePicture = $profilePicture !== '' && is_file($profilePictureFile);
                                            ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <?php if ($hasProfilePicture) { ?>
                                                            <img src="/hr-management/assets/uploads/profile-pictures/<?php echo rawurlencode($profilePicture); ?>" alt="<?php echo htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8'); ?>" class="rounded-circle object-fit-cover flex-shrink-0" width="42" height="42">
                                                        <?php } else { ?>
                                                            <span class="bg-success-subtle text-success-emphasis fw-bold rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true" style="width: 42px; height: 42px;"><?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?></span>
                                                        <?php } ?>
                                                        <div>
                                                            <div class="fw-bold"><?php echo htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8'); ?></div>
                                                            <div class="small text-secondary"><?php echo htmlspecialchars($employees['email'], ENT_QUOTES, 'UTF-8'); ?></div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold text-secondary"><?php echo "EMP - " . $employees['employee_id'] ?></td>
                                                <td><span class="badge rounded-pill text-bg-light border fw-medium px-3 py-2">Engineering</span></td>
                                                <td><?php echo $employees['designation'] ?></td>
                                                <td><span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">&#9679; Active</span></td>
                                                <td class="text-end pe-4 text-nowrap">

                                                    <a class="btn btn-sm btn-outline-success" href="editEmployee.php?employee_id=<?php echo $employees['employee_id'] ?>" aria-label="Edit employee"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 20h4L19 9l-4-4L4 16v4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></a>

                                                    <form method="post" class="d-inline" onsubmit="return confirm('Delete this employee and all linked records?');">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['employee_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <input type="hidden" name="employee_id" value="<?php echo (int) $employees['employee_id']; ?>">
                                                        <button class="btn btn-sm btn-outline-danger" type="submit" aria-label="Delete employee"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M10 11v6M14 11v6M6.5 7l1 12h9l1-12M9.5 7V5h5v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php }  ?>    
                                    <?php } ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer goes here -->
        </main>
    </div>

    


            </div>
            <?php include_once '../includes/footer.php'; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
