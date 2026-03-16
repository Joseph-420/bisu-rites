<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is ITSO Director (Role ID 3)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 3){ 
    header("location: ../login.php"); 
    exit; 
}

// --- ITSO STATISTICS ---

// 1. Pending Disclosures
$pending_count = 0;
$pending_query = $conn->query("SELECT COUNT(*) as count FROM ip_assets WHERE status IN ('Disclosure Submitted', 'Under Review')");
if($pending_query) $pending_count = $pending_query->fetch_assoc()['count'];

// 2. Active Processing (Drafting or Filed with IPOPHL)
$active_count = 0;
$active_query = $conn->query("SELECT COUNT(*) as count FROM ip_assets WHERE status IN ('Approved for Drafting', 'Filed')");
if($active_query) $active_count = $active_query->fetch_assoc()['count'];

// 3. Successfully Registered IPs
$registered_count = 0;
$registered_query = $conn->query("SELECT COUNT(*) as count FROM ip_assets WHERE status = 'Registered'");
if($registered_query) $registered_count = $registered_query->fetch_assoc()['count'];

// 4. Pending Commercialization Requests
$comm_count = 0;
$comm_query = $conn->query("SELECT COUNT(*) as count FROM ip_commercialization WHERE status = 'Pending'");
if($comm_query) $comm_count = $comm_query->fetch_assoc()['count'];

// --- DATA FOR CHARTS ---
$ip_status_data = [];
$ip_status_query = $conn->query("SELECT status, COUNT(*) as count FROM ip_assets GROUP BY status");
if($ip_status_query) {
    while($row = $ip_status_query->fetch_assoc()) {
        $ip_status_data[$row['status']] = (int)$row['count'];
    }
}

$comm_status_data = [];
$comm_status_query = $conn->query("SELECT status, COUNT(*) as count FROM ip_commercialization GROUP BY status");
if($comm_status_query) {
    while($row = $comm_status_query->fetch_assoc()) {
        $comm_status_data[$row['status']] = (int)$row['count'];
    }
}

$ip_type_data = [];
$ip_type_query = $conn->query("SELECT ip_type, COUNT(*) as count FROM ip_assets GROUP BY ip_type");
if($ip_type_query) {
    while($row = $ip_type_query->fetch_assoc()) {
        $ip_type_data[$row['ip_type']] = (int)$row['count'];
    }
}

