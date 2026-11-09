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
            include_once('includes/sidebar.php');
        ?>

        <main class="main-content">
            <?php 
                include_once('includes/topbar.php');
            ?> 

            <div class="dashboard-content">
                <section class="welcome-row">
                    <div>
                        <span class="welcome-kicker">WELCOME BACK</span>
                        <h1>Good morning, <?php echo htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8'); ?> <span aria-hidden="true">✦</span></h1>
                        <p>Your day-to-day overview is ready.</p>
                    </div>
                    <a class="btn btn-primary add-employee-button" href="#attendance">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
                        Submit timesheet
                    </a>
                </section>

                <section class="row g-3 stat-grid" aria-label="Employee overview">
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon attendance-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="8.5" stroke="currentColor" stroke-width="1.7"/><path d="M12 7v5l3.5 2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Attendance</span>
                            <div class="stat-value-row"><strong>92%</strong><span class="trend positive">↗ 5.4%</span></div>
                            <span class="stat-footnote">This month</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon leave-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5h7l4.5 4.5v12a1.5 1.5 0 0 1-1.5 1.5H7A2.5 2.5 0 0 1 4.5 19V6A2.5 2.5 0 0 1 7 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 4v5h5M8 13h8M8 16.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Leave balance</span>
                            <div class="stat-value-row"><strong>12</strong><span class="trend neutral">Days</span></div>
                            <span class="stat-footnote">4 remaining this quarter</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon payroll-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9.5h17M7.5 14h3M16 13v.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Net pay</span>
                            <div class="stat-value-row"><strong class="currency-value">৳ 52K</strong><span class="trend positive">Paid</span></div>
                            <span class="stat-footnote">Updated on 27 Oct</span>
                        </article>
                    </div>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="card stat-card h-100">
                            <div class="stat-top"><span class="stat-icon people-icon"><svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg></span><span class="stat-menu">···</span></div>
                            <span class="stat-label">Profile status</span>
                            <div class="stat-value-row"><strong>79%</strong><span class="trend positive">Complete</span></div>
                            <span class="stat-footnote">One task left</span>
                        </article>
                    </div>
                </section>

                <section class="row g-3 dashboard-row">
                    <div class="col-12 col-xl-8">
                        <article class="card dashboard-card h-100">
                            <div class="card-heading">
                                <div>
                                    <h2>My schedule</h2>
                                    <p>Upcoming meetings and focus blocks.</p>
                                </div>
                                <button class="btn btn-sm dropdown-button" type="button">This week <span aria-hidden="true">⌄</span></button>
                            </div>
                            <div class="attendance-chart" role="img" aria-label="Weekly working hours chart">
                                <div class="chart-grid-lines"><span>40</span><span>30</span><span>20</span><span>10</span></div>
                                <div class="chart-columns">
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 58%"></i><i class="bar-absent" style="height: 14%"></i><i class="bar-leave" style="height: 10%"></i></div><span>Mon</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 72%"></i><i class="bar-absent" style="height: 8%"></i><i class="bar-leave" style="height: 12%"></i></div><span>Tue</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 64%"></i><i class="bar-absent" style="height: 12%"></i><i class="bar-leave" style="height: 8%"></i></div><span>Wed</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 88%"></i><i class="bar-absent" style="height: 6%"></i><i class="bar-leave" style="height: 8%"></i></div><span>Thu</span></div>
                                    <div class="chart-column"><div class="bar-stack"><i class="bar-present" style="height: 76%"></i><i class="bar-absent" style="height: 10%"></i><i class="bar-leave" style="height: 6%"></i></div><span>Fri</span></div>
                                    <div class="chart-column"><div class="bar-stack weekend-bars"><i class="bar-present" style="height: 26%"></i><i class="bar-absent" style="height: 22%"></i><i class="bar-leave" style="height: 18%"></i></div><span>Sat</span></div>
                                    <div class="chart-column"><div class="bar-stack weekend-bars"><i class="bar-present" style="height: 18%"></i><i class="bar-absent" style="height: 20%"></i><i class="bar-leave" style="height: 14%"></i></div><span>Sun</span></div>
                                </div>
                            </div>
                        </article>
                    </div>
                    <div class="col-12 col-xl-4">
                        <article class="card dashboard-card h-100">
                            <div class="card-heading">
                                <div>
                                    <h2>Priority tasks</h2>
                                    <p>Finish the essentials.</p>
                                </div>
                            </div>
                            <div class="leave-list" style="margin-top: 18px;">
                                <div class="leave-item">
                                    <span class="employee-avatar avatar-mint">01</span>
                                    <div class="leave-person">
                                        <strong>Project update</strong>
                                        <small>Due today, 4:30 PM</small>
                                    </div>
                                    <span class="pending-count">3</span>
                                </div>
                                <div class="leave-item">
                                    <span class="employee-avatar avatar-peach">02</span>
                                    <div class="leave-person">
                                        <strong>Onboarding notes</strong>
                                        <small>Review before Friday</small>
                                    </div>
                                    <span class="pending-count">1</span>
                                </div>
                                <div class="leave-item">
                                    <span class="employee-avatar avatar-blue">03</span>
                                    <div class="leave-person">
                                        <strong>Team sync</strong>
                                        <small>Tomorrow, 10:00 AM</small>
                                    </div>
                                    <span class="pending-count">2</span>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>

                <?php include_once("includes/footer.php"); ?>
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
