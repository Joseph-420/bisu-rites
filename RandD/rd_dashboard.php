<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is R&D Director (Role ID 2)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 2){ 
    header("location: ../login.php"); 
    exit; 
}

// --- R&D STATISTICS ---

// 1. Pending Proposals (Status = 'Submitted' or 'Under Review')
$pending_count = 0;
$pending_query = $conn->query("SELECT COUNT(*) as count FROM rd_projects WHERE status IN ('Submitted', 'Under Review')");
if($pending_query) $pending_count = $pending_query->fetch_assoc()['count'];

// 2. Active/Ongoing Projects
$ongoing_count = 0;
$ongoing_query = $conn->query("SELECT COUNT(*) as count FROM rd_projects WHERE status = 'Ongoing'");
if($ongoing_query) $ongoing_count = $ongoing_query->fetch_assoc()['count'];

// 3. Completed/Published Projects
$completed_count = 0;
$completed_query = $conn->query("SELECT COUNT(*) as count FROM rd_projects WHERE status IN ('Completed', 'Published')");
if($completed_query) $completed_count = $completed_query->fetch_assoc()['count'];

// --- DATA FOR CHARTS ---
$status_data = [];
$status_query = $conn->query("SELECT status, COUNT(*) as count FROM rd_projects GROUP BY status");
if($status_query) {
    while($row = $status_query->fetch_assoc()) {
        $status_data[$row['status']] = (int)$row['count'];
    }
}

// College distribution
$college_data = [];
$college_query = $conn->query("SELECT c.college_code, COUNT(*) as count FROM rd_projects p LEFT JOIN colleges c ON p.college_id = c.college_id GROUP BY c.college_code");
if($college_query) {
    while($row = $college_query->fetch_assoc()) {
        $college_data[$row['college_code'] ?? 'Unassigned'] = (int)$row['count'];
    }
}

$page_title = "R&D Director Dashboard";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Research & Development Office</h1>
                <p class="text-slate-500 text-sm mt-1">Review proposals and track institutional research progress.</p>
            </div>
            <div class="flex items-center space-x-4 bg-white px-4 py-2 rounded-lg shadow-sm border border-slate-200">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">
                    <i class="fas fa-flask"></i>
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
                    <i class="fas fa-file-signature"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Active Researches</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $ongoing_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center text-blue-600 text-xl">
                    <i class="fas fa-spinner fa-spin-pulse"></i>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex items-center justify-between border-l-4 border-l-emerald-500">
                <div>
                    <p class="text-sm font-medium text-slate-500 mb-1">Completed / Published</p>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo $completed_count; ?></h3>
                </div>
                <div class="w-12 h-12 bg-emerald-50 rounded-lg flex items-center justify-center text-emerald-600 text-xl">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-slate-800">Action Required: Recent Submissions</h3>
                <a href="rd_projects.php" class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All</a>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50">
                        <tr>
                            <th class="p-3">Project Title</th>
                            <th class="p-3">College</th>
                            <th class="p-3">Status</th>
                            <th class="p-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php
                        $recent = $conn->query("SELECT p.rd_id, p.project_title, p.status, c.college_code FROM rd_projects p LEFT JOIN colleges c ON p.college_id = c.college_id WHERE p.status IN ('Submitted', 'Under Review') ORDER BY p.rd_id DESC LIMIT 5");
                        
                        if($recent && $recent->num_rows > 0) {
                            while($r = $recent->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='p-3 font-medium text-slate-800'>" . htmlspecialchars($r['project_title']) . "</td>";
                                echo "<td class='p-3 text-slate-600'>" . htmlspecialchars($r['college_code'] ?? 'N/A') . "</td>";
                                echo "<td class='p-3'><span class='bg-amber-100 text-amber-800 px-2 py-1 rounded text-xs font-semibold'>" . $r['status'] . "</span></td>";
                                echo "<td class='p-3 text-right'><a href='rd_project_review.php?id=".$r['rd_id']."' class='text-blue-600 hover:underline'>Review</a></td>";
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
                <h3 class="text-lg font-bold text-slate-800 mb-4">Project Status Distribution</h3>
                <div class="flex justify-center" style="max-height: 300px;">
                    <canvas id="statusChart"></canvas>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-lg font-bold text-slate-800 mb-4">Projects by College</h3>
                <div class="flex justify-center" style="max-height: 300px;">
                    <canvas id="collegeChart"></canvas>
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
    'Submitted': '#f59e0b', 'Under Review': '#f97316', 'Approved': '#8b5cf6',
    'Ongoing': '#3b82f6', 'Completed': '#10b981', 'Published': '#06b6d4', 'Deferred': '#6b7280'
};
const statusBgColors = statusLabels.map(l => statusColors[l] || '#94a3b8');

if(statusLabels.length > 0) {
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: { labels: statusLabels, datasets: [{ data: statusValues, backgroundColor: statusBgColors, borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } } }
    });
} else {
    document.getElementById('statusChart').parentElement.innerHTML += '<p class="text-center text-slate-400 mt-4">No project data yet.</p>';
}

const collegeLabels = <?php echo json_encode(array_keys($college_data)); ?>;
const collegeValues = <?php echo json_encode(array_values($college_data)); ?>;
const collegeColors = ['#3b82f6','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899','#14b8a6','#6366f1'];

if(collegeLabels.length > 0) {
    new Chart(document.getElementById('collegeChart'), {
        type: 'pie',
        data: { labels: collegeLabels, datasets: [{ data: collegeValues, backgroundColor: collegeColors.slice(0, collegeLabels.length), borderWidth: 2, borderColor: '#fff' }] },
        options: { responsive: true, maintainAspectRatio: true, plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } } }
    });
} else {
    document.getElementById('collegeChart').parentElement.innerHTML += '<p class="text-center text-slate-400 mt-4">No project data yet.</p>';
}
</script>