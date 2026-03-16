<?php
session_start();
require_once "../db_connect.php";

if (!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 4) {
    header("location: ../login.php");
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) { header("location: ext_monitoring.php"); exit; }

$msg = "";
$msg_type = "";

// --- Handle Monitoring Form Submit ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {

    if ($_POST['action'] === 'save_monitoring') {
        $target_outcome = trim($_POST['target_outcome'] ?? '');
        $achieved_outcome = trim($_POST['achieved_outcome'] ?? '');
        $unmet_outcomes = trim($_POST['unmet_outcomes'] ?? '');
        $risk_assessment = trim($_POST['risk_assessment'] ?? '');
        $recommendation = $_POST['recommendation'] ?? 'Continue';

        $valid_recs = ['Continue', 'Modify', 'End Program'];
        if (!in_array($recommendation, $valid_recs)) $recommendation = 'Continue';

        // Check if monitoring record already exists
        $check = $conn->prepare("SELECT monitor_id FROM ext_monitoring WHERE ext_id = ?");
        $check->bind_param("i", $id);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if ($existing) {
            $stmt = $conn->prepare("UPDATE ext_monitoring SET target_outcome=?, achieved_outcome=?, unmet_outcomes=?, risk_assessment=?, recommendation=? WHERE ext_id=?");
            $stmt->bind_param("sssssi", $target_outcome, $achieved_outcome, $unmet_outcomes, $risk_assessment, $recommendation, $id);
        } else {
            $stmt = $conn->prepare("INSERT INTO ext_monitoring (ext_id, target_outcome, achieved_outcome, unmet_outcomes, risk_assessment, recommendation) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssss", $id, $target_outcome, $achieved_outcome, $unmet_outcomes, $risk_assessment, $recommendation);
        }
        if ($stmt->execute()) {
            $msg = "Monitoring record saved successfully.";
            $msg_type = "success";
        } else {
            $msg = "Error saving monitoring record.";
            $msg_type = "error";
        }
        $stmt->close();
    }

    if ($_POST['action'] === 'add_feedback') {
        $participant = trim($_POST['participant_name'] ?? '');
        $quality = intval($_POST['rating_quality'] ?? 0);
        $relevance = intval($_POST['rating_relevance'] ?? 0);
        $comments = trim($_POST['comments'] ?? '');

        if ($participant === '' || $quality < 1 || $quality > 5 || $relevance < 1 || $relevance > 5) {
            $msg = "Please fill all required feedback fields with valid values.";
            $msg_type = "error";
        } else {
            $stmt = $conn->prepare("INSERT INTO ext_feedback (ext_id, participant_name, rating_quality, rating_relevance, comments) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("isiis", $id, $participant, $quality, $relevance, $comments);
            if ($stmt->execute()) {
                $msg = "Feedback added successfully.";
                $msg_type = "success";
            } else {
                $msg = "Error adding feedback.";
                $msg_type = "error";
            }
            $stmt->close();
        }
    }

    if ($_POST['action'] === 'delete_feedback' && isset($_POST['feedback_id'])) {
        $fid = intval($_POST['feedback_id']);
        $stmt = $conn->prepare("DELETE FROM ext_feedback WHERE feedback_id = ? AND ext_id = ?");
        $stmt->bind_param("ii", $fid, $id);
        if ($stmt->execute()) {
            $msg = "Feedback entry removed.";
            $msg_type = "success";
        }
        $stmt->close();
    }
}

