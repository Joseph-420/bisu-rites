<?php
session_start();
require_once "../db_connect.php";

// ITSO Director only (Role ID 3)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 3){ 
    header("location: ../login.php"); 
    exit; 
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$msg = "";
$msg_type = "";

// --- HANDLE STATUS / REMARKS UPDATE ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_status'])) {
    $new_status = $_POST['new_status'];
    $remarks    = trim($_POST['remarks']);

    $valid = ['Pending', 'Processing', 'Completed'];
    if (in_array($new_status, $valid)) {
        $upd = $conn->prepare("UPDATE ip_commercialization SET status = ?, remarks = ? WHERE comm_id = ?");
        $upd->bind_param("ssi", $new_status, $remarks, $id);
        if ($upd->execute()) {
            $msg      = "Request updated to '<strong>{$new_status}</strong>' successfully.";
            $msg_type = "success";
        } else {
            $msg      = "Database error. Please try again.";
            $msg_type = "error";
        }
    }
}

// --- FETCH COMMERCIALIZATION RECORD ---
$stmt = $conn->prepare(
    "SELECT c.*, 
            a.title AS ip_title, a.ip_type, a.application_number,
            a.status AS ip_status, a.filing_date, a.registration_date,
            a.is_externally_funded, a.funding_agency,
            CONCAT(u.first_name, ' ', u.last_name) AS submitted_by_name
     FROM ip_commercialization c
     JOIN ip_assets a ON c.ip_id = a.ip_id
     LEFT JOIN users u ON a.created_by_user_id = u.user_id
     WHERE c.comm_id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$comm = $stmt->get_result()->fetch_assoc();

if (!$comm) {
    header("location: itso_commercialization.php");
    exit;
}

// -- Re-fetch after possible update so status display is fresh --
if ($msg_type === "success") {
    $stmt->execute();
    $comm = $stmt->get_result()->fetch_assoc();
    // $comm may be gone if id changed; guard:
    if (!$comm) { header("location: itso_commercialization.php"); exit; }
}

// Fetch inventors for the linked IP
$inv_stmt = $conn->prepare(
    "SELECT i.task_assignment, i.contribution_percentage,
            COALESCE(CONCAT(u.first_name,' ',u.last_name), i.external_name) AS name
     FROM ip_inventors i LEFT JOIN users u ON i.user_id = u.user_id
     WHERE i.ip_id = ?"
);
$inv_stmt->bind_param("i", $comm['ip_id']);
$inv_stmt->execute();
$inventors = $inv_stmt->get_result();

$page_title = "Manage Commercialization – COMM-" . $id;
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-5xl mx-auto w-full">

            <!-- Breadcrumb Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">
                        <i class="fas fa-file-contract text-teal-600 mr-2"></i> Commercialization Request
                    </h1>
                    <p class="text-slate-500 text-sm">Reference ID: <span class="font-mono font-semibold">COMM-<?php echo $comm['comm_id']; ?></span></p>
                </div>
                <a href="itso_commercialization.php" class="text-slate-500 hover:text-slate-700 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Commercialization
                </a>
            </div>

            <!-- Alert Message -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-md <?php echo $msg_type === 'success' ? 'bg-teal-50 text-teal-800 border border-teal-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <i class="fas <?php echo $msg_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo $msg; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- Left Column: Details -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Linked IP Asset Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
                        <h2 class="text-lg font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">
                            <i class="fas fa-lightbulb text-teal-500 mr-2"></i> Linked IP Asset
                        </h2>
                        <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                            <div class="col-span-2">
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Technology Title</h3>
                                <p class="font-semibold text-slate-800 text-base"><?php echo htmlspecialchars($comm['ip_title']); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">IP Type</h3>
                                <p class="text-slate-700"><?php echo htmlspecialchars($comm['ip_type']); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">IP Status</h3>
                                <?php
                                    $ipColor = 'bg-slate-100 text-slate-700';
                                    if ($comm['ip_status'] === 'Registered')       $ipColor = 'bg-teal-100 text-teal-800';
                                    elseif ($comm['ip_status'] === 'Filed')        $ipColor = 'bg-indigo-100 text-indigo-800';
                                    elseif (in_array($comm['ip_status'], ['Refused','Rejected'])) $ipColor = 'bg-red-100 text-red-800';
                                ?>
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $ipColor; ?>"><?php echo htmlspecialchars($comm['ip_status']); ?></span>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">IPOPHL App. No.</h3>
                                <p class="font-mono text-slate-700"><?php echo htmlspecialchars($comm['application_number'] ?? '—'); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Filing Date</h3>
                                <p class="text-slate-700"><?php echo $comm['filing_date'] ? date('M d, Y', strtotime($comm['filing_date'])) : '—'; ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Externally Funded</h3>
                                <p class="text-slate-700">
                                    <?php echo $comm['is_externally_funded'] ? '<span class="text-amber-600"><i class="fas fa-check mr-1"></i>Yes – '.htmlspecialchars($comm['funding_agency']).'</span>' : 'No'; ?>
                                </p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Submitted By</h3>
                                <p class="text-slate-700"><?php echo $comm['submitted_by_name'] ? htmlspecialchars($comm['submitted_by_name']) : '<span class="italic text-slate-400">System Encoded</span>'; ?></p>
                            </div>
                        </div>

                        <?php if ($inventors->num_rows > 0): ?>
                        <div class="mt-5 pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Inventors / Makers</h3>
                            <table class="w-full text-sm">
                                <thead class="text-xs text-slate-500 bg-slate-50">
                                    <tr>
                                        <th class="p-2 text-left">Name</th>
                                        <th class="p-2 text-left">Task</th>
                                        <th class="p-2 text-right">% Contribution</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php while($inv = $inventors->fetch_assoc()): ?>
                                    <tr>
                                        <td class="p-2 font-medium text-slate-800"><?php echo htmlspecialchars($inv['name']); ?></td>
                                        <td class="p-2 text-slate-600"><?php echo htmlspecialchars($inv['task_assignment']); ?></td>
                                        <td class="p-2 text-right font-bold text-teal-600"><?php echo $inv['contribution_percentage']; ?>%</td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Request Details Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h2 class="text-lg font-bold text-slate-800 mb-4 pb-2 border-b border-slate-100">
                            <i class="fas fa-handshake text-blue-500 mr-2"></i> Commercialization Request Details
                        </h2>
                        <div class="grid grid-cols-2 gap-y-4 gap-x-6">
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Request Type</h3>
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($comm['request_type']); ?></p>
                            </div>
                            <div>
                                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Date Requested</h3>
                                <p class="text-slate-700"><?php echo date('F d, Y', strtotime($comm['request_date'])); ?></p>
                            </div>
                        </div>
                        <?php if ($comm['remarks']): ?>
                        <div class="mt-5 pt-4 border-t border-slate-100">
                            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Current Remarks / Notes</h3>
                            <div class="bg-slate-50 border border-slate-200 rounded p-4 text-slate-700 text-sm leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($comm['remarks'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>

                <!-- Right Column: Update Panel -->
                <div class="space-y-6">

                    <!-- Current Status Badge -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 text-center">
                        <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Current Status</h3>
                        <?php
                            $statusColor = 'bg-amber-100 text-amber-800';
                            if ($comm['status'] === 'Processing') $statusColor = 'bg-blue-100 text-blue-800';
                            if ($comm['status'] === 'Completed')  $statusColor = 'bg-teal-100 text-teal-800';
                        ?>
                        <span class="inline-block px-5 py-2 rounded-full text-sm font-bold <?php echo $statusColor; ?>">
                            <i class="fas fa-circle text-xs mr-1"></i> <?php echo $comm['status']; ?>
                        </span>
                    </div>

                    <!-- Update Form -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-base font-bold text-slate-800 mb-4 border-b border-slate-100 pb-2">
                            <i class="fas fa-edit text-slate-400 mr-1"></i> Update Request
                        </h3>
                        <form method="post" class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Update Status To:</label>
                                <select name="new_status" class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                                    <?php foreach (['Pending', 'Processing', 'Completed'] as $st): ?>
                                        <option value="<?php echo $st; ?>" <?php echo ($st === $comm['status']) ? 'selected' : ''; ?>>
                                            <?php echo $st; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Remarks / Official Notes</label>
                                <textarea name="remarks" rows="6" 
                                    class="w-full border border-slate-300 rounded p-2 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none resize-none"
                                    placeholder="Record any conditions, progress updates, partner details, or official decisions..."><?php echo htmlspecialchars($comm['remarks'] ?? ''); ?></textarea>
                            </div>
                            <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 px-4 rounded-lg transition shadow-sm">
                                <i class="fas fa-save mr-1"></i> Save Changes
                            </button>
                        </form>
                    </div>

                    <!-- Quick Links -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Related Actions</p>
                        <a href="itso_asset_review.php?id=<?php echo $comm['ip_id']; ?>" class="flex items-center gap-2 text-sm text-teal-600 hover:text-teal-800 font-medium py-1">
                            <i class="fas fa-lightbulb w-4"></i> View Full IP Asset Record
                        </a>
                        <a href="itso_commercialization.php" class="flex items-center gap-2 text-sm text-slate-500 hover:text-slate-700 font-medium py-1">
                            <i class="fas fa-list w-4"></i> All Commercialization Requests
                        </a>
                    </div>

                </div>

            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
