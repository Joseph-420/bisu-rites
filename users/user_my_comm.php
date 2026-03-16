<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$user_id = $_SESSION["id"];

// Fetch all commercialization requests for IPs this user is an inventor on
$stmt = $conn->prepare(
    "SELECT c.comm_id, c.request_type, c.status, c.remarks, c.request_date,
            a.title, a.ip_type, a.application_number
     FROM ip_commercialization c
     JOIN ip_assets a ON c.ip_id = a.ip_id
     JOIN ip_inventors i ON i.ip_id = a.ip_id AND i.user_id = ?
     ORDER BY c.comm_id DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$requests = $stmt->get_result();

$filter = isset($_GET['status']) ? $_GET['status'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Commercialization Requests - BISU RITES</title>
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
                <a href="user_my_itso.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-teal-600 rounded-md hover:bg-teal-50">My IP Disclosures</a>
                <a href="user_my_ext.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-green-600 rounded-md hover:bg-green-50">My Extensions</a>
                <a href="user_my_comm.php" class="px-4 py-2 text-sm font-medium text-purple-700 bg-purple-50 rounded-md">My Commercialization</a>
            </div>
        </div>
    </div>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">My Commercialization Requests</h1>
                <p class="text-slate-500 text-sm mt-1">Track all your IP commercialization service requests.</p>
            </div>
            <a href="user_comm_request.php" class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-plus mr-1"></i> Request Service
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="flex space-x-2 mb-6">
            <a href="user_my_comm.php" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === '' ? 'bg-purple-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">All</a>
            <a href="?status=Pending" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Pending' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Pending</a>
            <a href="?status=Processing" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Processing' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Processing</a>
            <a href="?status=Completed" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Completed' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Completed</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">IP Title</th>
                            <th class="px-4 py-3">IP Type</th>
                            <th class="px-4 py-3">Service Requested</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Remarks</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php
                        $has_rows = false;
                        $requests->data_seek(0);
                        while($row = $requests->fetch_assoc()) {
                            if($filter !== '' && $row['status'] !== $filter) continue;
                            $has_rows = true;

                            $status_colors = [
                                'Pending' => 'bg-amber-100 text-amber-800',
                                'Processing' => 'bg-blue-100 text-blue-800',
                                'Completed' => 'bg-green-100 text-green-800',
                            ];
                            $badge = $status_colors[$row['status']] ?? 'bg-slate-100 text-slate-600';

                            echo "<tr class='hover:bg-slate-50'>";
                            echo "<td class='px-4 py-3 font-medium text-slate-800'>" . htmlspecialchars($row['title']) . "</td>";
                            echo "<td class='px-4 py-3 text-slate-600 text-xs'>" . htmlspecialchars($row['ip_type']) . "</td>";
                            echo "<td class='px-4 py-3 text-slate-600'>" . htmlspecialchars($row['request_type']) . "</td>";
                            echo "<td class='px-4 py-3 text-slate-500 text-xs'>" . date('M d, Y', strtotime($row['request_date'])) . "</td>";
                            echo "<td class='px-4 py-3'><span class='px-2 py-1 rounded-full text-xs font-semibold " . $badge . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                            echo "<td class='px-4 py-3 text-slate-500 text-xs max-w-xs truncate'>" . htmlspecialchars($row['remarks'] ?: '—') . "</td>";
                            echo "</tr>";
                        }
                        if(!$has_rows) {
                            echo "<tr><td colspan='6' class='px-4 py-8 text-center text-slate-400'><i class='fas fa-file-contract text-3xl mb-2 block'></i>No commercialization requests found" . ($filter ? " with status '" . htmlspecialchars($filter) . "'" : "") . ".</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>