$page_title = "ITSO Director Dashboard";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Innovation & Technology Support</h1>
                <p class="text-slate-500 text-sm mt-1">Review IP disclosures and track patent/copyright registrations.</p>
            </div>
            <div class="flex items-center space-x-4 bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200">
                <div class="w-8 h-8 bg-teal-100 rounded-full flex items-center justify-center text-teal-600 font-bold">
                    <i class="fas fa-lightbulb"></i>
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

        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Pending Disclosures</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $pending_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 text-xl">
                    <i class="fas fa-inbox"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">In Process / Filed</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $active_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 text-xl">
                    <i class="fas fa-file-signature"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-teal-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Registered IPs</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $registered_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-teal-50 rounded-lg flex items-center justify-center text-teal-600 text-xl">
                    <i class="fas fa-certificate"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-purple-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Pending Commercialization</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $comm_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center text-purple-600 text-xl">
                    <i class="fas fa-file-contract"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">Action Required: New IP Disclosures</h3>
                <a href="itso_assets.php" class="text-sm text-teal-600 hover:text-teal-800 font-medium">View All</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="p-3">Technology Title</th>
                            <th class="p-3">IP Type</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php
                        $recent = $conn->query("SELECT ip_id, title, ip_type, status FROM ip_assets WHERE status IN ('Disclosure Submitted', 'Under Review') ORDER BY ip_id DESC LIMIT 5");
                        
                        if($recent && $recent->num_rows > 0) {
                            while($r = $recent->fetch_assoc()) {
                                echo "<tr class='hover:bg-slate-50 transition'>";
                                echo "<td class='p-3 font-medium text-slate-800'>" . htmlspecialchars(substr($r['title'], 0, 50)) . "</td>";
                                echo "<td class='p-3 text-slate-600'>" . $r['ip_type'] . "</td>";
                                echo "<td class='p-3'><span class='bg-amber-100 text-amber-800 px-2 py-1 rounded-full text-xs font-semibold'>" . $r['status'] . "</span></td>";
                                echo "<td class='p-3 text-right'><a href='itso_asset_review.php?id=".$r['ip_id']."' class='text-teal-600 hover:underline font-bold'>Review</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='p-6 text-center text-slate-500'>No new disclosures waiting for review.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pending Commercialization Widget -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-purple-500 mt-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">Pending Commercialization Requests</h3>
                <a href="itso_commercialization.php" class="text-sm text-purple-600 hover:text-purple-800 font-medium">View All</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="p-3">Technology Title</th>
                            <th class="p-3">Request Type</th>
                            <th class="p-3">Date</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php
                        $comm_recent = $conn->query("SELECT c.comm_id, c.request_type, c.request_date, a.title FROM ip_commercialization c JOIN ip_assets a ON c.ip_id = a.ip_id WHERE c.status = 'Pending' ORDER BY c.comm_id DESC LIMIT 5");
                        if($comm_recent && $comm_recent->num_rows > 0) {
                            while($cr = $comm_recent->fetch_assoc()) {
                                echo "<tr class='hover:bg-slate-50 transition'>";
                                echo "<td class='p-3 font-medium text-slate-800'>" . htmlspecialchars(substr($cr['title'], 0, 45)) . "</td>";
                                echo "<td class='p-3 text-slate-600'>" . htmlspecialchars($cr['request_type']) . "</td>";
                                echo "<td class='p-3 text-slate-500'>" . date('M d, Y', strtotime($cr['request_date'])) . "</td>";
                                echo "<td class='p-3 text-right'><a href='itso_comm_manage.php?id=".$cr['comm_id']."' class='text-purple-600 hover:underline font-bold'>Manage</a></td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' class='p-6 text-center text-slate-500'>No pending commercialization requests.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">IP Status Pipeline</h3>
                <div class="flex justify-center" style="max-height: 280px;">
                    <canvas id="ipStatusChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">IP Types Breakdown</h3>
                <div class="flex justify-center" style="max-height: 280px;">
                    <canvas id="ipTypeChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Commercialization Status</h3>
                <div class="flex justify-center" style="max-height: 280px;">
                    <canvas id="commStatusChart"></canvas>
                </div>
            </div>
        </div>

    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
// IP Status Pipeline
const ipLabels = <?php echo json_encode(array_keys($ip_status_data)); ?>;
const ipValues = <?php echo json_encode(array_values($ip_status_data)); ?>;
const ipColors = {
    'Disclosure Submitted': '#f59e0b', 'Under Review': '#f97316', 'Approved for Drafting': '#8b5cf6',
    'Filed': '#3b82f6', 'Registered': '#10b981', 'Refused': '#ef4444', 'Expired': '#6b7280'
};
if(ipLabels.length > 0) {
    new Chart(document.getElementById('ipStatusChart'), {
        type: 'doughnut',
        data: { labels: ipLabels, datasets: [{ data: ipValues, backgroundColor: ipLabels.map(l => ipColors[l] || '#94a3b8'), borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } } } }
    });
} else {
    document.getElementById('ipStatusChart').parentElement.innerHTML += '<p class="text-center text-slate-400 mt-4">No IP data yet.</p>';
}

// IP Types
const typeLabels = <?php echo json_encode(array_keys($ip_type_data)); ?>;
const typeValues = <?php echo json_encode(array_values($ip_type_data)); ?>;
const typeColors = ['#3b82f6','#10b981','#f59e0b','#8b5cf6','#ef4444','#06b6d4','#ec4899'];
if(typeLabels.length > 0) {
    new Chart(document.getElementById('ipTypeChart'), {
        type: 'pie',
        data: { labels: typeLabels, datasets: [{ data: typeValues, backgroundColor: typeColors.slice(0, typeLabels.length), borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } } } }
    });
} else {
    document.getElementById('ipTypeChart').parentElement.innerHTML += '<p class="text-center text-slate-400 mt-4">No IP data yet.</p>';
}

// Commercialization Status
const commLabels = <?php echo json_encode(array_keys($comm_status_data)); ?>;
const commValues = <?php echo json_encode(array_values($comm_status_data)); ?>;
const commColors = { 'Pending': '#f59e0b', 'Processing': '#3b82f6', 'Completed': '#10b981' };
if(commLabels.length > 0) {
    new Chart(document.getElementById('commStatusChart'), {
        type: 'doughnut',
        data: { labels: commLabels, datasets: [{ data: commValues, backgroundColor: commLabels.map(l => commColors[l] || '#94a3b8'), borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } } } }
    });
} else {
    document.getElementById('commStatusChart').parentElement.innerHTML += '<p class="text-center text-slate-400 mt-4">No requests yet.</p>';
}
</script>