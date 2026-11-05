
            <aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="adminSidebar" aria-labelledby="adminSidebarLabel">
            <div class="sidebar-header">
                <a class="brand" href="index.php" id="adminSidebarLabel">
                    <span class="brand-mark" aria-hidden="true">
                        <svg viewBox="0 0 32 32" fill="none">
                            <path d="M7 23.5V10.8l9-5.3 9 5.3v12.7l-9 5.3-9-5.3Z" stroke="currentColor" stroke-width="2.4" stroke-linejoin="round"/>
                            <path d="m11.5 16.3 3.1 3.1 6-7" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <span>people<span class="brand-light">desk</span></span>
                </a>
                <button class="btn-close d-lg-none" type="button" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar" aria-label="Close navigation"></button>
            </div>

            <!-- <div class="workspace-switcher">
                <span class="workspace-avatar">N</span>
                <span class="workspace-details"><strong>Northstar Studio</strong><small>Workspace</small></span>
                <span class="workspace-caret" aria-hidden="true">⌄</span>
            </div> -->

            <nav class="sidebar-nav" aria-label="Main navigation">
                <span class="nav-caption">WORKSPACE</span>
                <a class="nav-link active" href="index.php" aria-current="page" data-bs-dismiss="offcanvas" data-bs-target="#adminSidebar">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5" stroke="currentColor" stroke-width="1.7"/></svg>
                    <span>Overview</span>
                </a>
                <a class="nav-link" href="staff.php">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.2" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 19a5.5 5.5 0 0 1 11 0M16 5.2a3.2 3.2 0 0 1 0 6.1M17 14a5.2 5.2 0 0 1 3.5 5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <span>Employees</span>
                    <span class="nav-count">48</span>
                </a>
                <a class="nav-link" href="#attendance">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="15.5" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 14l2.2 2.2L16.5 11" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>Attendance</span>
                </a>
                <a class="nav-link" href="#leave-requests">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3.5h7l4.5 4.5v12a1.5 1.5 0 0 1-1.5 1.5H7A2.5 2.5 0 0 1 4.5 19V6A2.5 2.5 0 0 1 7 3.5Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M14 4v5h5M8 13h8M8 16.5h5" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <span>Leave requests</span>
                    <span class="nav-count nav-count-alert">4</span>
                </a>
                <a class="nav-link" href="#payroll">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="5" width="17" height="14" rx="2.5" stroke="currentColor" stroke-width="1.7"/><path d="M3.5 9.5h17M7.5 14h3M16 13v.1" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
                    <span>Payroll</span>
                </a>

                <span class="nav-caption nav-caption-spaced">MANAGE</span>
                <a class="nav-link" href="#departments">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3.5" y="7" width="17" height="13" rx="2" stroke="currentColor" stroke-width="1.7"/><path d="M8 7V5.5A1.5 1.5 0 0 1 9.5 4h5A1.5 1.5 0 0 1 16 5.5V7M3.5 12h17M10 12v2h4v-2" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/></svg>
                    <span>Departments</span>
                </a>
                <a class="nav-link" href="#reports">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 20V12M10 20V5M16 20v-8M22 20V8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>Reports</span>
                </a>
            </nav>

            <div class="sidebar-bottom">
                <a class="nav-link" href="#settings">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z" stroke="currentColor" stroke-width="1.7"/><path d="m19.4 13.5 1.2 1-.9 1.6-1.5-.3a7.6 7.6 0 0 1-1.5.9l-.4 1.5h-1.9l-.5-1.4a7.5 7.5 0 0 1-1.7 0l-.8 1.2-1.7-.7.2-1.5a7.5 7.5 0 0 1-1.2-1.3l-1.5.1-.6-1.8 1.2-.9a7.4 7.4 0 0 1-.1-1.7l-1.2-.8.6-1.8 1.5.1a7.5 7.5 0 0 1 1.3-1.2l-.1-1.5 1.8-.6.9 1.2a7.4 7.4 0 0 1 1.7-.1l.8-1.2 1.8.6-.1 1.5a7.5 7.5 0 0 1 1.2 1.3l1.5-.1.6 1.8-1.2.9a7.4 7.4 0 0 1 .1 1.7Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/></svg>
                    <span>Settings</span>
                </a>    
                <!-- <div class="sidebar-help">
                    <span class="help-icon">?</span>
                    <div><strong>Need a hand?</strong><small>Visit our help center</small></div>
                    <span class="help-arrow" aria-hidden="true">↗</span>
                </div> -->
                <div class="profile-link">
                    <span class="profile-avatar">AM</span>
                    <span class="profile-details"><strong>Alex Morgan</strong><small>Administrator</small></span>
                    <span class="profile-menu" aria-hidden="true">···</span>
                </div>
            </div>
        </aside>