// --- Fetch Project ---
$stmt = $conn->prepare("SELECT * FROM ext_projects WHERE ext_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) { header("location: ext_monitoring.php"); exit; }

// --- Fetch Monitoring Record ---
$stmt = $conn->prepare("SELECT * FROM ext_monitoring WHERE ext_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$monitoring = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- Fetch All Feedback ---
$stmt = $conn->prepare("SELECT * FROM ext_feedback WHERE ext_id = ? ORDER BY date_evaluated DESC, feedback_id DESC");
$stmt->bind_param("i", $id);
$stmt->execute();
$feedback_result = $stmt->get_result();
$stmt->close();

// Compute averages
$avg_q = 0; $avg_r = 0; $fb_count = 0;
$all_feedback = [];
$quality_dist = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];
$relevance_dist = [1=>0, 2=>0, 3=>0, 4=>0, 5=>0];

while ($fb = $feedback_result->fetch_assoc()) {
    $all_feedback[] = $fb;
    $avg_q += $fb['rating_quality'];
    $avg_r += $fb['rating_relevance'];
    $quality_dist[$fb['rating_quality']]++;
    $relevance_dist[$fb['rating_relevance']]++;
    $fb_count++;
}
if ($fb_count > 0) { $avg_q = round($avg_q / $fb_count, 1); $avg_r = round($avg_r / $fb_count, 1); }

// Team members
$team_stmt = $conn->prepare("SELECT ep.role, u.first_name, u.last_name FROM ext_proponents ep LEFT JOIN users u ON ep.user_id = u.user_id WHERE ep.ext_id = ? ORDER BY FIELD(ep.role, 'Project Leader', 'Coordinator', 'Member')");
$team_stmt->bind_param("i", $id);
$team_stmt->execute();
$team = $team_stmt->get_result();
$team_stmt->close();

$page_title = "Monitor: " . htmlspecialchars($project['project_title']);
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-6xl mx-auto w-full">

            <!-- Header -->
            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-chart-pie text-green-600 mr-2"></i>Impact Monitoring</h1>
                    <p class="text-slate-500 text-sm">EXT-<?php echo $project['ext_id']; ?> &mdash; <?php echo htmlspecialchars($project['project_title']); ?></p>
                </div>
                <a href="ext_monitoring.php" class="text-slate-500 hover:text-slate-700 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to Monitoring List
                </a>
            </div>

            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $msg_type === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <i class="fas <?php echo $msg_type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo htmlspecialchars($msg); ?>
                </div>
            <?php endif; ?>

            <!-- Project Summary Strip -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-5 mb-6 border-t-4 border-t-green-500">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                        <?php
                            $sc = 'bg-slate-100 text-slate-700';
                            if (in_array($project['service_status'], ['Proposed','Under Review'])) $sc = 'bg-amber-100 text-amber-800';
                            if ($project['service_status'] == 'Approved') $sc = 'bg-blue-100 text-blue-800';
                            if ($project['service_status'] == 'Ongoing') $sc = 'bg-indigo-100 text-indigo-800';
                            if ($project['service_status'] == 'Completed') $sc = 'bg-green-100 text-green-800';
                            if (in_array($project['service_status'], ['Not Completed','Needs Follow-up'])) $sc = 'bg-red-100 text-red-800';
                        ?>
                        <span class="inline-block mt-1 px-2 py-1 rounded-full text-xs font-bold <?php echo $sc; ?>"><?php echo $project['service_status']; ?></span>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">Beneficiary</p>
                        <p class="text-sm font-medium text-slate-800 mt-1"><?php echo htmlspecialchars($project['beneficiary_name'] ?? '—'); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">Budget</p>
                        <p class="text-sm font-bold text-slate-800 mt-1">₱<?php echo number_format($project['budget'] ?? 0, 2); ?></p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">Timeline</p>
                        <p class="text-sm text-slate-800 mt-1">
                            <?php echo $project['start_date'] ? date('M j, Y', strtotime($project['start_date'])) : 'TBD'; ?> &mdash;
                            <?php echo $project['end_date'] ? date('M j, Y', strtotime($project['end_date'])) : 'TBD'; ?>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase">Team</p>
                        <div class="mt-1 space-y-0.5">
                            <?php if ($team->num_rows > 0): ?>
                                <?php while($m = $team->fetch_assoc()): ?>
                                    <p class="text-xs text-slate-600">
                                        <span class="font-semibold"><?php echo htmlspecialchars($m['first_name'] . ' ' . $m['last_name']); ?></span>
                                        <span class="text-slate-400">(<?php echo $m['role']; ?>)</span>
                                    </p>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p class="text-xs text-slate-400 italic">No team assigned</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

                <!-- LEFT: Monitoring Form + Feedback Form (2/3 width) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Monitoring Assessment Form -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800"><i class="fas fa-clipboard-check text-green-500 mr-2"></i>Outcome Monitoring</h3>
                            <?php if ($monitoring): ?>
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-semibold"><i class="fas fa-check mr-1"></i>Recorded</span>
                            <?php else: ?>
                                <span class="text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full font-semibold"><i class="fas fa-exclamation mr-1"></i>Not Yet Assessed</span>
                            <?php endif; ?>
                        </div>
                        <form method="post" class="p-6 space-y-5">
                            <input type="hidden" name="action" value="save_monitoring">
                            
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Target Outcome</label>
                                <textarea name="target_outcome" rows="3" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none" placeholder="What were the intended outcomes of this extension program?"><?php echo htmlspecialchars($monitoring['target_outcome'] ?? ''); ?></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Achieved Outcome</label>
                                <textarea name="achieved_outcome" rows="3" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none" placeholder="What outcomes were successfully achieved?"><?php echo htmlspecialchars($monitoring['achieved_outcome'] ?? ''); ?></textarea>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Unmet Outcomes / Gaps</label>
                                    <textarea name="unmet_outcomes" rows="3" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none" placeholder="What outcomes were not met?"><?php echo htmlspecialchars($monitoring['unmet_outcomes'] ?? ''); ?></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Risk Assessment</label>
                                    <textarea name="risk_assessment" rows="3" class="w-full border border-slate-300 rounded-lg p-3 text-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 focus:outline-none" placeholder="Any identified risks or concerns?"><?php echo htmlspecialchars($monitoring['risk_assessment'] ?? ''); ?></textarea>
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Recommendation</label>
                                <div class="flex gap-3">
                                    <?php 
                                    $recs = ['Continue' => ['bg-green-100 text-green-800 border-green-300', 'fa-play-circle'], 'Modify' => ['bg-amber-100 text-amber-800 border-amber-300', 'fa-edit'], 'End Program' => ['bg-red-100 text-red-800 border-red-300', 'fa-stop-circle']];
                                    foreach ($recs as $val => $cfg):
                                        $checked = (($monitoring['recommendation'] ?? 'Continue') === $val) ? 'checked' : '';
                                    ?>
                                    <label class="flex-1 cursor-pointer">
                                        <input type="radio" name="recommendation" value="<?php echo $val; ?>" class="peer hidden" <?php echo $checked; ?>>
                                        <div class="border-2 border-slate-200 rounded-lg p-3 text-center text-sm font-semibold peer-checked:<?php echo $cfg[0]; ?> peer-checked:border-current transition hover:bg-slate-50">
                                            <i class="fas <?php echo $cfg[1]; ?> mr-1"></i> <?php echo $val; ?>
                                        </div>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <button type="submit" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-lg transition">
                                <i class="fas fa-save mr-1"></i> <?php echo $monitoring ? 'Update Monitoring Record' : 'Save Monitoring Record'; ?>
                            </button>
                        </form>
                    </div>

                    <!-- Add Feedback Form -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-200 bg-slate-50">
                            <h3 class="font-bold text-slate-800"><i class="fas fa-comment-dots text-purple-500 mr-2"></i>Add Beneficiary Feedback</h3>
                        </div>
                        <form method="post" class="p-6">
                            <input type="hidden" name="action" value="add_feedback">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Participant Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="participant_name" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none" placeholder="Full name">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Quality Rating <span class="text-red-500">*</span></label>
                                    <select name="rating_quality" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none">
                                        <option value="">Select</option>
                                        <option value="5">5 - Excellent</option>
                                        <option value="4">4 - Very Good</option>
                                        <option value="3">3 - Good</option>
                                        <option value="2">2 - Fair</option>
                                        <option value="1">1 - Poor</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Relevance Rating <span class="text-red-500">*</span></label>
                                    <select name="rating_relevance" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none">
                                        <option value="">Select</option>
                                        <option value="5">5 - Highly Relevant</option>
                                        <option value="4">4 - Very Relevant</option>
                                        <option value="3">3 - Moderately Relevant</option>
                                        <option value="2">2 - Slightly Relevant</option>
                                        <option value="1">1 - Not Relevant</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="block text-sm font-semibold text-slate-700 mb-1.5">Comments / Observations</label>
                                <textarea name="comments" rows="2" class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-purple-500 focus:border-purple-500 focus:outline-none" placeholder="Optional participant feedback or notes..."></textarea>
                            </div>
                            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-5 rounded-lg transition text-sm">
                                <i class="fas fa-plus mr-1"></i> Add Feedback Entry
                            </button>
                        </form>
                    </div>

                    <!-- Feedback Table -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
                            <h3 class="font-bold text-slate-800"><i class="fas fa-comments text-blue-500 mr-2"></i>Collected Feedback</h3>
                            <span class="text-xs text-slate-400"><?php echo $fb_count; ?> entries</span>
                        </div>
                        <?php if ($fb_count > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="bg-white text-slate-500 text-xs uppercase tracking-wider border-b border-slate-100">
                                    <tr>
                                        <th class="p-3">Participant</th>
                                        <th class="p-3 text-center">Quality</th>
                                        <th class="p-3 text-center">Relevance</th>
                                        <th class="p-3">Comments</th>
                                        <th class="p-3 text-center">Date</th>
                                        <th class="p-3 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($all_feedback as $fb): ?>
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="p-3 font-medium text-slate-800"><?php echo htmlspecialchars($fb['participant_name']); ?></td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center gap-1 font-bold <?php echo $fb['rating_quality'] >= 4 ? 'text-green-600' : ($fb['rating_quality'] >= 3 ? 'text-amber-600' : 'text-red-600'); ?>">
                                                <?php echo $fb['rating_quality']; ?> <i class="fas fa-star text-xs"></i>
                                            </span>
                                        </td>
                                        <td class="p-3 text-center">
                                            <span class="inline-flex items-center gap-1 font-bold <?php echo $fb['rating_relevance'] >= 4 ? 'text-green-600' : ($fb['rating_relevance'] >= 3 ? 'text-amber-600' : 'text-red-600'); ?>">
                                                <?php echo $fb['rating_relevance']; ?> <i class="fas fa-star text-xs"></i>
                                            </span>
                                        </td>
                                        <td class="p-3 text-slate-600 max-w-xs truncate"><?php echo htmlspecialchars($fb['comments'] ?? '—'); ?></td>
                                        <td class="p-3 text-center text-xs text-slate-400"><?php echo $fb['date_evaluated'] ? date('M j, Y', strtotime($fb['date_evaluated'])) : '—'; ?></td>
                                        <td class="p-3 text-center">
                                            <form method="post" class="inline" onsubmit="return confirm('Remove this feedback entry?')">
                                                <input type="hidden" name="action" value="delete_feedback">
                                                <input type="hidden" name="feedback_id" value="<?php echo $fb['feedback_id']; ?>">
                                                <button type="submit" class="text-red-400 hover:text-red-600 transition" title="Remove"><i class="fas fa-trash-alt"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="p-8 text-center text-slate-400">
                                <i class="fas fa-comment-slash text-3xl mb-3 block"></i>
                                <p>No feedback collected yet. Use the form above to add participant evaluations.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- RIGHT: Summary Sidebar (1/3 width) -->
                <div class="space-y-6">

                    <!-- Rating Summary Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-sm font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2"><i class="fas fa-star text-amber-500 mr-2"></i>Rating Summary</h3>
                        <?php if ($fb_count > 0): ?>
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div class="text-center p-3 rounded-lg bg-blue-50">
                                <p class="text-2xl font-bold text-blue-700"><?php echo $avg_q; ?></p>
                                <p class="text-xs text-blue-500 font-semibold mt-1">Avg Quality</p>
                            </div>
                            <div class="text-center p-3 rounded-lg bg-green-50">
                                <p class="text-2xl font-bold text-green-700"><?php echo $avg_r; ?></p>
                                <p class="text-xs text-green-500 font-semibold mt-1">Avg Relevance</p>
                            </div>
                        </div>
                        <p class="text-xs text-slate-400 text-center mb-4">Based on <?php echo $fb_count; ?> feedback entries</p>
                        <div style="height: 200px;">
                            <canvas id="ratingDistChart"></canvas>
                        </div>
                        <?php else: ?>
                            <div class="text-center py-6 text-slate-400">
                                <i class="fas fa-chart-bar text-3xl mb-2 block"></i>
                                <p class="text-sm">No ratings to display yet.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Monitoring Status Card -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-sm font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2"><i class="fas fa-clipboard-check text-green-500 mr-2"></i>Monitoring Status</h3>
                        <?php if ($monitoring): ?>
                            <div class="space-y-3">
                                <div>
                                    <p class="text-xs text-slate-400 uppercase font-semibold">Recommendation</p>
                                    <?php
                                        $rc = 'bg-slate-100 text-slate-700';
                                        if ($monitoring['recommendation'] === 'Continue') $rc = 'bg-green-100 text-green-800';
                                        if ($monitoring['recommendation'] === 'Modify') $rc = 'bg-amber-100 text-amber-800';
                                        if ($monitoring['recommendation'] === 'End Program') $rc = 'bg-red-100 text-red-800';
                                    ?>
                                    <span class="inline-block mt-1 px-3 py-1.5 rounded-full text-sm font-bold <?php echo $rc; ?>">
                                        <?php echo $monitoring['recommendation']; ?>
                                    </span>
                                </div>
                                <?php if ($monitoring['target_outcome']): ?>
                                <div>
                                    <p class="text-xs text-slate-400 uppercase font-semibold">Target Outcome</p>
                                    <p class="text-sm text-slate-700 mt-1"><?php echo nl2br(htmlspecialchars(mb_strimwidth($monitoring['target_outcome'], 0, 150, '...'))); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($monitoring['achieved_outcome']): ?>
                                <div>
                                    <p class="text-xs text-slate-400 uppercase font-semibold">Achieved</p>
                                    <p class="text-sm text-slate-700 mt-1"><?php echo nl2br(htmlspecialchars(mb_strimwidth($monitoring['achieved_outcome'], 0, 150, '...'))); ?></p>
                                </div>
                                <?php endif; ?>
                                <?php if ($monitoring['risk_assessment']): ?>
                                <div>
                                    <p class="text-xs text-slate-400 uppercase font-semibold">Risks</p>
                                    <p class="text-sm text-red-600 mt-1"><?php echo nl2br(htmlspecialchars(mb_strimwidth($monitoring['risk_assessment'], 0, 100, '...'))); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-6 text-slate-400">
                                <i class="fas fa-exclamation-circle text-3xl mb-2 block"></i>
                                <p class="text-sm">Not yet assessed. Fill out the monitoring form.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                        <h3 class="text-sm font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2"><i class="fas fa-bolt text-amber-500 mr-2"></i>Quick Actions</h3>
                        <div class="space-y-2">
                            <a href="ext_project_review.php?id=<?php echo $id; ?>" class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-4 rounded-lg transition text-sm">
                                <i class="fas fa-edit mr-1"></i> View / Edit Project
                            </a>
                            <a href="ext_monitoring.php" class="block w-full text-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-2 px-4 rounded-lg transition text-sm">
                                <i class="fas fa-list mr-1"></i> All Monitoring
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>

<script>
// Rating Distribution Chart
<?php if ($fb_count > 0): ?>
const qualityDist = <?php echo json_encode(array_values($quality_dist)); ?>;
const relevanceDist = <?php echo json_encode(array_values($relevance_dist)); ?>;

new Chart(document.getElementById('ratingDistChart'), {
    type: 'bar',
    data: {
        labels: ['1 ★', '2 ★', '3 ★', '4 ★', '5 ★'],
        datasets: [
            { label: 'Quality', data: qualityDist, backgroundColor: '#3b82f6', borderRadius: 4 },
            { label: 'Relevance', data: relevanceDist, backgroundColor: '#10b981', borderRadius: 4 }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 10 } } },
            x: { ticks: { font: { size: 10 } } }
        },
        plugins: {
            legend: { position: 'bottom', labels: { padding: 10, usePointStyle: true, pointStyle: 'circle', font: { size: 10 } } }
        }
    }
});
<?php endif; ?>

// Tailwind peer-checked workaround for radio buttons
document.querySelectorAll('input[name="recommendation"]').forEach(radio => {
    radio.addEventListener('change', function() {
        document.querySelectorAll('input[name="recommendation"]').forEach(r => {
            const div = r.nextElementSibling;
            div.className = 'border-2 rounded-lg p-3 text-center text-sm font-semibold transition hover:bg-slate-50 ';
            if (r.checked) {
                if (r.value === 'Continue') div.className += 'bg-green-100 text-green-800 border-green-400';
                else if (r.value === 'Modify') div.className += 'bg-amber-100 text-amber-800 border-amber-400';
                else div.className += 'bg-red-100 text-red-800 border-red-400';
            } else {
                div.className += 'border-slate-200 text-slate-600';
            }
        });
    });
    // Trigger on load for initial state
    if (radio.checked) radio.dispatchEvent(new Event('change'));
});
</script>
