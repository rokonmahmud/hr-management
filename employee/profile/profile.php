<?php
session_start();

if (($_SESSION['role'] ?? '') !== 'employee' || empty($_SESSION['employee_id'])) {
    header("Location: ../../index.php");
    exit;
}

include_once("../../config/db.php");
/** @var mysqli $conn */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$employeeId = (int) $_SESSION['employee_id'];
$errorMessage = '';
$notice = '';

$profilePictureDirectory = "../../assets/uploads/profile-pictures/";

if (empty($_SESSION['profile_csrf_token'])) {
    $_SESSION['profile_csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['profile_csrf_token'], $token)) {
        $errorMessage = 'Your session expired. Refresh the page and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $birthDate = $_POST['birth_date'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $validBirthDate = DateTimeImmutable::createFromFormat('!Y-m-d', $birthDate);
        $profilePicture = null;

        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload = $_FILES['profile_picture'];
            $allowedTypes = [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ];
            $detectedType = @getimagesize($upload['tmp_name']);
            $mimeType = $detectedType ? ($detectedType['mime'] ?? '') : '';

            if ($upload['error'] !== UPLOAD_ERR_OK) {
                $errorMessage = 'Profile picture upload failed.';
            } elseif ($upload['size'] > 2 * 1024 * 1024) {
                $errorMessage = 'Profile picture must be 2 MB or smaller.';
            } elseif (!isset($allowedTypes[$mimeType])) {
                $errorMessage = 'Upload a JPG, PNG, or WebP image.';
            } else {
                if (!is_dir($profilePictureDirectory)) {
                    mkdir($profilePictureDirectory, 0775, true);
                }
                $extension = $allowedTypes[$mimeType];
                $fileName = 'employee-' . $employeeId . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
                if (!move_uploaded_file($upload['tmp_name'], $profilePictureDirectory . $fileName)) {
                    $errorMessage = 'Unable to save the profile picture.';
                } else {
                    $profilePicture = 'assets/uploads/profile-pictures/' . $fileName;
                }
            }
        }

        if ($errorMessage === '' && (!$validBirthDate || $validBirthDate->format('Y-m-d') !== $birthDate ||
            $validBirthDate > new DateTimeImmutable('today'))) {
            $errorMessage = 'Enter a valid birth date.';
        } elseif ($errorMessage === '' && ($name === '' || strlen($name) > 150)) {
            $errorMessage = 'Enter a valid name.';
        } elseif ($errorMessage === '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150)) {
            $errorMessage = 'Enter a valid email address.';
        } elseif ($errorMessage === '' && ($phone !== '' && strlen($phone) > 15)) {
            $errorMessage = 'Enter a valid phone number.';
        } elseif ($errorMessage === '' && ($password !== '' && (strlen($password) < 5 || $password !== $confirmPassword))) {
            $errorMessage = 'Password must be at least 5 characters and both password fields must match.';
        } elseif ($errorMessage === '') {
            $emailStmt = $conn->prepare(
                "SELECT employee_id FROM employees
                 WHERE email = ? AND employee_id <> ? LIMIT 1"
            );
            $emailStmt->bind_param("si", $email, $employeeId);
            $emailStmt->execute();
            if ($emailStmt->get_result()->num_rows > 0) {
                $errorMessage = 'This email address is already in use.';
            } else {
                $conn->begin_transaction();
                try {
                    if ($password === '') {
                        $stmt = $conn->prepare(
                            "UPDATE employees
                             SET name = ?, email = ?, phone = ?, birth_date = ?,
                                 profile_picture = COALESCE(?, profile_picture)
                             WHERE employee_id = ?"
                        );
                        $stmt->bind_param("sssssi", $name, $email, $phone, $birthDate, $profilePicture, $employeeId);
                        $stmt->execute();

                        $userStmt = $conn->prepare(
                            "UPDATE users SET name = ?, email = ? WHERE user_id = (
                                SELECT user_id FROM employees WHERE employee_id = ?
                            )"
                        );
                        $userStmt->bind_param("ssi", $name, $email, $employeeId);
                        $userStmt->execute();
                    } else {
                        $passwordHash = md5($password);
                        $stmt = $conn->prepare(
                            "UPDATE employees SET name = ?, email = ?, phone = ?, temporary_password = ?,
                             birth_date = ?, profile_picture = COALESCE(?, profile_picture)
                             WHERE employee_id = ?"
                        );
                        $stmt->bind_param("ssssssi", $name, $email, $phone, $passwordHash, $birthDate, $profilePicture, $employeeId);
                        $stmt->execute();

                        $userStmt = $conn->prepare(
                            "UPDATE users SET name = ?, email = ?, password = ? WHERE user_id = (
                                SELECT user_id FROM employees WHERE employee_id = ?
                            )"
                        );
                        $userStmt->bind_param("sssi", $name, $email, $passwordHash, $employeeId);
                        $userStmt->execute();
                    }
                    $conn->commit();
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                    if ($profilePicture !== null) {
                        $_SESSION['profile_picture'] = $profilePicture;
                    }
                    $notice = 'Profile updated successfully.';
                } catch (Throwable $exception) {
                    $conn->rollback();
                    $errorMessage = 'Unable to update your profile. Please try again.';
                }
            }
        }
    }
}

$stmt = $conn->prepare(
    "SELECT employee_id, name, email, phone, department, designation, joining_date, birth_date,
            basic_salary, profile_picture
     FROM employees
     WHERE employee_id = ?"
);
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();

if (!$profile) {
    http_response_code(404);
    exit('Employee profile is not available.');
}

