
<?php 
include_once(__DIR__ . "/../config/db.php");



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
        <!-- Sidebar goes here -->

        <main class="main-content">
            <!-- Topbar goes here -->

            <div class="dashboard-content">

                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Employees</h1>
                        <p class="text-secondary mb-0">Everyone who works with your team.</p>
                    </div>
                    <a class="btn btn-success px-4" href="addEmployee.html">+ Add employee</a>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-4 py-3">
                        <h2 class="h6 fw-bold mb-0">All employees</h2>
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">3 total</span>
                    </div>
                    <?php 

                        $data = $conn -> query("SELECT * FROM employees");
                    
                    ?>
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
                                            ?>
                                            <tr>
                                                <td class="ps-4 py-3">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <span class="bg-success-subtle text-success-emphasis fw-bold rounded-3 px-3 py-2">AM</span>
                                                        <div>
                                                            <div class="fw-bold"><?php echo $employees['name'] ?></div>
                                                            <div class="small text-secondary">alex@company.com</div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="fw-semibold text-secondary">EMP-001</td>
                                                <td><span class="badge rounded-pill text-bg-light border fw-medium px-3 py-2">Engineering</span></td>
                                                <td>Developer</td>
                                                <td><span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2">&#9679; Active</span></td>
                                                <td class="text-end pe-4 text-nowrap">
                                                    <a class="btn btn-sm btn-outline-success" href="#" aria-label="Edit employee"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M4 20h4L19 9l-4-4L4 16v4Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg></a>
                                                    <a class="btn btn-sm btn-outline-danger" href="#" aria-label="Delete employee"><svg width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M5 7h14M10 11v6M14 11v6M6.5 7l1 12h9l1-12M9.5 7V5h5v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
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
            <?php include_once("includes/footer.php") ?>
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
