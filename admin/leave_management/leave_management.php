<?php
include_once(__DIR__ . "/../../config/db.php");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ---------- Approve / Reject handle ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_id'], $_POST['action'])) {
    $leave_id = (int)$_POST['leave_id'];
    $action   = $_POST['action'];
    $remarks  = trim($_POST['admin_remarks'] ?? '');
    $remarks  = ($remarks === '') ? null : $remarks;

    if (in_array($action, ['approve', 'reject'], true)) {
        $new_status = ($action === 'approve') ? 'Approved' : 'Rejected';
        $admin_name = $_SESSION['name'] ?? 'Admin';   // session key tomar login onujayi bodle nio

        $stmt = $conn->prepare(
            "UPDATE leave_requests
             SET status = ?, approved_by = ?, admin_remarks = ?
             WHERE leave_id = ? AND status = 'Pending'"
        );
        $stmt->bind_param("sssi", $new_status, $admin_name, $remarks, $leave_id);
        $stmt->execute();
    }

    // Refresh e abar submit na hoy, filter o thake
    header("Location: " . $_SERVER['REQUEST_URI']);
    exit;
}

// ---------- Status filter ----------
$filter = $_GET['status'] ?? 'all';
if (!in_array($filter, ['all', 'pending', 'approved', 'rejected'], true)) {
    $filter = 'all';
}

$sql = "SELECT lr.*, e.name AS employee_name, e.designation
        FROM leave_requests lr
        JOIN employees e ON lr.employee_id = e.employee_id";

if ($filter === 'all') {
    $data = $conn->query($sql . " ORDER BY lr.leave_id DESC");
} else {
    $stmt = $conn->prepare($sql . " WHERE LOWER(lr.status) = ? ORDER BY lr.leave_id DESC");
    $stmt->bind_param("s", $filter);
    $stmt->execute();
    $data = $stmt->get_result();
}

// ---------- Summary counts ----------
$counts = ['pending' => 0, 'approved' => 0, 'rejected' => 0];
$res = $conn->query("SELECT LOWER(status) AS s, COUNT(*) AS c FROM leave_requests GROUP BY LOWER(status)");
foreach ($res as $r) {
    if (isset($counts[$r['s']])) {
        $counts[$r['s']] = (int)$r['c'];
    }
}
$total = array_sum($counts);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Leave Management | PeopleDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/admin.css">
    <style>
        .leave-avatar {
            width: 40px; height: 40px; border-radius: 12px;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: .85rem;
            background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis);
        }
        .stat-icon {
            width: 44px; height: 44px; border-radius: 12px;
            display: flex; align-items: center; justify-content: center; font-size: 1.2rem;
        }
        .reason-cell { max-width: 220px; }
    </style>
