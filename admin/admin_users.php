<?php
session_start();
require_once "../db_connect.php";

// 1. Check if user is Superadmin (Role ID 1)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 1){
    header("location: ../login.php");
    exit;
}

// 2. Role filter
$role_filter = isset($_GET['role']) ? $_GET['role'] : '';
$allowed_filters = ['directors', 'faculty', 'student', ''];
if (!in_array($role_filter, $allowed_filters)) $role_filter = '';

// 3. Fetch users with optional role filter
$sql = "SELECT u.user_id, u.username, u.first_name, u.last_name, u.role_id, r.role_name 
        FROM users u 
        LEFT JOIN system_roles r ON u.role_id = r.role_id";
if ($role_filter === 'directors') {
    $sql .= " WHERE u.role_id IN (2, 3, 4)";
} elseif ($role_filter === 'faculty') {
    $sql .= " WHERE u.role_id = 5";
} elseif ($role_filter === 'student') {
    $sql .= " WHERE u.role_id = 6";
}
$sql .= " ORDER BY u.created_at DESC";
$result = $conn->query($sql);

// 4. Count by role groups
$count_all = $conn->query("SELECT COUNT(*) as c FROM users")->fetch_assoc()['c'];
$count_directors = $conn->query("SELECT COUNT(*) as c FROM users WHERE role_id IN (2, 3, 4)")->fetch_assoc()['c'];
$count_faculty = $conn->query("SELECT COUNT(*) as c FROM users WHERE role_id = 5")->fetch_assoc()['c'];
$count_students = $conn->query("SELECT COUNT(*) as c FROM users WHERE role_id = 6")->fetch_assoc()['c'];

$page_title = "User Management";
include "../includes/header.php";
?>

<style>
    body {
        display: flex;
        margin: 0;
        padding: 0;
    }
    .page-container {
        display: flex;
        width: 100%;
    }
</style>

<div class="page-container">
    <?php include "../includes/navigation.php"; ?>

    <!-- Main Content -->
    <div class="main-content">
        
        <!-- Header -->
        <div class="header">
            <h1 class="header-title"><i class="fas fa-users mr-2"></i> Manage Users</h1>
            <div class="header-actions">
                <a href="admin_user_add.php" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add User
                </a>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-4 mb-6">
                <div class="stat-card animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Total Users</div>
                            <div class="stat-card-value"><?php echo $count_all; ?></div>
                            <div class="stat-card-footer">Active accounts</div>
                        </div>
                        <div class="stat-card-icon">
                            <i class="fas fa-users"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card variant-secondary animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Directors</div>
                            <div class="stat-card-value"><?php echo $count_directors; ?></div>
                            <div class="stat-card-footer">Department heads</div>
                        </div>
                        <div class="stat-card-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card variant-success animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Faculty</div>
                            <div class="stat-card-value"><?php echo $count_faculty; ?></div>
                            <div class="stat-card-footer">Teaching staff</div>
                        </div>
                        <div class="stat-card-icon">
                            <i class="fas fa-chalkboard-user"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card animate-fadeIn">
                    <div class="stat-card-header">
                        <div>
                            <div class="stat-card-label">Students</div>
                            <div class="stat-card-value"><?php echo $count_students; ?></div>
                            <div class="stat-card-footer">Student accounts</div>
                        </div>
                        <div class="stat-card-icon">
                            <i class="fas fa-graduation-cap"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Filter Tabs -->
            <div class="flex space-x-2 mb-4">
                <a href="admin_users.php" class="btn btn-sm <?php echo $role_filter === '' ? 'btn-primary' : 'btn-outline'; ?>">
                    <i class="fas fa-users"></i> All (<?php echo $count_all; ?>)
                </a>
                <a href="?role=directors" class="btn btn-sm <?php echo $role_filter === 'directors' ? 'btn-primary' : 'btn-outline'; ?>">
                    <i class="fas fa-user-tie"></i> Directors (<?php echo $count_directors; ?>)
                </a>
                <a href="?role=faculty" class="btn btn-sm <?php echo $role_filter === 'faculty' ? 'btn-primary' : 'btn-outline'; ?>">
                    <i class="fas fa-chalkboard-user"></i> Faculty (<?php echo $count_faculty; ?>)
                </a>
                <a href="?role=student" class="btn btn-sm <?php echo $role_filter === 'student' ? 'btn-primary' : 'btn-outline'; ?>">
                    <i class="fas fa-graduation-cap"></i> Students (<?php echo $count_students; ?>)
                </a>
            </div>

            <!-- Table Card -->
            <div class="card animate-fadeIn">
                <div class="card-header">
                    <h2>System Users<?php echo $role_filter ? ' — ' . ucfirst($role_filter) : ''; ?></h2>
                    <p>Manage all user accounts and permissions</p>
                </div>
                
                <div style="overflow-x: auto;">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 8%;"><i class="fas fa-hashtag"></i> ID</th>
                                <th style="width: 30%;"><i class="fas fa-user"></i> Full Name</th>
                                <th style="width: 25%;"><i class="fas fa-at"></i> Username</th>
                                <th style="width: 20%;"><i class="fas fa-shield-alt"></i> Role</th>
                                <th style="width: 17%;"><i class="fas fa-cogs"></i> Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()) {
                                    $fullName = htmlspecialchars($row["first_name"] . " " . $row["last_name"]);
                                    $username = htmlspecialchars($row["username"]);
                                    $roleName = $row["role_name"];
                                    $roleId = $row["role_id"];
                                    $userId = $row["user_id"];
                                    
                                    $roleBadgeClass = 'badge-primary';
                                    if ($roleId == 1) $roleBadgeClass = 'badge-destructive';
                                    elseif ($roleId >= 2 && $roleId <= 4) $roleBadgeClass = 'badge-secondary';
                                    
                                    echo "<tr>";
                                    echo "<td><span class='badge badge-outline'>#" . $userId . "</span></td>";
                                    echo "<td class='font-semibold'>" . $fullName . "</td>";
                                    echo "<td class='text-muted'>@" . $username . "</td>";
                                    echo "<td><span class='badge " . $roleBadgeClass . "'>" . $roleName . "</span></td>";
                                    echo "<td class='flex gap-4'>";
                                    
                                    if($roleId != 1) { 
                                        echo '<a href="admin_user_edit.php?id='. $userId .'" class="btn btn-primary btn-sm"><i class="fas fa-edit"></i> Edit</a>';
                                        echo '<a href="admin_user_delete.php?id='. $userId .'" class="btn btn-destructive btn-sm" onclick="return confirm(\'Delete this user? This action cannot be undone.\');"><i class="fas fa-trash"></i> Delete</a>';
                                    } else {
                                        echo "<span class='text-xs text-muted'><i class='fas fa-shield-alt mr-1'></i> System Admin</span>";
                                    }
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='5' class='text-center p-8'><i class='fas fa-inbox text-2xl text-muted mb-3'></i><p>No users found. <a href='admin_user_add.php' style='color: var(--primary);'>Create one now</a></p></td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Info Alert -->
            <div class="alert alert-primary mt-6">
                <i class="fas fa-info-circle alert-icon"></i>
                <div class="alert-content">
                    <h4>User Management</h4>
                    <p>System administrators cannot be edited or deleted to protect the system. Role assignments determine user permissions and access levels throughout the application.</p>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include "../includes/footer.php"; ?>