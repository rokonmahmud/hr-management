
<?php 

    if($_SERVER["REQUEST_METHOD"] == "POST"){
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $emp_code = $_POST['emp_code'];
        $designation = $_POST['designation'];
        $department = $_POST['department'];
        $status = $_POST['status'];
        $join_date = $_POST['join_date'];

        include_once("./config/db.php");
        $conn->query("INSERT INTO employees(id, user_id, department_id , designation_id, emp_code, phone, join_date, $status ) ");


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
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>
    <div class="app-shell">
        <?php 
            include_once('includes/sidebar.php')
        ?>

        <main class="main-content">
            <?php 
                include_once('includes/topbar.php')
            ?> 

              <div class="app-shell">
        <!-- sidebar include goes here -->
 
        <main class="main-content">
            <!-- topbar include goes here -->
 
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
                        <!-- error alert goes here -->
 
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
                                    <input class="form-control" type="text" id="name" name="name" maxlength="100" placeholder="e.g. Alex Morgan" autocomplete="name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="email">Work email <span class="text-danger">*</span></label>
                                    <input class="form-control" type="email" id="email" name="email" maxlength="150" placeholder="alex@company.com" autocomplete="email" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="phone">Phone number</label>
                                    <input class="form-control" type="tel" id="phone" name="phone" maxlength="20" placeholder="+1 (555) 000-0000" autocomplete="tel">
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
                                    <input class="form-control" type="text" id="emp_code" name="emp_code" maxlength="20" placeholder="e.g. EMP-001" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="join_date">Joining date <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" id="join_date" name="join_date" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="department">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="" disabled selected>Select department</option>
                                        <!-- department options go here -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="designation">Designation <span class="text-danger">*</span></label>
                                    <select class="form-select" id="designation" name="designation" required>
                                        <option value="" disabled selected>Select designation</option>
                                        <!-- designation options go here -->
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-semibold" for="status">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="active" selected>Active</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
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
           
            <?php include_once("includes/footer.php") ?>
        </main>
    </div>
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
