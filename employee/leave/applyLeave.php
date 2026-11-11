
<?php 

    session_start();
    if($_SERVER['REQUEST_METHOD'] === 'POST') {
        $leaveType = $_POST['leave_type'];
        $startDate = $_POST['start_date'];
        $endDate = $_POST['end_date'];
        $reason = $_POST['reason'];
        $handoverNotes = $_POST['handover_notes'];
        $total_days = (new DateTime($startDate))->diff(new DateTime($endDate))->days + 1;
        $employeeId = $_SESSION['employee_id'];

        if (strtotime($endDate) < strtotime($startDate)) {
            $error = "End date cannot be before start date.";
        }
        
        $status = 'Pending';
        $approved_by = NULL;
        $admin_remarks = NULL;

        include_once('../../config/db.php');
        $conn -> query("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, total_days, reason, handover_notes, status, approved_by, admin_remarks) VALUES ($employeeId, '$leaveType', '$startDate', '$endDate', $total_days, '$reason', '$handoverNotes', '$status', $approved_by, $admin_remarks)");

        if ($conn -> affected_rows > 0) {
            header("Location: leave_request.php");
            exit();
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
            include_once('../includes/sidebar.php')
        ?>

        <main class="main-content">
            <?php 
                include_once('../includes/topbar.php')
            ?> 

            <div class="dashboard-content">
                <section class="welcome-row">
    <main class="container py-4 py-lg-5">
        <nav aria-label="breadcrumb">
           
        </nav>

        <div class="row align-items-end g-3 mb-4">
            <div class="col">
                
                <h1 class="h2 fw-bold mb-2">Apply for leave</h1>
                <p class="text-secondary mb-0">Share your time-off details and send a request for approval.</p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <section class="card border-0 shadow-sm" aria-labelledby="leave-form-title">
                    <div class="card-body p-4 p-lg-5">
                        <div class="mb-4">
                            <h2 class="h5 fw-bold mb-1" id="leave-form-title">Leave request details</h2>
                            <p class="text-secondary small mb-0">Fields marked with <span class="text-danger">*</span> are required.</p>
                        </div>

                        <form>
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="leave-type">Leave type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="leave-type" name="leave_type" required>
                                        <option value="" selected disabled>Select a leave type</option>
                                        <option value="annual">Annual leave</option>
                                        <option value="sick">Sick leave</option>
                                        <option value="personal">Personal leave</option>
                                        <option value="unpaid">Unpaid leave</option>
                                    </select>
                                </div>

                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="start-date">Start date <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" id="start-date" name="start_date" required>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label class="form-label fw-semibold" for="end-date">End date <span class="text-danger">*</span></label>
                                    <input class="form-control" type="date" id="end-date" name="end_date" required>
                                    <div class="form-text">Include your first and last day away.</div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="reason">Reason for leave <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="reason" name="reason" rows="4" placeholder="Add a short explanation for your request..." required></textarea>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold" for="handover">Handover notes <span class="text-secondary fw-normal">(optional)</span></label>
                                    <textarea class="form-control" id="handover" name="handover_notes" rows="3" placeholder="Let your team know about any tasks or coverage arrangements..."></textarea>
                                </div>
                            </div>

                            <div class="alert alert-success d-flex gap-2 align-items-start mt-4 mb-0" role="note">
                                <span class="fw-bold" aria-hidden="true">i</span>
                                <div><strong>Before you submit:</strong> Please allow your manager enough time to review your request. Your leave is not confirmed until it has been approved.</div>
                            </div>

                            <div class="d-flex flex-column-reverse flex-sm-row justify-content-sm-end gap-2 mt-4 pt-3 border-top">
                                <button class="btn btn-outline-secondary" type="reset">Clear form</button>
                                <button class="btn btn-success" type="submit">Submit request</button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>

            <aside class="col-12 col-xl-4" aria-label="Leave information">
                <section class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-1">Your leave balance</h2>
                        <p class="text-secondary small mb-3">Available for this year</p>
                        <p class="mb-3"><span class="display-6 text-success fw-bold">12</span> <span class="text-secondary text-success ">days available</span></p>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Annual leave</span><strong>8 days</strong></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Sick leave</span><strong>3 days</strong></li>
                            <li class="list-group-item px-0 d-flex justify-content-between"><span>Personal leave</span><strong>1 day</strong></li>
                        </ul>
                    </div>
                </section>

                <section class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 fw-bold mb-2">What happens next?</h2>
                        <p class="text-secondary small">Your request will follow the usual approval process.</p>
                        <ol class="list-group list-group-numbered list-group-flush">
                            <li class="list-group-item px-0">
                                <strong>Request submitted</strong>
                                <div class="small text-secondary">Your manager receives your request.</div>
                            </li>
                            <li class="list-group-item px-0">
                                <strong>Manager review</strong>
                                <div class="small text-secondary">Your dates and leave balance are reviewed.</div>
                            </li>
                            <li class="list-group-item px-0">
                                <strong>Decision shared</strong>
                                <div class="small text-secondary">You’ll be notified once it’s approved or declined.</div>
                            </li>
                        </ol>
                    </div>
                </section>
            </aside>
        </div>

        <footer class="d-flex flex-column flex-sm-row justify-content-between gap-2 border-top mt-5 pt-3 text-secondary small">
            <span>PeopleDesk Employee</span>
            <span>Employee workspace · Data shown for demonstration</span>
        </footer>
    </main>
             

               
            </div>
            <?php include_once("../includes/footer.php") ?>
        </main>
    </div>
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
