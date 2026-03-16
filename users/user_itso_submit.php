<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$error = "";

// Fetch users for dropdown (same logic as R&D)
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
    $title = trim($_POST["title"]);
    $ip_type = $_POST["ip_type"];
    $is_funded = isset($_POST["is_externally_funded"]) ? 1 : 0;
    $funding_agency = $is_funded ? trim($_POST["funding_agency"]) : NULL;
    $status = 'Disclosure Submitted'; 

    // Main Author specifics
    $main_task = trim($_POST["main_task"]);
    $main_contribution = floatval($_POST["main_contribution"]);

    if(empty($title) || empty($ip_type)){
        $error = "Technology Title and IP Type are required.";
    } elseif (!isset($_FILES['ip_file']) || $_FILES['ip_file']['error'] == UPLOAD_ERR_NO_FILE) {
        $error = "Please attach your Draft Document.";
    } else {
        $conn->begin_transaction();

        try {
            // 1. Insert IP Asset
            $sql1 = "INSERT INTO ip_assets (title, ip_type, status, is_externally_funded, funding_agency, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt1 = $conn->prepare($sql1);
            $stmt1->bind_param("sssiis", $title, $ip_type, $status, $is_funded, $funding_agency, $_SESSION["id"]);
            $stmt1->execute();
            $new_ip_id = $stmt1->insert_id;

            // 2. Assign Main Inventor (with their defined task and percentage)
            $sql2 = "INSERT INTO ip_inventors (ip_id, user_id, contribution_percentage, task_assignment) VALUES (?, ?, ?, ?)";
            $stmt2 = $conn->prepare($sql2);
            $stmt2->bind_param("iids", $new_ip_id, $_SESSION["id"], $main_contribution, $main_task);
            $stmt2->execute();

            // 3. Process Dynamic Co-Makers / Inventors
            if (isset($_POST['team_user_id']) && is_array($_POST['team_user_id'])) {
                $team_users = $_POST['team_user_id'];
                $team_tasks = $_POST['team_task'];
                $team_contribs = $_POST['team_contribution'];
                
                $sql_team = "INSERT INTO ip_inventors (ip_id, user_id, contribution_percentage, task_assignment) VALUES (?, ?, ?, ?)";
                $stmt_team = $conn->prepare($sql_team);
                
                for ($i = 0; $i < count($team_users); $i++) {
                    $t_user = intval($team_users[$i]);
                    $t_task = trim($team_tasks[$i]);
                    $t_contrib = floatval($team_contribs[$i]);
                    
                    if ($t_user > 0) {
                        $stmt_team->bind_param("iids", $new_ip_id, $t_user, $t_contrib, $t_task);
                        $stmt_team->execute();
                    }
                }
            }

            // 4. File Upload
            $file = $_FILES['ip_file'];
            $file_name = basename($file["name"]);
            $clean_file_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
            $unique_file_name = time() . "_IP_" . $new_ip_id . "_" . $clean_file_name;
            $target_file = "../uploads/itso/" . $unique_file_name;

            if (move_uploaded_file($file["tmp_name"], $target_file)) {
                $sql3 = "INSERT INTO documents (module_type, reference_id, doc_category, file_name, file_path, uploaded_by) VALUES ('ITSO', ?, 'Draft Patent / Copy of Work', ?, ?, ?)";
                $stmt3 = $conn->prepare($sql3);
                $stmt3->bind_param("issi", $new_ip_id, $file_name, $target_file, $_SESSION["id"]);
                $stmt3->execute();
            } else {
                throw new Exception("File upload failed. Ensure 'uploads/itso/' exists.");
            }

            $conn->commit();
            echo "<script>alert('Intellectual Property successfully disclosed to the ITSO Office!'); window.location.href='user_dashboard.php';</script>";
            exit;
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Failed to submit disclosure: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit IP Disclosure</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        function toggleFunding() {
            var checkbox = document.getElementById('funding_checkbox');
            var inputDiv = document.getElementById('funding_input_div');
            inputDiv.style.display = checkbox.checked ? 'block' : 'none';
        }
    </script>
</head>
<body class="bg-slate-50 min-h-screen">
    
    <nav class="bg-blue-800 text-white shadow-md p-4 flex justify-between">
        <div class="font-bold text-xl tracking-wider">BISU R.I.T.E.S</div>
        <a href="user_dashboard.php" class="text-blue-200 hover:text-white"><i class="fas fa-arrow-left"></i> Back to Portal</a>
    </nav>

    <div class="max-w-4xl mx-auto py-10 px-4">
        <h2 class="text-2xl font-bold text-slate-800 mb-6"><i class="fas fa-lightbulb text-teal-600 mr-2"></i> Submit IP Disclosure</h2>
        
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
            <?php if($error) echo "<p class='text-red-600 mb-4 bg-red-50 p-3 rounded'>$error</p>"; ?>
            
            <form method="post" enctype="multipart/form-data" class="space-y-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Title of the Technology / Work *</label>
                        <input type="text" name="title" required class="w-full border border-slate-300 rounded p-2 focus:ring-teal-500">
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Type of Intellectual Property *</label>
                        <select name="ip_type" required class="w-full border border-slate-300 rounded p-2 focus:ring-teal-500">
                            <option value="">-- Select IP Type --</option>
                            <option value="Patent">Patent (Invention)</option>
                            <option value="Utility Model">Utility Model</option>
                            <option value="Industrial Design">Industrial Design</option>
                            <option value="Copyright">Copyright</option>
                            <option value="Trademark">Trademark</option>
                        </select>
                    </div>

                    <div class="bg-slate-50 p-4 rounded border border-slate-200">
                        <label class="flex items-center space-x-2 text-sm text-slate-700 cursor-pointer">
                            <input type="checkbox" name="is_externally_funded" id="funding_checkbox" onchange="toggleFunding()" class="rounded text-teal-600 focus:ring-teal-500">
                            <span>Is your technology funded by an external agency?</span>
                        </label>
                        <div id="funding_input_div" style="display:none;" class="mt-3">
                            <input type="text" name="funding_agency" placeholder="Name of Funding Agency" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-teal-500">
                        </div>
                    </div>
                </div>

                <div class="space-y-4 bg-teal-50 p-4 rounded-lg border border-teal-200">
                    <div class="flex justify-between items-center border-b border-teal-300 pb-2 mb-3">
                        <h3 class="text-lg font-bold text-slate-800">Inventors / Makers Setup</h3>
                        <button type="button" onclick="addInventor()" class="bg-teal-600 hover:bg-teal-700 text-white px-3 py-1 rounded text-sm font-bold transition">
                            <i class="fas fa-plus"></i> Add Co-Maker
                        </button>
                    </div>
                    
                    <p class="text-xs text-slate-600 mb-4">Total contribution percentage across all members must equal 100%.</p>
                    
                    <div class="bg-white p-3 border border-slate-300 rounded shadow-sm mb-4 border-l-4 border-l-teal-500">
                        <p class="text-sm font-bold text-slate-700 mb-2">My Information (Main Developer)</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">Task Assignment *</label>
                                <input type="text" name="main_task" required placeholder="e.g. Lead Programmer, Hardware Design" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-teal-500">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-1">My Contribution (%) *</label>
                                <input type="number" step="0.01" name="main_contribution" required placeholder="e.g. 60" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-teal-500">
                            </div>
                        </div>
                    </div>

                    <div id="inventors_container" class="space-y-3"></div>
                </div>

                <div class="p-4 border border-dashed border-teal-300 rounded bg-white">
                    <label class="block text-sm font-bold text-slate-800 mb-2"><i class="fas fa-file-upload mr-1"></i> Attach Draft Document *</label>
                    <input type="file" name="ip_file" required class="w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:bg-teal-600 file:text-white hover:file:bg-teal-700">
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="bg-teal-600 hover:bg-teal-700 text-white px-8 py-3 rounded shadow transition font-bold text-lg">Submit Disclosure</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function addInventor() {
            const container = document.getElementById('inventors_container');
            const rowHtml = `
                <div class="flex gap-3 items-end inventor-row bg-white p-3 border border-slate-300 rounded shadow-sm animate-fadeIn">
                    <div class="w-1/3">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Select Co-Maker</label>
                        <select name="team_user_id[]" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-teal-500" required>
                            <option value="">-- Search User --</option>
                            <?php echo $user_options; ?>
                        </select>
                    </div>
                    <div class="w-1/3">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Task Assignment</label>
                        <input type="text" name="team_task[]" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-teal-500" placeholder="e.g. Data Analyst" required>
                    </div>
                    <div class="w-1/4">
                        <label class="block text-xs font-bold text-slate-600 mb-1">Contrib (%)</label>
                        <input type="number" step="0.01" name="team_contribution[]" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-teal-500" placeholder="e.g. 40" required>
                    </div>
                    <button type="button" onclick="this.closest('.inventor-row').remove()" class="bg-red-100 text-red-600 hover:bg-red-200 h-[38px] px-3 rounded font-bold transition" title="Remove">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', rowHtml);
        }
    </script>
</body>
</html>