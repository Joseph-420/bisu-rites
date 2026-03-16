<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is R&D Director (Role ID 2)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 2){ header("location: ../login.php"); exit; }

// Fetch Faculty (Role 8) and Students (Role 9) and count their R&D projects
$sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, r.role_name, c.college_code,
        COUNT(rp.rd_id) as project_count
        FROM users u
        LEFT JOIN system_roles r ON u.role_id = r.role_id
        LEFT JOIN colleges c ON u.college_id = c.college_id
        LEFT JOIN rd_proponents rp ON u.user_id = rp.user_id
        WHERE u.role_id IN (8, 9)
        GROUP BY u.user_id
        ORDER BY project_count DESC, u.last_name ASC";

$result = $conn->query($sql);

$page_title = "Researchers Directory";
include "../includes/header.php";
?>

<div class="page-container flex h-screen overflow-hidden">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8 bg-slate-50">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-users mr-2 text-blue-600"></i> Researchers Directory</h1>
                <p class="text-slate-500 text-sm mt-1">Track research involvement of University Faculty and Students.</p>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-100 border-b border-slate-200 text-slate-600 text-sm">
                            <th class="p-4 font-semibold">Name</th>
                            <th class="p-4 font-semibold">System Role</th>
                            <th class="p-4 font-semibold">College</th>
                            <th class="p-4 font-semibold">Email Contact</th>
                            <th class="p-4 font-semibold text-center">Involved Projects</th>
                        </tr>
                    </thead>
                    <tbody class="text-sm">
                        <?php
                        if ($result && $result->num_rows > 0) {
                            while($row = $result->fetch_assoc()) {
                                
                                $roleBadge = ($row['role_name'] == 'Faculty') ? 'bg-indigo-100 text-indigo-800' : 'bg-emerald-100 text-emerald-800';
                                
                                echo "<tr class='border-b border-slate-100 hover:bg-slate-50 transition'>";
                                echo "<td class='p-4 font-medium text-slate-800'>" . htmlspecialchars($row["last_name"] . ", " . $row["first_name"]) . "</td>";
                                echo "<td class='p-4'><span class='px-2 py-1 rounded-full text-xs font-semibold {$roleBadge}'>" . htmlspecialchars($row["role_name"]) . "</span></td>";
                                echo "<td class='p-4 text-slate-600'>" . ($row["college_code"] ? htmlspecialchars($row["college_code"]) : '<span class="italic text-slate-400">N/A</span>') . "</td>";
                                echo "<td class='p-4 text-blue-600'>" . ($row["email"] ? htmlspecialchars($row["email"]) : '<span class="italic text-slate-400">No email</span>') . "</td>";
                                echo "<td class='p-4 text-center font-bold text-slate-700'>" . $row["project_count"] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='p-8 text-center text-slate-500'>No researchers found in the system.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>