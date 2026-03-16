<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is R&D Director (Role ID 2)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 2){ header("location: ../login.php"); exit; }

$title = $abstract = $status = $budget = $start_date = $end_date = $college_id = "";
$error = "";

// Fetch Colleges for the dropdown
$colleges_sql = "SELECT college_id, college_name FROM colleges WHERE college_code != 'ADMIN' ORDER BY college_name ASC";
$colleges_result = $conn->query($colleges_sql);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["project_title"]);
    $abstract = trim($_POST["abstract"]);
    $status = trim($_POST["status"]); // Now matches the new ENUMs
    $budget = empty($_POST["budget"]) ? 0.00 : floatval($_POST["budget"]);
    $start_date = empty($_POST["start_date"]) ? NULL : $_POST["start_date"];
    $end_date = empty($_POST["end_date"]) ? NULL : $_POST["end_date"];
    $college_id = empty($_POST["college_id"]) ? NULL : intval($_POST["college_id"]);

    if(empty($title)){
        $error = "Project Title is required.";
    } else {
        $sql = "INSERT INTO rd_projects (project_title, abstract, status, budget, start_date, end_date, college_id) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if($stmt = $conn->prepare($sql)){
            $stmt->bind_param("sssdssi", $title, $abstract, $status, $budget, $start_date, $end_date, $college_id);
            if($stmt->execute()){
                header("location: rd_projects.php");
                exit;
            } else {
                $error = "Database Error: " . $conn->error;
            }
        }
    }
}

$page_title = "Encode Research Proposal";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-plus-circle mr-2 text-blue-600"></i> Encode Project</h1>
                <button onclick="window.history.back()" class="text-slate-500 hover:text-slate-700 font-medium">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <?php if($error): ?>
                    <div class="bg-red-50 text-red-800 p-4 rounded-md mb-6 border border-red-200">
                        <i class="fas fa-exclamation-triangle mr-2"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Project Title *</label>
                        <input type="text" name="project_title" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Abstract / Description</label>
                        <textarea name="abstract" rows="4" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Lead College / Department</label>
                            <select name="college_id" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500" required>
                                <option value="">-- Select College --</option>
                                <?php 
                                if ($colleges_result->num_rows > 0) {
                                    while($c = $colleges_result->fetch_assoc()) {
                                        echo "<option value='{$c['college_id']}'>{$c['college_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                            <select name="status" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                                <option value="Draft">Draft (Encoding)</option>
                                <option value="Submitted">Submitted (Triggers Review)</option>
                                <option value="Under Review">Under Review</option>
                                <option value="Approved">Approved</option>
                                <option value="Ongoing">Ongoing</option>
                                <option value="Completed">Completed</option>
                                <option value="Published">Published</option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Budget (₱)</label>
                            <input type="number" step="0.01" name="budget" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500" placeholder="0.00">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Target Start Date</label>
                            <input type="date" name="start_date" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Target End Date</label>
                            <input type="date" name="end_date" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                        </div>
                    </div>
                    
                    <div class="pt-6 border-t border-slate-100 flex justify-end gap-3">
                        <a href="rd_projects.php" class="px-4 py-2 text-slate-600 hover:text-slate-800 font-medium transition">Cancel</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition shadow-sm">
                            <i class="fas fa-save mr-1"></i> Save Proposal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>