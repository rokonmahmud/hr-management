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

$employeeIdValue = $_SERVER['REQUEST_METHOD'] === 'POST'
    ? (isset($_POST['employee_id']) && is_string($_POST['employee_id']) ? $_POST['employee_id'] : '')
    : (isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '');

if (!ctype_digit($employeeIdValue) || (int) $employeeIdValue < 1) {
    header('Location: employeePage.php');
    exit;
}

$employeeId = (int) $employeeIdValue;
$loadEmployee = $conn->prepare(
    'SELECT e.id, e.user_id, e.department_id, e.designation_id, e.emp_code, e.phone, e.join_date, e.status,
            u.name, u.email
     FROM employees e
     INNER JOIN users u ON u.id = e.user_id
     WHERE e.id = ?'
);
$loadEmployee->bind_param('i', $employeeId);
$loadEmployee->execute();
$employee = $loadEmployee->get_result()->fetch_assoc();
$loadEmployee->close();

if (!$employee) {
    $_SESSION['employee_flash'] = 'The requested employee was not found.';
    header('Location: employeePage.php');
    exit;
}

$form = [
    'name' => $employee['name'],
    'email' => $employee['email'],
    'phone' => $employee['phone'] ?? '',
    'emp_code' => $employee['emp_code'],
    'join_date' => $employee['join_date'],
    'department' => (string) $employee['department_id'],
    'designation' => (string) $employee['designation_id'],
    'status' => $employee['status'],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $form[$key] = trim($_POST[$key]);
        }
    }

    $token = isset($_POST['csrf_token']) && is_string($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    if (!hash_equals($_SESSION['employee_csrf_token'], $token)) {
        $errors[] = 'Your session expired. Refresh the page and try again.';
    }
    if ($form['name'] === '' || strlen($form['name']) > 100) {
        $errors[] = 'Enter a name between 1 and 100 characters.';
    }
    if (!filter_var($form['email'], FILTER_VALIDATE_EMAIL) || strlen($form['email']) > 150) {
        $errors[] = 'Enter a valid email address (up to 150 characters).';
    }
    if ($form['emp_code'] === '' || strlen($form['emp_code']) > 20) {
        $errors[] = 'Enter an employee ID between 1 and 20 characters.';
    }
    if (strlen($form['phone']) > 20) {
        $errors[] = 'The phone number cannot exceed 20 characters.';
    }
    if (!in_array($form['status'], ['active', 'inactive', 'resigned'], true)) {
        $errors[] = 'Choose a valid employee status.';
    }

    $joinDate = DateTime::createFromFormat('!Y-m-d', $form['join_date']);
    if (!$joinDate || $joinDate->format('Y-m-d') !== $form['join_date']) {
        $errors[] = 'Choose a valid joining date.';
    }
    foreach (['department', 'designation'] as $field) {
        if (!ctype_digit($form[$field]) || (int) $form[$field] < 1) {
            $errors[] = 'Choose a valid ' . $field . '.';
        }
    }

    if (!$errors) {
        $transactionStarted = false;
        try {
            $checkEmail = $conn->prepare('SELECT id FROM users WHERE email = ? AND id <> ? LIMIT 1');
            $checkEmail->bind_param('si', $form['email'], $employee['user_id']);
            $checkEmail->execute();
            $emailExists = $checkEmail->get_result()->fetch_assoc();
            $checkEmail->close();

            $checkCode = $conn->prepare('SELECT id FROM employees WHERE emp_code = ? AND id <> ? LIMIT 1');
            $checkCode->bind_param('si', $form['emp_code'], $employeeId);
            $checkCode->execute();
            $codeExists = $checkCode->get_result()->fetch_assoc();
            $checkCode->close();

            $departmentId = (int) $form['department'];
            $checkDepartment = $conn->prepare('SELECT id FROM departments WHERE id = ?');
            $checkDepartment->bind_param('i', $departmentId);
            $checkDepartment->execute();
            $departmentExists = $checkDepartment->get_result()->fetch_assoc();
            $checkDepartment->close();

            $designationId = (int) $form['designation'];
            $checkDesignation = $conn->prepare('SELECT id FROM designations WHERE id = ?');
            $checkDesignation->bind_param('i', $designationId);
            $checkDesignation->execute();
            $designationExists = $checkDesignation->get_result()->fetch_assoc();
            $checkDesignation->close();

            if ($emailExists) {
                $errors[] = 'That email address is already in use.';
            }
            if ($codeExists) {
                $errors[] = 'That employee ID is already in use.';
            }
            if (!$departmentExists) {
                $errors[] = 'Choose a valid department.';
            }
            if (!$designationExists) {
                $errors[] = 'Choose a valid designation.';
            }

            if (!$errors) {
                $conn->begin_transaction();
                $transactionStarted = true;
                $accountStatus = $form['status'] === 'active' ? 'active' : 'inactive';
                $updateUser = $conn->prepare('UPDATE users SET name = ?, email = ?, status = ? WHERE id = ?');
                $updateUser->bind_param('sssi', $form['name'], $form['email'], $accountStatus, $employee['user_id']);
                $updateUser->execute();
                $updateUser->close();

                $updateEmployee = $conn->prepare(
                    'UPDATE employees
                     SET department_id = ?, designation_id = ?, emp_code = ?, phone = ?, join_date = ?, status = ?
                     WHERE id = ?'
                );
                $updateEmployee->bind_param(
                    'iissssi',
                    $departmentId,
                    $designationId,
                    $form['emp_code'],
                    $form['phone'],
                    $form['join_date'],
                    $form['status'],
                    $employeeId
                );
                $updateEmployee->execute();
                $updateEmployee->close();
                $conn->commit();
                $transactionStarted = false;

                $_SESSION['employee_flash'] = 'Employee updated successfully.';
                header('Location: employeePage.php');
                exit;
            }
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            error_log('Unable to update employee: ' . $exception->getMessage());
            $errors[] = 'The employee could not be updated. Check that the email and employee ID are unique, then try again.';
        }
    }
}

