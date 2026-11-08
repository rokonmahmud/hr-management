<?php 
session_start();

   $error = "";

   if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/admin_dashboard.php");
    } else if ($_SESSION['role'] == 'hr') {
        header("Location: hr/hr_dashboard.php");
    } else if ($_SESSION['role'] == 'employee') {
        header("Location: employee/employee_dashboard.php");
    }
    exit;
    } 

   if($_SERVER['REQUEST_METHOD'] == "POST"){
      extract($_POST);
      
      $hashPass = md5($password);
      
      include_once("config/db.php");
      $result = $conn -> query("SELECT * FROM users WHERE email ='$email' AND password = '$hashPass'");

      if($result -> num_rows > 0){
        $row = $result -> fetch_assoc();
        $_SESSION['email'] = $email;
        $_SESSION['name'] = $row['name'];
        $_SESSION['role'] = $row['role'];

        if($row['role'] == 'admin'){
            $_SESSION['url'] = "admin/index.php";
            header("Location: admin/admin_dashboard.php");
            exit;
        }elseif($row['role'] == 'employee'){
            $_SESSION['url'] = "employee/index.php";
            header("Location: employee/employee_dashboard.php");
            exit;
        }elseif($row['role'] == 'hr'){
            $_SESSION['url'] = "hr/index.php";
            header("Location: hr/hr_dashboard.php");
            exit;
        }
      }else{
        $error = "Email or password is wrong.";
      }
      
   }

?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Sign in | PeopleDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/auth.css">
</head>
<body>

    <main class="auth-layout container-fluid vh-100 min-vh-100 overflow-hidden p-0">
        <div class="row g-0 h-100">
            <aside class="brand-panel col-md-5 d-none d-md-flex flex-column">
                <a class="brand d-inline-flex align-items-center gap-2" href="../index.php" aria-label="PeopleDesk home">
                    <span class="brand-mark d-grid place-items-center" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M7 23.5V10.8l9-5.3 9 5.3v12.7l-9 5.3-9-5.3Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                            <path d="m11.5 16.3 3.1 3.1 6-7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>people<span class="brand-light">desk</span></span>
                </a>

                <div class="brand-copy">
                    <span class="eyebrow"><span class="eyebrow-dot"></span> PEOPLE OPERATIONS, MADE SIMPLE</span>
                    <h1>Great work<br>starts with<br><span>great people.</span></h1>
                    <p>One calm, clear place to manage your team, time and growth.</p>
                </div>

                <div class="preview-card">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-grid gap-1">
                            <span class="preview-label">TEAM OVERVIEW</span>
                            <strong>Your people, in sync.</strong>
                        </div>
                        <span class="preview-more" aria-hidden="true">···</span>
                    </div>
                    <div class="preview-stats d-flex justify-content-between">
                        <div class="preview-stat"><span>At work today</span><strong>24 <small>/ 28</small></strong></div>
                        <div class="preview-stat"><span>On leave</span><strong>04</strong></div>
                    </div>
                    <div class="preview-avatars d-flex align-items-center" aria-hidden="true">
                        <span class="avatar avatar-one">AM</span>
                        <span class="avatar avatar-two">JR</span>
                        <span class="avatar avatar-three">SK</span>
                        <span class="avatar avatar-four">TN</span>
                        <span class="avatar-count">+20</span>
                        <span class="online-note d-flex align-items-center gap-1"><i></i> Team is looking good</span>
                    </div>
                </div>
                <p class="brand-footer mt-auto">A little more human, every day.</p>
            </aside>

            <section class="form-panel col-12 col-md-7 d-flex flex-column">
                <div class="form-wrap m-auto">
                    <a class="mobile-brand d-inline-flex align-items-center gap-2" href="../index.php" aria-label="PeopleDesk home">
                        <span class="brand-mark d-grid place-items-center" aria-hidden="true">
                            <svg viewBox="0 0 32 32" fill="none">
                                <path d="M7 23.5V10.8l9-5.3 9 5.3v12.7l-9 5.3-9-5.3Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                                <path d="m11.5 16.3 3.1 3.1 6-7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <span>people<span class="brand-light">desk</span></span>
                    </a>
                    <div class="form-heading">
                        <span class="form-kicker">WELCOME BACK</span>
                        <h2>Sign in to your account</h2>
                        <p>Good to see you again. Let's get you back to work.</p>
                    </div>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label" for="email">Work email</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="14" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="m5 7 7 5.5L19 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <input class="form-control" id="email" name="email" type="email" autocomplete="email" placeholder="you@company.com" required>
                            </div>
                        </div>

                        <div class="mb-2">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0" for="password">Password</label>
                                <a class="text-link" href="#" aria-label="Reset your password">Forgot password?</a>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><rect x="4.5" y="10" width="15" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M12 14.5v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                </span>
                                <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" placeholder="Enter your password" required>
                            </div>
                        </div>

                        <div class="form-check my-3">
                            <input class="form-check-input" id="remember" name="remember" type="checkbox">
                            <label class="form-check-label" for="remember">Keep me signed in</label>
                        </div>

                        <button class="btn btn-primary w-100 submit-button" type="submit">Sign in <span aria-hidden="true">→</span></button>
                    </form>

                    <p class="form-switch">New to PeopleDesk? <a href="signup.php">Create an account</a></p>
                    <p class="secure-note d-flex align-items-center justify-content-center gap-1">
                        <svg viewBox="0 0 16 16" fill="none" aria-hidden="true"><path d="M8 1.8 13 4v3.7c0 3.1-2.1 5.4-5 6.5-2.9-1.1-5-3.4-5-6.5V4l5-2.2Z" stroke="currentColor" stroke-width="1.3" stroke-linejoin="round"/><path d="m5.8 7.8 1.5 1.5 2.9-3" stroke="currentColor" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Your information is private and secure
                    </p>
                </div>
                <footer class="panel-footer d-flex align-items-center gap-3">
                    <span class="me-auto">© 2026 PeopleDesk</span><a href="#">Privacy</a><a href="#">Help center</a>
                </footer>
            </section>
        </div>
    </main>
    <?php 

        if($error != ""){
            echo "<script>alert('$error');</script>";
        }
    
    ?>
</body>
</html>