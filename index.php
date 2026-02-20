<?php
session_start();
require_once "db_connect.php";

// Fetch statistics from database WITH ERROR HANDLING
$research_count = 0;
$ip_count = 0;
$extension_count = 0;

// Query research count - with error handling
try {
    $sql_research = "SELECT COUNT(*) as count FROM research_projects";
    $result = $conn->query($sql_research);
    if ($result) {
        $row = $result->fetch_assoc();
        $research_count = $row['count'] ?? 0;
    }
} catch (Exception $e) {
    $research_count = 0; // Default to 0 if table doesn't exist
}

// Query IP/Innovation count
try {
    $sql_ip = "SELECT COUNT(*) as count FROM ip_projects";
    $result = $conn->query($sql_ip);
    if ($result) {
        $row = $result->fetch_assoc();
        $ip_count = $row['count'] ?? 0;
    }
} catch (Exception $e) {
    $ip_count = 0; // Default to 0 if table doesn't exist
}

// Query Extension programs count
try {
    $sql_extension = "SELECT COUNT(*) as count FROM extension_programs";
    $result = $conn->query($sql_extension);
    if ($result) {
        $row = $result->fetch_assoc();
        $extension_count = $row['count'] ?? 0;
    }
} catch (Exception $e) {
    $extension_count = 0; // Default to 0 if table doesn't exist
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BISU R.I.T.E.S - Research, Innovation & Extension Services</title>
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <style>
        :root {
            --primary: #3b82f6;
            --secondary: #8b5cf6;
            --success: #10b981;
        }
        
        .gradient-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .stats-card {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .stats-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0,0,0,0.15);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .rie-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .research-icon { color: var(--primary); }
        .innovation-icon { color: var(--secondary); }
        .extension-icon { color: var(--success); }
        
        .office-card {
            background: white;
            border-radius: 0.5rem;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }
        
        .office-card:hover {
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
        }
        
        .office-card.research {
            border-left-color: var(--primary);
        }
        
        .office-card.innovation {
            border-left-color: var(--secondary);
        }
        
        .office-card.extension {
            border-left-color: var(--success);
        }
        
        .nav-header {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav-links a {
            color: #374151;
            text-decoration: none;
            margin: 0 1rem;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: var(--primary);
        }
        
        .btn-login {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        .btn-login:hover {
            background: #2563eb;
        }
    </style>
</head>
<body class="bg-gray-50">

    <!-- Navigation Header -->
    <nav class="nav-header">
        <div class="text-2xl font-bold" style="color: var(--primary);">
            <i class="fas fa-flask-vial mr-2"></i>BISU R.I.T.E.S
        </div>
        <div class="nav-links">
            <a href="#about">About</a>
            <a href="#offices">Offices</a>
            <a href="#statistics">Statistics</a>
            <?php if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"]): ?>
                <a href="logout.php">Login</a>
                <?php if ($_SESSION["role_id"] == 1): ?>
                    <a href="admin/admin_dashboard.php" class="btn-login"><i class="fas fa-lock-open mr-1"></i>Admin Panel</a>
                <?php elseif ($_SESSION["role_id"] == 2): ?>
                    <a href="RandD/rd_dashboard.php" class="btn-login"><i class="fas fa-flask mr-1"></i>R&D Dashboard</a>
                <?php elseif ($_SESSION["role_id"] == 3): ?>
                    <a href="itso/itso_dashboard.php" class="btn-login"><i class="fas fa-lightbulb mr-1"></i>ITSO Dashboard</a>
                <?php elseif ($_SESSION["role_id"] == 4): ?>
                    <a href="extension/extension_dashboard.php" class="btn-login"><i class="fas fa-handshake mr-1"></i>Extension Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn-login"><i class="fas fa-sign-in-alt mr-1"></i>Login</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-header text-white py-20">
        <div class="container mx-auto px-4 text-center">
            <h1 class="text-5xl font-bold mb-4">Research, Innovation & Extension Services</h1>
            <p class="text-xl mb-8 opacity-90">Bohol Island State University - Advancing Knowledge, Innovation, and Community Development</p>
            <div class="flex justify-center gap-4">
                <a href="login.php" class="bg-white text-purple-600 px-6 py-3 rounded-lg font-semibold hover:bg-gray-100 transition">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login to Dashboard
                </a>
                <a href="#statistics" class="border-2 border-white text-white px-6 py-3 rounded-lg font-semibold hover:bg-white hover:bg-opacity-10 transition">
                    <i class="fas fa-chart-bar mr-2"></i>View Statistics
                </a>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section id="statistics" class="py-20">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-16">Our Impact</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <!-- Research Projects -->
                <div class="stats-card">
                    <div class="text-center">
                        <div class="rie-icon research-icon">
                            <i class="fas fa-flask-vial"></i>
                        </div>
                        <div class="stats-number"><?php echo $research_count; ?></div>
                        <p class="text-gray-600 text-lg font-semibold">Research Projects</p>
                        <p class="text-gray-500 text-sm mt-2">Active and completed research initiatives advancing knowledge</p>
                    </div>
                </div>
                
                <!-- IP Projects -->
                <div class="stats-card">
                    <div class="text-center">
                        <div class="rie-icon innovation-icon">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="stats-number"><?php echo $ip_count; ?></div>
                        <p class="text-gray-600 text-lg font-semibold">IP Projects</p>
                        <p class="text-gray-500 text-sm mt-2">Innovative solutions and intellectual property developments</p>
                    </div>
                </div>
                
                <!-- Extension Programs -->
                <div class="stats-card">
                    <div class="text-center">
                        <div class="rie-icon extension-icon">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <div class="stats-number"><?php echo $extension_count; ?></div>
                        <p class="text-gray-600 text-lg font-semibold">Extension Programs</p>
                        <p class="text-gray-500 text-sm mt-2">Community outreach and development programs</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Offices Section -->
    <section id="offices" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-16">Our R.I.T.E.S Offices</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Research & Development Office -->
                <div class="office-card research">
                    <div class="flex items-center mb-4">
                        <div style="color: var(--primary); font-size: 2.5rem; margin-right: 1rem;">
                            <i class="fas fa-flask-vial"></i>
                        </div>
                        <h3 class="text-xl font-bold">R&D Office</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Manages and facilitates research projects across various disciplines, promoting scholarly research and scientific advancement within the university.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check" style="color: var(--primary); margin-right: 0.5rem;"></i>Research Project Management</li>
                        <li><i class="fas fa-check" style="color: var(--primary); margin-right: 0.5rem;"></i>Grant Administration</li>
                        <li><i class="fas fa-check" style="color: var(--primary); margin-right: 0.5rem;"></i>Publication Support</li>
                        <li><i class="fas fa-check" style="color: var(--primary); margin-right: 0.5rem;"></i>Research Ethics</li>
                    </ul>
                </div>
                
                <!-- Innovation & Technology Services Office -->
                <div class="office-card innovation">
                    <div class="flex items-center mb-4">
                        <div style="color: var(--secondary); font-size: 2.5rem; margin-right: 1rem;">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <h3 class="text-xl font-bold">ITSO</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Focuses on innovation and intellectual property development, promoting technological advancements and commercialization of research outputs.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check" style="color: var(--secondary); margin-right: 0.5rem;"></i>IP Development</li>
                        <li><i class="fas fa-check" style="color: var(--secondary); margin-right: 0.5rem;"></i>Patent Management</li>
                        <li><i class="fas fa-check" style="color: var(--secondary); margin-right: 0.5rem;"></i>Technology Transfer</li>
                        <li><i class="fas fa-check" style="color: var(--secondary); margin-right: 0.5rem;"></i>Incubation Support</li>
                    </ul>
                </div>
                
                <!-- Extension Services Office -->
                <div class="office-card extension">
                    <div class="flex items-center mb-4">
                        <div style="color: var(--success); font-size: 2.5rem; margin-right: 1rem;">
                            <i class="fas fa-handshake"></i>
                        </div>
                        <h3 class="text-xl font-bold">Extension Office</h3>
                    </div>
                    <p class="text-gray-600 mb-4">Bridges university resources with community needs, implementing extension programs that create social impact and development initiatives.</p>
                    <ul class="text-sm text-gray-600 space-y-2">
                        <li><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>Community Programs</li>
                        <li><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>Capacity Building</li>
                        <li><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>Partnerships</li>
                        <li><i class="fas fa-check" style="color: var(--success); margin-right: 0.5rem;"></i>Advocacy Programs</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-16">About R.I.T.E.S</h2>
            
            <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
                <p class="text-gray-700 mb-4">
                    The Research, Innovation & Extension Services (R.I.T.E.S) is a comprehensive system at Bohol Island State University designed to promote, support, and coordinate research, innovation, and extension activities across the institution.
                </p>
                <p class="text-gray-700 mb-4">
                    Our mission is to advance knowledge creation, foster innovation, and strengthen community engagement through coordinated efforts among our three main offices: the Research & Development Office, the Innovation & Technology Services Office, and the Extension Services Office.
                </p>
                <p class="text-gray-700">
                    By integrating these critical functions, R.I.T.E.S enables the university to fulfill its role as a knowledge and innovation hub, serving not only the academic community but also contributing meaningfully to regional development and societal advancement.
                </p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <h4 class="font-bold mb-4"><i class="fas fa-flask-vial mr-2"></i>BISU R.I.T.E.S</h4>
                    <p class="text-gray-400">Advancing knowledge, innovation, and community development.</p>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Quick Links</h4>
                    <ul class="text-gray-400 space-y-2">
                        <li><a href="#about" class="hover:text-white">About</a></li>
                        <li><a href="#offices" class="hover:text-white">Offices</a></li>
                        <li><a href="login.php" class="hover:text-white">Login</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Offices</h4>
                    <ul class="text-gray-400 space-y-2">
                        <li>Research & Development</li>
                        <li>Innovation & Technology</li>
                        <li>Extension Services</li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-bold mb-4">Contact</h4>
                    <p class="text-gray-400">
                        <i class="fas fa-envelope mr-2"></i>rites@bisu.edu.ph<br>
                        <i class="fas fa-phone mr-2"></i>+63-38-XXX-XXXX
                    </p>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 flex justify-between items-center">
                <p class="text-gray-400">&copy; 2026 Bohol Island State University. All rights reserved.</p>
                <div class="text-gray-400 space-x-4">
                    <a href="#" class="hover:text-white"><i class="fab fa-facebook"></i></a>
                    <a href="#" class="hover:text-white"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="hover:text-white"><i class="fab fa-instagram"></i></a>
                </div>
            </div>
        </div>
    </footer>

</body>
</html>
