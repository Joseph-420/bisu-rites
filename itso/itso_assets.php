<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 3){ header("location: ../login.php"); exit; }

// Fetch IP Assets and join with Inventors to get the Main Developer
$sql = "SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) AS inventor_name 
        FROM ip_assets a 
        LEFT JOIN ip_inventors i ON a.ip_id = i.ip_id AND i.task_assignment = 'Main Developer'
        LEFT JOIN users u ON i.user_id = u.user_id
        ORDER BY FIELD(a.status, 'Disclosure Submitted', 'Under Review', 'Approved for Drafting', 'Filed', 'Registered', 'Draft', 'Refused', 'Expired', 'Rejected'), a.ip_id DESC";

$result = $conn->query($sql);

$page_title = "Manage IP Disclosures";
include "../includes/header.php";
?>

<div class="page-container flex h-screen overflow-hidden">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8 bg-slate-50">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-lightbulb mr-2 text-teal-600"></i> IP Disclosures & Assets</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-sm">
                            <th class="p-4 font-semibold">ID</th>
                            <th class="p-4 font-semibold">Technology Title</th>
                            <th class="p-4 font-semibold">Main Inventor/Maker</th>
                            <th class="p-4 font-semibold">Type</th>
                            <th class="p-4 font-semibold">Status</th>
                            <th class="p-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                
                                // Advanced Status Coloring for ITSO
                                $statusColor = 'bg-slate-100 text-slate-800'; // Default
                                if(in_array($row['status'], ['Disclosure Submitted', 'Under Review'])) $statusColor = 'bg-amber-100 text-amber-800';
                                if($row['status'] == 'Approved for Drafting') $statusColor = 'bg-blue-100 text-blue-800';
                                if($row['status'] == 'Filed') $statusColor = 'bg-indigo-100 text-indigo-800';
                                if($row['status'] == 'Registered') $statusColor = 'bg-teal-100 text-teal-800';
                                if(in_array($row['status'], ['Refused', 'Rejected', 'Expired'])) $statusColor = 'bg-red-100 text-red-800';
                                
                                echo "<tr class='border-b border-slate-100 hover:bg-slate-50 transition'>";
                                echo "<td class='p-4 text-slate-500'>IP-" . $row["ip_id"] . "</td>";
                                echo "<td class='p-4 font-medium text-slate-800'>" . htmlspecialchars(substr($row["title"], 0, 45)) . (strlen($row["title"]) > 45 ? "..." : "") . "</td>";
                                echo "<td class='p-4 text-slate-600'>" . ($row["inventor_name"] ? htmlspecialchars($row["inventor_name"]) : '<span class="italic text-slate-400">System Encoded</span>') . "</td>";
                                echo "<td class='p-4 text-slate-600'>" . $row["ip_type"] . "</td>";
                                echo "<td class='p-4'><span class='px-2 py-1 rounded-full text-xs font-semibold {$statusColor}'>" . $row["status"] . "</span></td>";
                                
                                echo "<td class='p-4 space-x-2'>";
                                
                                // Review Button Logic
                                if(in_array($row['status'], ['Disclosure Submitted', 'Under Review'])) {
                                    echo '<a href="itso_asset_review.php?id='. $row["ip_id"] .'" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded text-xs font-bold transition shadow-sm">Review</a>';
                                } else {
                                    echo '<a href="itso_asset_review.php?id='. $row["ip_id"] .'" class="bg-teal-500 hover:bg-teal-600 text-white px-3 py-1 rounded text-xs font-bold transition shadow-sm">Manage</a>';
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='p-8 text-center text-slate-500'>No IP Disclosures found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>