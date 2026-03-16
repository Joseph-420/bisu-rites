<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$user_id = $_SESSION["id"];

// Fetch all IPs this user is an inventor on
$stmt = $conn->prepare(
    "SELECT a.ip_id, a.title, a.ip_type, a.status, a.application_number,
            a.filing_date, a.registration_date, i.contribution_percentage, i.task_assignment
     FROM ip_inventors i
     JOIN ip_assets a ON i.ip_id = a.ip_id
     WHERE i.user_id = ?
     ORDER BY a.ip_id DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$assets = $stmt->get_result();

$filter = isset($_GET['status']) ? $_GET['status'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My IP Disclosures - BISU RITES</title>
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
                <div>
                    <span class="mr-4">Welcome, <strong><?php echo htmlspecialchars($_SESSION["username"]); ?></strong></span>
                    <a href="../logout.php" class="bg-red-500 hover:bg-red-600 px-3 py-1 rounded text-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Sub Navigation -->
    <div class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex space-x-1 overflow-x-auto py-1">
                <a href="user_dashboard.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-blue-600 rounded-md hover:bg-blue-50">Dashboard</a>
                <a href="user_my_rd.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-blue-600 rounded-md hover:bg-blue-50">My R&D</a>
                <a href="user_my_itso.php" class="px-4 py-2 text-sm font-medium text-teal-700 bg-teal-50 rounded-md">My IP Disclosures</a>
                <a href="user_my_ext.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-green-600 rounded-md hover:bg-green-50">My Extensions</a>
                <a href="user_my_comm.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-purple-600 rounded-md hover:bg-purple-50">My Commercialization</a>
            </div>
        </div>
    </div>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">My IP Disclosures</h1>
                <p class="text-slate-500 text-sm mt-1">View all intellectual property disclosures you are listed as an inventor on.</p>
            </div>
            <a href="user_itso_submit.php" class="bg-teal-600 hover:bg-teal-700 text-white font-medium px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-plus mr-1"></i> Submit Disclosure
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="flex space-x-2 mb-6 flex-wrap">
            <a href="user_my_itso.php" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === '' ? 'bg-teal-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">All</a>
            <a href="?status=Disclosure Submitted" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Disclosure Submitted' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Submitted</a>
            <a href="?status=Filed" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Filed' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Filed</a>
            <a href="?status=Registered" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Registered' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Registered</a>
            <a href="?status=Refused" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Refused' ? 'bg-red-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Refused</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Technology Title</th>
                            <th class="px-4 py-3">IP Type</th>
                            <th class="px-4 py-3">Application No.</th>
                            <th class="px-4 py-3">Contribution</th>
                            <th class="px-4 py-3">Filed</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php
                        $has_rows = false;
                        $assets->data_seek(0);
                        while($row = $assets->fetch_assoc()) {
                            if($filter !== '' && $row['status'] !== $filter) continue;
                            $has_rows = true;

                            $status_colors = [
                                'Disclosure Submitted' => 'bg-amber-100 text-amber-800',
                                'Under Review' => 'bg-orange-100 text-orange-800',
                                'Approved for Drafting' => 'bg-purple-100 text-purple-800',
                                'Filed' => 'bg-blue-100 text-blue-800',
                                'Registered' => 'bg-green-100 text-green-800',
                                'Refused' => 'bg-red-100 text-red-800',
                                'Expired' => 'bg-slate-100 text-slate-600',
                            ];
                            $badge = $status_colors[$row['status']] ?? 'bg-slate-100 text-slate-600';

                            echo "<tr class='hover:bg-slate-50'>";
                            echo "<td class='px-4 py-3 font-medium text-slate-800'>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td class='px-4 py-3 text-slate-600'>" . htmlspecialchars($row['ip_type']) . "</td>";
                            echo "<td class='px-4 py-3 text-slate-500 font-mono text-xs'>" . htmlspecialchars($row['application_number'] ?: '—') . "</td>";
                            echo "<td class='px-4 py-3 text-slate-600'>" . ($row['contribution_percentage'] ? $row['contribution_percentage'] . '%' : '—') . "</td>";
                            echo "<td class='px-4 py-3 text-slate-500 text-xs'>" . ($row['filing_date'] ? date('M d, Y', strtotime($row['filing_date'])) : '—') . "</td>";
                            echo "<td class='px-4 py-3'><span class='px-2 py-1 rounded-full text-xs font-semibold " . $badge . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                            echo "</tr>";
                        }
                        if(!$has_rows) {
                            echo "<tr><td colspan='6' class='px-4 py-8 text-center text-slate-400'><i class='fas fa-lightbulb text-3xl mb-2 block'></i>No IP disclosures found" . ($filter ? " with status '$filter'" : "") . ".</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>