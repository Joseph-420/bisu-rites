<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 4){ header("location: ../login.php"); exit; }

// Fetch Extension Projects and join with Proponents to get the Project Leader
$sql = "SELECT e.*, CONCAT(u.first_name, ' ', u.last_name) AS leader_name 
        FROM ext_projects e 
        LEFT JOIN ext_proponents ep ON e.ext_id = ep.ext_id AND ep.role = 'Project Leader'
        LEFT JOIN users u ON ep.user_id = u.user_id
        ORDER BY FIELD(e.service_status, 'Proposed', 'Under Review', 'Approved', 'Ongoing', 'Needs Follow-up', 'Completed', 'Draft', 'Not Completed', 'Rejected'), e.ext_id DESC";

$result = $conn->query($sql);

$page_title = "Manage Extension Projects";
include "../includes/header.php";
?>

<div class="page-container flex h-screen overflow-hidden">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8 bg-slate-50">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-handshake mr-2 text-green-600"></i> Extension Portfolio</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-sm">
                            <th class="p-4 font-semibold">ID</th>
                            <th class="p-4 font-semibold">Project Title</th>
                            <th class="p-4 font-semibold">Project Leader</th>
                            <th class="p-4 font-semibold">Beneficiary</th>
                            <th class="p-4 font-semibold">Status</th>
                            <th class="p-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                
                                // Advanced Status Coloring for Extension
                                $statusColor = 'bg-slate-100 text-slate-800'; // Default
                                if(in_array($row['service_status'], ['Proposed', 'Under Review'])) $statusColor = 'bg-amber-100 text-amber-800';
                                if($row['service_status'] == 'Approved') $statusColor = 'bg-blue-100 text-blue-800';
                                if($row['service_status'] == 'Ongoing') $statusColor = 'bg-indigo-100 text-indigo-800';
                                if($row['service_status'] == 'Completed') $statusColor = 'bg-green-100 text-green-800';
                                if(in_array($row['service_status'], ['Not Completed', 'Rejected', 'Needs Follow-up'])) $statusColor = 'bg-red-100 text-red-800';
                                
                                echo "<tr class='border-b border-slate-100 hover:bg-slate-50 transition'>";
                                echo "<td class='p-4 text-slate-500'>EXT-" . $row["ext_id"] . "</td>";
                                echo "<td class='p-4 font-medium text-slate-800'>" . htmlspecialchars(substr($row["project_title"], 0, 40)) . "...</td>";
                                echo "<td class='p-4 text-slate-600'>" . ($row["leader_name"] ? htmlspecialchars($row["leader_name"]) : '<span class="italic text-slate-400">System Encoded</span>') . "</td>";
                                echo "<td class='p-4 text-slate-600'>" . htmlspecialchars(substr($row["beneficiary_name"], 0, 30)) . "</td>";
                                echo "<td class='p-4'><span class='px-2 py-1 rounded-full text-xs font-semibold {$statusColor}'>" . $row["service_status"] . "</span></td>";
                                
                                echo "<td class='p-4 space-x-2'>";
                                
                                // Review Button Logic
                                if(in_array($row['service_status'], ['Proposed', 'Under Review'])) {
                                    echo '<a href="ext_project_review.php?id='. $row["ext_id"] .'" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded text-xs font-bold transition shadow-sm">Review</a>';
                                } else {
                                    echo '<a href="ext_project_review.php?id='. $row["ext_id"] .'" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs font-bold transition shadow-sm">Manage</a>';
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='p-8 text-center text-slate-500'>No Extension projects found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>