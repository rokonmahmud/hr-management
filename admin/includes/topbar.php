            
<?php
$userName = $_SESSION['name'] ?? 'Administrator';
$userInitials = strtoupper(substr(preg_replace('/\s+/', '', $userName), 0, 2));
if ($userInitials === '') {
    $userInitials = 'AD';
}
?>

<header class="topbar">
    <div class="d-flex align-items-center gap-3">
        <button class="btn sidebar-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#adminSidebar" aria-controls="adminSidebar" aria-label="Open navigation">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
        </button>
        <div class="breadcrumb-line"><span>Workspace</span><span class="breadcrumb-slash">/</span><strong>Overview</strong></div>
    </div>
    <div class="topbar-actions">
        <span class="today-label"><?php echo date('l, F j, Y'); ?></span>
        <button class="icon-button notification-button" type="button" aria-label="Notifications">
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M18 9a6 6 0 0 0-12 0c0 7-2.5 7-2.5 9h17C20.5 16 18 16 18 9ZM10 21h4" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/></svg>
            <span class="notification-dot"></span>
        </button>
        <div class="dropdown">
            <button class="topbar-avatar border-0" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Profile menu">
                <?php echo htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item text-danger" href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </div>
</header>
