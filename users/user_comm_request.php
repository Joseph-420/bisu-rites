<?php
session_start();
require_once "../db_connect.php";

// Only faculty/students (roles 5 & 6) can access this
if(!isset($_SESSION["loggedin"]) || in_array($_SESSION["role_id"], [1, 2, 3, 4])) {
    header("location: ../login.php"); exit;
}

$user_id = $_SESSION["id"];
$error   = "";

// Fetch only IPs this user is an inventor on AND have eligible status for commercialization
$ip_stmt = $conn->prepare(
    "SELECT a.ip_id, a.title, a.ip_type, a.status, a.application_number
     FROM ip_inventors i
     JOIN ip_assets a ON i.ip_id = a.ip_id
     WHERE i.user_id = ? AND a.status IN ('Approved for Drafting', 'Filed', 'Registered')
     ORDER BY a.ip_id DESC"
);
$ip_stmt->bind_param("i", $user_id);
$ip_stmt->execute();
$eligible_ips = $ip_stmt->get_result();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ip_id        = intval($_POST['ip_id']);
    $request_type = $_POST['request_type'];

    if ($ip_id <= 0 || empty($request_type)) {
        $error = "Please select your IP and a service type.";
    } else {
        // Security: verify this IP actually belongs to the logged-in user
        $verify = $conn->prepare("SELECT i.ip_id FROM ip_inventors i JOIN ip_assets a ON i.ip_id = a.ip_id WHERE i.ip_id = ? AND i.user_id = ? AND a.status IN ('Approved for Drafting','Filed','Registered')");
        $verify->bind_param("ii", $ip_id, $user_id);
        $verify->execute();
        if ($verify->get_result()->num_rows === 0) {
            $error = "Invalid IP selection. You may only request services for your own eligible IPs.";
        } else {
            // Check if same type already has an active request
            $check = $conn->prepare("SELECT comm_id FROM ip_commercialization WHERE ip_id = ? AND request_type = ? AND status != 'Completed'");
            $check->bind_param("is", $ip_id, $request_type);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "A '<strong>" . htmlspecialchars($request_type) . "</strong>' request is already active for this IP. Please wait for it to be completed before submitting another.";
            } else {
                $ins = $conn->prepare("INSERT INTO ip_commercialization (ip_id, request_type, status, request_date) VALUES (?, ?, 'Pending', CURDATE())");
                $ins->bind_param("is", $ip_id, $request_type);
                if ($ins->execute()) {
                    // Redirect with success message
                    header("location: user_dashboard.php?comm_success=1");
                    exit;
                } else {
                    $error = "Failed to submit request. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Commercialization – BISU R.I.T.E.S</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 min-h-screen">

    <nav class="bg-teal-800 text-white shadow-md p-4 flex justify-between items-center">
        <div class="font-bold text-xl tracking-wider">BISU R.I.T.E.S</div>
        <a href="user_dashboard.php" class="text-teal-200 hover:text-white text-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Portal
        </a>
    </nav>

    <div class="max-w-2xl mx-auto py-10 px-4">

        <h2 class="text-2xl font-bold text-slate-800 mb-2">
            <i class="fas fa-file-contract text-teal-600 mr-2"></i> Request Commercialization Service
        </h2>
        <p class="text-slate-500 text-sm mb-6">Submit a request to the ITSO office to help commercialize your intellectual property.</p>

        <?php if ($error): ?>
            <div class="mb-5 p-4 rounded-md bg-red-50 text-red-800 border border-red-200 text-sm">
                <i class="fas fa-exclamation-triangle mr-2"></i><?php echo $error; ?>
            </div>
        <?php endif; ?>

        <?php if ($eligible_ips->num_rows === 0): ?>
            <div class="bg-white border border-slate-200 rounded-xl p-8 text-center shadow-sm">
                <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 text-2xl mx-auto mb-4">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3 class="font-bold text-slate-700 mb-2">No Eligible IP Disclosures</h3>
                <p class="text-slate-500 text-sm">You don't have any IP disclosures that are approved, filed, or registered yet. ITSO must first process your disclosure before you can request commercialization services.</p>
                <a href="user_dashboard.php" class="mt-5 inline-block bg-teal-600 hover:bg-teal-700 text-white px-5 py-2 rounded-lg text-sm font-medium transition">Return to Portal</a>
            </div>
        <?php else: ?>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
            <form method="post" class="space-y-5">

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">
                        Select Your IP Asset <span class="text-red-500">*</span>
                    </label>
                    <p class="text-xs text-slate-500 mb-2">Only your IPs that have been approved, filed, or registered are shown.</p>
                    <select name="ip_id" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                        <option value="">-- Select your IP --</option>
                        <?php
                        // Reset pointer
                        $eligible_ips->data_seek(0);
                        while ($ip = $eligible_ips->fetch_assoc()) {
                            $sel = (isset($_POST['ip_id']) && $_POST['ip_id'] == $ip['ip_id']) ? 'selected' : '';
                            $label = "IP-{$ip['ip_id']}: " . htmlspecialchars(substr($ip['title'], 0, 50)) . " [{$ip['ip_type']}] – {$ip['status']}";
                            echo "<option value='{$ip['ip_id']}' {$sel}>{$label}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">
                        Type of Commercialization Service <span class="text-red-500">*</span>
                    </label>
                    <select name="request_type" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                        <option value="">-- Select service type --</option>
                        <?php
                        $types = [
                            'Technology Adopter Search' => 'Find industry partners or adopters for your technology.',
                            'IP Valuation'              => 'Determine the market value of your intellectual property.',
                            'Licensing Advice'          => 'Get guidance on licensing your IP to third parties.',
                            'Online Promotion'          => 'Promote your technology on ITSO digital platforms.',
                            'Other'                     => 'Other commercialization-related assistance.',
                        ];
                        foreach ($types as $val => $desc) {
                            $sel = (isset($_POST['request_type']) && $_POST['request_type'] === $val) ? 'selected' : '';
                            echo "<option value='{$val}' {$sel}>{$val} – {$desc}</option>";
                        }
                        ?>
                    </select>
                </div>

                <!-- Info box -->
                <div class="bg-teal-50 border border-teal-200 rounded-lg p-4 text-sm text-teal-800">
                    <i class="fas fa-info-circle mr-2"></i>
                    Your request will be reviewed by the ITSO Director. You will be notified once processing begins.
                </div>

                <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 px-4 rounded-lg transition shadow-sm">
                    <i class="fas fa-paper-plane mr-1"></i> Submit Commercialization Request
                </button>

            </form>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
