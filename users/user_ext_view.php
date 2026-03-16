<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$ext_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION["id"];

// 1. SECURITY CHECK: Ensure this user is actually a proponent for this extension project!
$check_sql = "SELECT role FROM ext_proponents WHERE ext_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $ext_id, $user_id);
$check_stmt->execute();
$role_result = $check_stmt->get_result();

if($role_result->num_rows === 0) {
    header("location: user_dashboard.php"); exit; // Unauthorized access
}
$my_role = $role_result->fetch_assoc()['role'];

// 2. Fetch Extension Project Details
$proj_sql = "SELECT * FROM ext_projects WHERE ext_id = ?";
$proj_stmt = $conn->prepare($proj_sql);
$proj_stmt->bind_param("i", $ext_id);
$proj_stmt->execute();
$project = $proj_stmt->get_result()->fetch_assoc();

// 3. Fetch Documents
$doc_sql = "SELECT * FROM documents WHERE module_type = 'EXT' AND reference_id = ? ORDER BY uploaded_at DESC";
$doc_stmt = $conn->prepare($doc_sql);
$doc_stmt->bind_param("i", $ext_id);
$doc_stmt->execute();
$documents = $doc_stmt->get_result();

// 4. Fetch Team
$team_sql = "SELECT ep.role, u.first_name, u.last_name FROM ext_proponents ep JOIN users u ON ep.user_id = u.user_id WHERE ep.ext_id = ?";
$team_stmt = $conn->prepare($team_sql);
$team_stmt->bind_param("i", $ext_id);
$team_stmt->execute();
$team = $team_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Extension - <?php echo htmlspecialchars($project['project_title']); ?></title>
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
            <h2 class="text-2xl font-bold text-slate-800"><i class="fas fa-handshake text-green-600 mr-2"></i> Extension Workspace</h2>
            <?php 
                $statusColor = 'bg-slate-200 text-slate-700 border-slate-300';
                if(in_array($project['service_status'], ['Proposed', 'Under Review'])) $statusColor = 'bg-amber-100 text-amber-800 border-amber-300';
                if($project['service_status'] == 'Approved') $statusColor = 'bg-blue-100 text-blue-800 border-blue-300';
                if($project['service_status'] == 'Ongoing') $statusColor = 'bg-indigo-100 text-indigo-800 border-indigo-300';
                if($project['service_status'] == 'Completed') $statusColor = 'bg-green-100 text-green-800 border-green-300';
                if(in_array($project['service_status'], ['Needs Follow-up', 'Not Completed'])) $statusColor = 'bg-red-100 text-red-800 border-red-300';
            ?>
            <span class="px-4 py-1 rounded-full text-sm font-bold border <?php echo $statusColor; ?>">
                Status: <?php echo $project['service_status']; ?>
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-green-500">
                    <h3 class="text-xl font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($project['project_title']); ?></h3>
                    <p class="text-sm font-semibold text-green-600 mb-4">Beneficiary: <?php echo htmlspecialchars($project['beneficiary']); ?></p>
                    
                    <div class="bg-slate-50 p-4 rounded border border-slate-100 mb-6">
                        <h4 class="text-sm font-bold text-slate-700 mb-2">Description</h4>
                        <p class="text-sm text-slate-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($project['description'] ?? 'No description provided')); ?></p>
                    </div>

                    <div class="bg-slate-50 p-4 rounded border border-slate-100 mb-6">
                        <h4 class="text-sm font-bold text-slate-700 mb-2">Expected Deliverables</h4>
                        <p class="text-sm text-slate-600 leading-relaxed"><?php echo nl2br(htmlspecialchars($project['deliverables'] ?? 'No deliverables specified')); ?></p>
                    </div>

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold">Budget</p>
                            <p class="font-bold text-slate-800 mt-1">₱<?php echo number_format($project['budget'] ?? 0, 2); ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold">Timeline Start</p>
                            <p class="font-bold text-slate-800 mt-1"><?php echo $project['timeline_start'] ? date('M j, Y', strtotime($project['timeline_start'])) : '--'; ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold">Timeline End</p>
                            <p class="font-bold text-slate-800 mt-1"><?php echo $project['timeline_end'] ? date('M j, Y', strtotime($project['timeline_end'])) : '--'; ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold">My Role</p>
                            <p class="font-bold text-green-600 mt-1"><?php echo htmlspecialchars($my_role); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3"><i class="fas fa-users text-green-500 mr-2"></i> Project Team</h3>
                    <ul class="space-y-2">
                        <?php while($m = $team->fetch_assoc()): ?>
                            <li class="flex justify-between items-center text-sm">
                                <span class="font-medium text-slate-700"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></span>
                                <span class="text-xs bg-green-50 text-green-700 px-2 py-1 rounded"><?php echo htmlspecialchars($m['role']); ?></span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3"><i class="fas fa-folder-open text-green-500 mr-2"></i> Documents</h3>
                    <?php if($documents->num_rows > 0): ?>
                        <ul class="space-y-3">
                            <?php while($doc = $documents->fetch_assoc()): ?>
                                <li class="p-3 bg-slate-50 border border-slate-200 rounded hover:bg-slate-100 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="truncate pr-2">
                                            <p class="text-sm font-bold text-slate-700 truncate" title="<?php echo htmlspecialchars($doc['file_name']); ?>"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo $doc['doc_category']; ?> • <?php echo date('M j, Y', strtotime($doc['uploaded_at'])); ?></p>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-green-600 hover:text-green-800" title="Download"><i class="fas fa-download"></i></a>
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
