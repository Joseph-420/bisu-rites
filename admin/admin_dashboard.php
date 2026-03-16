<?php
session_start();
require_once "../db_connect.php";

// Check if the user is logged in and is a Superadmin (Role 1)
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role_id"] !== 1){
    header("location: ../login.php");
    exit;
}

// --- SYSTEM STATISTICS FOR SUPERADMIN ---

// 1. Get Total Users Count
$user_count = 0;
$user_query = $conn->query("SELECT COUNT(*) as count FROM users");
if($user_query) {
    $user_count = $user_query->fetch_assoc()['count'];
}

// 2. Get Total Colleges Count
$college_count = 0;
$college_query = $conn->query("SELECT COUNT(*) as count FROM colleges");
if($college_query) {
    $college_count = $college_query->fetch_assoc()['count'];
}

// 3. Get Count of Directors
$director_count = 0;
$dir_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id IN (2, 3, 4)");
if($dir_query) {
    $director_count = $dir_query->fetch_assoc()['count'];
}

// 4. Get Faculty Count
$faculty_count = 0;
$fac_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 5");
if($fac_query) {
    $faculty_count = $fac_query->fetch_assoc()['count'];
}

// 5. Get Student Count
$student_count = 0;
$stu_query = $conn->query("SELECT COUNT(*) as count FROM users WHERE role_id = 6");
if($stu_query) {
    $student_count = $stu_query->fetch_assoc()['count'];
}

$page_title = "System Admin Dashboard";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">System Controller</h1>
                <p class="text-slate-500 text-sm mt-1">Manage university departments and user access roles.</p>
            </div>
            <div class="flex items-center space-x-4 bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="text-sm">
                    <p class="text-slate-500 text-xs">Logged in as</p>
                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION["username"]); ?></p>
                </div>
                <button class="btn btn-outline btn-sm" onclick="window.location.href='../logout.php'" style="margin-left: auto;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Total Users</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $user_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 text-xl">
                    <i class="fas fa-users"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-emerald-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Colleges</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $college_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600 text-xl">
                    <i class="fas fa-university"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-purple-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Directors</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $director_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center text-purple-600 text-xl">
                    <i class="fas fa-user-tie"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Faculty</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $faculty_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center text-amber-600 text-xl">
                    <i class="fas fa-chalkboard-user"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-cyan-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Students</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $student_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-cyan-50 rounded-lg flex items-center justify-center text-cyan-600 text-xl">
                    <i class="fas fa-graduation-cap"></i>
                </div>
            </div>

        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">Quick Setup Actions</h3>
            <div class="grid grid-cols-2 gap-4">
                <a href="admin_college_add.php" class="p-4 border border-slate-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition flex items-center">
                    <i class="fas fa-plus-circle text-blue-500 text-2xl mr-4"></i>
                    <div>
                        <h4 class="font-bold text-slate-700">Register New College</h4>
                        <p class="text-sm text-slate-500">Add a new department to the system.</p>
                    </div>
                </a>
                <a href="admin_user_add.php" class="p-4 border border-slate-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition flex items-center">
                    <i class="fas fa-user-plus text-blue-500 text-2xl mr-4"></i>
                    <div>
                        <h4 class="font-bold text-slate-700">Assign Director/Staff</h4>
                        <p class="text-sm text-slate-500">Create accounts for office personnel.</p>
                    </div>
                </a>
            </div>
        </div>
        
    </div>
</div>

<?php include "../includes/footer.php"; ?>