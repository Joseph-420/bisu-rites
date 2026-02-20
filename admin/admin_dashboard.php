<?php
session_start();
require_once "../db_connect.php";

// Check if the user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role_id"] !== 1){
    // Redirect to login
    header("location: ../login.php");
    exit;
}

$page_title = "Admin Dashboard";
include "../includes/header.php";
?>



<div class="page-container">
    <?php include "../includes/navigation.php"; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <h1 class="header-title">
                <i class="fas fa-chart-line" style="margin-right: 0.75rem; color: var(--primary);"></i>
                Admin Dashboard Overview
            </h1>
            <div class="header-actions">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?></div>
                    <div class="user-info-text">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION["username"]); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
                <button class="btn btn-outline btn-sm" onclick="window.location.href='../logout.php'" style="margin-left: auto;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper content-wrapper-full">
            
            <!-- Welcome Alert -->
            <div class="alert alert-primary animate-fadeIn mb-6">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">
                    <h4>Welcome to Admin Dashboard</h4>
                    <p>System overview and administrative control center. Manage users, colleges, and monitor system activities.</p>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-3">
                <!-- R&D Projects Card -->
                <div class="stat-card animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">R&D Projects</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer positive">
                                <i class="fas fa-arrow-up"></i> Ongoing Research
                            </div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--primary);">
                            <i class="fas fa-flask"></i>
                        </div>
                    </div>
                </div>

                <!-- ITSO Assets Card -->
                <div class="stat-card variant-secondary animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">ITSO Assets</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Patents & Copyrights</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--secondary);">
                            <i class="fas fa-certificate"></i>
                        </div>
                    </div>
                </div>

                <!-- Extension Programs Card -->
                <div class="stat-card variant-success animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Extension Programs</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Community Engagement</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--success);">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Stats Section -->
            <div class="grid grid-cols-2 mt-6">
                <!-- Recent Activities Card -->
                <div class="card animate-fadeIn">
                    <div class="card-header">
                        <h2>Recent System Activities</h2>
                        <p>Latest updates and events</p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-primary mb-4">
                            <i class="fas fa-history alert-icon"></i>
                            <div class="alert-content">
                                <p>No recent activities logged.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats Card -->
                <div class="card animate-fadeIn">
                    <div class="card-header">
                        <h2>System Overview</h2>
                        <p>Key metrics at a glance</p>
                    </div>
                    <div class="card-body">
                        <div class="flex flex-col gap-4">
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Total Users</span>
                                <span class="font-bold text-lg">--</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">Active Sessions</span>
                                <span class="font-bold text-lg">1</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-sm">System Health</span>
                                <span class="badge badge-success">
                                    <i class="fas fa-check-circle"></i> Healthy
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-6 animate-fadeIn">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                    <p>Common administrative tasks</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-4">
                        <a href="admin_users.php" class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-blue-50 transition">
                            <i class="fas fa-user-plus text-2xl" style="color: var(--primary);"></i>
                            <span class="text-sm font-semibold">Add User</span>
                        </a>
                        <a href="admin_colleges.php" class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-purple-50 transition">
                            <i class="fas fa-university text-2xl" style="color: var(--secondary);"></i>
                            <span class="text-sm font-semibold">Manage Colleges</span>
                        </a>
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-green-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-file-download text-2xl" style="color: var(--success);"></i>
                            <span class="text-sm font-semibold">Export Data</span>
                        </button>
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-orange-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-cog text-2xl" style="color: var(--warning);"></i>
                            <span class="text-sm font-semibold">Settings</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include "../includes/footer.php"; ?>