<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 4) {
    header("location: ../login.php");
    exit;
}

// --- Summary Statistics ---
$total_projects = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM ext_projects");
if ($r) $total_projects = $r->fetch_assoc()['c'];

$monitored_count = 0;
$r = $conn->query("SELECT COUNT(DISTINCT ext_id) as c FROM ext_monitoring");
if ($r) $monitored_count = $r->fetch_assoc()['c'];

$feedback_count = 0;
$r = $conn->query("SELECT COUNT(*) as c FROM ext_feedback");
if ($r) $feedback_count = $r->fetch_assoc()['c'];

$avg_quality = 0;
$r = $conn->query("SELECT AVG(rating_quality) as avg_q FROM ext_feedback");
if ($r) { $v = $r->fetch_assoc()['avg_q']; $avg_quality = $v ? round($v, 1) : 0; }

// Recommendation breakdown for chart
$rec_data = [];
$r = $conn->query("SELECT recommendation, COUNT(*) as c FROM ext_monitoring GROUP BY recommendation");
if ($r) { while ($row = $r->fetch_assoc()) $rec_data[$row['recommendation']] = (int)$row['c']; }

// Average ratings per project for chart
$project_ratings = [];
$r = $conn->query("SELECT e.project_title, AVG(f.rating_quality) as avg_q, AVG(f.rating_relevance) as avg_r
    FROM ext_feedback f 
    JOIN ext_projects e ON f.ext_id = e.ext_id 
    GROUP BY f.ext_id ORDER BY avg_q DESC LIMIT 8");
if ($r) { while ($row = $r->fetch_assoc()) $project_ratings[] = $row; }

// Main listing - all projects with monitoring + feedback summary
$sql = "SELECT e.ext_id, e.project_title, e.program_name, e.beneficiary_name, e.service_status,
        m.monitor_id, m.recommendation, m.target_outcome, m.achieved_outcome,
        COUNT(f.feedback_id) as feedback_total,
        ROUND(AVG(f.rating_quality), 1) as avg_quality,
        ROUND(AVG(f.rating_relevance), 1) as avg_relevance
    FROM ext_projects e
    LEFT JOIN ext_monitoring m ON e.ext_id = m.ext_id
    LEFT JOIN ext_feedback f ON e.ext_id = f.ext_id
    GROUP BY e.ext_id
    ORDER BY FIELD(e.service_status, 'Ongoing', 'Approved', 'Completed', 'Needs Follow-up', 'Proposed', 'Under Review', 'Not Completed'), e.ext_id DESC";
$result = $conn->query($sql);

$page_title = "Extension Impact Monitoring";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-chart-pie text-green-600 mr-2"></i>Impact Monitoring</h1>
                <p class="text-slate-500 text-sm mt-1">Track outcomes, assess impact, and collect beneficiary feedback for all extension programs.</p>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between border-l-4 border-l-blue-500">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Programs</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo $total_projects; ?></h3>
                </div>
                <div class="w-11 h-11 bg-blue-50 rounded-lg flex items-center justify-center text-blue-500 text-lg"><i class="fas fa-project-diagram"></i></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between border-l-4 border-l-green-500">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Monitored</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo $monitored_count; ?></h3>
                </div>
                <div class="w-11 h-11 bg-green-50 rounded-lg flex items-center justify-center text-green-500 text-lg"><i class="fas fa-clipboard-check"></i></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between border-l-4 border-l-purple-500">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Feedback Entries</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo $feedback_count; ?></h3>
                </div>
                <div class="w-11 h-11 bg-purple-50 rounded-lg flex items-center justify-center text-purple-500 text-lg"><i class="fas fa-comments"></i></div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex items-center justify-between border-l-4 border-l-amber-500">
                <div>
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Avg Quality Rating</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1"><?php echo $avg_quality; ?><span class="text-sm font-normal text-slate-400"> / 5</span></h3>
                </div>
                <div class="w-11 h-11 bg-amber-50 rounded-lg flex items-center justify-center text-amber-500 text-lg"><i class="fas fa-star"></i></div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-700 mb-4"><i class="fas fa-clipboard-list text-green-500 mr-2"></i>Recommendations Breakdown</h3>
                <div style="height: 250px;">
                    <canvas id="recChart"></canvas>
                </div>
                <?php if (empty($rec_data)): ?>
                    <p class="text-center text-slate-400 text-sm mt-2">No monitoring data yet. Start by adding monitoring records.</p>
                <?php endif; ?>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-sm font-bold text-slate-700 mb-4"><i class="fas fa-chart-bar text-blue-500 mr-2"></i>Average Ratings by Program</h3>
                <div style="height: 250px;">
                    <canvas id="ratingsChart"></canvas>
                </div>
                <?php if (empty($project_ratings)): ?>
                    <p class="text-center text-slate-400 text-sm mt-2">No feedback data yet. Collect participant feedback to see ratings.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-5 border-b border-slate-200 flex justify-between items-center">
                <h3 class="font-bold text-slate-800"><i class="fas fa-list-check text-slate-400 mr-2"></i>All Extension Programs</h3>
                <span class="text-xs text-slate-400"><?php echo $result ? $result->num_rows : 0; ?> programs</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wider">
                            <th class="p-4 font-semibold">Program</th>
                            <th class="p-4 font-semibold">Status</th>
                            <th class="p-4 font-semibold text-center">Monitoring</th>
                            <th class="p-4 font-semibold text-center">Recommendation</th>
                            <th class="p-4 font-semibold text-center">Feedback</th>
                            <th class="p-4 font-semibold text-center">Avg Quality</th>
                            <th class="p-4 font-semibold text-center">Avg Relevance</th>
                            <th class="p-4 font-semibold text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php if ($result && $result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()):
                                // Status badge color
                                $sc = 'bg-slate-100 text-slate-700';
                                if (in_array($row['service_status'], ['Proposed', 'Under Review'])) $sc = 'bg-amber-100 text-amber-800';
                                if ($row['service_status'] == 'Approved') $sc = 'bg-blue-100 text-blue-800';
                                if ($row['service_status'] == 'Ongoing') $sc = 'bg-indigo-100 text-indigo-800';
                                if ($row['service_status'] == 'Completed') $sc = 'bg-green-100 text-green-800';
                                if (in_array($row['service_status'], ['Not Completed', 'Needs Follow-up'])) $sc = 'bg-red-100 text-red-800';

                                // Recommendation badge
                                $rc = 'bg-slate-100 text-slate-500';
                                if ($row['recommendation'] == 'Continue') $rc = 'bg-green-100 text-green-800';
                                if ($row['recommendation'] == 'Modify') $rc = 'bg-amber-100 text-amber-800';
                                if ($row['recommendation'] == 'End Program') $rc = 'bg-red-100 text-red-800';

                                // Rating color helper
                                $ratingColor = function($v) {
                                    if (!$v) return 'text-slate-400';
                                    if ($v >= 4) return 'text-green-600 font-bold';
                                    if ($v >= 3) return 'text-amber-600 font-bold';
                                    return 'text-red-600 font-bold';
                                };
                            ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition">
                                <td class="p-4">
                                    <p class="font-semibold text-slate-800"><?php echo htmlspecialchars(mb_strimwidth($row['project_title'], 0, 45, '...')); ?></p>
                                    <?php if ($row['program_name']): ?>
                                        <p class="text-xs text-slate-400 mt-0.5"><?php echo htmlspecialchars($row['program_name']); ?></p>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4"><span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $sc; ?>"><?php echo $row['service_status']; ?></span></td>
                                <td class="p-4 text-center">
                                    <?php if ($row['monitor_id']): ?>
                                        <span class="text-green-600"><i class="fas fa-check-circle"></i></span>
                                    <?php else: ?>
                                        <span class="text-slate-300"><i class="fas fa-minus-circle"></i></span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <?php if ($row['recommendation']): ?>
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold <?php echo $rc; ?>"><?php echo $row['recommendation']; ?></span>
                                    <?php else: ?>
                                        <span class="text-slate-400 text-xs">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="text-slate-700 font-medium"><?php echo (int)$row['feedback_total']; ?></span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="<?php echo $ratingColor($row['avg_quality']); ?>">
                                        <?php echo $row['avg_quality'] ? $row['avg_quality'] . ' <i class="fas fa-star text-xs"></i>' : '—'; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <span class="<?php echo $ratingColor($row['avg_relevance']); ?>">
                                        <?php echo $row['avg_relevance'] ? $row['avg_relevance'] . ' <i class="fas fa-star text-xs"></i>' : '—'; ?>
                                    </span>
                                </td>
                                <td class="p-4 text-center">
                                    <a href="ext_monitoring_view.php?id=<?php echo $row['ext_id']; ?>" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-xs font-bold transition shadow-sm inline-flex items-center">
                                        <i class="fas fa-eye mr-1"></i> View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="8" class="p-8 text-center text-slate-400">No extension programs found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
// Recommendation Chart
const recLabels = <?php echo json_encode(array_keys($rec_data)); ?>;
const recValues = <?php echo json_encode(array_values($rec_data)); ?>;
const recColors = {'Continue': '#10b981', 'Modify': '#f59e0b', 'End Program': '#ef4444'};

if (recLabels.length > 0) {
    new Chart(document.getElementById('recChart'), {
        type: 'doughnut',
        data: {
            labels: recLabels,
            datasets: [{
                data: recValues,
                backgroundColor: recLabels.map(l => recColors[l] || '#94a3b8'),
                borderWidth: 2, borderColor: '#fff'
            }]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } }
        }
    });
}

// Ratings Bar Chart
const ratingData = <?php echo json_encode($project_ratings); ?>;
if (ratingData.length > 0) {
    const labels = ratingData.map(r => r.project_title.length > 20 ? r.project_title.substring(0, 20) + '...' : r.project_title);
    new Chart(document.getElementById('ratingsChart'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                { label: 'Quality', data: ratingData.map(r => parseFloat(r.avg_q).toFixed(1)), backgroundColor: '#3b82f6', borderRadius: 4 },
                { label: 'Relevance', data: ratingData.map(r => parseFloat(r.avg_r).toFixed(1)), backgroundColor: '#10b981', borderRadius: 4 }
            ]
        },
        options: {
            responsive: true, maintainAspectRatio: false,
            scales: { y: { beginAtZero: true, max: 5, ticks: { stepSize: 1 } }, x: { ticks: { font: { size: 10 } } } },
            plugins: { legend: { position: 'bottom', labels: { padding: 15, usePointStyle: true, pointStyle: 'circle' } } }
        }
    });
}
</script>
