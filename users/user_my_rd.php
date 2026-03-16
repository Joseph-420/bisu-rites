<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$user_id = $_SESSION["id"];

// Fetch all R&D projects this user is involved in
$stmt = $conn->prepare(
    "SELECT p.rd_id, p.project_title, p.status, p.budget, p.start_date, p.end_date, 
            rp.project_role, c.college_code
     FROM rd_proponents rp
     JOIN rd_projects p ON rp.rd_id = p.rd_id
     LEFT JOIN colleges c ON p.college_id = c.college_id
     WHERE rp.user_id = ?
     ORDER BY p.rd_id DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$projects = $stmt->get_result();

// Status filter
$filter = isset($_GET['status']) ? $_GET['status'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My R&D Submissions - BISU RITES</title>
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
                <a href="user_my_rd.php" class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 rounded-md">My R&D</a>
                <a href="user_my_itso.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-teal-600 rounded-md hover:bg-teal-50">My IP Disclosures</a>
                <a href="user_my_ext.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-green-600 rounded-md hover:bg-green-50">My Extensions</a>
                <a href="user_my_comm.php" class="px-4 py-2 text-sm font-medium text-slate-500 hover:text-purple-600 rounded-md hover:bg-purple-50">My Commercialization</a>
            </div>
        </div>
    </div>

    <main class="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">My R&D Submissions</h1>
                <p class="text-slate-500 text-sm mt-1">View all research & development projects you are involved in.</p>
            </div>
            <a href="user_rd_submit.php" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-4 py-2 rounded-lg text-sm">
                <i class="fas fa-plus mr-1"></i> Submit Proposal
            </a>
        </div>

        <!-- Filter Tabs -->
        <div class="flex space-x-2 mb-6">
            <a href="user_my_rd.php" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === '' ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">All</a>
            <a href="?status=Submitted" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Submitted' ? 'bg-amber-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Submitted</a>
            <a href="?status=Ongoing" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Ongoing' ? 'bg-blue-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Ongoing</a>
            <a href="?status=Completed" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Completed' ? 'bg-green-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Completed</a>
            <a href="?status=Published" class="px-3 py-1.5 rounded-full text-xs font-semibold <?php echo $filter === 'Published' ? 'bg-cyan-500 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">Published</a>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="text-xs text-slate-500 uppercase bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3">Project Title</th>
                            <th class="px-4 py-3">My Role</th>
                            <th class="px-4 py-3">College</th>
                            <th class="px-4 py-3">Budget</th>
                            <th class="px-4 py-3">Duration</th>
                            <th class="px-4 py-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm divide-y divide-slate-100">
                        <?php
                        $has_rows = false;
                        $projects->data_seek(0);
                        while($row = $projects->fetch_assoc()) {
                            if($filter !== '' && $row['status'] !== $filter) continue;
                            $has_rows = true;

                            $status_colors = [
                                'Submitted' => 'bg-amber-100 text-amber-800',
                                'Under Review' => 'bg-orange-100 text-orange-800',
                                'Proposed' => 'bg-amber-100 text-amber-800',
                                'Ongoing' => 'bg-blue-100 text-blue-800',
                                'Completed' => 'bg-green-100 text-green-800',
                                'Published' => 'bg-cyan-100 text-cyan-800',
                                'Deferred' => 'bg-slate-100 text-slate-600',
                            ];
                            $badge = $status_colors[$row['status']] ?? 'bg-slate-100 text-slate-600';
                            
                            $duration = '—';
                            if($row['start_date'] && $row['end_date']) {
                                $duration = date('M Y', strtotime($row['start_date'])) . ' – ' . date('M Y', strtotime($row['end_date']));
                            }

                            echo "<tr class='hover:bg-slate-50'>";
                            echo "<td class='px-4 py-3 font-medium text-slate-800'>" . htmlspecialchars($row['project_title']) . "</td>";
                            echo "<td class='px-4 py-3 text-slate-600'><span class='bg-blue-50 text-blue-700 px-2 py-0.5 rounded text-xs'>" . htmlspecialchars($row['project_role']) . "</span></td>";
                            echo "<td class='px-4 py-3 text-slate-600'>" . htmlspecialchars($row['college_code'] ?? '—') . "</td>";
                            echo "<td class='px-4 py-3 text-slate-600'>₱" . number_format($row['budget'], 2) . "</td>";
                            echo "<td class='px-4 py-3 text-slate-500 text-xs'>" . $duration . "</td>";
                            echo "<td class='px-4 py-3'><span class='px-2 py-1 rounded-full text-xs font-semibold " . $badge . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                            echo "</tr>";
                        }
                        if(!$has_rows) {
                            echo "<tr><td colspan='6' class='px-4 py-8 text-center text-slate-400'><i class='fas fa-flask text-3xl mb-2 block'></i>No R&D submissions found" . ($filter ? " with status '$filter'" : "") . ".</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</body>
</html>