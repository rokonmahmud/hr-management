
<?php 
    include_once("../../config/db.php");
    /** @var mysqli $conn */
    $employeeId = filter_var($_GET['employee_id'] ?? null, FILTER_VALIDATE_INT);
    if (!$employeeId || $employeeId < 1) {
        http_response_code(400);
        exit('Invalid employee.');
    }
    $selectStmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    $selectStmt->bind_param("i", $employeeId);
    $selectStmt->execute();
    $row = $selectStmt->get_result()->fetch_object();
    if (!$row) {
        http_response_code(404);
        exit('Employee not found.');
    }
    $errorMessage = '';

    if($_SERVER["REQUEST_METHOD"] == "POST"){

        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['temporary_password'] ?? '';
        $temporary_password = $password === '' ? $row->temporary_password : md5($password);
        $designation = trim($_POST['designation'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $joining_date = $_POST['joining_date'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $basic_salary = filter_var($_POST['basic_salary'] ?? null, FILTER_VALIDATE_FLOAT);
        $role = $_POST['role'] ?? '';
        $profilePicture = null;
        $validBirthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $birth_date);
        $validJoiningDate = DateTimeImmutable::createFromFormat('!Y-m-d', $joining_date);

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['profile_picture'];
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $detectedType = @getimagesize($upload['tmp_name']);
            $mimeType = $detectedType ? ($detectedType['mime'] ?? '') : '';

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'Profile picture upload failed.';
            } elseif ($upload['size'] > 2 * 1024 * 1024) {
                $errorMessage = 'Profile picture must be 2 MB or smaller.';
            } elseif (!isset($allowedTypes[$mimeType])) {
                $errorMessage = 'Upload a JPG, PNG, or WebP profile image.';
            } else {
                $directory = dirname(__DIR__, 2) . '/assets/uploads/profile-pictures/';
                if (!is_dir($directory)) {
                    mkdir($directory, 0775, true);
                }
                $extension = $allowedTypes[$mimeType];
                $fileName = 'employee-' . $employeeId . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
                if (!move_uploaded_file($upload['tmp_name'], $directory . $fileName)) {
                    $errorMessage = 'Unable to save the profile picture.';
                } else {
                    $profilePicture = 'assets/uploads/profile-pictures/' . $fileName;
                }
            }
        }

        if ($errorMessage === '' && ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($phone) > 15 ||
            ($password !== '' && strlen($password) < 5) || $designation === '' || $department === '' ||
            $basic_salary === false || $basic_salary < 0 ||
            !in_array($role, ['employee', 'hr', 'admin'], true) ||
            !$validJoiningDate || $validJoiningDate->format('Y-m-d') !== $joining_date ||
            !$validBirthDate || $validBirthDate->format('Y-m-d') !== $birth_date ||
            $validBirthDate > new DateTimeImmutable('today'))) {
            $errorMessage = 'Please enter valid employee details and a valid birth date.';
        } elseif ($errorMessage === '') {
            $updateStmt = $conn->prepare(
                "UPDATE employees
                 SET name = ?, email = ?, phone = ?, temporary_password = ?, designation = ?,
                     department = ?, joining_date = ?, birth_date = ?, basic_salary = ?, role = ?,
                     profile_picture = COALESCE(?, profile_picture)
                 WHERE employee_id = ?"
            );
            $basicSalaryValue = (string) $basic_salary;
            $updateStmt->bind_param(
                "sssssssssssi",
                $name,
                $email,
                $phone,
                $temporary_password,
                $designation,
                $department,
                $joining_date,
                $birth_date,
                $basicSalaryValue,
                $role,
                $profilePicture,
                $employeeId
            );
            $updateStmt->execute();
            header('Location: employeePage.php');
            exit;
        }
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

            <div class="dashboard-content">
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <span class="small fw-bold text-uppercase text-secondary">People management</span>
                        <h1 class="h2 fw-bold mt-2 mb-1">Edit Employee</h1>
                        <p class="text-secondary mb-0">Edit a profile for a existing member of your team.</p>
                    </div>
                    <a class="btn btn-outline-secondary" href="employeePage.php">
                        <span aria-hidden="true">&larr;</span> Back to employees
                    </a>
                </div>
 
                <form action="" method="post" enctype="multipart/form-data" class="card border-0 shadow-sm rounded-3">
                    <div class="card-body p-3 p-md-4 p-xl-5">
                        <?php if ($errorMessage !== '') { ?>
                            <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php } ?>

                        <section aria-labelledby="profile-image-heading">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success p-2" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                </span>
                                <div>
                                    <h2 class="h5 fw-bold mb-1" id="profile-image-heading">Profile image</h2>
                                    <p class="small text-secondary mb-0">Update the photo shown for this employee.</p>
                                </div>
                            </div>
                            <?php
                                $editProfilePicture = basename((string) ($row->profile_picture ?? ''));
                                $editProfilePictureFile = dirname(__DIR__, 2) . '/assets/uploads/profile-pictures/' . $editProfilePicture;
                                $hasEditProfilePicture = $editProfilePicture !== '' && is_file($editProfilePictureFile);
                                $editInitials = strtoupper(substr(preg_replace('/\s+/', '', (string) $row->name), 0, 2));
                            ?>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                <?php if ($hasEditProfilePicture) { ?>
                                    <img id="profilePicturePreview" class="rounded-circle object-fit-cover border" alt="Profile image" width="96" height="96" src="/hr-management/assets/uploads/profile-pictures/<?php echo rawurlencode($editProfilePicture); ?>">
                                    <span id="profilePicturePlaceholder" class="d-none"></span>
                                <?php } else { ?>
                                    <img id="profilePicturePreview" class="rounded-circle object-fit-cover border d-none" alt="Profile image preview" width="96" height="96">
                                    <span id="profilePicturePlaceholder" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold flex-shrink-0" style="width:96px;height:96px;font-size:2rem;"><?php echo htmlspecialchars($editInitials ?: 'EM', ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php } ?>
                                <div class="flex-grow-1">
                                    <label class="form-label fw-semibold" for="profile_picture">Choose profile image</label>
                                    <input class="form-control" id="profile_picture" name="profile_picture" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                                    <div class="form-text">JPG, PNG or WebP. Maximum size 2 MB.</div>
                                </div>
                            </div>
                        </section>

                        <hr class="my-4 my-md-5">
 
                        <section aria-labelledby="personal-info-heading">
                            <div class="d-flex align-items-center gap-3 mb-4">
                                <span class="d-inline-flex align-items-center justify-content-center rounded-3 bg-success-subtle text-success p-2" aria-hidden="true">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                </span>
                                <div>
                                    <h2 class="h5 fw-bold mb-1" id="personal-info-heading">Personal information</h2>
                                    <p class="small text-secondary mb-0">Contact and login details for this employee.</p>
                                </div>
                            </div>
 
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="name">Full name <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" value="<?php echo htmlspecialchars($row->name, ENT_QUOTES, 'UTF-8'); ?>" id="name" name="name" maxlength="150" placeholder="e.g. Alex Morgan" autocomplete="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email">Work email <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" id="email" name="email" maxlength="150"  value="<?php echo htmlspecialchars($row->email, ENT_QUOTES, 'UTF-8'); ?>" placeholder="alex@company.com" autocomplete="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="phone">Phone number <span class="text-danger">*</span></label>
                                    <input class="form-control" value="<?php echo htmlspecialchars($row->phone, ENT_QUOTES, 'UTF-8'); ?>" type="tel" id="phone" name="phone" maxlength="15" pattern="[0-9+]{7,15}" placeholder="e.g. 01712345678" autocomplete="tel" required>
                                    <div class="form-text">Digits only (optionally starting with +), max 15 characters.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="role">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="employee" <?php echo $row->role === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                        <option value="hr" <?php echo $row->role === 'hr' ? 'selected' : ''; ?>>HR</option>
                                        <option value="admin" <?php echo $row->role === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="temporary_password">Temporary password <span class="text-danger">*</span></label>
                                    <input class="form-control" type="password" id="temporary_password" name="temporary_password" minlength="5" placeholder="Leave blank to keep current password" autocomplete="new-password">
                                    <div class="form-text">Leave blank to keep the current password.</div>
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
                                    <p class="small text-secondary mb-0">Role, salary and start-date information.</p>
                                </div>
                            </div>
 
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="department">Department <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="department" name="department" value="<?php echo htmlspecialchars($row->department, ENT_QUOTES, 'UTF-8'); ?>" maxlength="100" placeholder="e.g. Human Resources" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="designation">Designation <span class="text-danger">*</span></label>
                                    <input class="form-control" value="<?php echo htmlspecialchars($row->designation, ENT_QUOTES, 'UTF-8'); ?>" type="text" id="designation" name="designation" maxlength="150" placeholder="e.g. Software Engineer" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="basic_salary">Basic salary <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" id="basic_salary" value="<?php echo htmlspecialchars($row->basic_salary, ENT_QUOTES, 'UTF-8'); ?>" name="basic_salary" min="0" max="9999999999" step="1" placeholder="e.g. 25000" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="joining_date">Joining date <span class="text-danger">*</span></label>
                                    <input class="form-control" value="<?php echo htmlspecialchars($row->joining_date, ENT_QUOTES, 'UTF-8'); ?>" type="date" id="joining_date" name="joining_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="birth_date">Date of birth <span class="text-danger">*</span></label>
                                    <input class="form-control" value="<?php echo htmlspecialchars($_POST['birth_date'] ?? ($row->birth_date ?? ''), ENT_QUOTES, 'UTF-8'); ?>" max="<?php echo date('Y-m-d'); ?>" type="date" id="birth_date" name="birth_date" required>
                                </div>
                            </div>
                        </section>
                    </div>
 
                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2 p-3 p-md-4">
                        <!-- csrf hidden input goes here -->
                        <a class="btn btn-light" href="employeePage.php">Cancel</a>
                        <button class="btn btn-success px-4" type="submit">UPDATE</button>
                    </div>
                </form>
            </div>
           
            <?php include_once('../includes/footer.php'); ?>
        </main>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const profilePictureInput = document.getElementById('profile_picture');
        const profilePicturePreview = document.getElementById('profilePicturePreview');
        const profilePicturePlaceholder = document.getElementById('profilePicturePlaceholder');
        profilePictureInput?.addEventListener('change', () => {
            const file = profilePictureInput.files?.[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                profilePicturePreview.src = reader.result;
                profilePicturePreview.classList.remove('d-none');
                profilePicturePlaceholder?.classList.add('d-none');
            };
            reader.readAsDataURL(file);
        });
    </script>
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
