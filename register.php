<?php
// register.php
require_once "db_connect.php";

$username = $password = $role_id = "";
$first_name = $last_name = "";
$username_err = $password_err = $role_err = $register_err = "";
$success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST["first_name"] ?? "");
    $last_name = trim($_POST["last_name"] ?? "");

    // Validate Username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        $sql = "SELECT user_id FROM users WHERE username = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = trim($_POST["username"]);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            }
            $stmt->close();
        }
    }

    // Validate Password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate Role
    if (empty($_POST["role_id"]) || !in_array($_POST["role_id"], ['5', '6'])) {
        $role_err = "Please select a valid role.";
    } else {
        $role_id = $_POST["role_id"];
    }

    // Check input errors before inserting
    if (empty($username_err) && empty($password_err) && empty($role_err)) {
        $sql = "INSERT INTO users (username, password_hash, first_name, last_name, role_id) VALUES (?, ?, ?, ?, ?)";
        if ($stmt = $conn->prepare($sql)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt->bind_param("ssssi", $username, $password_hash, $first_name, $last_name, $role_id);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $register_err = "Something went wrong. Please try again later.";
            }
            $stmt->close();
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - BISU R.I.T.E.S</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * { 
            font-family: 'Inter', sans-serif; 
            margin: 0; 
            padding: 0; 
            box-sizing: border-box; 
        }

        html, body { 
            height: 100%; 
            width: 100%; 
            overflow: hidden; 
        }

        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .register-container { 
            animation: slideIn 0.6s ease-out;
            width: 500px;
            height: 500px;
            margin: 0 20px;
            overflow: hidden;
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-field {
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
            border: 2px solid #e5e7eb;
            font-size: 16px;
        }
        .input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .input-field.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }

        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        .btn-register:active { transform: translateY(0); }

        .logo-icon { animation: float 3s ease-in-out infinite; }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mx-4">

            <!-- Header -->
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-5 py-4">
                <div class="flex flex-col items-center">
                    <div class="logo-icon mb-2">
                        <div class="w-12 h-12 bg-white rounded-full flex items-center justify-center">
                            <i class="fas fa-user-plus text-lg" style="background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                        </div>
                    </div>
                    <h1 class="text-xl font-bold text-white text-center">Create Account</h1>
                    <p class="text-slate-300 text-xs mt-0.5 text-center">Faculty or Student</p>
                </div>
            </div>

            <!-- Body -->
            <div class="px-5 py-4" style="max-height: 410px; overflow-y: auto;">
                <?php if ($success): ?>
                    <div class="text-center py-4">
                        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-2">
                            <i class="fas fa-check text-green-600 text-lg"></i>
                        </div>
                        <h2 class="text-base font-bold text-slate-800 mb-1">Account Created!</h2>
                        <p class="text-slate-500 text-xs mb-3">Successfully registered.</p>
                        <a href="login.php" class="btn-register inline-block text-white font-semibold py-2 px-5 rounded-lg text-xs">
                            <i class="fas fa-sign-in-alt mr-1"></i>Login
                        </a>
                    </div>
                <?php else: ?>

                <?php if (!empty($register_err)): ?>
                    <div class="mb-3 p-2 bg-red-50 border border-red-200 rounded-lg flex items-start text-xs">
                        <i class="fas fa-exclamation-circle text-red-500 mr-1.5 flex-shrink-0 mt-0.5"></i>
                        <span class="text-red-700"><?php echo htmlspecialchars($register_err); ?></span>
                    </div>
                <?php endif; ?>

                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-2">
                    <!-- Name Row -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-0.5">
                                <i class="fas fa-user mr-1 text-slate-400"></i>First Name
                            </label>
                            <input type="text" name="first_name" value="<?php echo htmlspecialchars($first_name); ?>"
                                class="input-field w-full px-3 py-2 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none"
                                placeholder="Juan" required>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-700 mb-0.5">
                                <i class="fas fa-user mr-1 text-slate-400"></i>Last Name
                            </label>
                            <input type="text" name="last_name" value="<?php echo htmlspecialchars($last_name); ?>"
                                class="input-field w-full px-3 py-2 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none"
                                placeholder="Dela Cruz" required>
                        </div>
                    </div>

                    <!-- Username -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-0.5">
                            <i class="fas fa-at mr-1 text-slate-400"></i>Username
                        </label>
                        <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>"
                            class="input-field w-full px-3 py-2 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none <?php echo !empty($username_err) ? 'error' : ''; ?>"
                            placeholder="Username" required>
                        <?php if (!empty($username_err)): ?>
                            <p class="mt-0.5 text-red-500 text-xs"><i class="fas fa-times-circle mr-0.5"></i><?php echo htmlspecialchars($username_err); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-0.5">
                            <i class="fas fa-lock mr-1 text-slate-400"></i>Password
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                class="input-field w-full px-3 py-2 pr-9 rounded-lg text-sm text-slate-900 placeholder-slate-400 focus:outline-none <?php echo !empty($password_err) ? 'error' : ''; ?>"
                                placeholder="Min. 6 chars" required>
                            <button type="button" id="togglePassword" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none text-xs" tabindex="-1">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <?php if (!empty($password_err)): ?>
                            <p class="mt-0.5 text-red-500 text-xs"><i class="fas fa-times-circle mr-0.5"></i><?php echo htmlspecialchars($password_err); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Role -->
                    <div>
                        <label class="block text-xs font-medium text-slate-700 mb-0.5">
                            <i class="fas fa-id-badge mr-1 text-slate-400"></i>Role
                        </label>
                        <select name="role_id" required
                            class="input-field w-full px-3 py-2 rounded-lg text-sm text-slate-900 focus:outline-none <?php echo !empty($role_err) ? 'error' : ''; ?>">
                            <option value="">-- Select role --</option>
                            <option value="5" <?php echo $role_id == '5' ? 'selected' : ''; ?>>Faculty</option>
                            <option value="6" <?php echo $role_id == '6' ? 'selected' : ''; ?>>Student</option>
                        </select>
                        <?php if (!empty($role_err)): ?>
                            <p class="mt-0.5 text-red-500 text-xs"><i class="fas fa-times-circle mr-0.5"></i><?php echo htmlspecialchars($role_err); ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Submit -->
                    <button type="submit"
                        class="btn-register w-full text-white font-semibold py-2 rounded-lg mt-2 flex items-center justify-center text-xs">
                        <i class="fas fa-user-plus mr-1.5"></i>Register
                    </button>
                </form>

                <div class="mt-3 pt-2.5 border-t border-slate-200">
                    <p class="text-center text-slate-600 text-xs">
                        Have account?
                        <a href="login.php" class="text-purple-600 hover:text-purple-700 font-medium">Sign in</a>
                    </p>
                </div>

                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword')?.addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        });
    </script>
</body>
</html>