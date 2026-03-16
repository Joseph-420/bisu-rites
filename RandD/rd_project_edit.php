<?php
session_start();
require_once "../db_connect.php";

// Check if user is logged in AND is R&D Director (Role ID 2)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 2){ header("location: ../login.php"); exit; }

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error = "";

// 1. Handle Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = trim($_POST["project_title"]);
    $abstract = trim($_POST["abstract"]);
    $status = trim($_POST["status"]);
    $budget = empty($_POST["budget"]) ? 0.00 : floatval($_POST["budget"]);
    $start_date = empty($_POST["start_date"]) ? NULL : $_POST["start_date"];
    $end_date = empty($_POST["end_date"]) ? NULL : $_POST["end_date"];
    $college_id = empty($_POST["college_id"]) ? NULL : intval($_POST["college_id"]);

    $sql = "UPDATE rd_projects SET project_title=?, abstract=?, status=?, budget=?, start_date=?, end_date=?, college_id=? WHERE rd_id=?";
    if($stmt = $conn->prepare($sql)){
        $stmt->bind_param("sssdssii", $title, $abstract, $status, $budget, $start_date, $end_date, $college_id, $id);
        if($stmt->execute()){
            header("location: rd_projects.php");
            exit;
        } else {
            $error = "Error updating project record.";
        }
    }
}

// 2. Fetch Existing Data
$stmt = $conn->prepare("SELECT * FROM rd_projects WHERE rd_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if(!$data) { header("location: rd_projects.php"); exit; }

$colleges_result = $conn->query("SELECT college_id, college_name FROM colleges WHERE college_code != 'ADMIN' ORDER BY college_name ASC");

$page_title = "Edit Research Project";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-4xl mx-auto w-full">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-slate-800"><i class="fas fa-edit mr-2 text-blue-600"></i> Edit Project</h1>
                <a href="rd_projects.php" class="text-slate-500 hover:text-slate-700 font-medium"><i class="fas fa-arrow-left mr-1"></i> Back</a>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <?php if($error): ?>
                    <div class="bg-red-50 text-red-800 p-4 rounded-md mb-6 border border-red-200"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <form method="post" class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Project Title *</label>
                        <input type="text" name="project_title" value="<?php echo htmlspecialchars($data['project_title']); ?>" required class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Abstract / Description</label>
                        <textarea name="abstract" rows="4" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500"><?php echo htmlspecialchars($data['abstract']); ?></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Lead College / Department</label>
                            <select name="college_id" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                                <option value="">-- Select College --</option>
                                <?php 
                                if ($colleges_result->num_rows > 0) {
                                    while($c = $colleges_result->fetch_assoc()) {
                                        $selected = ($c['college_id'] == $data['college_id']) ? "selected" : "";
                                        echo "<option value='{$c['college_id']}' {$selected}>{$c['college_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Status</label>
                            <select name="status" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                                <?php
                                $statuses = ['Draft', 'Submitted', 'Under Review', 'Approved', 'Ongoing', 'Completed', 'Published', 'Deferred', 'Rejected'];
                                foreach($statuses as $st) {
                                    $selected = ($st == $data['status']) ? "selected" : "";
                                    echo "<option value='{$st}' {$selected}>{$st}</option>";
                                }
                                ?>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Budget (₱)</label>
                            <input type="number" step="0.01" name="budget" value="<?php echo $data['budget']; ?>" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" value="<?php echo $data['start_date']; ?>" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">End Date</label>
                            <input type="date" name="end_date" value="<?php echo $data['end_date']; ?>" class="w-full border border-slate-300 rounded-md p-2 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="pt-6 border-t border-slate-100 flex justify-end gap-3">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-md font-medium transition shadow-sm">
                            Update Project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>