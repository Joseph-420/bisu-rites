<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$rd_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION["id"];
$msg = "";
$msg_type = "";

// 1. SECURITY CHECK: Ensure this user is actually part of this project!
$check_sql = "SELECT project_role FROM rd_proponents WHERE rd_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $rd_id, $user_id);
$check_stmt->execute();
$role_result = $check_stmt->get_result();

if($role_result->num_rows === 0) {
    header("location: user_dashboard.php"); exit; // Unauthorized access
}
$my_role = $role_result->fetch_assoc()['project_role'];

// 2. Fetch Project Details
$proj_sql = "SELECT p.*, c.college_name FROM rd_projects p LEFT JOIN colleges c ON p.college_id = c.college_id WHERE p.rd_id = ?";
$proj_stmt = $conn->prepare($proj_sql);
$proj_stmt->bind_param("i", $rd_id);
$proj_stmt->execute();
$project = $proj_stmt->get_result()->fetch_assoc();

// 3. Handle Updates (Only allowed if status is Draft or Deferred)
$is_editable = in_array($project['status'], ['Draft', 'Deferred', 'Rejected']);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $is_editable && $my_role == 'Main Author') {
    $abstract = trim($_POST['abstract']);
    
    // Update abstract and reset status to 'Submitted' for re-evaluation
    $update_sql = "UPDATE rd_projects SET abstract = ?, status = 'Submitted' WHERE rd_id = ?";
    $up_stmt = $conn->prepare($update_sql);
    $up_stmt->bind_param("si", $abstract, $rd_id);
    
    if($up_stmt->execute()) {
        
        // Handle new file upload if provided
        if(isset($_FILES['new_file']) && $_FILES['new_file']['error'] == 0) {
            $file = $_FILES['new_file'];
            $file_name = basename($file["name"]);
            $clean_file_name = preg_replace("/[^a-zA-Z0-9.-]/", "_", $file_name);
            $unique_file_name = time() . "_REV_" . $rd_id . "_" . $clean_file_name;
            $target_file = "../uploads/rd/" . $unique_file_name;
            
            if(move_uploaded_file($file["tmp_name"], $target_file)) {
                $doc_sql = "INSERT INTO documents (module_type, reference_id, doc_category, file_name, file_path, uploaded_by) VALUES ('RD', ?, 'Revised Proposal', ?, ?, ?)";
                $d_stmt = $conn->prepare($doc_sql);
                $d_stmt->bind_param("issi", $rd_id, $file_name, $target_file, $user_id);
                $d_stmt->execute();
            }
        }
        
        $msg = "Project successfully updated and re-submitted for review!";
        $msg_type = "success";
        $project['status'] = 'Submitted'; // Update local variable for UI
        $project['abstract'] = $abstract;
        $is_editable = false; // Lock editing again
    }
}

// 4. Fetch Documents
$doc_sql = "SELECT * FROM documents WHERE module_type = 'RD' AND reference_id = ? ORDER BY uploaded_at DESC";
$doc_stmt = $conn->prepare($doc_sql);
$doc_stmt->bind_param("i", $rd_id);
$doc_stmt->execute();
$documents = $doc_stmt->get_result();