</head>
<body>
    <div class="app-shell">
        <?php include_once('../includes/sidebar.php'); ?>

        <main class="main-content">
            <?php include_once('../includes/topbar.php'); ?>

            <div class="dashboard-content">

                <!-- Page header -->
                <div class="d-flex flex-column flex-sm-row align-items-sm-end justify-content-between gap-3 mb-4">
                    <div>
                        <h1 class="h2 fw-bold mb-1">Leave Management</h1>
                        <p class="text-secondary mb-0">Manage and track employee leave requests.</p>
                    </div>
                </div>

                <!-- Summary cards -->
                <div class="row g-3 mb-4">
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-primary-subtle text-primary-emphasis">&#9776;</div>
                                <div>
                                    <div class="text-secondary small">Total</div>
                                    <div class="h4 fw-bold mb-0"><?= $total ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-warning-subtle text-warning-emphasis">&#9203;</div>
                                <div>
                                    <div class="text-secondary small">Pending</div>
                                    <div class="h4 fw-bold mb-0"><?= $counts['pending'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-success-subtle text-success-emphasis">&#10003;</div>
                                <div>
                                    <div class="text-secondary small">Approved</div>
                                    <div class="h4 fw-bold mb-0"><?= $counts['approved'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-body d-flex align-items-center gap-3">
                                <div class="stat-icon bg-danger-subtle text-danger-emphasis">&#10005;</div>
                                <div>
                                    <div class="text-secondary small">Rejected</div>
                                    <div class="h4 fw-bold mb-0"><?= $counts['rejected'] ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Table card -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="card-header bg-white border-bottom px-4 py-3">
                        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                            <h2 class="h6 fw-bold mb-0">All Leave Requests
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis px-3 py-2 ms-2">
                                    <?= $data->num_rows ?> total
                                </span>
                            </h2>

                            <!-- Status filter -->
                            <ul class="nav nav-pills small gap-1">
                                <?php foreach (['all' => 'All', 'pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $key => $label) { ?>
                                    <li class="nav-item">
                                        <a class="nav-link py-1 px-3 <?= $filter === $key ? 'active' : 'text-secondary' ?>"
                                           href="?status=<?= $key ?>"><?= $label ?></a>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-4 py-3 text-secondary fw-semibold small">Leave Id</th>
                                    <th class="text-secondary fw-semibold small">Employee</th>
                                    <th class="text-secondary fw-semibold small">Leave Type</th>
                                    <th class="text-secondary fw-semibold small">Reason</th>
                                    <th class="text-secondary fw-semibold small">Start Date</th>
                                    <th class="text-secondary fw-semibold small">End Date</th>
                                    <th class="text-secondary fw-semibold small">Days</th>
                                    <th class="text-secondary fw-semibold small">Status</th>
                                    <th class="text-secondary fw-semibold small">Approved By</th>
                                    <th class="text-end pe-4 text-secondary fw-semibold small">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($data->num_rows > 0) { ?>
                                    <?php foreach ($data as $leave) { ?>
                                        <?php
                                            $status = strtolower($leave['status']);
                                            $badge = 'bg-warning-subtle text-warning-emphasis';
                                            if ($status === 'approved') $badge = 'bg-success-subtle text-success-emphasis';
                                            if ($status === 'rejected') $badge = 'bg-danger-subtle text-danger-emphasis';
                                            $initials = strtoupper(substr($leave['employee_name'], 0, 2));
                                        ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">#<?= (int)$leave['leave_id'] ?></td>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <span class="leave-avatar"><?= htmlspecialchars($initials) ?></span>
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($leave['employee_name']) ?></div>
                                                        <div class="small text-secondary"><?= htmlspecialchars($leave['designation']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-light text-dark border fw-medium"><?= htmlspecialchars($leave['leave_type']) ?></span></td>
                                            <td class="reason-cell text-secondary small"><?= htmlspecialchars($leave['leave_reason']) ?></td>
                                            <td class="text-nowrap"><?= htmlspecialchars($leave['start_date']) ?></td>
                                            <td class="text-nowrap"><?= htmlspecialchars($leave['end_date']) ?></td>
                                            <td class="fw-semibold"><?= (int)$leave['total_days'] ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?= $badge ?> px-3 py-2">
                                                    &#9679; <?= htmlspecialchars($leave['status']) ?>
                                                </span>
                                                <?php if (!empty($leave['admin_remarks'])) { ?>
                                                    <div class="small text-secondary mt-1"><?= htmlspecialchars($leave['admin_remarks']) ?></div>
                                                <?php } ?>
                                            </td>
                                            <td class="text-secondary"><?= htmlspecialchars($leave['approved_by'] ?: '-') ?></td>
                                            <td class="text-end pe-4 text-nowrap">
                                                <?php if ($status === 'pending') { ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                            data-bs-toggle="modal" data-bs-target="#decisionModal"
                                                            data-leave-id="<?= (int)$leave['leave_id'] ?>"
                                                            data-action="approve"
                                                            data-name="<?= htmlspecialchars($leave['employee_name']) ?>">Approve</button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            data-bs-toggle="modal" data-bs-target="#decisionModal"
                                                            data-leave-id="<?= (int)$leave['leave_id'] ?>"
                                                            data-action="reject"
                                                            data-name="<?= htmlspecialchars($leave['employee_name']) ?>">Reject</button>
                                                <?php } else { ?>
                                                    <span class="text-secondary small">Done</span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                <?php } else { ?>
                                    <tr>
                                        <td colspan="10" class="text-center text-secondary py-5">
                                            <div class="fs-1 mb-2">&#128196;</div>
                                            No leave requests found.
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php include_once __DIR__ . '/../includes/footer.php'; ?>
        </main>
    </div>

    <!-- Approve / Reject modal -->
    <div class="modal fade" id="decisionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <form method="post" class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold" id="decisionTitle">Approve leave</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-secondary mb-3" id="decisionText">Are you sure?</p>

                    <input type="hidden" name="leave_id" id="decisionLeaveId">
                    <input type="hidden" name="action" id="decisionAction">

                    <label for="admin_remarks" class="form-label small fw-semibold">
                        Remarks <span class="text-secondary fw-normal">(optional)</span>
                    </label>
                    <textarea class="form-control" id="admin_remarks" name="admin_remarks" rows="3" maxlength="200"
                              placeholder="Write a short remark..."></textarea>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="decisionSubmit">Confirm</button>
                </div>
            </form>
        </div>
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

        // Modal fill
        document.getElementById('decisionModal').addEventListener('show.bs.modal', (e) => {
            const btn = e.relatedTarget;
            const isApprove = btn.dataset.action === 'approve';

            document.getElementById('decisionLeaveId').value = btn.dataset.leaveId;
            document.getElementById('decisionAction').value = btn.dataset.action;
            document.getElementById('decisionTitle').textContent = isApprove ? 'Approve leave' : 'Reject leave';
            document.getElementById('decisionText').textContent =
                (isApprove ? 'Approve' : 'Reject') + ' the leave request of ' + btn.dataset.name + '?';

            const submit = document.getElementById('decisionSubmit');
            submit.textContent = isApprove ? 'Approve' : 'Reject';
            submit.className = 'btn ' + (isApprove ? 'btn-success' : 'btn-danger');
        });
    </script>
</body>
</html>