<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is R&D Director (Role ID 2)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 2){
    header("location: ../login.php");
    exit;
}

$page_title = "R&D Director Dashboard";
include "../includes/header.php";
?>



<div class="page-container">
    <?php include "../includes/navigation.php"; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <h1 class="header-title">
                <i class="fas fa-flask" style="margin-right: 0.75rem; color: var(--primary);"></i>
                R&D Director Dashboard
            </h1>
            <div class="header-actions">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?></div>
                    <div class="user-info-text">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION["username"]); ?></div>
                        <div class="user-role">R&D Director</div>
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
                    <h4>Welcome to R&D Office Dashboard</h4>
                    <p>Manage research projects, proposals, and collaborate with your research team members.</p>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-3">
                <!-- Pending Proposals Card -->
                <div class="stat-card animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Pending Proposals</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Awaiting review</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--primary);">
                            <i class="fas fa-file-alt"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Projects Card -->
                <div class="stat-card variant-secondary animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Active Projects</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Ongoing research</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--secondary);">
                            <i class="fas fa-flask-vial"></i>
                        </div>
                    </div>
                </div>

                <!-- Published Papers Card -->
                <div class="stat-card variant-success animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Published Papers</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Disseminated findings</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--success);">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card mt-6 animate-fadeIn">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                    <p>Common R&D management tasks</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-3">
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-blue-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-plus-circle text-2xl" style="color: var(--primary);"></i>
                            <span class="text-sm font-semibold">New Proposal</span>
                        </button>
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-purple-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-users text-2xl" style="color: var(--secondary);"></i>
                            <span class="text-sm font-semibold">My Researchers</span>
                        </button>
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-green-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-chart-bar text-2xl" style="color: var(--success);"></i>
                            <span class="text-sm font-semibold">View Reports</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include "../includes/footer.php"; ?>