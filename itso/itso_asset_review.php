<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is ITSO Director (Role ID 3)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 3){ 
    header("location: ../login.php"); 
    exit; 
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";
$msg_type = "";

// 1. Handle Status Updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];
    $app_number = isset($_POST['application_number']) ? trim($_POST['application_number']) : NULL;
    $filing_date = !empty($_POST['filing_date']) ? $_POST['filing_date'] : NULL;
    $registration_date = !empty($_POST['registration_date']) ? $_POST['registration_date'] : NULL;
    
    $valid_statuses = ['Under Review', 'Approved for Drafting', 'Filed', 'Registered', 'Refused', 'Rejected'];
    
    if (in_array($new_status, $valid_statuses)) {
        // Update query includes IPOPHL data if provided
        $update_sql = "UPDATE ip_assets SET status = ?, application_number = ?, filing_date = ?, registration_date = ? WHERE ip_id = ?";
        if ($stmt = $conn->prepare($update_sql)) {
            $stmt->bind_param("ssssi", $new_status, $app_number, $filing_date, $registration_date, $id);
            if ($stmt->execute()) {
                $msg = "IP status successfully updated to '$new_status'.";
                $msg_type = "success";
            } else {
                $msg = "Error updating status.";
                $msg_type = "error";
            }
        }
    }
}

// 2. Fetch the IP Details
$sql = "SELECT * FROM ip_assets WHERE ip_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    header("location: itso_assets.php");
    exit;
}

// 3. Fetch Inventors/Makers
$inv_sql = "SELECT i.*, u.first_name, u.last_name FROM ip_inventors i LEFT JOIN users u ON i.user_id = u.user_id WHERE i.ip_id = ?";
$inv_stmt = $conn->prepare($inv_sql);
$inv_stmt->bind_param("i", $id);
$inv_stmt->execute();
$inventors = $inv_stmt->get_result();

// 4. Fetch Attached Documents
$doc_sql = "SELECT * FROM documents WHERE module_type = 'ITSO' AND reference_id = ?";
$doc_stmt = $conn->prepare($doc_sql);
$doc_stmt->bind_param("i", $id);
$doc_stmt->execute();
$documents = $doc_stmt->get_result();