$departments = $conn->query('SELECT id, name FROM departments ORDER BY name');
$designations = $conn->query('SELECT id, title FROM designations ORDER BY title');
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
    <title>Edit employee | PeopleDesk</title>
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
                        <h1 class="h2 fw-bold mt-2 mb-1">Edit employee</h1>
                        <p class="text-secondary mb-0">Update profile and employment information.</p>
                    </div>
                    <a class="btn btn-outline-secondary" href="employeePage.php">&larr; Back to employees</a>
                </div>

                <form action="editEmployee.php?id=<?= $employeeId ?>" method="post" class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3 p-md-4 p-xl-5">
                        <?php if ($errors): ?>
                            <div class="alert alert-danger" role="alert">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?= $escape($error) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['employee_csrf_token']) ?>">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="name">Full name <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="name" name="name" maxlength="100" required value="<?= $escape($form['name']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="email">Work email <span class="text-danger">*</span></label>
                                <input class="form-control" type="email" id="email" name="email" maxlength="150" required value="<?= $escape($form['email']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="phone">Phone number</label>
                                <input class="form-control" type="tel" id="phone" name="phone" maxlength="20" value="<?= $escape($form['phone']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="emp_code">Employee ID <span class="text-danger">*</span></label>
                                <input class="form-control" type="text" id="emp_code" name="emp_code" maxlength="20" required value="<?= $escape($form['emp_code']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="join_date">Joining date <span class="text-danger">*</span></label>
                                <input class="form-control" type="date" id="join_date" name="join_date" required value="<?= $escape($form['join_date']) ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="department">Department <span class="text-danger">*</span></label>
                                <select class="form-select" id="department" name="department" required>
                                    <option value="" disabled>Select department</option>
                                    <?php while ($department = $departments->fetch_assoc()): ?>
                                        <option value="<?= (int) $department['id'] ?>" <?= (string) $department['id'] === $form['department'] ? 'selected' : '' ?>>
                                            <?= $escape($department['name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="designation">Designation <span class="text-danger">*</span></label>
                                <select class="form-select" id="designation" name="designation" required>
                                    <option value="" disabled>Select designation</option>
                                    <?php while ($designation = $designations->fetch_assoc()): ?>
                                        <option value="<?= (int) $designation['id'] ?>" <?= (string) $designation['id'] === $form['designation'] ? 'selected' : '' ?>>
                                            <?= $escape($designation['title']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?= $form['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                                    <option value="inactive" <?= $form['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                    <option value="resigned" <?= $form['status'] === 'resigned' ? 'selected' : '' ?>>Resigned</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2 p-3 p-md-4">
                        <a class="btn btn-light" href="employeePage.php">Cancel</a>
                        <button class="btn btn-success px-4" type="submit">Save changes</button>
                    </div>
                </form>
            </div>
            <?php include_once __DIR__ . '/includes/footer.php'; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
