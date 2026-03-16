<?php
session_start();
require_once "db_connect.php";

$username = $password = "";
$username_err = $password_err = $login_err = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check if username is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = trim($_POST["username"]);
    }
    
    // Check if password is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate credentials
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement
        $sql = "SELECT user_id, username, password_hash, role_id FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $param_username);
            $param_username = $username;
            
            if ($stmt->execute()) {
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($id, $username, $hashed_password, $role_id);
                    if ($stmt->fetch()) {
                        // In a real app, use password_verify($password, $hashed_password)
                        // For this setup, we assume you might strictly compare strings if not using hash yet
                        // But let's stick to best practice:
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_start();
                            
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["username"] = $username;
                            $_SESSION["role_id"] = $role_id;                            
                            
                            // Redirect user based on Role ID
                            switch($_SESSION["role_id"]) {
                                case 1: // Superadmin
                                    header("location: admin/admin_dashboard.php");
                                    break;
                                case 2: // R&D Director
                                    header("location: RandD/rd_dashboard.php");
                                    break;
                                case 3: // ITSO Director
                                    header("location: itso/itso_dashboard.php");
                                    break;
                                // ... (other cases remain the same)
                                case 4: // Extension Director
                                header("location: extension/extension_dashboard.php"); // Update this if you put it in an extension folder
                                    break;
                                default: // Faculty/Student
                                header("location: users/user_dashboard.php"); 
                                    break;
                    }
                        } else {
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    $login_err = "Invalid username or password.";
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
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
    <title>BISU-R.I.T.E.S Login</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
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
        
        .login-container {
            animation: slideIn 0.6s ease-out;
            width: 100%;
            max-width: 420px;
            display: flex;
            flex-direction: column;
            height: auto;
            max-height: 95vh;
            overflow-y: auto;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .input-field {
            transition: all 0.3s ease;
            border: 2px solid #e5e7eb;
        }
        
        .input-field:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-field.error {
            border-color: #ef4444;
            background-color: #fef2f2;
        }
        
        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .logo-icon {
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }
        
        .error-alert {
            animation: shake 0.5s ease-in-out;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
        
        .form-group {
            transition: all 0.3s ease;
        }

        /* Scrollbar styling */
        .login-container::-webkit-scrollbar {
            width: 6px;
        }

        .login-container::-webkit-scrollbar-track {
            background: transparent;
        }

        .login-container::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .login-container::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Main Card -->
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mx-4 sm:mx-0">
            
            <!-- Header Section with Gradient -->
            <div class="bg-gradient-to-r from-slate-900 to-slate-800 px-6 sm:px-8 py-6">
                <div class="flex flex-col items-center">
                    <div class="logo-icon mb-3">
                        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-microscope text-xl text-transparent bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text" style="background-image: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent;"></i>
                        </div>
                    </div>
                    <h1 class="text-2xl font-bold text-white text-center">BISU R.I.T.E.S</h1>
                    <p class="text-slate-300 text-xs mt-1 text-center">Research, Innovation, and Extension System</p>
                </div>
            </div>
            
            <!-- Body Section - Compact -->
            <div class="px-6 sm:px-8 py-6">
                <p class="text-center text-slate-600 text-xs mb-5">Sign in to your account to continue</p>
                
                <!-- Error Alert -->
                <?php 
                if(!empty($login_err)){
                    echo '<div class="error-alert mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-start text-sm">
                        <i class="fas fa-exclamation-circle text-red-500 mt-0.5 mr-2 flex-shrink-0 text-xs"></i>
                        <span class="text-red-700 text-xs">' . htmlspecialchars($login_err) . '</span>
                    </div>';
                }        
                ?>
                
                <!-- Login Form -->
                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="space-y-4">
                    
                    <!-- Username Field -->
                    <div class="form-group">
                        <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            <i class="fas fa-user mr-1.5 text-slate-500"></i>Username
                        </label>
                        <input 
                            type="text" 
                            id="username"
                            name="username" 
                            class="input-field w-full px-3 py-2.5 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none <?php echo (!empty($username_err)) ? 'error' : ''; ?>" 
                            placeholder="Enter your username"
                            value="<?php echo htmlspecialchars($username); ?>"
                            required
                        >
                        <?php if(!empty($username_err)): ?>
                            <p class="mt-1 text-red-500 text-xs flex items-center">
                                <i class="fas fa-times-circle mr-1"></i><?php echo htmlspecialchars($username_err); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Password Field -->
                    <div class="form-group">
                        <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">
                            <i class="fas fa-lock mr-1.5 text-slate-500"></i>Password
                        </label>
                        <div class="relative">
                            <input 
                                type="password" 
                                id="password"
                                name="password" 
                                class="input-field w-full px-3 py-2.5 pr-10 rounded-lg text-xs text-slate-900 placeholder-slate-400 focus:outline-none <?php echo (!empty($password_err)) ? 'error' : ''; ?>" 
                                placeholder="Enter your password"
                                required
                            >
                            <button type="button" id="togglePassword" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 focus:outline-none text-xs" tabindex="-1">
                                <i class="fas fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                        <?php if(!empty($password_err)): ?>
                            <p class="mt-1 text-red-500 text-xs flex items-center">
                                <i class="fas fa-times-circle mr-1"></i><?php echo htmlspecialchars($password_err); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Remember Me & Forgot Password -->
                    <div class="flex items-center justify-between text-xs">
                        <label class="flex items-center text-slate-700 cursor-pointer">
                            <input type="checkbox" class="w-3.5 h-3.5 rounded border-slate-300" name="remember">
                            <span class="ml-1.5 text-xs">Remember me</span>
                        </label>
                        <a href="#" class="text-purple-600 hover:text-purple-700 font-medium text-xs">Forgot?</a>
                    </div>
                    
                    <!-- Submit Button -->
                    <button 
                        type="submit" 
                        class="btn-login w-full bg-gradient-to-r from-blue-600 to-purple-600 text-white font-semibold py-2.5 px-4 rounded-lg mt-5 flex items-center justify-center text-sm"
                    >
                        <i class="fas fa-sign-in-alt mr-1.5"></i>Sign In
                    </button>
                </form>
                
                <!-- Register Link -->
                <div class="mt-5 pt-4 border-t border-slate-200">
                    <p class="text-center text-slate-600 text-xs mb-2.5">
                        Don't have an account?
                        <a href="register.php" class="text-purple-600 hover:text-purple-700 font-medium">Register</a>
                    </p>
                    <p class="text-center text-slate-500 text-xs">
                        Need help? <a href="mailto:support@bisu.edu" class="text-purple-600 hover:text-purple-700 font-medium">support@bisu.edu</a>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Add real-time validation feedback
        document.getElementById('username').addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });

        document.getElementById('password').addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('error');
            } else {
                this.classList.remove('error');
            }
        });

        // Remove error class on input
        document.getElementById('username').addEventListener('input', function() {
            this.classList.remove('error');
        });

        document.getElementById('password').addEventListener('input', function() {
            this.classList.remove('error');
        });

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('eyeIcon');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    </script>
</body>
</html>