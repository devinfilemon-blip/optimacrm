<?php
$crmIsAdmin = isset($_SESSION['userRole']) && $_SESSION['userRole'] === 'Admin';
$crmIsTeamLeader = isset($_SESSION['userRole']) && $_SESSION['userRole'] === 'Team Leader';
$crmCurrentScript = basename($_SERVER['SCRIPT_NAME']);
function crmIsActive($scripts) {
    global $crmCurrentScript;
    $scripts = (array) $scripts;
    return in_array($crmCurrentScript, $scripts, true);
}
// "active" (not metisMenu's own "mm-active") so opening/closing the Masters
// accordion doesn't strip the current-page highlight from sibling items —
// metisMenu clears mm-active off other top-level <li> when you toggle one open.
function crmActive($scripts) {
    return crmIsActive($scripts) ? 'active' : '';
}

// The Masters submenu links never carry their own active state, so landing on
// any of them left the whole sidebar showing nothing selected and the submenu
// collapsed. Compute it once so the parent + submenu both reflect where we are.
$crmMastersScripts = ['list-status.php', 'list-source.php', 'list-post.php'];
$crmMastersOpen = crmIsActive($crmMastersScripts);
?>
<header id="page-topbar">
    <div class="navbar-header">
        <div class="crm-header-left">
            <button type="button" class="btn btn-sm header-item" id="vertical-menu-btn">
                <i class="fa fa-fw fa-bars"></i>
            </button>
            <a href="list-reminder.php" class="crm-header-icon" title="Reminders">
                <i class="bx bx-bell"></i>
                <span class="crm-notif-badge" id="crmNotifBadge" style="display: none;">0</span>
            </a>
        </div>
        <div class="crm-header-search" id="crmHeaderSearch">
            <div class="crm-search-wrap">
                <i class="bx bx-search"></i>
                <input type="search" id="crmGlobalSearchInput" placeholder="Search companies, requirements, candidates…" autocomplete="off" aria-label="Global search">
                <div class="crm-search-results" id="crmGlobalSearchResults" style="display:none;"></div>
            </div>
        </div>
        <div class="crm-header-right">
            <button type="button" class="crm-theme-toggle" id="crmThemeToggle" title="Toggle Dark / Light theme" aria-label="Toggle theme">
                <i class="bx bx-moon" id="crmThemeIcon"></i>
            </button>
            <div class="dropdown d-inline-block">
                <button type="button" class="crm-user-btn" id="page-header-user-dropdown"
                    data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <div class="crm-user-avatar"><?php echo strtoupper(substr($_SESSION["username"] ?? 'U', 0, 1)); ?></div>
                    <div class="d-none d-xl-block">
                        <span class="crm-user-name"><?php echo htmlspecialchars($_SESSION["username"] ?? 'User'); ?></span>
                        <span class="crm-user-role"><?php echo htmlspecialchars($_SESSION['userRole'] ?? 'User'); ?></span>
                    </div>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block ms-1 crm-chevron-down"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="MyProfile.php"><i class="fas fa-user-alt font-size-16 align-middle me-1"></i> My Profile</a>
                    <a class="dropdown-item" href="newpassword.php"><i class="fas fa-key font-size-16 align-middle me-1"></i> Change Password</a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item text-danger" href="logout.php"><i class="bx bx-power-off font-size-16 align-middle me-1 text-danger"></i> Logout</a>
                </div>
            </div>
        </div>
    </div>
</header>

<div class="crm-sidebar-overlay" id="crmSidebarOverlay" aria-hidden="true"></div>

<!-- ========== Left Sidebar Start ========== -->
<div class="vertical-menu">
    <div data-simplebar class="h-100">
        <div class="crm-sidebar-brand">
            <a href="index.php">
                <img src="<?php echo APP_LOGO; ?>" alt="<?php echo APP_NAME; ?>" class="crm-sidebar-logo">
                <img src="<?php echo APP_ICON; ?>" alt="<?php echo APP_NAME; ?>" class="crm-sidebar-icon">
            </a>
        </div>
        <div id="sidebar-menu">
            <ul class="metismenu list-unstyled" id="side-menu">
                <li class="menu-title">Menu</li>

                <li>
                    <a href="index.php" class="waves-effect <?php echo crmActive('index.php'); ?>">
                        <i class="bx bx-grid-alt"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <li>
                    <a href="calendar.php" class="waves-effect <?php echo crmActive('calendar.php'); ?>">
                        <i class="bx bx-calendar"></i>
                        <span>Calendar</span>
                    </a>
                </li>

                <li>
                    <a href="list-company.php" class="waves-effect <?php echo crmActive(['list-company.php', 'add-company.php']); ?>">
                        <i class="bx bx-buildings"></i>
                        <span>Companies</span>
                    </a>
                </li>

                <li>
                    <a href="list-requirement.php" class="waves-effect <?php echo crmActive(['list-requirement.php', 'add-requirement.php']); ?>">
                        <i class="bx bx-briefcase-alt-2"></i>
                        <span>Job Requirements</span>
                    </a>
                </li>

                <li>
                    <a href="list-placement.php" class="waves-effect <?php echo crmActive(['list-placement.php', 'add-placement.php']); ?>">
                        <i class="bx bx-user-check"></i>
                        <span>Candidates &amp; Placements</span>
                    </a>
                </li>

                <li>
                    <a href="list-reminder.php" class="waves-effect <?php echo crmActive(['list-reminder.php', 'add-reminder.php']); ?>">
                        <i class="bx bx-calendar-check"></i>
                        <span>Reminders</span>
                    </a>
                </li>

                <li>
                    <a href="reports.php" class="waves-effect <?php echo crmActive('reports.php'); ?>">
                        <i class="bx bx-bar-chart-alt-2"></i>
                        <span>Reports</span>
                    </a>
                </li>

                <?php if ($crmIsAdmin || $crmIsTeamLeader) : ?>
                <li class="menu-title">Admin</li>
                <li>
                    <a href="list-user.php" class="waves-effect <?php echo crmActive(['list-user.php', 'add-user.php']); ?>">
                        <i class="bx bx-group"></i>
                        <span><?php echo $crmIsAdmin ? 'Recruiters &amp; Users' : 'My Recruiters'; ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($crmIsAdmin) : ?>
                <li class="<?php echo $crmMastersOpen ? 'mm-active' : ''; ?>">
                    <a href="javascript: void(0);" class="has-arrow waves-effect<?php echo $crmMastersOpen ? ' mm-active' : ''; ?>">
                        <i class="bx bx-cog"></i>
                        <span>Masters</span>
                    </a>
                    <ul class="sub-menu<?php echo $crmMastersOpen ? ' mm-show' : ''; ?>" aria-expanded="<?php echo $crmMastersOpen ? 'true' : 'false'; ?>">
                        <li><a href="list-status.php" class="<?php echo crmActive('list-status.php'); ?>">Requirement Status</a></li>
                        <li><a href="list-source.php" class="<?php echo crmActive('list-source.php'); ?>">Lead Sources</a></li>
                        <li><a href="list-post.php" class="<?php echo crmActive('list-post.php'); ?>">Post / Designation</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>
<!-- Left Sidebar End -->
