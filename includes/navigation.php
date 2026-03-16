<?php
// Navigation component - displays appropriate sidebar based on user role
// Call this after session_start() and role checks

// Determine current directory for active state
$current_file = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Calculate base path for links
if (strpos(dirname($_SERVER['PHP_SELF']), '/admin') !== false) {
    $base_link = './';
    $parent_link = '../';
} elseif (strpos(dirname($_SERVER['PHP_SELF']), '/RandD') !== false || 
          strpos(dirname($_SERVER['PHP_SELF']), '/itso') !== false ||
          strpos(dirname($_SERVER['PHP_SELF']), '/extension') !== false) {
    $base_link = './';
    $parent_link = '../';
} else {
    $base_link = './';
    $parent_link = './';
}
?>

<!-- Sidebar Navigation -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h1>BISU R.I.T.E.S</h1>
        <p><?php 
            $role_titles = array(
                1 => 'Admin Panel',
                2 => 'R&D Office',
                3 => 'ITSO Office',
                4 => 'Extension Office',
                5 => 'Faculty Portal'
            );
            echo isset($role_titles[$_SESSION["role_id"]]) ? $role_titles[$_SESSION["role_id"]] : 'Dashboard';
        ?></p>
    </div>
    
    <nav class="sidebar-nav">
        <?php if ($_SESSION["role_id"] == 1): // Superadmin Navigation ?>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>admin_dashboard.php" class="sidebar-nav-link <?php echo $current_file == 'admin_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>admin_colleges.php" class="sidebar-nav-link <?php echo strpos($current_file, 'admin_college') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-university"></i>
                    <span>Manage Colleges</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>admin_users.php" class="sidebar-nav-link <?php echo strpos($current_file, 'admin_user') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link">
                    <i class="fas fa-shield-alt"></i>
                    <span>System Logs</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="#" class="sidebar-nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
        <?php elseif ($_SESSION["role_id"] == 2): // R&D Director Navigation ?>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>rd_dashboard.php" class="sidebar-nav-link <?php echo $current_file == 'rd_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>rd_projects.php" class="sidebar-nav-link <?php echo $current_file == 'rd_projects.php' || $current_file == 'rd_project_add.php' ? 'active' : ''; ?>">
                    <i class="fas fa-flask-vial"></i>
                    <span>Research Projects</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>rd_researchers.php" class="sidebar-nav-link <?php echo $current_file == 'rd_researchers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Researchers</span>
                </a>
            </li>
        <?php elseif ($_SESSION["role_id"] == 3): // ITSO Director Navigation ?>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>itso_dashboard.php" class="sidebar-nav-link <?php echo $current_file == 'itso_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>itso_assets.php" class="sidebar-nav-link <?php echo strpos($current_file, 'itso_asset') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-lightbulb"></i>
                    <span>IP Disclosures</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>itso_commercialization.php" class="sidebar-nav-link <?php echo strpos($current_file, 'itso_comm') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-file-contract"></i>
                    <span>Commercialization</span>
                </a>
            </li>
        <?php elseif ($_SESSION["role_id"] == 4): // Extension Director Navigation ?>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>extension_dashboard.php" class="sidebar-nav-link <?php echo $current_file == 'extension_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>ext_projects.php" class="sidebar-nav-link <?php echo strpos($current_file, 'ext_project') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-handshake"></i>
                    <span>Extension Projects</span>
                </a>
            </li>
            <li class="sidebar-nav-item">
                <a href="<?php echo $base_link; ?>ext_monitoring.php" class="sidebar-nav-link <?php echo strpos($current_file, 'ext_monitoring') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-chart-pie"></i>
                    <span>Impact Monitoring</span>
                </a>
            </li>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <button class="btn btn-ghost w-full btn-sm" onclick="window.location.href='<?php echo $parent_link; ?>logout.php'">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </button>
    </div>
</aside>
