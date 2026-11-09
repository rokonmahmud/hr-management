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
                        <span class="welcome-kicker">SYSTEM OVERVIEW</span>
                        <h1>Workspace dashboard <span aria-hidden="true">✦</span></h1>
                        <p>Monitor the team and keep daily operations moving.</p>
                    </div>
                </section>

                <section class="row g-3 stat-grid">
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
                </section>
            </div>
            <?php include_once("includes/footer.php") ?>
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
