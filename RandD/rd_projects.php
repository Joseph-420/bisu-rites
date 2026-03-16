<?php
session_start();
require_once "../db_connect.php";

if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 2){ header("location: ../login.php"); exit; }

$sql = "SELECT p.*, c.college_code 
        FROM rd_projects p 
        LEFT JOIN colleges c ON p.college_id = c.college_id 
        ORDER BY FIELD(p.status, 'Submitted', 'Under Review', 'Approved', 'Ongoing', 'Completed', 'Draft', 'Deferred', 'Rejected'), p.rd_id DESC";
// This ORDER BY pushes things that need attention (Submitted) to the top!

$result = $conn->query($sql);

$page_title = "Manage Research Projects";
include "../includes/header.php";
?>

<div class="page-container flex h-screen overflow-hidden">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8 bg-slate-50">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-flask-vial mr-2 text-blue-600"></i> Research Portfolio</h1>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-sm">
                            <th class="p-4 font-semibold">ID</th>
                            <th class="p-4 font-semibold">Project Title</th>
                            <th class="p-4 font-semibold">College</th>
                            <th class="p-4 font-semibold">Status</th>
                            <th class="p-4 font-semibold">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                
                                // Advanced Status Coloring
                                $statusColor = 'bg-slate-100 text-slate-800'; // Default (Draft)
                                if(in_array($row['status'], ['Submitted', 'Under Review'])) $statusColor = 'bg-amber-100 text-amber-800';
                                if($row['status'] == 'Approved') $statusColor = 'bg-emerald-100 text-emerald-800';
                                if($row['status'] == 'Ongoing') $statusColor = 'bg-blue-100 text-blue-800';
                                if($row['status'] == 'Completed' || $row['status'] == 'Published') $statusColor = 'bg-purple-100 text-purple-800';
                                if($row['status'] == 'Rejected') $statusColor = 'bg-red-100 text-red-800';
                                
                                echo "<tr class='border-b border-slate-100 hover:bg-slate-50 transition'>";
                                echo "<td class='p-4 text-slate-500'>RD-" . $row["rd_id"] . "</td>";
                                echo "<td class='p-4 font-medium text-slate-800'>" . htmlspecialchars(substr($row["project_title"], 0, 50)) . "</td>";
                                echo "<td class='p-4'>" . ($row["college_code"] ? htmlspecialchars($row["college_code"]) : '<span class="italic text-slate-400">None</span>') . "</td>";
                                echo "<td class='p-4'><span class='px-2 py-1 rounded-full text-xs font-semibold {$statusColor}'>" . $row["status"] . "</span></td>";
                                
                                echo "<td class='p-4 space-x-2'>";
                                
                                // If it needs review, show a Review button. Otherwise show standard Edit/View.
                                if(in_array($row['status'], ['Submitted', 'Under Review'])) {
                                    echo '<a href="rd_project_review.php?id='. $row["rd_id"] .'" class="bg-amber-500 hover:bg-amber-600 text-white px-3 py-1 rounded text-xs font-bold transition">Review</a>';
                                } else {
                                    echo '<a href="rd_project_edit.php?id='. $row["rd_id"] .'" class="text-blue-600 hover:text-blue-800" title="Edit"><i class="fas fa-edit"></i></a>';
                                    echo '<a href="rd_project_delete.php?id='. $row["rd_id"] .'" class="text-red-600 hover:text-red-800" title="Delete" onclick="return confirm(\'Delete this project?\')"><i class="fas fa-trash-alt"></i></a>';
                                }
                                
                                echo "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='p-8 text-center text-slate-500'>No research projects found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>