<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit;
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

            <div class="dashboard-content">
                <section class="welcome-row">
                    <div>
                        <span class="welcome-kicker">WEDNESDAY, NOVEMBER 4, 2026</span>
                        <h1>Good morning, <?php echo $_SESSION['name']; ?> <span aria-hidden="true">✦</span></h1>
                        <p>Here’s what’s happening with your team today.</p>
                    </div>
                    <a class="btn btn-primary add-employee-button" href="#employees">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Add employee
                    </a>
                </section>

                <section class="row g-3 stat-grid" aria-label="HR overview">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon people-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.2a3.2 3.2 0 0 1 0 6.1M17 14a5.2 5.2 0 0 1 3.5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Total employees</span>
                            <div class="stat-value-row"><strong>48</strong><span class="trend positive">↗ 8.2%</span></div>
                            <span class="stat-footnote">vs. last month</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon attendance-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Present today</span>
                            <div class="stat-value-row"><strong>42</strong><span class="trend positive">87.5%</span></div>
                            <span class="stat-footnote">of your team is at work</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon leave-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5h7l4.5 4.5v12a1.5 1.5 0 0 1-1.5 1.5H7A2.5 2.5 0 0 1 4.5 19V6A2.5 2.5 0 0 1 7 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 4v5h5M8 13h8M8 16.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">On leave</span>
                            <div class="stat-value-row"><strong>4</strong><span class="trend neutral">Today</span></div>
                            <span class="stat-footnote">2 returning this week</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon payroll-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9.5h17M8 14h3M16 13v.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Payroll this month</span>
                            <div class="stat-value-row"><strong class="currency-value">৳ 8.4L</strong><span class="trend neutral">October</span></div>
                            <span class="stat-footnote">for 48 employees</span>
                        </article>
                    </div>
                </section>

                <section class="row g-3 dashboard-row">
                    <div class="col-12 col-xl-8" id="attendance">
                        <article class="card dashboard-card attendance-card h-100">
                            <div class="card-heading">
                                <div><h2>Attendance overview</h2><p>A look at your team's attendance this week.</p></div>
                                <button class="btn btn-sm dropdown-button" type="button">This week <span aria-hidden="true">⌄</span></button>
                            </div>
                            <div class="chart-legend">
                                <span><i class="legend-dot present-dot"></i>Present</span>
                                <span><i class="legend-dot absent-dot"></i>Absent</span>
                                <span><i class="legend-dot leave-dot"></i>On leave</span>
                            </div>
                            <div class="attendance-chart" role="img" aria-label="Bar chart showing attendance Monday through Sunday, with the highest attendance on Friday">
                                <div class="chart-grid-lines"><span>50</span><span>40</span><span>30</span><span>20</span><span>10</span></div>
                                <div class="chart-columns">
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 67%"></i><i class="bar-absent" style="height: 8%"></i><i class="bar-leave" style="height: 7%"></i></div><span>Mon</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 74%"></i><i class="bar-absent" style="height: 6%"></i><i class="bar-leave" style="height: 8%"></i></div><span>Tue</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 70%"></i><i class="bar-absent" style="height: 8%"></i><i class="bar-leave" style="height: 7%"></i></div><span>Wed</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 79%"></i><i class="bar-absent" style="height: 5%"></i><i class="bar-leave" style="height: 7%"></i></div><span>Thu</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 84%"></i><i class="bar-absent" style="height: 4%"></i><i class="bar-leave" style="height: 6%"></i></div><span>Fri</span></div>
                                    <div class="chart-column"><div class="bar-stack weekend-bars"><i class="bar-present" style="height: 12%"></i><i class="bar-absent" style="height: 2%"></i><i class="bar-leave" style="height: 2%"></i></div><span>Sat</span></div>
                                    <div class="chart-column"><div class="bar-stack weekend-bars"><i class="bar-present" style="height: 10%"></i><i class="bar-absent" style="height: 2%"></i><i class="bar-leave" style="height: 2%"></i></div><span>Sun</span></div>
                                </div>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-xl-4" id="departments">
                        <article class="card dashboard-card department-card h-100">
                            <div class="card-heading">
                                <div><h2>Departments</h2><p>People across your teams.</p></div>
                                <a class="text-action" href="#departments">View all</a>
                            </div>
                            <div class="department-list">
                                <div class="department-item"><span class="department-symbol design-symbol">D</span><span class="department-name">Design<small>Creative team</small></span><span class="department-total">12</span><span class="department-track"><i class="design-bar" style="width: 75%"></i></span></div>
                                <div class="department-item"><span class="department-symbol engineering-symbol">E</span><span class="department-name">Engineering<small>Product &amp; tech</small></span><span class="department-total">16</span><span class="department-track"><i class="engineering-bar" style="width: 100%"></i></span></div>
                                <div class="department-item"><span class="department-symbol marketing-symbol">M</span><span class="department-name">Marketing<small>Growth team</small></span><span class="department-total">8</span><span class="department-track"><i class="marketing-bar" style="width: 50%"></i></span></div>
                                <div class="department-item"><span class="department-symbol people-symbol">P</span><span class="department-name">People &amp; Ops<small>Operations</small></span><span class="department-total">7</span><span class="department-track"><i class="people-bar" style="width: 44%"></i></span></div>
                                <div class="department-item"><span class="department-symbol sales-symbol">S</span><span class="department-name">Sales<small>Client team</small></span><span class="department-total">5</span><span class="department-track"><i class="sales-bar" style="width: 31%"></i></span></div>
                            </div>
                        </article>
                    </div>
                </section>

                <section class="row g-3 dashboard-row">
                    <div class="col-12 col-xl-7" id="employees">
                        <article class="card dashboard-card employee-card h-100">
                            <div class="card-heading">
                                <div><h2>New employees</h2><p>Welcome the newest members of your team.</p></div>
                                <a class="text-action" href="#employees">View all employees <span aria-hidden="true">→</span></a>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle employee-table mb-0">
                                    <thead><tr><th scope="col">EMPLOYEE</th><th scope="col">DEPARTMENT</th><th scope="col">JOINED</th><th scope="col">STATUS</th></tr></thead>
                                    <tbody>
                                        <tr><td><div class="employee-cell"><span class="employee-avatar avatar-lavender">NC</span><span><strong>Nadia Chowdhury</strong><small>Product Designer</small></span></div></td><td>Design</td><td>Nov 03, 2026</td><td><span class="status-badge status-active">Active</span></td></tr>
                                        <tr><td><div class="employee-cell"><span class="employee-avatar avatar-peach">RK</span><span><strong>Rafi Khan</strong><small>Frontend Engineer</small></span></div></td><td>Engineering</td><td>Oct 28, 2026</td><td><span class="status-badge status-active">Active</span></td></tr>
                                        <tr><td><div class="employee-cell"><span class="employee-avatar avatar-blue">MA</span><span><strong>Meher Afroz</strong><small>Marketing Associate</small></span></div></td><td>Marketing</td><td>Oct 24, 2026</td><td><span class="status-badge status-onboarding">Onboarding</span></td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </article>
                    </div>

                    <div class="col-12 col-xl-5" id="leave-requests">
                        <article class="card dashboard-card leave-card h-100">
                            <div class="card-heading">
                                <div><h2>Leave requests <span class="pending-count">4</span></h2><p>Requests waiting for your review.</p></div>
                                <a class="text-action" href="#leave-requests">View all</a>
                            </div>
                            <div class="leave-list">
                                <div class="leave-item"><span class="employee-avatar avatar-peach">SA</span><span class="leave-person"><strong>Samira Ahmed</strong><small>Annual leave · Nov 06–08</small></span><span class="leave-duration">3 days</span></div>
                                <div class="leave-item"><span class="employee-avatar avatar-blue">TI</span><span class="leave-person"><strong>Tamim Iqbal</strong><small>Sick leave · Nov 05</small></span><span class="leave-duration">1 day</span></div>
                                <div class="leave-item"><span class="employee-avatar avatar-mint">FR</span><span class="leave-person"><strong>Farah Rahman</strong><small>Personal leave · Nov 10–11</small></span><span class="leave-duration">2 days</span></div>
                            </div>
                            <a class="review-link" href="#leave-requests">Review all requests <span aria-hidden="true">→</span></a>
                        </article>
                    </div>
                </section>

                <?php include_once("includes/footer.php") ?>
            </div>
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
