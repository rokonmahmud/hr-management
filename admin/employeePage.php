<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['employee_csrf_token'])) {
    $_SESSION['employee_csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
if (isset($_SESSION['employee_flash'])) {
    $success = $_SESSION['employee_flash'];
    unset($_SESSION['employee_flash']);
} else {
    $success = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    $token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    $employeeId = isset($_POST['employee_id']) && is_string($_POST['employee_id']) ? $_POST['employee_id'] : '';

    if (!hash_equals($_SESSION['employee_csrf_token'], $token)) {
        $error = 'Your session expired. Refresh the page and try again.';
    } elseif (!ctype_digit($employeeId) || (int) $employeeId < 1) {
        $error = 'Choose a valid employee to delete.';
    } else {
        $transactionStarted = false;
        try {
            $findUser = $conn->prepare('SELECT user_id FROM employees WHERE id = ?');
            $id = (int) $employeeId;
            $findUser->bind_param('i', $id);
            $findUser->execute();
            $employee = $findUser->get_result()->fetch_assoc();
            $findUser->close();

            if (!$employee) {
                $error = 'That employee could not be found.';
            } else {
                $conn->begin_transaction();
                $transactionStarted = true;
                $userId = (int) $employee['user_id'];
                $deleteUser = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'employee'");
                $deleteUser->bind_param('i', $userId);
                $deleteUser->execute();
                $deleted = $deleteUser->affected_rows;
                $deleteUser->close();

                if ($deleted !== 1) {
                    throw new RuntimeException('Employee account was not deleted.');
                }

                $conn->commit();
                $_SESSION['employee_csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['employee_flash'] = 'Employee and linked account deleted.';
                header('Location: employeePage.php');
                exit;
            }
        } catch (mysqli_sql_exception | RuntimeException $exception) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            error_log('Unable to delete employee: ' . $exception->getMessage());
            $error = 'The employee could not be deleted. Please try again.';
        }
    }
}

$employeesResult = $conn->query(
    'SELECT e.id, e.emp_code, e.phone, e.join_date, e.status,
            u.name, u.email, d.name AS department_name, g.title AS designation_title
     FROM employees e
     INNER JOIN users u ON u.id = e.user_id
     INNER JOIN departments d ON d.id = e.department_id
     INNER JOIN designations g ON g.id = e.designation_id
     ORDER BY e.id DESC'
);
$employees = $employeesResult->fetch_all(MYSQLI_ASSOC);

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Employees | PeopleDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <div class="app-shell">
        <?php include_once __DIR__ . '/includes/sidebar.php'; ?>

        <main class="main-content">
            <?php include_once __DIR__ . '/includes/topbar.php'; ?>

            <div class="dashboard-content">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <span class="small fw-bold text-uppercase text-secondary">People management</span>
                        <h1 class="h2 fw-bold mt-2 mb-1">Employees</h1>
                        <p class="text-secondary mb-0">Manage employee profiles and account details.</p>
                    </div>
                    <a class="btn btn-success px-3" href="addEmployee.php">+ Add employee</a>
                </div>

                <?php if ($success !== ''): ?>
                    <div class="alert alert-success" role="status"><?= $escape($success) ?></div>
                <?php endif; ?>
                <?php if ($error !== ''): ?>
                    <div class="alert alert-danger" role="alert"><?= $escape($error) ?></div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white border-0 px-3 px-md-4 pt-4 pb-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <h2 class="h5 fw-bold mb-0">Employee directory</h2>
                            <span class="badge text-bg-light"><?= count($employees) ?> total</span>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="px-3 px-md-4">Employee</th>
                                    <th>Employee ID</th>
                                    <th>Department / designation</th>
                                    <th>Phone</th>
                                    <th>Joining date</th>
                                    <th>Status</th>
                                    <th class="text-end px-3 px-md-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!$employees): ?>
                                    <tr>
                                        <td colspan="7" class="py-5 text-center text-secondary">
                                            No employees yet. Add an employee to get started.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($employees as $employee): ?>
                                        <tr>
                                            <td class="px-3 px-md-4">
                                                <div class="fw-semibold"><?= $escape($employee['name']) ?></div>
                                                <div class="small text-secondary"><?= $escape($employee['email']) ?></div>
                                            </td>
                                            <td><?= $escape($employee['emp_code']) ?></td>
                                            <td>
                                                <div><?= $escape($employee['department_name']) ?></div>
                                                <div class="small text-secondary"><?= $escape($employee['designation_title']) ?></div>
                                            </td>
                                            <td><?= $employee['phone'] !== null && $employee['phone'] !== '' ? $escape($employee['phone']) : '<span class="text-secondary">—</span>' ?></td>
                                            <td><?= $escape($employee['join_date']) ?></td>
                                            <td>
                                                <span class="badge <?= $employee['status'] === 'active' ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                    <?= $escape(ucfirst($employee['status'])) ?>
                                                </span>
                                            </td>
                                            <td class="text-end px-3 px-md-4">
                                                <div class="d-inline-flex gap-2">
                                                    <a class="btn btn-sm btn-outline-secondary" href="editEmployee.php?id=<?= (int) $employee['id'] ?>">Edit</a>
                                                    <form action="employeePage.php" method="post" onsubmit="return confirm('Delete this employee and their linked account and records?');">
                                                        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['employee_csrf_token']) ?>">
                                                        <input type="hidden" name="employee_id" value="<?= (int) $employee['id'] ?>">
                                                        <input type="hidden" name="action" value="delete">
                                                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php include_once __DIR__ . '/includes/footer.php'; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
