<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is Extension Director (Role ID 4)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 4){ 
    header("location: ../login.php"); 
    exit; 
}

// --- EXTENSION STATISTICS ---

// 1. Pending Proposals
$pending_count = 0;
$pending_query = $conn->query("SELECT COUNT(*) as count FROM ext_projects WHERE service_status IN ('Proposed', 'Under Review')");
if($pending_query) $pending_count = $pending_query->fetch_assoc()['count'];

// 2. Ongoing Programs
$ongoing_count = 0;
$ongoing_query = $conn->query("SELECT COUNT(*) as count FROM ext_projects WHERE service_status IN ('Approved', 'Ongoing')");
if($ongoing_query) $ongoing_count = $ongoing_query->fetch_assoc()['count'];

// 3. Completed Programs
$completed_count = 0;
$completed_query = $conn->query("SELECT COUNT(*) as count FROM ext_projects WHERE service_status = 'Completed'");
if($completed_query) $completed_count = $completed_query->fetch_assoc()['count'];

// --- DATA FOR CHARTS ---
$status_data = [];
$status_query = $conn->query("SELECT service_status, COUNT(*) as count FROM ext_projects GROUP BY service_status");
if($status_query) {
    while($row = $status_query->fetch_assoc()) {
        $status_data[$row['service_status']] = (int)$row['count'];
    }
}

// Funding distribution
$fund_data = [];
$fund_query = $conn->query("SELECT source_of_funds, COUNT(*) as count FROM ext_projects WHERE source_of_funds IS NOT NULL AND source_of_funds != '' GROUP BY source_of_funds");
if($fund_query) {
    while($row = $fund_query->fetch_assoc()) {
        $fund_data[$row['source_of_funds'] ?: 'Unspecified'] = (int)$row['count'];
    }
}

$page_title = "Extension Director Dashboard";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Extension Services Office</h1>
                <p class="text-slate-500 text-sm mt-1">Manage community outreach, trainings, and technology transfer programs.</p>
            </div>
            <div class="flex items-center space-x-4 bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200">
                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center text-green-600 font-bold">
                    <i class="fas fa-handshake"></i>
                </div>
                <div class="text-sm">
                    <p class="text-slate-500 text-xs">Director Account</p>
                    <p class="font-bold text-slate-800"><?php echo htmlspecialchars($_SESSION["username"]); ?></p>
                </div>
                <button class="btn btn-outline btn-sm" onclick="window.location.href='../logout.php'" style="margin-left: auto;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Pending Proposals</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $pending_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 text-xl">
                    <i class="fas fa-inbox"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Ongoing Programs</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $ongoing_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 text-xl">
                    <i class="fas fa-spinner fa-spin-pulse"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-green-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Completed Activities</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $completed_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center text-green-600 text-xl">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-green-500">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">Action Required: New Proposals</h3>
                <a href="ext_projects.php" class="text-sm text-green-600 hover:text-green-800 font-medium">View All</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="p-3">Project Title</th>
                            <th class="p-3">Target Beneficiary</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php
                        $recent = $conn->query("SELECT ext_id, project_title, beneficiary_name, service_status FROM ext_projects WHERE service_status IN ('Proposed', 'Under Review') ORDER BY ext_id DESC LIMIT 5");
                        
                        if($recent && $recent->num_rows > 0) {
                            while($r = $recent->fetch_assoc()) {
                                echo "<tr class='hover:bg-slate-50 transition'>";
                                echo "<td class='p-3 font-medium text-slate-800'>" . htmlspecialchars(substr($r['project_title'], 0, 50)) . "</td>";
                                echo "<td class='p-3 text-slate-600'>" . htmlspecialchars(substr($r['beneficiary_name'], 0, 40)) . "</td>";
                                echo "<td class='p-3'><span class='bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs font-semibold'>" . $r['service_status'] . "</span></td>";
                                echo "<td class='p-3 text-right'><a href='ext_project_review.php?id=".$r['ext_id']."' class='text-green-600 hover:underline font-bold'>Review</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='p-6 text-center text-slate-500'>No new proposals waiting for review.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Program Status Distribution</h3>
                <div class="flex justify-center" style="max-height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Funding Sources</h3>
                <div class="flex justify-center" style="max-height: 300px;">
                    <canvas id="fundChart"></canvas>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
const statusLabels = <?php echo json_encode(array_keys($status_data)); ?>;
const statusValues = <?php echo json_encode(array_values($status_data)); ?>;
const statusColors = {
    'Proposed': '#8b5cf6', 'Under Review': '#f97316', 'Approved': '#06b6d4',
    'Ongoing': '#3b82f6', 'Completed': '#10b981', 'Not Completed': '#ef4444', 'Needs Follow-up': '#f59e0b'
};
if(statusLabels.length > 0) {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: statusLabels, datasets: [{ data: statusValues, backgroundColor: statusLabels.map(l => statusColors[l] || '#94a3b8'), borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } } }
    });
} else {
    document.getElementById('statusChart').parentElement.innerHTML += '<p class="text-center text-slate-400 mt-4">No program data yet.</p>';
}

const fundLabels = <?php echo json_encode(array_keys($fund_data)); ?>;
const fundValues = <?php echo json_encode(array_values($fund_data)); ?>;
const fundColors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899'];
if(fundLabels.length > 0) {
    new Chart(document.getElementById('fundChart'), {
        type: 'pie',
        data: { labels: fundLabels, datasets: [{ data: fundValues, backgroundColor: fundColors.slice(0, fundLabels.length), borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } } }
    });
} else {
    document.getElementById('fundChart').parentElement.innerHTML += '<p class="text-center text-slate-400 mt-4">No funding data yet.</p>';
}
</script>