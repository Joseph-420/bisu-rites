<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$user_id = $_SESSION["id"];
$msg = "";
$msg_type = "";

// --- Handle Form Submissions ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ACTION 1: Update Profile Information
    if (isset($_POST['update_profile'])) {
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        $email = trim($_POST['email']);
        $college_id = !empty($_POST['college_id']) ? intval($_POST['college_id']) : NULL;

        if (empty($first_name) || empty($last_name)) {
            $msg = "First name and last name are required.";
            $msg_type = "error";
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, college_id=? WHERE user_id=?");
            $stmt->bind_param("sssii", $first_name, $last_name, $email, $college_id, $user_id);
            if ($stmt->execute()) {
                $msg = "Profile information updated successfully!";
                $msg_type = "success";
            } else {
                $msg = "Error updating profile. Please try again.";
                $msg_type = "error";
            }
            $stmt->close();
        }
    }

    // ACTION 2: Update Password
    if (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Fetch current hashed password from database to verify
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id=?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_row = $result->fetch_assoc();
        $hashed_password = $user_row['password'];
        $stmt->close();

        // Verify old password
        if (password_verify($current_password, $hashed_password)) {
            // Check if new passwords match
            if ($new_password === $confirm_password) {
                // Ensure password isn't empty and meets basic length requirement
                if (strlen($new_password) >= 6) {
                    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    $update_stmt = $conn->prepare("UPDATE users SET password=? WHERE user_id=?");
                    $update_stmt->bind_param("si", $new_hashed_password, $user_id);
                    
                    if ($update_stmt->execute()) {
                        $msg = "Password changed successfully!";
                        $msg_type = "success";
                    } else {
                        $msg = "Oops! Something went wrong updating your password.";
                        $msg_type = "error";
                    }
                    $update_stmt->close();
                } else {
                    $msg = "New password must have at least 6 characters.";
                    $msg_type = "error";
                }
            } else {
                $msg = "New passwords do not match.";
                $msg_type = "error";
            }
        } else {
            $msg = "The current password you entered is incorrect.";
            $msg_type = "error";
        }
    }
}

// --- Fetch User's Current Data ---
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Fetch Colleges for Dropdown ---
$colleges_result = $conn->query("SELECT * FROM colleges WHERE college_code != 'ADMIN' ORDER BY college_name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Account Settings - BISU RITES</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

    <nav class="bg-blue-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 font-bold text-xl tracking-wider">
                    BISU R.I.T.E.S <span class="text-blue-300 text-sm font-normal">| Researcher Portal</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="user_dashboard.php" class="text-blue-200 hover:text-white text-sm font-medium"><i class="fas fa-home mr-1"></i> Dashboard</a>
                    <span class="text-slate-400">|</span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <main class="flex-grow max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full">
        
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-user-cog text-blue-600 mr-2"></i> Account Settings</h1>
        </div>

        <?php if ($msg): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $msg_type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                <i class="fas <?php echo $msg_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden h-fit">
                <div class="p-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="font-bold text-slate-800"><i class="fas fa-id-card text-slate-400 mr-2"></i> Personal Information</h3>
                </div>
                <div class="p-6">
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="update_profile" value="1">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">First Name *</label>
                                <input type="text" name="first_name" value="<?php echo htmlspecialchars($current_user['first_name']); ?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Last Name *</label>
                                <input type="text" name="last_name" value="<?php echo htmlspecialchars($current_user['last_name']); ?>" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Email Address</label>
                            <input type="email" name="email" value="<?php echo htmlspecialchars($current_user['email'] ?? ''); ?>" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Department / College</label>
                            <select name="college_id" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 text-sm">
                                <option value="">-- Select your College --</option>
                                <?php 
                                if ($colleges_result->num_rows > 0) {
                                    while($c = $colleges_result->fetch_assoc()) {
                                        $selected = ($c['college_id'] == $current_user['college_id']) ? "selected" : "";
                                        echo "<option value='{$c['college_id']}' {$selected}>{$c['college_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">System Username (Read-Only)</label>
                            <input type="text" disabled value="<?php echo htmlspecialchars($current_user['username']); ?>" class="w-full border border-slate-200 bg-slate-100 text-slate-500 rounded p-2 text-sm cursor-not-allowed">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition text-sm">
                                Save Profile Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden h-fit">
                <div class="p-4 border-b border-slate-200 bg-slate-50">
                    <h3 class="font-bold text-slate-800"><i class="fas fa-lock text-slate-400 mr-2"></i> Security & Password</h3>
                </div>
                <div class="p-6">
                    <form method="post" class="space-y-4">
                        <input type="hidden" name="update_password" value="1">
                        
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Current Password *</label>
                            <input type="password" name="current_password" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Verify your current password">
                        </div>

                        <div class="border-t border-slate-100 pt-4 mt-2">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">New Password *</label>
                            <input type="password" name="new_password" required minlength="6" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Minimum 6 characters">
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Confirm New Password *</label>
                            <input type="password" name="confirm_password" required minlength="6" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Retype new password">
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-bold py-2 px-4 rounded transition text-sm">
                                Update Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </main>
</body>
</html>