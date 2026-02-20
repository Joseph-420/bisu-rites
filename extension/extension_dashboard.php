<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is Extension Director (Role ID 4)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 4){
    header("location: ../login.php");
    exit;
}

$page_title = "Extension Director Dashboard";
include "../includes/header.php";
?>



<div class="page-container">
    <?php include "../includes/navigation.php"; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <h1 class="header-title">
                <i class="fas fa-handshake" style="margin-right: 0.75rem; color: var(--success);"></i>
                Extension Director Dashboard
            </h1>
            <div class="header-actions">
                <div class="user-profile">
                    <div class="user-avatar"><?php echo strtoupper(substr($_SESSION["username"], 0, 1)); ?></div>
                    <div class="user-info-text">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION["username"]); ?></div>
                        <div class="user-role">Extension Director</div>
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
            <div class="alert alert-success animate-fadeIn mb-6">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">
                    <h4>Welcome to Extension Services Dashboard</h4>
                    <p>Manage community programs, partnerships, and outreach activities that create social impact.</p>
                </div>
            </div>

            <!-- Statistics Grid -->
            <div class="grid grid-cols-3">
                <!-- Active Programs Card -->
                <div class="stat-card animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Active Programs</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Currently running</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--primary);">
                            <i class="fas fa-project-diagram"></i>
                        </div>
                    </div>
                </div>

                <!-- Partnerships Card -->
                <div class="stat-card variant-secondary animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Partnerships</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Active collaborations</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--secondary);">
                            <i class="fas fa-handshake"></i>
                        </div>
                    </div>
                </div>

                <!-- Beneficiaries Card -->
                <div class="stat-card variant-success animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Total Beneficiaries</div>
                            <div class="stat-card-value">0</div>
                            <div class="stat-card-footer">Communities reached</div>
                        </div>
                        <div class="stat-card-icon" style="color: var(--success);">
                            <i class="fas fa-people-carry"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Card -->
            <div class="card mt-6 animate-fadeIn">
                <div class="card-header">
                    <h2>Quick Actions</h2>
                    <p>Common extension outreach tasks</p>
                </div>
                <div class="card-body">
                    <div class="grid grid-cols-3">
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-blue-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-plus-circle text-2xl" style="color: var(--primary);"></i>
                            <span class="text-sm font-semibold">New Program</span>
                        </button>
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-purple-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-link text-2xl" style="color: var(--secondary);"></i>
                            <span class="text-sm font-semibold">New Partnership</span>
                        </button>
                        <button class="flex flex-col items-center gap-2 p-4 rounded text-center hover:bg-green-50 transition border-none bg-transparent cursor-pointer">
                            <i class="fas fa-clipboard text-2xl" style="color: var(--success);"></i>
                            <span class="text-sm font-semibold">File Report</span>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include "../includes/footer.php"; ?>