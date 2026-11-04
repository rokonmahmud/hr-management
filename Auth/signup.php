<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f5f7fb">
    <title>Create account | PeopleDesk</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/auth.css">
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
                    <span class="eyebrow"><span class="eyebrow-dot"></span> A BETTER WAY TO WORK TOGETHER</span>
                    <h1>Make room<br>for <span>better work.</span></h1>
                    <p>Bring your people, everyday HR and team moments together in one place.</p>
                </div>

                <div class="quote-card">
                    <div class="quote-stars" aria-label="5 out of 5 stars">★★★★★</div>
                    <blockquote>“Finally, the people stuff feels simple. Our whole team knows where to go and what’s next.”</blockquote>
                    <div class="quote-person d-flex align-items-center gap-2">
                        <span class="quote-avatar">SR</span>
                        <span class="d-grid"><strong>Sarah Rahman</strong><small>People &amp; Culture Lead</small></span>
                    </div>
                </div>
                <p class="brand-footer mt-auto">A little more human, every day.</p>
            </aside>

            <section class="form-panel col-12 col-md-7 d-flex flex-column">
                <div class="form-wrap signup-form-wrap m-auto">
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
                        <span class="form-kicker">GET STARTED FOR FREE</span>
                        <h2>Create your account</h2>
                        <p>Set up your PeopleDesk account in just a few steps.</p>
                    </div>

                    <form method="post" action="">
                        <div class="mb-3">
                            <label class="form-label" for="name">Full name</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.7"/><path d="M4.5 20a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                </span>
                                <input class="form-control" id="name" name="name" type="text" autocomplete="name" placeholder="Your name" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="email">Work email</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><rect x="3.5" y="5" width="17" height="14" rx="3" stroke="currentColor" stroke-width="1.7"/><path d="m5 7 7 5.5L19 7" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <input class="form-control" id="email" name="email" type="email" autocomplete="email" placeholder="you@company.com" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="password">Password</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><rect x="4.5" y="10" width="15" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="M12 14.5v2" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                                </span>
                                <input class="form-control" id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 characters" minlength="8" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="confirm-password">Confirm password</label>
                            <div class="input-group">
                                <span class="input-group-text" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none"><rect x="4.5" y="10" width="15" height="11" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/><path d="m9.5 15.5 1.7 1.7 3.6-3.8" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </span>
                                <input class="form-control" id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Re-enter your password" minlength="8" required>
                            </div>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" id="terms" name="terms" type="checkbox" required>
                            <label class="form-check-label" for="terms">I agree to the <a class="text-link" href="#">Terms of Service</a> and <a class="text-link" href="#">Privacy Policy</a>.</label>
                        </div>

                        <button class="btn btn-primary w-100 submit-button" type="submit">Create account <span aria-hidden="true">→</span></button>
                    </form>

                    <p class="form-switch">Already have an account? <a href="login.php">Sign in</a></p>
                </div>
                <footer class="panel-footer d-flex align-items-center gap-3">
                    <span class="me-auto">© 2026 PeopleDesk</span><a href="#">Privacy</a><a href="#">Help center</a>
                </footer>
            </section>
        </div>
    </main>
</body>
</html>
