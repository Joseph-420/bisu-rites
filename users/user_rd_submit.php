<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$error = "";
$colleges_result = $conn->query("SELECT college_id, college_name FROM colleges WHERE college_code != 'ADMIN' ORDER BY college_name ASC");

// Fetch eligible users (Faculty = 8, Student = 9) to populate the team dropdown
// We exclude the currently logged-in user since they are automatically the Main Author.
$users_sql = "SELECT user_id, first_name, last_name, role_id FROM users WHERE role_id IN (5, 6) AND user_id != ? ORDER BY last_name ASC";
$ustmt = $conn->prepare($users_sql);
$ustmt->bind_param("i", $_SESSION['id']);
$ustmt->execute();
$users_result = $ustmt->get_result();

// Pre-build the <option> list in PHP so we can inject it into our JavaScript easily
$user_options = "";
while($u = $users_result->fetch_assoc()) {
    $role_label = ($u['role_id'] == 8) ? 'Faculty' : 'Student';
    $user_options .= "<option value='{$u['user_id']}'>".htmlspecialchars($u['last_name'].", ".$u['first_name'])." ({$role_label})</option>";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["project_title"]);
    $abstract = trim($_POST["abstract"]);
    $budget = empty($_POST["budget"]) ? 0.00 : floatval($_POST["budget"]);
    $college_id = empty($_POST["college_id"]) ? NULL : intval($_POST["college_id"]);
    $start_date = empty($_POST["start_date"]) ? NULL : $_POST["start_date"];
    $end_date = empty($_POST["end_date"]) ? NULL : $_POST["end_date"];
    $status = 'Submitted'; 

    if(empty($title) || empty($college_id)){
        $error = "Title and College are required.";
    } elseif (!isset($_FILES['proposal_file']) || $_FILES['proposal_file']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please attach your proposal document.";
    } else {
        $conn->begin_transaction();

        try {
            // 1. Insert Project Details
            $sql1 = "INSERT INTO rd_projects (project_title, abstract, status, budget, start_date, end_date, college_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("sssdssi", $title, $abstract, $status, $budget, $start_date, $end_date, $college_id);
            $stmt1->execute();
            $new_project_id = $stmt1->insert_id;

            // 2. Assign Logged-in User as Main Author
            $sql2 = "INSERT INTO rd_proponents (rd_id, user_id, project_role) VALUES (?, ?, 'Main Author')";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("ii", $new_project_id, $_SESSION["id"]);
            $stmt2->execute();

            // 3. Process Dynamic Co-Authors / Team Members
            if (isset($_POST['team_user_id']) && is_array($_POST['team_user_id'])) {
                $team_users = $_POST['team_user_id'];
                $team_roles = $_POST['team_role'];
                
                $sql_team = "INSERT INTO rd_proponents (rd_id, user_id, project_role) VALUES (?, ?, ?)";
                $stmt_team = $conn->prepare($sql_team);
                
                // Loop through the arrays sent by the dynamic JS form
                for ($i = 0; $i < count($team_users); $i++) {
                    $t_user = intval($team_users[$i]);
                    $t_role = trim($team_roles[$i]);
                    
                    if ($t_user > 0) { // Only insert if they actually selected a user
                        $stmt_team->bind_param("iis", $new_project_id, $t_user, $t_role);
                        $stmt_team->execute();
                    }
                }
            }

            // 4. Handle File Upload
            $file = $_FILES['proposal_file'];
            $file_name = basename($file["name"]);
            $clean_file_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
            $unique_file_name = time() . "_" . $new_project_id . "_" . $clean_file_name;
            $target_dir = "../uploads/rd/";
            $target_file = $target_dir . $unique_file_name;

            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                $doc_category = "Initial Proposal";
                $module = "RD";
                $sql3 = "INSERT INTO documents (module_type, reference_id, doc_category, file_name, file_path, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt3 = $conn->prepare($sql3);
                $stmt3->bind_param("sisssi", $module, $new_project_id, $doc_category, $file_name, $target_file, $_SESSION["id"]);
                $stmt3->execute();
            } else {
                throw new Exception("File upload failed. Please check folder permissions.");
            }

            $conn->commit();
            echo "<script>alert('Proposal successfully submitted!'); window.location.href='user_dashboard.php';</script>";
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
    <title>Submit Proposal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen">
    
    <nav class="bg-blue-800 text-white shadow-md p-4 flex justify-between">
        <div class="font-bold text-xl tracking-wider">BISU R.I.T.E.S</div>
        <a href="user_dashboard.php" class="text-blue-200 hover:text-white"><i class="fas fa-arrow-left"></i> Back to Portal</a>
    </nav>

    <div class="max-w-4xl mx-auto py-10 px-4">
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><i class="fas fa-file-upload text-blue-600 mr-2"></i> Submit R&D Proposal</h2>
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <?php if($error) echo "<p class='text-red-600 mb-4 bg-red-50 p-3 rounded'>$error</p>"; ?>
            
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-2">1. Project Details</h3>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Proposed Project Title *</label>
                        <input type="text" name="project_title" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Abstract / Brief Rationale</label>
                        <textarea name="abstract" rows="4" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-slate-700 mb-1">Lead College *</label>
                            <select name="college_id" required class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500">
                                <option value="">-- Select --</option>
                                <?php 
                                if ($colleges_result->num_rows > 0) {
                                    while($c = $colleges_result->fetch_assoc()) {
                                        echo "<option value='{$c['college_id']}'>{$c['college_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">End Date</label>
                            <input type="date" name="end_date" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>

                <div class="space-y-4 bg-slate-50 p-4 rounded-lg border border-slate-200">
                    <div class="flex justify-between items-center border-b border-slate-300 pb-2 mb-3">
                        <h3 class="text-lg font-bold text-slate-800">2. Project Team</h3>
                        <button type="button" onclick="addTeamMember()" class="bg-emerald-100 hover:bg-emerald-200 text-emerald-700 px-3 py-1 rounded text-sm font-bold transition">
                            <i class="fas fa-plus"></i> Add Member
                        </button>
                    </div>
                    
                    <p class="text-xs text-slate-500 mb-4">Note: You are automatically assigned as the <strong>Main Author/Project Leader</strong>.</p>
                    
                    <div id="team_container" class="space-y-3">
                        </div>
                </div>

                <div class="space-y-4 mt-6">
                    <h3 class="text-lg font-bold text-slate-800 border-b pb-2">3. Supporting Documents</h3>
                    <div class="p-4 border border-dashed border-blue-300 rounded bg-blue-50">
                        <label class="block text-sm font-bold text-slate-700 mb-2"><i class="fas fa-paperclip mr-1"></i> Attach Proposal Document *</label>
                        <input type="file" name="proposal_file" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700">
                    </div>
                </div>

                <div class="pt-6 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-8 py-3 rounded shadow-md transition font-bold text-lg">Submit to R&D Office</button>
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
                        <select name="team_user_id[]" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-blue-500" required>
                            <option value="">-- Search User --</option>
                            <?php echo $user_options; // Inject the PHP options here ?>
                        </select>
                    </div>
                    <div class="w-1/3">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Project Role</label>
                        <select name="team_role[]" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-blue-500" required>
                            <option value="Co-Author">Co-Author</option>
                            <option value="Adviser">Adviser</option>
                            <option value="Member">Member</option>
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