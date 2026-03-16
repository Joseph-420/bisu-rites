<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is Extension Director (Role ID 4)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 4){ 
    header("location: ../login.php"); 
    exit; 
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";
$msg_type = "";

// 1. Handle Status Updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];
    
    // Valid statuses based on our database design
    $valid_statuses = ['Under Review', 'Approved', 'Ongoing', 'Completed', 'Not Completed', 'Needs Follow-up', 'Rejected'];
    
    if (in_array($new_status, $valid_statuses)) {
        $update_sql = "UPDATE ext_projects SET service_status = ? WHERE ext_id = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("si", $new_status, $id);
            if ($stmt->execute()) {
                $msg = "Project status successfully updated to '$new_status'.";
                $msg_type = "success";
            } else {
                $msg = "Error updating status.";
                $msg_type = "error";
            }
        }
    }
}

// 2. Fetch the Project Details
$sql = "SELECT * FROM ext_projects WHERE ext_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();

if (!$project) {
    header("location: ext_projects.php");
    exit;
}

// 3. Fetch Implementation Team (Proponents)
$team_sql = "SELECT ep.*, u.first_name, u.last_name FROM ext_proponents ep LEFT JOIN users u ON ep.user_id = u.user_id WHERE ep.ext_id = ? ORDER BY FIELD(ep.role, 'Project Leader', 'Coordinator', 'Member', 'Trainer')";
$team_stmt = $conn->prepare($team_sql);
$team_stmt->bind_param("i", $id);
$team_stmt->execute();
$team_members = $team_stmt->get_result();

// 4. Fetch Attached Documents
$doc_sql = "SELECT * FROM documents WHERE module_type = 'EXT' AND reference_id = ?";
$doc_stmt = $conn->prepare($doc_sql);
$doc_stmt->bind_param("i", $id);
$doc_stmt->execute();
$documents = $doc_stmt->get_result();

$page_title = "Review Extension - " . htmlspecialchars($project['project_title']);
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-5xl mx-auto w-full">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-handshake text-green-600 mr-2"></i> Extension Proposal Review</h1>
                    <p class="text-slate-500 text-sm">Tracking ID: EXT-<?php echo $project['ext_id']; ?></p>
                </div>
                <a href="ext_projects.php" class="text-slate-500 hover:text-slate-700 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
            </div>

            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $msg_type == 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-green-500">
                        <div class="mb-4">
                            <h2 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($project['project_title']); ?></h2>
                            <?php if($project['program_name']): ?>
                                <p class="text-sm font-semibold text-green-700 mt-1">Program: <?php echo htmlspecialchars($project['program_name']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="grid grid-cols-2 gap-6 border-t border-slate-100 pt-4">
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Target Beneficiary</h3>
                                <p class="font-medium text-slate-800 flex items-start">
                                    <i class="fas fa-users text-green-500 mt-1 mr-2"></i> 
                                    <?php echo htmlspecialchars($project['beneficiary_name']); ?>
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Location / Address</h3>
                                <p class="font-medium text-slate-800 flex items-start">
                                    <i class="fas fa-map-marker-alt text-red-500 mt-1 mr-2"></i> 
                                    <?php echo htmlspecialchars($project['beneficiary_address'] ?? 'Not specified'); ?>
                                </p>
                            </div>
                        </div>

                        <div class="grid grid-cols-3 gap-4 border-t border-slate-100 pt-4 mt-4">
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Budget</h3>
                                <p class="font-bold text-slate-800">₱<?php echo number_format($project['budget'], 2); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Funding Source</h3>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($project['source_of_funds'] ?? 'N/A'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Timeline</h3>
                                <p class="font-medium text-slate-800 text-sm">
                                    <?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'TBD'; ?> to 
                                    <?php echo $project['end_date'] ? date('M j, Y', strtotime($project['end_date'])) : 'TBD'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-800"><i class="fas fa-users-cog text-slate-400 mr-2"></i> Implementation Team</h3>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="text-slate-500 bg-white border-b border-slate-100">
                                <tr>
                                    <th class="p-3">Team Member Name</th>
                                    <th class="p-3">Project Role</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php while($member = $team_members->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-3 font-medium text-slate-800">
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                        </td>
                                        <td class="p-3">
                                            <?php 
                                            $roleBadge = 'bg-slate-100 text-slate-700';
                                            if($member['role'] == 'Project Leader') $roleBadge = 'bg-green-100 text-green-800 font-bold';
                                            if($member['role'] == 'Coordinator') $roleBadge = 'bg-blue-100 text-blue-800';
                                            ?>
                                            <span class="px-2 py-1 rounded-full text-xs <?php echo $roleBadge; ?>">
                                                <?php echo htmlspecialchars($member['role']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 text-center">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Current Status</h3>
                        <?php 
                            $statusColor = 'bg-slate-100 text-slate-800';
                            if(in_array($project['service_status'], ['Proposed', 'Under Review'])) $statusColor = 'bg-amber-100 text-amber-800';
                            if($project['service_status'] == 'Approved') $statusColor = 'bg-blue-100 text-blue-800';
                            if($project['service_status'] == 'Ongoing') $statusColor = 'bg-indigo-100 text-indigo-800';
                            if($project['service_status'] == 'Completed') $statusColor = 'bg-green-100 text-green-800';
                            if(in_array($project['service_status'], ['Not Completed', 'Rejected', 'Needs Follow-up'])) $statusColor = 'bg-red-100 text-red-800';
                        ?>
                        <span class="inline-block px-4 py-2 rounded-full text-sm font-bold <?php echo $statusColor; ?>">
                            <?php echo $project['service_status']; ?>
                        </span>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">Director Actions</h3>
                        
                        <form method="post" class="space-y-4">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Update Status To:</label>
                            <select name="new_status" class="w-full border border-slate-300 rounded p-2 focus:ring-green-500">
                                <?php
                                $statuses = ['Proposed', 'Under Review', 'Approved', 'Ongoing', 'Completed', 'Not Completed', 'Needs Follow-up', 'Rejected'];
                                foreach($statuses as $st) {
                                    $selected = ($st == $project['service_status']) ? "selected" : "";
                                    echo "<option value='{$st}' {$selected}>{$st}</option>";
                                }
                                ?>
                            </select>

                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition">
                                <i class="fas fa-save mr-1"></i> Save Update
                            </button>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2"><i class="fas fa-folder-open text-green-500 mr-2"></i> Proposal Documents</h3>
                        
                        <?php if($documents->num_rows > 0): ?>
                            <ul class="space-y-3">
                                <?php while($doc = $documents->fetch_assoc()): ?>
                                    <li class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                                        <div class="flex items-center overflow-hidden">
                                            <i class="fas fa-file-pdf text-red-500 text-xl mr-3 flex-shrink-0"></i>
                                            <div class="truncate">
                                                <p class="text-sm font-bold text-slate-700 truncate"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                                                <p class="text-xs text-slate-500"><?php echo $doc['doc_category']; ?></p>
                                            </div>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="ml-2 bg-green-100 text-green-800 px-3 py-1 rounded text-xs font-bold hover:bg-green-200">
                                            View
                                        </a>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-sm text-slate-500 italic">No documents attached.</p>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>