
<?php
include_once("../../config/db.php");
/** @var mysqli $conn */

$errorMessage = '';
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['temporary_password'] ?? '';
    $designation = trim($_POST['designation'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $joiningDate = $_POST['joining_date'] ?? '';
    $birthDate = $_POST['birth_date'] ?? '';
    $basicSalary = filter_var($_POST['basic_salary'] ?? null, FILTER_VALIDATE_FLOAT);
    $role = $_POST['role'] ?? '';
    $profilePicture = null;
    $validBirthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
    $validJoiningDate = DateTimeImmutable::createFromFormat('!Y-m-d', $joiningDate);

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
        }
    }

    if ($errorMessage === '' && ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($phone) > 15 ||
        strlen($password) < 5 || $designation === '' || $department === '' ||
        $basicSalary === false || $basicSalary < 0 ||
        !in_array($role, ['employee', 'hr', 'admin'], true) ||
        !$validJoiningDate || $validJoiningDate->format('Y-m-d') !== $joiningDate ||
        !$validBirthDate || $validBirthDate->format('Y-m-d') !== $birthDate ||
        $validBirthDate > new DateTimeImmutable('today'))) {
        $errorMessage = 'Please enter valid employee details and a valid birth date.';
    } elseif ($errorMessage === '') {
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload = $_FILES['profile_picture'];
            $allowedTypes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $detectedType = getimagesize($upload['tmp_name']);
            $extension = $allowedTypes[$detectedType['mime']];
            $directory = dirname(__DIR__, 2) . '/assets/uploads/profile-pictures/';
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
            $fileName = 'employee-new-' . bin2hex(random_bytes(8)) . '.' . $extension;
            if (!move_uploaded_file($upload['tmp_name'], $directory . $fileName)) {
                $errorMessage = 'Unable to save the profile picture.';
            } else {
                $profilePicture = 'assets/uploads/profile-pictures/' . $fileName;
            }
        }
    }

    if ($errorMessage === '') {
        $temporaryPassword = md5($password);
        $stmt = $conn->prepare(
            "INSERT INTO employees
                (name, email, temporary_password, department, designation, basic_salary,
                 joining_date, birth_date, role, phone, profile_picture)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->bind_param(
            "sssssdsssss",
            $name,
            $email,
            $temporaryPassword,
            $department,
            $designation,
            $basicSalary,
            $joiningDate,
            $birthDate,
            $role,
            $phone,
            $profilePicture
        );
        $stmt->execute();
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
                        <h1 class="h2 fw-bold mt-2 mb-1">Add employee</h1>
                        <p class="text-secondary mb-0">Create a profile for a new member of your team.</p>
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
                                    <p class="small text-secondary mb-0">Add an optional photo for this employee.</p>
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-sm-row align-items-sm-center gap-3">
                                <img id="profilePicturePreview" class="rounded-circle object-fit-cover border d-none" alt="Profile image preview" width="96" height="96">
                                <span id="profilePicturePlaceholder" class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold flex-shrink-0" style="width:96px;height:96px;font-size:2rem;">EM</span>
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
                                    <input class="form-control" type="text" id="name" name="name" maxlength="150" placeholder="e.g. Alex Morgan" autocomplete="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email">Work email <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" id="email" name="email" maxlength="150" placeholder="alex@company.com" autocomplete="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="phone">Phone number <span class="text-danger">*</span></label>
                                    <input class="form-control" type="tel" id="phone" name="phone" maxlength="15" pattern="[0-9+]{7,15}" placeholder="e.g. 01712345678" autocomplete="tel" required>
                                    <div class="form-text">Digits only (optionally starting with +), max 15 characters.</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="birth_date">Date of birth <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" id="birth_date" name="birth_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($_POST['birth_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="role">Role <span class="text-danger">*</span></label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="employee" <?php echo ($_POST['role'] ?? 'employee') === 'employee' ? 'selected' : ''; ?>>Employee</option>
                                        <option value="hr" <?php echo ($_POST['role'] ?? '') === 'hr' ? 'selected' : ''; ?>>HR</option>
                                        <option value="admin" <?php echo ($_POST['role'] ?? '') === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="temporary_password">Temporary password <span class="text-danger">*</span></label>
                                    <input class="form-control" type="password" id="temporary_password" name="temporary_password" minlength="5" placeholder="Enter a temporary password" autocomplete="new-password" required>
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
                                    <p class="small text-secondary mb-0">Role, salary and start-date information.</p>
                                </div>
                            </div>
 
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="department">Department <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="department" name="department" maxlength="100" placeholder="e.g. Human Resources" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="designation">Designation <span class="text-danger">*</span></label>
                                    <input class="form-control" type="text" id="designation" name="designation" maxlength="150" placeholder="e.g. Software Engineer" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="basic_salary">Basic salary <span class="text-danger">*</span></label>
                                    <input class="form-control" type="number" id="basic_salary" name="basic_salary" min="0" max="9999999999" step="1" placeholder="e.g. 25000" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="joining_date">Joining date <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" id="joining_date" name="joining_date" value="<?php echo htmlspecialchars($_POST['joining_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                </div>
                            </div>
                        </section>
                    </div>
 
                    <div class="card-footer bg-white border-top d-flex justify-content-end gap-2 p-3 p-md-4">
                        <!-- csrf hidden input goes here -->
                        <a class="btn btn-light" href="employeePage.php">Cancel</a>
                        <button class="btn btn-success px-4" type="submit">Add employee</button>
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
                profilePicturePlaceholder.classList.add('d-none');
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
