<?php session_start();   ?>

<?php 

include_once __DIR__ . '/../../config/db.php';
$empId = (int) $_SESSION['employee_id'];

$data = $conn -> query("SELECT * FROM leave_requests WHERE employee_id = $empId ORDER BY leave_id DESC");

$countData = $data -> num_rows;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Leave requests | PeopleDesk</title>
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
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Leave requests</h1>
                        <p class="text-secondary mb-0">Review your submitted time-off requests.</p>
                    </div>
                    <a class="btn btn-success px-4" href="applyLeave.php">+ Apply for leave</a>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom d-flex align-items-center justify-content-between px-4 py-3">
                        <h2 class="h6 fw-bold mb-0">All requests</h2>
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2"><?php echo $countData; ?> Requests</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-semibold small">Leave ID</th>
                                    <th class="text-secondary fw-semibold small">Leave type</th>
                                    <th class="text-secondary fw-semibold small">From</th>
                                    <th class="text-secondary fw-semibold small">To</th>
                                    <th class="text-secondary fw-semibold small">Total days</th>
                                    <th class="text-secondary fw-semibold small">Reason</th>
                                    <th class="text-secondary fw-semibold small">Status</th>
                                    <th class="text-secondary fw-semibold small">Approved by</th>
                                    <th class="pe-4 text-secondary fw-semibold small">Admin remarks</th>
                                </tr>
                            </thead>
                                <tbody>
                                    <?php
                                       

                                        while ($leave = $data -> fetch_assoc()) {
                                            $badge = 'bg-warning-subtle text-warning-emphasis';
                                            if ($leave['status'] === 'Approved') $badge = 'bg-success-subtle text-success-emphasis';
                                            if ($leave['status'] === 'Rejected') $badge = 'bg-danger-subtle text-danger-emphasis';
                                    ?>
                                        <tr>
                                            <td class="ps-4 py-3 fw-semibold">#LR-<?php echo str_pad($leave['leave_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                            <td><span class="badge rounded-pill text-bg-light border fw-medium px-3 py-2"><?php echo ucfirst($leave['leave_type']) ?> leave</span></td>
                                            <td><?php echo date('M d, Y', strtotime($leave['start_date'])) ?></td>
                                            <td><?php echo date('M d, Y', strtotime($leave['end_date'])) ?></td>
                                            <td><?php echo $leave['total_days'] ?></td>
                                            <td><?php echo htmlspecialchars($leave['leave_reason']) ?></td>
                                            <td><span class="badge rounded-pill <?php echo $badge ?> px-3 py-2"><?php echo $leave['status'] ?></span></td>
                                            <td class="text-secondary"><?php echo $leave['approved_by'] ?? '—' ?></td>
                                            <td class="pe-4 text-secondary"><?php echo $leave['admin_remarks'] ?? 'Awaiting review' ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                        </table>
                    </div>
                </div>
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