// 5. Fetch Team
$team_sql = "SELECT ep.project_role, u.first_name, u.last_name FROM rd_proponents ep JOIN users u ON ep.user_id = u.user_id WHERE ep.rd_id = ?";
$team_stmt = $conn->prepare($team_sql);
$team_stmt->bind_param("i", $rd_id);
$team_stmt->execute();
$team = $team_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Project - <?php echo htmlspecialchars($project['project_title']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen">
    
    <nav class="bg-blue-800 text-white shadow-md p-4 flex justify-between">
        <div class="font-bold text-xl tracking-wider">BISU R.I.T.E.S</div>
        <a href="user_dashboard.php" class="text-blue-200 hover:text-white"><i class="fas fa-arrow-left"></i> Back to Portal</a>
    </nav>

    <div class="max-w-5xl mx-auto py-10 px-4">
        
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800"><i class="fas fa-flask text-blue-600 mr-2"></i> Project Workspace</h2>
            <span class="bg-slate-200 text-slate-700 px-4 py-1 rounded-full text-sm font-bold border border-slate-300">
                Status: <span class="<?php echo $project['status'] == 'Approved' ? 'text-green-600' : ($project['status'] == 'Submitted' ? 'text-amber-600' : 'text-blue-600'); ?>"><?php echo $project['status']; ?></span>
            </span>
        </div>

        <?php if ($msg): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $msg_type == 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i> <?php echo $msg; ?>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-xl font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($project['project_title']); ?></h3>
                    <p class="text-sm font-semibold text-blue-600 mb-4"><?php echo htmlspecialchars($project['college_name']); ?></p>
                    
                    <?php if($is_editable && $my_role == 'Main Author'): ?>
                        <div class="bg-amber-50 border-l-4 border-amber-500 p-4 mb-4">
                            <p class="text-sm text-amber-800 font-bold"><i class="fas fa-info-circle mr-1"></i> Revision Required</p>
                            <p class="text-xs text-amber-700 mt-1">The R&D Director has flagged this proposal. You may update the abstract and upload a revised document below.</p>
                        </div>
                        
                        <form method="post" enctype="multipart/form-data" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-2">Update Abstract</label>
                                <textarea name="abstract" rows="6" class="w-full border border-slate-300 rounded p-2 focus:ring-blue-500"><?php echo htmlspecialchars($project['abstract']); ?></textarea>
                            </div>
                            
                            <div class="p-4 border border-dashed border-blue-300 rounded bg-blue-50">
                                <label class="block text-sm font-bold text-slate-700 mb-2"><i class="fas fa-upload mr-1"></i> Upload Revised Document (Optional)</label>
                                <input type="file" name="new_file" class="w-full text-sm text-slate-600">
                            </div>

                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded transition">
                                <i class="fas fa-paper-plane mr-1"></i> Re-submit Proposal
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="bg-slate-50 p-4 rounded border border-slate-100">
                            <h4 class="text-xs font-bold text-slate-400 uppercase mb-2">Abstract</h4>
                            <p class="text-sm text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($project['abstract'])); ?></p>
                        </div>
                        <div class="grid grid-cols-2 mt-4 gap-4">
                            <div><p class="text-xs text-slate-400 uppercase">Budget</p><p class="font-bold text-slate-800">₱<?php echo number_format($project['budget'], 2); ?></p></div>
                            <div><p class="text-xs text-slate-400 uppercase">My Role</p><p class="font-bold text-blue-600"><?php echo $my_role; ?></p></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3"><i class="fas fa-users text-blue-500 mr-2"></i> Project Team</h3>
                    <ul class="space-y-2">
                        <?php while($m = $team->fetch_assoc()): ?>
                            <li class="flex justify-between items-center text-sm">
                                <span class="font-medium text-slate-700"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></span>
                                <span class="text-xs bg-slate-100 text-slate-600 px-2 py-1 rounded"><?php echo $m['project_role']; ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3"><i class="fas fa-folder-open text-blue-500 mr-2"></i> Documents</h3>
                    <?php if($documents->num_rows > 0): ?>
                        <ul class="space-y-3">
                            <?php while($doc = $documents->fetch_assoc()): ?>
                                <li class="p-3 bg-slate-50 border border-slate-200 rounded hover:bg-slate-100 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="truncate pr-2">
                                            <p class="text-sm font-bold text-slate-700 truncate" title="<?php echo htmlspecialchars($doc['file_name']); ?>"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo $doc['doc_category']; ?> • <?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?></p>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-blue-600 hover:text-blue-800" title="Download"><i class="fas fa-download"></i></a>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-sm text-slate-500 italic">No files attached.</p>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</body>
</html>