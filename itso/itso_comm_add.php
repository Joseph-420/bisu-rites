<?php
session_start();
require_once "../db_connect.php";

// ITSO Director only (Role ID 3)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 3){ 
    header("location: ../login.php"); 
    exit; 
}

$error = "";

// Fetch only IPs that are at a stage eligible for commercialization
$ip_list = $conn->query(
    "SELECT ip_id, title, ip_type, status, application_number 
     FROM ip_assets 
     WHERE status IN ('Approved for Drafting', 'Filed', 'Registered')
     ORDER BY status DESC, title ASC"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip_id        = intval($_POST['ip_id']);
    $request_type = $_POST['request_type'];
    $request_date = !empty($_POST['request_date']) ? $_POST['request_date'] : date('Y-m-d');

    if ($ip_id <= 0 || empty($request_type)) {
        $error = "Please select an IP asset and a request type.";
    } else {
        // Check if a request of the same type already exists for this IP
        $check = $conn->prepare("SELECT comm_id FROM ip_commercialization WHERE ip_id = ? AND request_type = ? AND status != 'Completed'");
        $check->bind_param("is", $ip_id, $request_type);
        $check->execute();
        if ($check->get_result()->num_rows > 0) {
            $error = "An active '{$request_type}' request already exists for this IP asset.";
        } else {
            $stmt = $conn->prepare("INSERT INTO ip_commercialization (ip_id, request_type, status, request_date) VALUES (?, ?, 'Pending', ?)");
            $stmt->bind_param("iss", $ip_id, $request_type, $request_date);
            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                header("location: itso_comm_manage.php?id={$new_id}&logged=1");
                exit;
            } else {
                $error = "Failed to log request. Please try again.";
            }
        }
    }
}

$page_title = "Log Commercialization Request";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-2xl mx-auto w-full">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">
                        <i class="fas fa-plus-circle text-teal-600 mr-2"></i> Log Commercialization Request
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">Create a new commercialization request for an approved or registered IP.</p>
                </div>
                <a href="itso_commercialization.php" class="text-slate-500 hover:text-slate-700 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back
                </a>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 p-4 rounded-md bg-red-50 text-red-800 border border-red-200 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
                <form method="post" class="space-y-5">

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Select IP Asset <span class="text-red-500">*</span></label>
                        <p class="text-xs text-slate-500 mb-2">Only IPs with status "Approved for Drafting", "Filed", or "Registered" are eligible.</p>
                        <select name="ip_id" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            <option value="">-- Select an IP Asset --</option>
                            <?php
                            if ($ip_list && $ip_list->num_rows > 0) {
                                while ($ip = $ip_list->fetch_assoc()) {
                                    $label = "IP-{$ip['ip_id']}: " . htmlspecialchars(substr($ip['title'], 0, 50)) . " [{$ip['ip_type']}] – {$ip['status']}";
                                    $sel = (isset($_POST['ip_id']) && $_POST['ip_id'] == $ip['ip_id']) ? 'selected' : '';
                                    echo "<option value='{$ip['ip_id']}' {$sel}>{$label}</option>";
                                }
                            } else {
                                echo '<option value="" disabled>No eligible IP assets found</option>';
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Request / Service Type <span class="text-red-500">*</span></label>
                        <select name="request_type" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            <option value="">-- Select Service Type --</option>
                            <?php
                            $types = ['Technology Adopter Search', 'IP Valuation', 'Licensing Advice', 'Online Promotion', 'Other'];
                            foreach ($types as $t) {
                                $sel = (isset($_POST['request_type']) && $_POST['request_type'] === $t) ? 'selected' : '';
                                echo "<option value='{$t}' {$sel}>{$t}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Date of Request</label>
                        <input type="date" name="request_date" 
                            value="<?php echo isset($_POST['request_date']) ? htmlspecialchars($_POST['request_date']) : date('Y-m-d'); ?>"
                            class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 px-4 rounded-lg transition shadow-sm">
                            <i class="fas fa-save mr-1"></i> Log Commercialization Request
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
