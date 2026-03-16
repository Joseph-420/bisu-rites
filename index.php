<?php
session_start();
require_once "db_connect.php";

// Fetch statistics from actual database tables
$research_count = 0;
$ip_count = 0;
$extension_count = 0;

$r = $conn->query("SELECT COUNT(*) as count FROM rd_projects");
if ($r) $research_count = $r->fetch_assoc()['count'];

$r = $conn->query("SELECT COUNT(*) as count FROM ip_assets");
if ($r) $ip_count = $r->fetch_assoc()['count'];

$r = $conn->query("SELECT COUNT(*) as count FROM ext_projects");
if ($r) $extension_count = $r->fetch_assoc()['count'];

// Fetch published/completed items for public view
$published_research = $conn->query("SELECT p.project_title, p.abstract, p.status, c.college_code FROM rd_projects p LEFT JOIN colleges c ON p.college_id = c.college_id WHERE p.status IN ('Completed', 'Published') ORDER BY p.rd_id DESC LIMIT 6");

$registered_ips = $conn->query("SELECT title, ip_type, status, registration_date FROM ip_assets WHERE status IN ('Registered', 'Filed') ORDER BY ip_id DESC LIMIT 6");

$completed_ext = $conn->query("SELECT project_title, program_name, beneficiary_name, service_status FROM ext_projects WHERE service_status = 'Completed' ORDER BY ext_id DESC LIMIT 6");

// Fetch chart data - R&D status breakdown
$rd_chart_data = [];
$r = $conn->query("SELECT status, COUNT(*) as count FROM rd_projects GROUP BY status ORDER BY count DESC");
if ($r) { while ($row = $r->fetch_assoc()) $rd_chart_data[$row['status']] = (int)$row['count']; }

// IP type breakdown
$ip_chart_data = [];
$r = $conn->query("SELECT ip_type, COUNT(*) as count FROM ip_assets GROUP BY ip_type ORDER BY count DESC");
if ($r) { while ($row = $r->fetch_assoc()) $ip_chart_data[$row['ip_type']] = (int)$row['count']; }