$page_title = "Review IP - " . htmlspecialchars($asset['title']);
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-5xl mx-auto w-full">
            
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-lightbulb text-teal-600 mr-2"></i> IP Disclosure Review</h1>
                    <p class="text-slate-500 text-sm">Tracking ID: IP-<?php echo $asset['ip_id']; ?></p>
                </div>
                <a href="itso_assets.php" class="text-slate-500 hover:text-slate-700 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Back to List
                </a>
            </div>

            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $msg_type == 'success' ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <i class="fas <?php echo $msg_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
                        <div class="mb-4 flex justify-between items-start">
                            <h2 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($asset['title']); ?></h2>
                            <span class="px-3 py-1 rounded-full text-sm font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                <?php echo htmlspecialchars($asset['ip_type']); ?>
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-4 border-t border-slate-100 pt-4 mt-4">
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Externally Funded?</h3>
                                <p class="font-medium text-slate-800">
                                    <?php echo $asset['is_externally_funded'] ? '<span class="text-amber-600"><i class="fas fa-check mr-1"></i> Yes</span>' : 'No'; ?>
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Funding Agency</h3>
                                <p class="font-medium text-slate-800"><?php echo htmlspecialchars($asset['funding_agency'] ?? 'N/A'); ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-800"><i class="fas fa-users text-slate-400 mr-2"></i> Inventors / Makers</h3>
                        </div>
                        <table class="w-full text-left text-sm">
                            <thead class="text-slate-500 bg-white border-b border-slate-100">
                                <tr>
                                    <th class="p-3">Name</th>
                                    <th class="p-3">Task / Assignment</th>
                                    <th class="p-3 text-right">% Contribution</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php while($inv = $inventors->fetch_assoc()): ?>
                                    <tr class="hover:bg-slate-50">
                                        <td class="p-3 font-medium text-slate-800">
                                            <?php echo htmlspecialchars($inv['first_name'] . ' ' . $inv['last_name'] . ($inv['external_name'] ?? '')); ?>
                                        </td>
                                        <td class="p-3 text-slate-600"><?php echo htmlspecialchars($inv['task_assignment']); ?></td>
                                        <td class="p-3 text-right font-bold text-teal-600"><?php echo $inv['contribution_percentage']; ?>%</td>
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
                            if(in_array($asset['status'], ['Disclosure Submitted', 'Under Review'])) $statusColor = 'bg-amber-100 text-amber-800';
                            if($asset['status'] == 'Approved for Drafting') $statusColor = 'bg-blue-100 text-blue-800';
                            if($asset['status'] == 'Filed') $statusColor = 'bg-indigo-100 text-indigo-800';
                            if($asset['status'] == 'Registered') $statusColor = 'bg-teal-100 text-teal-800';
                            if(in_array($asset['status'], ['Refused', 'Rejected'])) $statusColor = 'bg-red-100 text-red-800';
                        ?>
                        <span class="inline-block px-4 py-2 rounded-full text-sm font-bold <?php echo $statusColor; ?>">
                            <?php echo $asset['status']; ?>
                        </span>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-lg font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">ITSO Processing</h3>
                        
                        <form method="post" class="space-y-4">
                            
                            <div class="bg-slate-50 p-3 rounded border border-slate-200 space-y-3">
                                <div>
                                    <label class="block text-xs font-bold text-slate-600 mb-1">IPOPHL App Number</label>
                                    <input type="text" name="application_number" value="<?php echo htmlspecialchars($asset['application_number'] ?? ''); ?>" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-teal-500" placeholder="e.g. 2-2023-000123">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1">Filing Date</label>
                                        <input type="date" name="filing_date" value="<?php echo $asset['filing_date']; ?>" class="w-full border border-slate-300 rounded p-1 text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-bold text-slate-600 mb-1">Reg. Date</label>
                                        <input type="date" name="registration_date" value="<?php echo $asset['registration_date']; ?>" class="w-full border border-slate-300 rounded p-1 text-sm">
                                    </div>
                                </div>
                            </div>

                            <label class="block text-sm font-bold text-slate-700 mb-1 mt-4">Update Status To:</label>
                            <select name="new_status" class="w-full border border-slate-300 rounded p-2 mb-4 focus:ring-teal-500">
                                <?php
                                $statuses = ['Under Review', 'Approved for Drafting', 'Filed', 'Registered', 'Refused', 'Rejected'];
                                foreach($statuses as $st) {
                                    $selected = ($st == $asset['status']) ? "selected" : "";
                                    echo "<option value='{$st}' {$selected}>{$st}</option>";
                                }
                                ?>
                            </select>

                            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2 px-4 rounded transition">
                                Update IP Record
                            </button>
                        </form>
                    </div>

                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-sm font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2"><i class="fas fa-folder-open text-teal-500 mr-2"></i> Documents</h3>
                        
                        <?php if($documents->num_rows > 0): ?>
                            <ul class="space-y-3">
                                <?php while($doc = $documents->fetch_assoc()): ?>
                                    <li class="flex items-center justify-between p-3 bg-slate-50 border border-slate-200 rounded-lg hover:bg-slate-100 transition">
                                        <div class="flex items-center overflow-hidden">
                                            <i class="fas fa-file-alt text-slate-400 text-xl mr-3 flex-shrink-0"></i>
                                            <div class="truncate">
                                                <p class="text-sm font-bold text-slate-700 truncate"><?php echo htmlspecialchars($doc['file_name']); ?></p>
                                                <p class="text-xs text-slate-500"><?php echo $doc['doc_category']; ?></p>
                                            </div>
                                        </div>
                                        <a href="<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="ml-2 bg-teal-100 text-teal-800 px-3 py-1 rounded text-xs font-bold hover:bg-teal-200">
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