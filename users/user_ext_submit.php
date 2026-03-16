<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$error = "";

// Fetch users for dynamic team dropdown
$users_sql = "SELECT user_id, first_name, last_name, role_id FROM users WHERE role_id IN (5, 6) AND user_id != ? ORDER BY last_name ASC";
$ustmt = $conn->prepare($users_sql);
$ustmt->bind_param("i", $_SESSION['id']);
$ustmt->execute();
$users_result = $ustmt->get_result();

$user_options = "";
while($u = $users_result->fetch_assoc()) {
    $role_label = ($u['role_id'] == 8) ? 'Faculty' : 'Student';
    $user_options .= "<option value='{$u['user_id']}'>".htmlspecialchars($u['last_name'].", ".$u['first_name'])." ({$role_label})</option>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["project_title"]);
    $program = trim($_POST["program_name"]);
    $beneficiary = trim($_POST["beneficiary_name"]);
    $address = trim($_POST["beneficiary_address"]);
    $budget = empty($_POST["budget"]) ? 0.00 : floatval($_POST["budget"]);
    $funding_source = trim($_POST["source_of_funds"]);
    $start_date = empty($_POST["start_date"]) ? NULL : $_POST["start_date"];
    $end_date = empty($_POST["end_date"]) ? NULL : $_POST["end_date"];
    
    // Default status for new Extension proposals (Based on the DB ENUM we set earlier)
    $status = 'Proposed'; 

    if(empty($title) || empty($beneficiary)){
        $error = "Project Title and Beneficiary Name are required.";
    } elseif (!isset($_FILES['ext_file']) || $_FILES['ext_file']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please attach your Extension Proposal Document.";
    } else {
        $conn->begin_transaction();

        try {
            // 1. Insert into ext_projects (Added proposed_by for tracking)
            $sql1 = "INSERT INTO ext_projects (project_title, program_name, beneficiary_name, beneficiary_address, budget, source_of_funds, start_date, end_date, service_status, proposed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("ssssdssssi", $title, $program, $beneficiary, $address, $budget, $funding_source, $start_date, $end_date, $status, $_SESSION["id"]);
            $stmt1->execute();
            $new_ext_id = $stmt1->insert_id;

            // 2. Assign Logged-in User as Project Leader
            $sql2 = "INSERT INTO ext_proponents (ext_id, user_id, role) VALUES (?, ?, 'Project Leader')";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("ii", $new_ext_id, $_SESSION["id"]);
            $stmt2->execute();

            // 3. Process Dynamic Team Members
            if (isset($_POST['team_user_id']) && is_array($_POST['team_user_id'])) {
                $team_users = $_POST['team_user_id'];
                $team_roles = $_POST['team_role'];
                
                $sql_team = "INSERT INTO ext_proponents (ext_id, user_id, role) VALUES (?, ?, ?)";
                $stmt_team = $conn->prepare($sql_team);
                
                for ($i = 0; $i < count($team_users); $i++) {
                    $t_user = intval($team_users[$i]);
                    $t_role = trim($team_roles[$i]);
                    
                    if ($t_user > 0) {
                        $stmt_team->bind_param("iis", $new_ext_id, $t_user, $t_role);
                        $stmt_team->execute();
                    }
                }
            }

            // 4. File Upload (Saved in uploads/extension/)
            $file = $_FILES['ext_file'];
            $file_name = basename($file["name"]);
            $clean_file_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
            $unique_file_name = time() . "_EXT_" . $new_ext_id . "_" . $clean_file_name;
            $target_file = "../uploads/extension/" . $unique_file_name;

            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                $sql3 = "INSERT INTO documents (module_type, reference_id, doc_category, file_name, file_path, uploaded_by) VALUES ('EXT', ?, 'Extension Proposal', ?, ?, ?)";
                $stmt3 = $conn->prepare($sql3);
                $stmt3->bind_param("issi", $new_ext_id, $file_name, $target_file, $_SESSION["id"]);
                $stmt3->execute();
            } else {
                throw new Exception("File upload failed. Ensure 'uploads/extension/' folder exists.");
            }

            $conn->commit();
            echo "<script>alert('Extension Project successfully proposed!'); window.location.href='user_dashboard.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to submit proposal: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Propose Extension Service</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen">
    
    <nav class="bg-blue-800 text-white shadow-md p-4 flex justify-between">
        <div class="font-bold text-xl tracking-wider">BISU R.I.T.E.S</div>
        <a href="user_dashboard.php" class="text-blue-200 hover:text-white"><i class="fas fa-arrow-left"></i> Back to Portal</a>
    </nav>

    <div class="max-w-4xl mx-auto py-10 px-4">
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><i class="fas fa-handshake text-green-600 mr-2"></i> Propose Extension Service</h2>
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-green-500">
            <?php if($error) echo "<p class='text-red-600 mb-4 bg-red-50 p-3 rounded'>$error</p>"; ?>
            
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-2">1. Activity Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Project Title *</label>
                            <input type="text" name="project_title" required class="w-full border border-slate-300 rounded p-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Program Name (Optional)</label>
                            <input type="text" name="program_name" class="w-full border border-slate-300 rounded p-2 focus:ring-green-500" placeholder="e.g. Adopt-a-Barangay">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Target Beneficiary Name *</label>
                            <input type="text" name="beneficiary_name" required class="w-full border border-slate-300 rounded p-2 focus:ring-green-500" placeholder="e.g. Farmers of Brgy. San Jose">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Beneficiary Address</label>
                            <input type="text" name="beneficiary_address" class="w-full border border-slate-300 rounded p-2 focus:ring-green-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Budget (₱)</label>
                            <input type="number" step="0.01" name="budget" placeholder="0.00" class="w-full border border-slate-300 rounded p-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Source of Funds</label>
                            <input type="text" name="source_of_funds" placeholder="e.g. LGU, NGO" class="w-full border border-slate-300 rounded p-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Target Start Date</label>
                            <input type="date" name="start_date" class="w-full border border-slate-300 rounded p-2 focus:ring-green-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Target End Date</label>
                            <input type="date" name="end_date" class="w-full border border-slate-300 rounded p-2 focus:ring-green-500">
                        </div>
                    </div>
                </div>

                <div class="space-y-4 bg-green-50 p-4 rounded-lg border border-green-200">
                    <div class="flex justify-between items-center border-b border-green-300 pb-2 mb-3">
                        <h3 class="text-lg font-bold text-slate-800">2. Implementation Team</h3>
                        <button type="button" onclick="addTeamMember()" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm font-bold transition">
                            <i class="fas fa-plus"></i> Add Member
                        </button>
                    </div>
                    
                    <p class="text-xs text-slate-600 mb-4">Note: You are automatically assigned as the <strong>Project Leader</strong>.</p>
                    
                    <div id="team_container" class="space-y-3">
                        </div>
                </div>

                <div class="space-y-4 mt-6">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-2">3. Proposal Document</h3>
                    <div class="p-4 border border-dashed border-green-400 rounded bg-white">
                        <label class="block text-sm font-bold text-slate-700 mb-2"><i class="fas fa-paperclip mr-1"></i> Attach Detailed Proposal / Endorsement *</label>
                        <input type="file" name="ext_file" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-green-600 file:text-white hover:file:bg-green-700 cursor-pointer">
                    </div>
                </div>

                <div class="pt-6 flex justify-end">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-8 py-3 rounded shadow-md transition font-bold text-lg">Submit Proposal</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addTeamMember() {
            const container = document.getElementById('team_container');
            const rowHtml = `
                <div class="flex gap-4 items-end team-row bg-white p-3 border border-slate-200 rounded shadow-sm animate-fadeIn">
                    <div class="flex-1">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Select Member</label>
                        <select name="team_user_id[]" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-green-500" required>
                            <option value="">-- Search User --</option>
                            <?php echo $user_options; ?>
                        </select>
                    </div>
                    <div class="w-1/3">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Role</label>
                        <select name="team_role[]" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-green-500" required>
                            <option value="Member">Member</option>
                            <option value="Coordinator">Coordinator</option>
                            <option value="Trainer">Trainer / Speaker</option>
                        </select>
                    </div>
                    <button type="button" onclick="this.closest('.team-row').remove()" class="bg-red-100 text-red-600 hover:bg-red-200 h-[38px] px-3 rounded font-bold transition" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }
    </script>
</body>
</html>