// Extension status breakdown
$ext_chart_data = [];
$r = $conn->query("SELECT service_status, COUNT(*) as count FROM ext_projects GROUP BY service_status ORDER BY count DESC");
if ($r) { while ($row = $r->fetch_assoc()) $ext_chart_data[$row['service_status']] = (int)$row['count']; }
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
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
    
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
            <a href="#publications">Publications</a>
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
            <h2 class="text-4xl font-bold text-center mb-4">Our Impact</h2>
            <p class="text-gray-500 text-center mb-16 max-w-2xl mx-auto">A snapshot of the research, innovation, and extension output across the university.</p>
            
            <!-- Stat Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-16">
                <div class="stats-card">
                    <div class="text-center">
                        <div class="rie-icon research-icon"><i class="fas fa-flask-vial"></i></div>
                        <div class="stats-number"><?php echo $research_count; ?></div>
                        <p class="text-gray-600 text-lg font-semibold">Research Projects</p>
                        <p class="text-gray-500 text-sm mt-2">Active and completed research initiatives</p>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="text-center">
                        <div class="rie-icon innovation-icon"><i class="fas fa-lightbulb"></i></div>
                        <div class="stats-number"><?php echo $ip_count; ?></div>
                        <p class="text-gray-600 text-lg font-semibold">IP Assets</p>
                        <p class="text-gray-500 text-sm mt-2">Intellectual property developments</p>
                    </div>
                </div>
                <div class="stats-card">
                    <div class="text-center">
                        <div class="rie-icon extension-icon"><i class="fas fa-handshake"></i></div>
                        <div class="stats-number"><?php echo $extension_count; ?></div>
                        <p class="text-gray-600 text-lg font-semibold">Extension Programs</p>
                        <p class="text-gray-500 text-sm mt-2">Community outreach programs</p>
                    </div>
                </div>
            </div>

            <!-- Visual Charts -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- R&D Status Chart -->
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <h4 class="text-lg font-bold text-gray-800 mb-1 text-center">Research by Status</h4>
                    <p class="text-xs text-gray-400 text-center mb-4">Distribution of project statuses</p>
                    <div class="relative" style="height: 220px;">
                        <canvas id="rdChart"></canvas>
                    </div>
                    <?php if (empty($rd_chart_data)): ?>
                        <p class="text-center text-gray-400 text-sm mt-4">No data yet</p>
                    <?php endif; ?>
                </div>

                <!-- IP Type Chart -->
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <h4 class="text-lg font-bold text-gray-800 mb-1 text-center">IP Assets by Type</h4>
                    <p class="text-xs text-gray-400 text-center mb-4">Breakdown of intellectual property</p>
                    <div class="relative" style="height: 220px;">
                        <canvas id="ipChart"></canvas>
                    </div>
                    <?php if (empty($ip_chart_data)): ?>
                        <p class="text-center text-gray-400 text-sm mt-4">No data yet</p>
                    <?php endif; ?>
                </div>

                <!-- Extension Status Chart -->
                <div class="bg-white rounded-xl p-6 shadow-md">
                    <h4 class="text-lg font-bold text-gray-800 mb-1 text-center">Extension by Status</h4>
                    <p class="text-xs text-gray-400 text-center mb-4">Distribution of program statuses</p>
                    <div class="relative" style="height: 220px;">
                        <canvas id="extChart"></canvas>
                    </div>
                    <?php if (empty($ext_chart_data)): ?>
                        <p class="text-center text-gray-400 text-sm mt-4">No data yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Published Works Section -->
    <section id="publications" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-4">Published Works & Accomplishments</h2>
            <p class="text-gray-500 text-center mb-16 max-w-2xl mx-auto">Browse completed research, registered innovations, and successful extension programs at BISU.</p>

            <!-- Published Research -->
            <div class="mb-16">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-lg mr-3" style="background: var(--primary);"><i class="fas fa-flask-vial"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800">Published / Completed Research</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if($published_research && $published_research->num_rows > 0): ?>
                        <?php while($row = $published_research->fetch_assoc()): ?>
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 hover:shadow-md" style="transition: box-shadow 0.2s ease;">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $row['status'] === 'Published' ? 'bg-cyan-100 text-cyan-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                                <?php if($row['college_code']): ?>
                                <span class="text-xs text-gray-400"><?php echo htmlspecialchars($row['college_code']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['project_title']); ?></h4>
                            <p class="text-gray-500 text-sm line-clamp-3"><?php echo htmlspecialchars(substr($row['abstract'] ?? '', 0, 150)); ?><?php echo strlen($row['abstract'] ?? '') > 150 ? '...' : ''; ?></p>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-8 text-gray-400"><i class="fas fa-flask text-4xl mb-3 block"></i><p>No published research yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Registered Innovations -->
            <div class="mb-16">
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-lg mr-3" style="background: var(--secondary);"><i class="fas fa-lightbulb"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800">Registered Innovations & IP</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if($registered_ips && $registered_ips->num_rows > 0): ?>
                        <?php while($row = $registered_ips->fetch_assoc()): ?>
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 hover:shadow-md" style="transition: box-shadow 0.2s ease;">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full <?php echo $row['status'] === 'Registered' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                    <?php echo htmlspecialchars($row['status']); ?>
                                </span>
                                <span class="text-xs text-gray-400"><?php echo htmlspecialchars($row['ip_type']); ?></span>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['title']); ?></h4>
                            <?php if($row['registration_date']): ?>
                            <p class="text-gray-500 text-sm"><i class="fas fa-calendar-check mr-1"></i>Registered: <?php echo date('M d, Y', strtotime($row['registration_date'])); ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-8 text-gray-400"><i class="fas fa-lightbulb text-4xl mb-3 block"></i><p>No registered innovations yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Completed Extension Programs -->
            <div>
                <div class="flex items-center mb-6">
                    <div class="w-10 h-10 rounded-lg flex items-center justify-center text-white text-lg mr-3" style="background: var(--success);"><i class="fas fa-handshake"></i></div>
                    <h3 class="text-2xl font-bold text-gray-800">Completed Extension Programs</h3>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php if($completed_ext && $completed_ext->num_rows > 0): ?>
                        <?php while($row = $completed_ext->fetch_assoc()): ?>
                        <div class="bg-gray-50 rounded-xl p-5 border border-gray-200 hover:shadow-md" style="transition: box-shadow 0.2s ease;">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-xs font-semibold px-2 py-1 rounded-full bg-green-100 text-green-800">Completed</span>
                                <?php if($row['program_name']): ?>
                                <span class="text-xs text-gray-400"><?php echo htmlspecialchars($row['program_name']); ?></span>
                                <?php endif; ?>
                            </div>
                            <h4 class="font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($row['project_title']); ?></h4>
                            <p class="text-gray-500 text-sm"><i class="fas fa-users mr-1"></i>Beneficiary: <?php echo htmlspecialchars($row['beneficiary_name']); ?></p>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-span-full text-center py-8 text-gray-400"><i class="fas fa-handshake text-4xl mb-3 block"></i><p>No completed extension programs yet.</p></div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Offices Section -->
    <section id="offices" class="py-20 bg-gray-50">
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
    <section id="about" class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-4xl font-bold text-center mb-4">About R.I.T.E.S</h2>
            <p class="text-gray-500 text-center mb-12 max-w-2xl mx-auto">Learn about our mission and purpose</p>
            
            <div class="max-w-4xl mx-auto">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="bg-gradient-to-br from-blue-50 to-purple-50 p-8 rounded-xl border border-blue-100">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center mb-4">
                            <i class="fas fa-bullseye text-blue-600 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Our Mission</h3>
                        <p class="text-gray-600 leading-relaxed">
                            To advance knowledge creation, foster innovation, and strengthen community engagement through coordinated efforts among our three main offices: the Research & Development Office, the Innovation & Technology Services Office, and the Extension Services Office.
                        </p>
                    </div>
                    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 p-8 rounded-xl border border-emerald-100">
                        <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center mb-4">
                            <i class="fas fa-eye text-emerald-600 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-3">Our Vision</h3>
                        <p class="text-gray-600 leading-relaxed">
                            By integrating research, innovation, and extension functions, R.I.T.E.S enables the university to fulfill its role as a knowledge and innovation hub — serving the academic community and contributing meaningfully to regional development and societal advancement.
                        </p>
                    </div>
                </div>
                <div class="mt-8 bg-gray-50 p-8 rounded-xl border border-gray-200 text-center">
                    <p class="text-gray-600 leading-relaxed max-w-3xl mx-auto">
                        The Research, Innovation & Extension Services (R.I.T.E.S) is a comprehensive system at <strong>Bohol Island State University</strong> designed to promote, support, and coordinate research, innovation, and extension activities across the institution.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gradient-to-b from-gray-900 to-gray-950 text-white pt-16 pb-8">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 mb-12">
                <!-- Brand -->
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-10 h-10 rounded-lg flex items-center justify-center mr-3" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <i class="fas fa-flask-vial text-white"></i>
                        </div>
                        <h4 class="font-bold text-lg">BISU R.I.T.E.S</h4>
                    </div>
                    <p class="text-gray-400 text-sm leading-relaxed">Advancing knowledge, innovation, and community development at Bohol Island State University.</p>
                    <div class="flex space-x-3 mt-5">
                        <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-blue-600 hover:text-white transition"><i class="fab fa-facebook-f text-sm"></i></a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-sky-500 hover:text-white transition"><i class="fab fa-twitter text-sm"></i></a>
                        <a href="#" class="w-9 h-9 rounded-lg bg-gray-800 flex items-center justify-center text-gray-400 hover:bg-pink-600 hover:text-white transition"><i class="fab fa-instagram text-sm"></i></a>
                    </div>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="font-bold mb-4 text-sm uppercase tracking-wider text-gray-300">Quick Links</h4>
                    <ul class="text-gray-400 space-y-3 text-sm">
                        <li><a href="#about" class="hover:text-white transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i>About</a></li>
                        <li><a href="#offices" class="hover:text-white transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i>Offices</a></li>
                        <li><a href="#publications" class="hover:text-white transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i>Publications</a></li>
                        <li><a href="login.php" class="hover:text-white transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i>Login</a></li>
                        <li><a href="register.php" class="hover:text-white transition flex items-center"><i class="fas fa-chevron-right text-xs mr-2 text-gray-600"></i>Register</a></li>
                    </ul>
                </div>

                <!-- Offices -->
                <div>
                    <h4 class="font-bold mb-4 text-sm uppercase tracking-wider text-gray-300">Offices</h4>
                    <ul class="text-gray-400 space-y-3 text-sm">
                        <li class="flex items-center"><span class="w-2 h-2 rounded-full mr-2" style="background: var(--primary);"></span>Research & Development</li>
                        <li class="flex items-center"><span class="w-2 h-2 rounded-full mr-2" style="background: var(--secondary);"></span>Innovation & Technology</li>
                        <li class="flex items-center"><span class="w-2 h-2 rounded-full mr-2" style="background: var(--success);"></span>Extension Services</li>
                    </ul>
                </div>

                <!-- Contact -->
                <div>
                    <h4 class="font-bold mb-4 text-sm uppercase tracking-wider text-gray-300">Contact Us</h4>
                    <ul class="text-gray-400 space-y-3 text-sm">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3 text-gray-500"></i>
                            <span>Bohol Island State University, Tagbilaran City, Bohol</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-gray-500"></i>
                            <span>rites@bisu.edu.ph</span>
                        </li>
                        <li class="flex items-center">
                            <i class="fas fa-phone mr-3 text-gray-500"></i>
                            <span>+63-38-XXX-XXXX</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4">
                <p class="text-gray-500 text-sm">&copy; 2026 Bohol Island State University. All rights reserved.</p>
                <p class="text-gray-600 text-xs">Research, Innovation & Extension Services</p>
            </div>
        </div>
    </footer>
    <script>
        const chartColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444', '#06b6d4', '#ec4899', '#6366f1'];
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, usePointStyle: true, pointStyle: 'circle', font: { size: 11 } } }
            },
            cutout: '55%'
        };

        // R&D Status Chart
        const rdData = <?php echo json_encode($rd_chart_data); ?>;
        if (Object.keys(rdData).length > 0) {
            new Chart(document.getElementById('rdChart'), {
                type: 'doughnut',
                data: { labels: Object.keys(rdData), datasets: [{ data: Object.values(rdData), backgroundColor: chartColors.slice(0, Object.keys(rdData).length), borderWidth: 0 }] },
                options: chartOptions
            });
        }

        // IP Type Chart
        const ipData = <?php echo json_encode($ip_chart_data); ?>;
        if (Object.keys(ipData).length > 0) {
            new Chart(document.getElementById('ipChart'), {
                type: 'doughnut',
                data: { labels: Object.keys(ipData), datasets: [{ data: Object.values(ipData), backgroundColor: chartColors.slice(0, Object.keys(ipData).length), borderWidth: 0 }] },
                options: chartOptions
            });
        }

        // Extension Status Chart
        const extData = <?php echo json_encode($ext_chart_data); ?>;
        if (Object.keys(extData).length > 0) {
            new Chart(document.getElementById('extChart'), {
                type: 'doughnut',
                data: { labels: Object.keys(extData), datasets: [{ data: Object.values(extData), backgroundColor: chartColors.slice(0, Object.keys(extData).length), borderWidth: 0 }] },
                options: chartOptions
            });
        }
    </script>

</body>
</html>
