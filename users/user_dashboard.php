<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$user_id = $_SESSION["id"];

// 1. Count R&D
$stmt_rd = $conn->prepare("SELECT COUNT(*) as count FROM rd_proponents WHERE user_id = ?");
$stmt_rd->bind_param("i", $user_id);
$stmt_rd->execute();
$my_rd_count = $stmt_rd->get_result()->fetch_assoc()['count'];

// 2. Count ITSO
$stmt_ip = $conn->prepare("SELECT COUNT(*) as count FROM ip_inventors WHERE user_id = ?");
$stmt_ip->bind_param("i", $user_id);
$stmt_ip->execute();
$my_itso_count = $stmt_ip->get_result()->fetch_assoc()['count'];

// 3. Count Extension
$stmt_ext = $conn->prepare("SELECT COUNT(*) as count FROM ext_proponents WHERE user_id = ?");
$stmt_ext->bind_param("i", $user_id);
$stmt_ext->execute();
$my_ext_count = $stmt_ext->get_result()->fetch_assoc()['count'];

$page_title = "My Portal - BISU RITES";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $page_title; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen flex flex-col">

    <nav class="bg-blue-800 text-white shadow-md">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex-shrink-0 font-bold text-xl tracking-wider">
                    BISU R.I.T.E.S <span class="text-blue-300 text-sm font-normal">| Researcher Portal</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="mr-2 hidden md:inline">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></span>
                    <a href="user_settings.php" class="text-blue-200 hover:text-white transition" title="Account Settings"><i class="fas fa-cog text-lg"></i></a>
                    <span class="text-blue-400">|</span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm transition font-medium shadow-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sub Navigation -->
    <div class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-1 overflow-x-auto py-1">
                <a href="user_dashboard.php" class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-md">Dashboard</a>
                <a href="user_my_rd.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-blue-600 rounded-md hover:bg-blue-50">My R&D</a>
                <a href="user_my_itso.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-teal-600 rounded-md hover:bg-teal-50">My IP Disclosures</a>
                <a href="user_my_ext.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-green-600 rounded-md hover:bg-green-50">My Extensions</a>
                <a href="user_my_comm.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-purple-600 rounded-md hover:bg-purple-50">My Commercialization</a>
            </div>
        </div>
    </div>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <h1 class="text-2xl font-bold text-slate-800 mb-6">My Workspace</h1>

        <?php if (isset($_GET['comm_success'])): ?>
        <div class="mb-6 p-4 rounded-md bg-teal-50 text-teal-800 border border-teal-200 text-sm">
            <i class="fas fa-check-circle mr-2"></i>
            Your commercialization request has been submitted! The ITSO Director will review it shortly.
        </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col items-center text-center hover:shadow-md transition">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 text-2xl mb-4"><i class="fas fa-flask"></i></div>
                <h3 class="font-bold text-lg text-slate-800 mb-2">Research & Development</h3>
                <p class="text-slate-500 text-sm mb-4">Submit a new research proposal or thesis for funding and approval.</p>
                <a href="user_rd_submit.php" class="mt-auto w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 rounded transition">Submit Proposal</a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col items-center text-center hover:shadow-md transition">
                <div class="w-16 h-16 bg-teal-100 rounded-full flex items-center justify-center text-teal-600 text-2xl mb-4"><i class="fas fa-lightbulb"></i></div>
                <h3 class="font-bold text-lg text-slate-800 mb-2">Innovation (ITSO)</h3>
                <p class="text-slate-500 text-sm mb-4">Disclose a new invention, patent, or copyright application.</p>
                <a href="user_itso_submit.php" class="mt-auto w-full bg-teal-600 hover:bg-teal-700 text-white font-medium py-2 rounded transition">Submit IP Disclosure</a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col items-center text-center hover:shadow-md transition border-t-4 border-t-green-500">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center text-green-600 text-2xl mb-4"><i class="fas fa-handshake"></i></div>
                <h3 class="font-bold text-lg text-slate-800 mb-2">Extension Services</h3>
                <p class="text-slate-500 text-sm mb-4">Propose a community outreach, training, or technology transfer.</p>
                <a href="user_ext_submit.php" class="mt-auto w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 rounded transition">Propose Extension</a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 flex flex-col items-center text-center hover:shadow-md transition border-t-4 border-t-purple-500">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center text-purple-600 text-2xl mb-4"><i class="fas fa-file-contract"></i></div>
                <h3 class="font-bold text-lg text-slate-800 mb-2">IP Commercialization</h3>
                <p class="text-slate-500 text-sm mb-4">Request technology transfer, licensing, or promotion services for your registered IP.</p>
                <a href="user_comm_request.php" class="mt-auto w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 rounded transition">Request Service</a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center"><h3 class="font-bold text-slate-800">My R&D (<?php echo $my_rd_count; ?>)</h3><a href="user_my_rd.php" class="text-xs text-blue-600 hover:text-blue-800 font-medium">View All &rarr;</a></div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <?php
                            $my_proj_sql = "SELECT p.rd_id, p.project_title, p.status FROM rd_proponents rp JOIN rd_projects p ON rp.rd_id = p.rd_id WHERE rp.user_id = ? ORDER BY p.rd_id DESC LIMIT 4";
                            $stmt = $conn->prepare($my_proj_sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            if($res->num_rows > 0) {
                                while($row = $res->fetch_assoc()) {
                                    echo "<tr class='hover:bg-slate-100 transition cursor-pointer' onclick=\"window.location='user_rd_view.php?id=".$row['rd_id']."'\">";
                                    echo "<td class='p-2 text-blue-600 font-medium'>" . htmlspecialchars(substr($row['project_title'],0,30)) . "...</td>";
                                    echo "<td class='p-2 text-right'><span class='px-2 py-1 rounded text-xs bg-slate-200 font-semibold'>" . $row['status'] . "</span></td>";
                                    echo "</tr>";
                                }
                            } else { echo "<tr><td class='p-4 text-center text-slate-400'>No R&D submissions.</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center"><h3 class="font-bold text-slate-800">My IP Disclosures (<?php echo $my_itso_count; ?>)</h3><a href="user_my_itso.php" class="text-xs text-teal-600 hover:text-teal-800 font-medium">View All &rarr;</a></div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <?php
                            $my_ip_sql = "SELECT a.ip_id, a.title, a.status FROM ip_inventors i JOIN ip_assets a ON i.ip_id = a.ip_id WHERE i.user_id = ? ORDER BY a.ip_id DESC LIMIT 4";
                            $stmt = $conn->prepare($my_ip_sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $res_ip = $stmt->get_result();
                            if($res_ip->num_rows > 0) {
                                while($row = $res_ip->fetch_assoc()) {
                                    echo "<tr class='hover:bg-slate-100 transition cursor-pointer' onclick=\"window.location='user_itso_view.php?id=".$row['ip_id']."'\">";
                                    echo "<td class='p-2 text-teal-600 font-medium'>" . htmlspecialchars(substr($row['title'],0,30)) . "...</td>";
                                    echo "<td class='p-2 text-right'><span class='px-2 py-1 rounded text-xs bg-slate-200 font-semibold'>" . $row['status'] . "</span></td>";
                                    echo "</tr>";
                                }
                            } else { echo "<tr><td class='p-4 text-center text-slate-400'>No IPs disclosed.</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-200 bg-slate-50 flex justify-between items-center"><h3 class="font-bold text-slate-800">My Extensions (<?php echo $my_ext_count; ?>)</h3><a href="user_my_ext.php" class="text-xs text-green-600 hover:text-green-800 font-medium">View All &rarr;</a></div>
                <div class="overflow-x-auto p-2">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-slate-100">
                            <?php
                            $my_ext_sql = "SELECT e.ext_id, e.project_title, e.service_status FROM ext_proponents ep JOIN ext_projects e ON ep.ext_id = e.ext_id WHERE ep.user_id = ? ORDER BY e.ext_id DESC LIMIT 4";
                            $stmt = $conn->prepare($my_ext_sql);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $res_ext = $stmt->get_result();
                            if($res_ext->num_rows > 0) {
                                while($row = $res_ext->fetch_assoc()) {
                                    echo "<tr class='hover:bg-slate-100 transition cursor-pointer' onclick=\"window.location='user_ext_view.php?id=".$row['ext_id']."'\">";
                                    echo "<td class='p-2 text-green-600 font-medium'>" . htmlspecialchars(substr($row['project_title'],0,30)) . "...</td>";
                                    echo "<td class='p-2 text-right'><span class='px-2 py-1 rounded text-xs bg-slate-200 font-semibold'>" . $row['service_status'] . "</span></td>";
                                    echo "</tr>";
                                }
                            } else { echo "<tr><td class='p-4 text-center text-slate-400'>No Extension projects.</td></tr>"; }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </main>
</body>
</html>