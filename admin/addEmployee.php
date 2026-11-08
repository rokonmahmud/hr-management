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

$errors = [];
$success = '';
$form = [
    'name' => '',
    'email' => '',
    'phone' => '',
    'emp_code' => '',
    'join_date' => date('Y-m-d'),
    'department' => '',
    'designation' => '',
    'status' => 'active',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($form as $key => $value) {
        if (isset($_POST[$key]) && is_string($_POST[$key])) {
            $form[$key] = trim($_POST[$key]);
        }
    }

    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';
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
    if (strlen($password) < 5) {
        $errors[] = 'The password must be at least 5 characters.';
    }
    if ($form['emp_code'] === '' || strlen($form['emp_code']) > 20) {
        $errors[] = 'Enter an employee ID between 1 and 20 characters.';
    }
    if (strlen($form['phone']) > 20) {
        $errors[] = 'The phone number cannot exceed 20 characters.';
    }
    if (!in_array($form['status'], ['active', 'inactive'], true)) {
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
            $checkUser = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $checkUser->bind_param('s', $form['email']);
            $checkUser->execute();
            $emailExists = $checkUser->get_result()->fetch_assoc();
            $checkUser->close();

            $checkCode = $conn->prepare('SELECT id FROM employees WHERE emp_code = ? LIMIT 1');
            $checkCode->bind_param('s', $form['emp_code']);
            $checkCode->execute();
            $codeExists = $checkCode->get_result()->fetch_assoc();
            $checkCode->close();

            $checkDepartment = $conn->prepare('SELECT id FROM departments WHERE id = ?');
            $departmentId = (int) $form['department'];
            $checkDepartment->bind_param('i', $departmentId);
            $checkDepartment->execute();
            $departmentExists = $checkDepartment->get_result()->fetch_assoc();
            $checkDepartment->close();

            $checkDesignation = $conn->prepare('SELECT id FROM designations WHERE id = ?');
            $designationId = (int) $form['designation'];
            $checkDesignation->bind_param('i', $designationId);
            $checkDesignation->execute();
            $designationExists = $checkDesignation->get_result()->fetch_assoc();
            $checkDesignation->close();

            if ($emailExists) {
                $errors[] = 'An account already exists for that email address.';
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

                $passwordHash = md5($password);
                $insertUser = $conn->prepare(
                    "INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, 'employee', ?)"
                );
                $insertUser->bind_param('ssss', $form['name'], $form['email'], $passwordHash, $form['status']);
                $insertUser->execute();
                $userId = $conn->insert_id;
                $insertUser->close();

                $insertEmployee = $conn->prepare(
                    'INSERT INTO employees (user_id, department_id, designation_id, emp_code, phone, join_date, status)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $insertEmployee->bind_param(
                    'iiissss',
                    $userId,
                    $departmentId,
                    $designationId,
                    $form['emp_code'],
                    $form['phone'],
                    $form['join_date'],
                    $form['status']
                );
                $insertEmployee->execute();
                $insertEmployee->close();

                $conn->commit();
                $_SESSION['employee_csrf_token'] = bin2hex(random_bytes(32));
                $_SESSION['employee_flash'] = 'Employee added successfully.';
                header('Location: employeePage.php');
                exit;
            }
        } catch (mysqli_sql_exception $exception) {
            if ($transactionStarted) {
                $conn->rollback();
            }
            error_log('Unable to add employee: ' . $exception->getMessage());
            $errors[] = 'The employee could not be saved. Check that the email and employee ID are unique, then try again.';
        }
    }
}

$escape = static function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

$departments = $conn->query('SELECT id, name FROM departments ORDER BY name');
$designations = $conn->query('SELECT id, title FROM designations ORDER BY title');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Add employee | PeopleDesk</title>
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
                        <h1 class="h2 fw-bold mt-2 mb-1">Add employee</h1>
                        <p class="text-secondary mb-0">Create a profile for a new member of your team.</p>
                    </div>
                    <a class="btn btn-outline-secondary" href="employeePage.php">
                        <span aria-hidden="true">&larr;</span> Back to employees
                    </a>
                </div>

                <form action="" method="post" class="card border-0 shadow-sm rounded-3">
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
                        <section aria-labelledby="personal-info-heading">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success p-2" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                </span>
                                <div>
                                    <h2 class="h5 fw-bold mb-1" id="personal-info-heading">Personal information</h2>
                                    <p class="small text-secondary mb-0">Basic contact details for this employee.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="name">Full name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="name" name="name" maxlength="100" placeholder="e.g. Alex Morgan" autocomplete="name" required value="<?= $escape($form['name']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email">Work email <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" id="email" name="email" maxlength="150" placeholder="alex@company.com" autocomplete="email" required value="<?= $escape($form['email']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="phone">Phone number</label>
                                    <input class="form-control" type="tel" id="phone" name="phone" maxlength="20" placeholder="+1 (555) 000-0000" autocomplete="tel" value="<?= $escape($form['phone']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="password">Temporary password <span class="text-danger">*</span></label>
                                    <input class="form-control" type="password" id="password" name="password" minlength="5" placeholder="Enter a temporary password" autocomplete="new-password" required>
                                    <div class="form-text">Share the initial password with the employee securely.</div>
                                </div>
                            </div>
                        </section>

                        <hr class="my-4 my-md-5">

                        <section aria-labelledby="employment-heading">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success p-2" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="3.5" y="7" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 7V5.5A1.5 1.5 0 0 1 9.5 4h5A1.5 1.5 0 0 1 16 5.5V7M3.5 12h17M10 12v2h4v-2" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                                </span>
                                <div>
                                    <h2 class="h5 fw-bold mb-1" id="employment-heading">Employment details</h2>
                                    <p class="small text-secondary mb-0">Role and start-date information.</p>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="emp_code">Employee ID <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="emp_code" name="emp_code" maxlength="20" placeholder="e.g. EMP-001" required value="<?= $escape($form['emp_code']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="join_date">Joining date <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" id="join_date" name="join_date" required value="<?= $escape($form['join_date']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="department">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="" disabled <?= $form['department'] === '' ? 'selected' : '' ?>>Select department</option>
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
                                        <option value="" disabled <?= $form['designation'] === '' ? 'selected' : '' ?>>Select designation</option>
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
                                    </select>
                                </div>
                            </div>
                        </section>
                    </div>

                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2 p-3 p-md-4">
                        <input type="hidden" name="csrf_token" value="<?= $escape($_SESSION['employee_csrf_token']) ?>">
                        <a class="btn btn-light" href="employeePage.php">Cancel</a>
                        <button class="btn btn-success px-4" type="submit">Add employee</button>
                    </div>
                </form>
            </div>
            <?php include_once __DIR__ . '/includes/footer.php'; ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
