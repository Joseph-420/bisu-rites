<?php
session_start();
require_once "../db_connect.php";

// ITSO Director only (Role ID 3)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 3){ 
    header("location: ../login.php"); 
    exit; 
}

// --- STATS ---
$pending_count = 0;
$processing_count = 0;
$completed_count = 0;

$pq = $conn->query("SELECT COUNT(*) as c FROM ip_commercialization WHERE status = 'Pending'");
if($pq) $pending_count = $pq->fetch_assoc()['c'];

$prq = $conn->query("SELECT COUNT(*) as c FROM ip_commercialization WHERE status = 'Processing'");
if($prq) $processing_count = $prq->fetch_assoc()['c'];

$cq = $conn->query("SELECT COUNT(*) as c FROM ip_commercialization WHERE status = 'Completed'");
if($cq) $completed_count = $cq->fetch_assoc()['c'];

// --- FILTER ---
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$where_clause = '';
if ($filter === 'pending')    $where_clause = "WHERE c.status = 'Pending'";
elseif ($filter === 'processing') $where_clause = "WHERE c.status = 'Processing'";
elseif ($filter === 'completed')  $where_clause = "WHERE c.status = 'Completed'";

$sql = "SELECT c.*, a.title AS ip_title, a.ip_type, a.status AS ip_status, a.application_number
        FROM ip_commercialization c
        JOIN ip_assets a ON c.ip_id = a.ip_id
        {$where_clause}
        ORDER BY FIELD(c.status, 'Pending', 'Processing', 'Completed'), c.comm_id DESC";
$result = $conn->query($sql);

$page_title = "IP Commercialization";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">

        <!-- Page Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">
                    <i class="fas fa-file-contract text-teal-600 mr-2"></i> IP Commercialization
                </h1>
                <p class="text-slate-500 text-sm mt-1">Manage technology transfer, licensing, and commercialization requests.</p>
            </div>
            <span class="bg-slate-100 text-slate-500 px-3 py-2 rounded-md text-xs font-medium border border-slate-200">
                <i class="fas fa-info-circle mr-1"></i> Requests are submitted by inventors via the User Portal
            </span>
        </div>

        <!-- Stats Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Pending Requests</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $pending_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 text-xl">
                    <i class="fas fa-hourglass-half"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">In Processing</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $processing_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 text-xl">
                    <i class="fas fa-spinner"></i>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-teal-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Completed</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $completed_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-teal-50 rounded-lg flex items-center justify-center text-teal-600 text-xl">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="flex items-center gap-2 mb-5">
            <a href="itso_commercialization.php" class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $filter === 'all' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'; ?>">
                All Requests
            </a>
            <a href="itso_commercialization.php?filter=pending" class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $filter === 'pending' ? 'bg-amber-500 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'; ?>">
                Pending
            </a>
            <a href="itso_commercialization.php?filter=processing" class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $filter === 'processing' ? 'bg-blue-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'; ?>">
                Processing
            </a>
            <a href="itso_commercialization.php?filter=completed" class="px-4 py-2 rounded-md text-sm font-medium transition <?php echo $filter === 'completed' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 border border-slate-300 hover:bg-slate-50'; ?>">
                Completed
            </a>
        </div>

        <!-- Data Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-sm">
                            <th class="p-4 font-semibold">Ref. #</th>
                            <th class="p-4 font-semibold">Technology Title</th>
                            <th class="p-4 font-semibold">IP Type</th>
                            <th class="p-4 font-semibold">Request Type</th>
                            <th class="p-4 font-semibold">Date Filed</th>
                            <th class="p-4 font-semibold">IP Status</th>
                            <th class="p-4 font-semibold">Comm. Status</th>
                            <th class="p-4 font-semibold">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php
                        if ($result && $result->num_rows > 0):
                            while($row = $result->fetch_assoc()):
                                // Commercialization status badge
                                $commColor = 'bg-amber-100 text-amber-800';
                                if ($row['status'] === 'Processing') $commColor = 'bg-blue-100 text-blue-800';
                                if ($row['status'] === 'Completed')  $commColor = 'bg-teal-100 text-teal-800';

                                // IP status badge
                                $ipColor = 'bg-slate-100 text-slate-700';
                                if ($row['ip_status'] === 'Registered') $ipColor = 'bg-teal-100 text-teal-800';
                                if ($row['ip_status'] === 'Filed')      $ipColor = 'bg-indigo-100 text-indigo-800';
                        ?>
                        <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                            <td class="p-4 text-slate-500 font-mono text-xs">COMM-<?php echo $row['comm_id']; ?></td>
                            <td class="p-4 font-medium text-slate-800"><?php echo htmlspecialchars(substr($row['ip_title'],0,40)) . (strlen($row['ip_title'])>40?"...":""); ?></td>
                            <td class="p-4 text-slate-600"><?php echo htmlspecialchars($row['ip_type']); ?></td>
                            <td class="p-4 text-slate-600"><?php echo htmlspecialchars($row['request_type']); ?></td>
                            <td class="p-4 text-slate-500"><?php echo date('M d, Y', strtotime($row['request_date'])); ?></td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $ipColor; ?>"><?php echo htmlspecialchars($row['ip_status']); ?></span>
                            </td>
                            <td class="p-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $commColor; ?>"><?php echo htmlspecialchars($row['status']); ?></span>
                            </td>
                            <td class="p-4">
                                <a href="itso_comm_manage.php?id=<?php echo $row['comm_id']; ?>" class="bg-teal-500 hover:bg-teal-600 text-white px-3 py-1 rounded text-xs font-bold transition shadow-sm">Manage</a>
                            </td>
                        </tr>
                        <?php
                            endwhile;
                        else:
                        ?>
                        <tr>
                            <td colspan="8" class="p-10 text-center text-slate-500">
                                <i class="fas fa-file-contract text-4xl text-slate-200 mb-3 block"></i>
                                No commercialization requests found.
                                <br><a href="itso_comm_add.php" class="text-teal-600 hover:underline text-sm mt-2 inline-block">+ Log the first request</a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include "../includes/footer.php"; ?>
