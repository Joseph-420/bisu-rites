<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$ip_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION["id"];

// 1. SECURITY CHECK: Ensure this user is actually an inventor for this IP!
$check_sql = "SELECT task_assignment FROM ip_inventors WHERE ip_id = ? AND user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param("ii", $ip_id, $user_id);
$check_stmt->execute();
$role_result = $check_stmt->get_result();

if($role_result->num_rows === 0) {
    header("location: user_dashboard.php"); exit; // Unauthorized access
}
$my_task = $role_result->fetch_assoc()['task_assignment'];

// 2. Fetch IP Details
$proj_sql = "SELECT * FROM ip_assets WHERE ip_id = ?";
$proj_stmt = $conn->prepare($proj_sql);
$proj_stmt->bind_param("i", $ip_id);
$proj_stmt->execute();
$asset = $proj_stmt->get_result()->fetch_assoc();

// 3. Fetch Documents
$doc_sql = "SELECT * FROM documents WHERE module_type = 'ITSO' AND reference_id = ? ORDER BY uploaded_at DESC";
$doc_stmt = $conn->prepare($doc_sql);
$doc_stmt->bind_param("i", $ip_id);
$doc_stmt->execute();
$documents = $doc_stmt->get_result();

// 4. Fetch Inventors Team
$team_sql = "SELECT i.task_assignment, i.contribution_percentage, u.first_name, u.last_name FROM ip_inventors i JOIN users u ON i.user_id = u.user_id WHERE i.ip_id = ?";
$team_stmt = $conn->prepare($team_sql);
$team_stmt->bind_param("i", $ip_id);
$team_stmt->execute();
$team = $team_stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View IP - <?php echo htmlspecialchars($asset['title']); ?></title>
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
            <h2 class="text-2xl font-bold text-slate-800"><i class="fas fa-lightbulb text-teal-600 mr-2"></i> IP Workspace</h2>
            <?php 
                $statusColor = 'bg-slate-200 text-slate-700 border-slate-300';
                if(in_array($asset['status'], ['Disclosure Submitted', 'Under Review'])) $statusColor = 'bg-amber-100 text-amber-800 border-amber-300';
                if($asset['status'] == 'Approved for Drafting') $statusColor = 'bg-blue-100 text-blue-800 border-blue-300';
                if($asset['status'] == 'Filed') $statusColor = 'bg-indigo-100 text-indigo-800 border-indigo-300';
                if($asset['status'] == 'Registered') $statusColor = 'bg-teal-100 text-teal-800 border-teal-300';
                if(in_array($asset['status'], ['Refused', 'Rejected'])) $statusColor = 'bg-red-100 text-red-800 border-red-300';
            ?>
            <span class="px-4 py-1 rounded-full text-sm font-bold border <?php echo $statusColor; ?>">
                Status: <?php echo $asset['status']; ?>
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
                    <h3 class="text-xl font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($asset['title']); ?></h3>
                    <p class="text-sm font-semibold text-teal-600 mb-6">Type: <?php echo htmlspecialchars($asset['ip_type']); ?></p>
                    
                    <div class="bg-slate-50 p-4 rounded border border-slate-100 mb-6">
                        <h4 class="text-sm font-bold text-slate-700 mb-3 border-b pb-2"><i class="fas fa-university text-slate-400 mr-2"></i> IPOPHL Registration Details</h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold">Application No.</p>
                                <p class="font-bold text-slate-800 mt-1"><?php echo $asset['application_number'] ? htmlspecialchars($asset['application_number']) : '<span class="text-slate-400 italic">Pending</span>'; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold">Filing Date</p>
                                <p class="font-bold text-slate-800 mt-1"><?php echo $asset['filing_date'] ? date('M j, Y', strtotime($asset['filing_date'])) : '<span class="text-slate-400 italic">--</span>'; ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-slate-400 uppercase font-semibold">Registration Date</p>
                                <p class="font-bold text-teal-600 mt-1"><?php echo $asset['registration_date'] ? date('M j, Y', strtotime($asset['registration_date'])) : '<span class="text-slate-400 italic">--</span>'; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold">Externally Funded?</p>
                            <p class="font-bold text-slate-800 mt-1"><?php echo $asset['is_externally_funded'] ? 'Yes (' . htmlspecialchars($asset['funding_agency']) . ')' : 'No'; ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-slate-400 uppercase font-semibold">My Assignment</p>
                            <p class="font-bold text-teal-600 mt-1"><?php echo htmlspecialchars($my_task); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3"><i class="fas fa-users text-teal-500 mr-2"></i> Co-Makers / Inventors</h3>
                    <ul class="space-y-3">
                        <?php while($m = $team->fetch_assoc()): ?>
                            <li class="flex justify-between items-center text-sm border-b border-slate-50 pb-2 last:border-0 last:pb-0">
                                <div>
                                    <p class="font-medium text-slate-700"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></p>
                                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($m['task_assignment']); ?></p>
                                </div>
                                <span class="text-xs font-bold bg-teal-50 text-teal-700 px-2 py-1 rounded"><?php echo $m['contribution_percentage']; ?>%</span>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 border-b border-slate-100 pb-2 mb-3"><i class="fas fa-folder-open text-teal-500 mr-2"></i> Documents</h3>
                    <?php if($documents->num_rows > 0): ?>
                        <ul class="space-y-3">
                            <?php while($doc = $documents->fetch_assoc()): ?>
                                <li class="p-3 bg-slate-50 border border-slate-200 rounded hover:bg-slate-100 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="truncate pr-2">
                                            <p class="text-sm font-bold text-slate-700 truncate" title="<?php echo htmlspecialchars($doc['file_name']); ?>"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                                            <p class="text-xs text-slate-500"><?php echo $doc['doc_category']; ?></p>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="text-teal-600 hover:text-teal-800" title="Download"><i class="fas fa-download"></i></a>
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