$profilePicture = $profile['profile_picture'] ?? '';
if ($profilePicture !== '') {
    $_SESSION['profile_picture'] = $profilePicture;
}

$initials = strtoupper(substr(preg_replace('/\s+/', '', $profile['name']), 0, 2));
if ($initials === '') {
    $initials = 'EM';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Profile | PeopleDesk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin.css">
</head>
<body>
<div class="app-shell">
    <?php include_once('../includes/sidebar.php'); ?>
    <main class="main-content">
        <?php $pageTitle = 'My Profile'; include_once('../includes/topbar.php'); ?>
        <div class="dashboard-content">
            <div class="mb-4">
                <h1 class="h2 fw-bold mb-1">My Profile</h1>
                <p class="text-secondary mb-0">View and update your basic account information.</p>
            </div>

            <?php if ($errorMessage !== '') { ?>
                <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php } ?>
            <?php if ($notice !== '') { ?>
                <div class="alert alert-success" role="alert"><?php echo htmlspecialchars($notice); ?></div>
            <?php } ?>

            <div class="row g-4">
                <div class="col-12 col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body text-center p-4">
                            <h2 class="h6 fw-bold mb-3 text-start">Profile image</h2>
                            <div class="mb-3">
                                <?php if ($profilePicture !== '') { ?>
                                    <img id="profilePicturePreview"
                                         class="rounded-circle object-fit-cover mb-3 border"
                                         src="../../<?php echo htmlspecialchars($profilePicture, ENT_QUOTES, 'UTF-8'); ?>"
                                         alt="Profile picture"
                                         style="width: 96px; height: 96px;">
                                <?php } else { ?>
                                    <span id="profilePicturePreview"
                                          class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold mb-3"
                                          style="width: 96px; height: 96px; font-size: 2rem;">
                                        <?php echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                <?php } ?>
                            </div>
                            <h2 class="h5 fw-bold mb-1"><?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?></h2>
                            <p class="text-secondary mb-3"><?php echo htmlspecialchars($profile['designation'], ENT_QUOTES, 'UTF-8'); ?></p>
                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">Active Employee</span>

                            <hr>
                            <div class="text-start mb-3">
                                <label class="form-label fw-semibold" for="profilePicture">Change profile image</label>
                                <input class="form-control" id="profilePicture" name="profile_picture" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" form="profileForm">
                                <div class="form-text">JPG, PNG or WebP. Maximum size 2 MB.</div>
                            </div>
                            <div class="text-start small">
                                <div class="d-flex justify-content-between py-2"><span class="text-secondary">Employee ID</span><strong>EMP-<?php echo (int) $profile['employee_id']; ?></strong></div>
                                <div class="d-flex justify-content-between py-2"><span class="text-secondary">Department</span><strong><?php echo htmlspecialchars($profile['department'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                                <div class="d-flex justify-content-between py-2"><span class="text-secondary">Joining date</span><strong><?php echo date('M d, Y', strtotime($profile['joining_date'])); ?></strong></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4">
                        <div class="card-header bg-white border-bottom px-4 py-3">
                            <h2 class="h6 fw-bold mb-0">Personal Information</h2>
                        </div>
                        <div class="card-body p-4">
                            <form id="profileForm" method="post" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['profile_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="name">Full name</label>
                                        <input class="form-control" id="name" name="name" maxlength="150" value="<?php echo htmlspecialchars($profile['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="email">Email address</label>
                                        <input class="form-control" id="email" name="email" type="email" maxlength="150" value="<?php echo htmlspecialchars($profile['email'], ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="phone">Phone number</label>
                                        <input class="form-control" id="phone" name="phone" maxlength="15" value="<?php echo htmlspecialchars($profile['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="birth_date">Date of birth</label>
                                        <input class="form-control" type="date" id="birth_date" name="birth_date" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($profile['birth_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="department">Department</label>
                                        <input class="form-control" id="department" value="<?php echo htmlspecialchars($profile['department'], ENT_QUOTES, 'UTF-8'); ?>" disabled>
                                    </div>
                                </div>

                                <hr class="my-4">
                                <h3 class="h6 fw-bold mb-3">Change password <span class="text-secondary fw-normal">(optional)</span></h3>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="password">New password</label>
                                        <input class="form-control" id="password" name="password" type="password" minlength="5" autocomplete="new-password" placeholder="Leave blank to keep current">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="confirmPassword">Confirm password</label>
                                        <input class="form-control" id="confirmPassword" name="confirm_password" type="password" minlength="5" autocomplete="new-password">
                                    </div>
                                </div>

                                <div class="d-flex justify-content-end mt-4">
                                    <button class="btn btn-success px-4" type="submit">Save changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php include_once("../includes/footer.php"); ?>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    const profilePictureInput = document.getElementById('profilePicture');
    let profilePicturePreview = document.getElementById('profilePicturePreview');

    profilePictureInput?.addEventListener('change', () => {
        const file = profilePictureInput.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = () => {
            if (profilePicturePreview?.tagName === 'IMG') {
                profilePicturePreview.src = reader.result;
            } else {
                const previewImage = document.createElement('img');
                previewImage.id = 'profilePicturePreview';
                previewImage.className = 'rounded-circle object-fit-cover mb-3 border';
                previewImage.alt = 'Profile picture preview';
                previewImage.style.cssText = 'width: 96px; height: 96px;';
                previewImage.src = reader.result;
                profilePicturePreview.replaceWith(previewImage);
                profilePicturePreview = previewImage;
            }
        };
        reader.readAsDataURL(file);
    });
</script>
</body>
</html>
