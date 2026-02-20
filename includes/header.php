<?php
// Determine the base path based on current directory
$current_dir = dirname($_SERVER['PHP_SELF']);
$base_path = '/bisu-rites';

// Calculate relative path to assets
if (strpos($current_dir, '/admin') !== false) {
    $assets_path = '../assets';
} elseif (strpos($current_dir, '/RandD') !== false) {
    $assets_path = '../assets';
} elseif (strpos($current_dir, '/itso') !== false) {
    $assets_path = '../assets';
} elseif (strpos($current_dir, '/extension') !== false) {
    $assets_path = '../assets';
} else {
    $assets_path = './assets';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - BISU R.I.T.E.S' : 'BISU R.I.T.E.S'; ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="<?php echo $assets_path; ?>/shadcn.css">
    
    <style>
        :root {
            --primary: #3b82f6;
            --secondary: #8b5cf6;
            --destructive: #ef4444;
            --success: #10b981;
            --warning: #f59e0b;
            --muted: #6b7280;
            --border: #e5e7eb;
            --radius: 0.5rem;
        }

        /* --- BULLETPROOF DASHBOARD LAYOUT FIX --- */
        
        /* 1. Stop the body from centering its contents */
        body {
            display: block !important; 
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background-color: #f9fafb !important;
            overflow-x: hidden !important; /* Prevent side-scrolling */
        }

        /* 2. Reset the wrapper */
        .page-container {
            display: block !important; 
            width: 100% !important;
            min-height: 100vh !important;
        }

        /* 3. Lock the sidebar to the left edge */
        .sidebar {
            width: 14rem !important;
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            bottom: 0 !important;
            z-index: 50 !important;
        }

        /* 4. Force the main content to lock to the sidebar and fill the right side */
        .main-content {
            position: relative !important;
            margin-left: 14rem !important; /* Push past the 14rem sidebar */
            width: calc(100% - 14rem) !important; /* Fill exact remaining screen width */
            min-height: 100vh !important;
            display: block !important;
            box-sizing: border-box !important;
        }

        /* 5. Ensure header stretches edge-to-edge inside main content */
        .header {
            width: 100% !important;
            box-sizing: border-box !important;
            margin: 0 !important;
            display: flex !important; /* Keeps your user profile flexed to the right */
        }
        
        /* 6. Fix internal padding wrapper */
        .content-wrapper {
            width: 100% !important;
            box-sizing: border-box !important;
        }
    </style>
</head>
<body class="bg-gray-50">