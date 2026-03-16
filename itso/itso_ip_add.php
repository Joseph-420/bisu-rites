<?php
session_start();
require_once "../db_connect.php";

// ITSO Director only (Role ID 3)
if(!isset($_SESSION["loggedin"]) || $_SESSION["role_id"] !== 3){ 
    header("location: ../login.php"); 
    exit; 
}

$error   = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title             = trim($_POST['title']);
    $ip_type           = $_POST['ip_type'];
    $app_number        = trim($_POST['application_number']);
    $status            = $_POST['status'];
    $filing_date       = !empty($_POST['filing_date'])       ? $_POST['filing_date']       : NULL;
    $registration_date = !empty($_POST['registration_date']) ? $_POST['registration_date'] : NULL;
    $is_funded         = isset($_POST['is_externally_funded']) ? 1 : 0;
    $funding_agency    = ($is_funded && !empty($_POST['funding_agency'])) ? trim($_POST['funding_agency']) : NULL;

    $valid_types    = ['Patent', 'Utility Model', 'Industrial Design', 'Trademark', 'Copyright'];
    $valid_statuses = ['Disclosure Submitted', 'Under Review', 'Approved for Drafting', 'Filed', 'Registered', 'Refused', 'Rejected', 'Expired'];

    if (empty($title)) {
        $error = "Technology title is required.";
    } elseif (!in_array($ip_type, $valid_types)) {
        $error = "Invalid IP type selected.";
    } elseif (!in_array($status, $valid_statuses)) {
        $error = "Invalid status selected.";
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO ip_assets (title, ip_type, application_number, status, filing_date, registration_date, is_externally_funded, funding_agency)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        // types: s  s  s  s  s  s  i  s
        $stmt->bind_param("ssssssis", $title, $ip_type, $app_number, $status, $filing_date, $registration_date, $is_funded, $funding_agency);

        if ($stmt->execute()) {
            $new_id  = $stmt->insert_id;
            $success = "IP Asset <strong>IP-{$new_id}</strong> ('{$title}') has been encoded successfully.";
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}

$page_title = "Encode Existing IP Asset";
include "../includes/header.php";
?>

<div class="flex h-screen overflow-hidden bg-slate-50">
    <?php include "../includes/navigation.php"; ?>

    <div class="main-content flex-1 flex flex-col overflow-y-auto p-8">
        <div class="max-w-2xl mx-auto w-full">

            <div class="flex justify-between items-center mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">
                        <i class="fas fa-database text-teal-600 mr-2"></i> Encode Existing IP Asset
                    </h1>
                    <p class="text-slate-500 text-sm mt-1">Manually add a previously registered or filed IP into the system.</p>
                </div>
                <a href="itso_assets.php" class="text-slate-500 hover:text-slate-700 font-medium text-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Back to IP List
                </a>
            </div>

            <?php if ($error): ?>
                <div class="mb-5 p-4 rounded-md bg-red-50 text-red-800 border border-red-200 text-sm">
                    <i class="fas fa-exclamation-triangle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="mb-5 p-4 rounded-md bg-teal-50 text-teal-800 border border-teal-200 text-sm">
                    <i class="fas fa-check-circle mr-2"></i><?php echo $success; ?>
                    <br>
                    <a href="itso_assets.php" class="underline font-semibold mt-1 inline-block">← Return to IP Disclosures list</a>
                    &nbsp;|&nbsp;
                    <a href="itso_ip_add.php" class="underline font-semibold mt-1 inline-block">+ Encode another IP</a>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 border-t-4 border-t-teal-500">
                <form method="post" class="space-y-5">

                    <!-- Technology Title -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Technology / Work Title <span class="text-red-500">*</span></label>
                        <input type="text" name="title" required
                            value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                            placeholder="e.g. Nutriwatch Nutrition Monitoring System"
                            class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                    </div>

                    <!-- IP Type -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Type of Intellectual Property <span class="text-red-500">*</span></label>
                        <select name="ip_type" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            <option value="">-- Select IP Type --</option>
                            <?php
                            foreach (['Patent', 'Utility Model', 'Industrial Design', 'Trademark', 'Copyright'] as $t) {
                                $sel = (isset($_POST['ip_type']) && $_POST['ip_type'] === $t) ? 'selected' : '';
                                echo "<option value='{$t}' {$sel}>{$t}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Status -->
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1">Current / Starting Status <span class="text-red-500">*</span></label>
                        <select name="status" required class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            <option value="">-- Select Status --</option>
                            <?php
                            $statuses = ['Disclosure Submitted', 'Under Review', 'Approved for Drafting', 'Filed', 'Registered', 'Refused', 'Rejected', 'Expired'];
                            foreach ($statuses as $st) {
                                $sel = (isset($_POST['status']) && $_POST['status'] === $st) ? 'selected' : '';
                                echo "<option value='{$st}' {$sel}>{$st}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- IPOPHL Details (collapsible section) -->
                    <fieldset class="border border-slate-200 rounded-lg p-4 space-y-4">
                        <legend class="text-sm font-bold text-slate-600 px-1">IPOPHL Filing Details (if available)</legend>

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Application / Registration Number</label>
                            <input type="text" name="application_number"
                                value="<?php echo isset($_POST['application_number']) ? htmlspecialchars($_POST['application_number']) : ''; ?>"
                                placeholder="e.g. 2-2023-000123"
                                class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Filing Date</label>
                                <input type="date" name="filing_date"
                                    value="<?php echo isset($_POST['filing_date']) ? htmlspecialchars($_POST['filing_date']) : ''; ?>"
                                    class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Registration Date</label>
                                <input type="date" name="registration_date"
                                    value="<?php echo isset($_POST['registration_date']) ? htmlspecialchars($_POST['registration_date']) : ''; ?>"
                                    class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                            </div>
                        </div>
                    </fieldset>

                    <!-- External Funding -->
                    <div class="bg-slate-50 border border-slate-200 rounded-lg p-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="is_externally_funded" id="funding_cb"
                                <?php echo (isset($_POST['is_externally_funded'])) ? 'checked' : ''; ?>
                                onchange="document.getElementById('funding_div').style.display=this.checked?'block':'none'"
                                class="w-4 h-4 accent-teal-600">
                            <span class="text-sm font-bold text-slate-700">Externally Funded (DOST, CHED, etc.)</span>
                        </label>
                        <div id="funding_div" style="display: <?php echo (isset($_POST['is_externally_funded'])) ? 'block' : 'none'; ?>;" class="mt-3">
                            <label class="block text-sm font-bold text-slate-700 mb-1">Funding Agency</label>
                            <input type="text" name="funding_agency"
                                value="<?php echo isset($_POST['funding_agency']) ? htmlspecialchars($_POST['funding_agency']) : ''; ?>"
                                placeholder="e.g. DOST-PCIEERD"
                                class="w-full border border-slate-300 rounded-lg p-2.5 text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">
                        </div>
                    </div>

                    <div class="pt-2">
                        <button type="submit" class="w-full bg-teal-600 hover:bg-teal-700 text-white font-bold py-2.5 px-4 rounded-lg transition shadow-sm">
                            <i class="fas fa-save mr-1"></i> Save IP Record
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
