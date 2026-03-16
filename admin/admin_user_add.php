<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 1){ header("location: ../login.php"); exit; }

$username = $password = $first_name = $last_name = $role_id = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $role_id = $_POST["role_id"];
    
    // Basic Validation
    if(empty($username) || empty($password) || empty($role_id)){
        $error = "All fields are required.";
    } else {
        // Insert
        $sql = "INSERT INTO users (username, password_hash, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssssi", $username, $hashed_password, $first_name, $last_name, $role_id);
            
            if ($stmt->execute()) {
                header("location: admin_users.php"); // Success! Back to list
                exit();
            } else {
                $error = "Error: Username might already exist.";
            }
        }
    }
}

$page_title = "Add User";
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
            <h1 class="header-title">
                <i class="fas fa-user-plus" style="margin-right: 0.75rem; color: var(--primary);"></i>
                Add New User
            </h1>
            <div class="header-actions">
                <button onclick="window.history.back()" class="btn btn-ghost">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>
        </div>

        <!-- Content Wrapper -->
        <div class="content-wrapper">
            
            <!-- Form Card -->
            <div class="card animate-fadeIn" style="max-width: 700px;">
                <div class="card-header">
                    <h2>User Account Details</h2>
                    <p>Create a new user account in the system</p>
                </div>
                
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-destructive mb-6">
                            <i class="fas fa-exclamation-triangle alert-icon"></i>
                            <div class="alert-content">
                                <h4>Error</h4>
                                <p><?php echo htmlspecialchars($error); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="post" class="space-y-6">
                        <!-- Name Fields Row -->
                        <div class="grid grid-cols-2">
                            <div class="form-group">
                                <label for="first_name" class="block text-sm font-medium text-foreground mb-2">
                                    <i class="fas fa-user-circle mr-2 text-primary"></i>First Name
                                </label>
                                <input 
                                    type="text" 
                                    id="first_name"
                                    name="first_name" 
                                    class="input" 
                                    placeholder="John"
                                    value="<?php echo htmlspecialchars($first_name); ?>"
                                    required
                                >
                            </div>
                            <div class="form-group">
                                <label for="last_name" class="block text-sm font-medium text-foreground mb-2">
                                    <i class="fas fa-user-circle mr-2 text-primary"></i>Last Name
                                </label>
                                <input 
                                    type="text" 
                                    id="last_name"
                                    name="last_name" 
                                    class="input" 
                                    placeholder="Doe"
                                    value="<?php echo htmlspecialchars($last_name); ?>"
                                    required
                                >
                            </div>
                        </div>
                        
                        <!-- Credentials Section -->
                        <div>
                            <h3 class="text-sm font-semibold text-foreground mb-4 pb-2 border-b border-border">Login Credentials</h3>
                            
                            <div class="form-group">
                                <label for="username" class="block text-sm font-medium text-foreground mb-2">
                                    <i class="fas fa-at mr-2 text-primary"></i>Username
                                </label>
                                <input 
                                    type="text" 
                                    id="username"
                                    name="username" 
                                    class="input" 
                                    placeholder="johndoe"
                                    value="<?php echo htmlspecialchars($username); ?>"
                                    required
                                >
                                <p class="text-xs text-muted mt-1">Use lowercase letters and numbers only. No spaces.</p>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="block text-sm font-medium text-foreground mb-2">
                                    <i class="fas fa-lock mr-2 text-primary"></i>Password
                                </label>
                                <input 
                                    type="password" 
                                    id="password"
                                    name="password" 
                                    class="input" 
                                    placeholder="••••••••"
                                    required
                                >
                                <p class="text-xs text-muted mt-1">Use a strong password with at least 8 characters.</p>
                            </div>
                        </div>
                        
                        <!-- Role Selection -->
                        <div>
                            <h3 class="text-sm font-semibold text-foreground mb-4 pb-2 border-b border-border">Role Assignment</h3>
                            
                            <div class="form-group">
                                <label for="role_id" class="block text-sm font-medium text-foreground mb-2">
                                    <i class="fas fa-shield-alt mr-2 text-primary"></i>User Role
                                </label>
                                <select name="role_id" id="role_id" class="input" required>
                                    <option value="">-- Select a role --</option>
                                    <option value="2" <?php echo $role_id == 2 ? 'selected' : ''; ?>>R&D Director</option>
                                    <option value="3" <?php echo $role_id == 3 ? 'selected' : ''; ?>>ITSO Director</option>
                                    <option value="4" <?php echo $role_id == 4 ? 'selected' : ''; ?>>Extension Director</option>
                                    <option value="5" <?php echo $role_id == 5 ? 'selected' : ''; ?>>Faculty</option>
                                    <option value="6" <?php echo $role_id == 6 ? 'selected' : ''; ?>>Student</option>
                                    <option value="1" <?php echo $role_id == 1 ? 'selected' : ''; ?>>Superadmin</option>
                                </select>
                                <p class="text-xs text-muted mt-1">Superadmin has full system access. Assign carefully.</p>
                            </div>
                        </div>
                        
                        <!-- Permissions Info -->
                        <div class="alert alert-primary">
                            <i class="fas fa-shield-alt alert-icon"></i>
                            <div class="alert-content">
                                <h4>Permissions</h4>
                                <p>Different roles have different permissions and access levels. Directors have management access to their departments.</p>
                            </div>
                        </div>
                        
                        <div class="card-footer" style="border-top: 1px solid var(--border); padding-top: 1.5rem;">
                            <a href="admin_users.php" class="btn btn-ghost">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-check mr-1"></i> Create User
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

</div>

<?php include "../includes/footer.php"; ?>