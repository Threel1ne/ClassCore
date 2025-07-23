<?php
// dashboard.php
require_once 'config.php';
requireLogin();

// Get user statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$total_users = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as total_payments FROM payments");
$stmt->execute();
$total_payments = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(amount) as total_amount FROM payments WHERE status = 'completed'");
$stmt->execute();
$total_amount = $stmt->fetchColumn() ?: 0;

// Get recent payments
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_payments = $stmt->fetchAll();

// Fetch subject exchanges for today only
$stmt = $pdo->prepare("SELECT se.*, u.full_name FROM subject_exchanges se JOIN users u ON se.created_by = u.id WHERE se.exchange_date = CURDATE() ORDER BY se.exchange_date DESC, se.id DESC LIMIT 5");
$stmt->execute();
$subject_exchanges = $stmt->fetchAll();

// Add: Only show user's own payment requests widget for all users
$user_payments = [];
$stmt = $pdo->prepare("SELECT * FROM payments WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$user_payments = $stmt->fetchAll();

$user_payments_count = count($user_payments);   


// Add subject period mapping for display
$subject_periods = [
    1 => 'คาบ 1 (08:20 - 09:10)',
    2 => 'คาบ 2 (09:10 - 10:00)',
    3 => 'คาบ 3 (10:00 - 10:50)',
    4 => 'คาบ 4 (10:50 - 11:40)',
    5 => 'คาบ 5 (11:40 - 12:30)',
    6 => 'คาบ 6 (12:30 - 13:20)',
    7 => 'คาบ 7 (13:20 - 14:10)',
    8 => 'คาบ 8 (14:10 - 15:00)',
    9 => 'คาบ 9 (15:00 - 15:50)',
    10 => 'คาบ 10 (15:50 - 16:40)',
];

// Fetch treasurer information
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE role = 'เหรัญญิก' LIMIT 1");
$stmt->execute();
$treasurer = $stmt->fetch();
$treasurer_name = $treasurer ? $treasurer['full_name'] : 'ไม่พบข้อมูลเหรัญญิก';

// Fetch latest 5 homework notes
$stmt = $pdo->prepare("SELECT n.*, u.full_name FROM notes n JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC LIMIT 5");
$stmt->execute();
$dashboard_notes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>หน้าหลัก - ระบบจัดการห้องเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --card-shadow: 0 10px 30px rgba(0,0,0,0.1);
            --card-hover-shadow: 0 15px 40px rgba(0,0,0,0.15);
            --border-radius: 15px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            line-height: 1.6;
        }

        .sidebar {
            background: var(--dark-gradient);
            min-height: 100vh;
            padding: 0;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            overflow-y: auto;
            z-index: 1000;
            transition: var(--transition);
        }

        .sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="50" cy="50" r="1" fill="white" opacity="0.03"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .sidebar-header {
            padding: 2rem 1rem;
            text-align: center;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
        }

        .sidebar-header h5 {
            color: #fff;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.2rem;
        }

        .sidebar-header .user-info {
            color: #ecf0f1;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .sidebar-header .user-role {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            margin-top: 0.5rem;
        }

        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: var(--transition);
            position: relative;
            display: flex;
            align-items: center;
            font-weight: 500;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .sidebar .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        /* Mobile Navbar Styles */
        .mobile-navbar {
            display: none;
            background: var(--dark-gradient);
            padding: 1rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1050;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .mobile-navbar-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: white;
        }

        .mobile-navbar h5 {
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
        }

        .burger-menu {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 5px;
            transition: var(--transition);
        }

        .burger-menu:hover {
            background: rgba(255,255,255,0.1);
        }

        .burger-menu.active {
            transform: rotate(90deg);
        }

        .main-content {
            background: transparent;
            min-height: 100vh;
            padding: 2rem;
        }

        .page-header {
            background: rgba(255,255,255,0.9);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .page-header h1 {
            color: #2c3e50;
            font-weight: 700;
            margin: 0;
            font-size: 2.5rem;
        }

        .stats-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255,255,255,0.2);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            height: 100%;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-gradient);
        }

        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-hover-shadow);
        }

        .stats-card.users::before { background: var(--primary-gradient); }
        .stats-card.money::before { background: var(--success-gradient); }
        .stats-card.payments::before { background: var(--warning-gradient); }
        .stats-card.notifications::before { background: var(--info-gradient); }

        .stats-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.8;
            transition: var(--transition);
        }

        .stats-card:hover .stats-icon {
            transform: scale(1.1);
            opacity: 1;
        }

        .stats-card h3 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }

        .stats-card p {
            color: #7f8c8d;
            font-weight: 500;
            margin: 0;
        }

        .card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255,255,255,0.2);
            transition: var(--transition);
            overflow: hidden;
        }

        .card:hover {
            box-shadow: var(--card-hover-shadow);
        }

        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
            position: relative;
        }

        .card-header h5 {
            font-weight: 600;
            margin: 0;
            font-size: 1.2rem;
        }

        .card-header .btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: var(--transition);
        }

        .card-header .btn:hover {
            background: rgba(255,255,255,0.3);
            transform: translateY(-2px);
        }

        .card-body {
            padding: 2rem;
        }

        .table {
            background: transparent;
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .table th {
            background: rgba(108, 117, 125, 0.1);
            border: none;
            color: #495057;
            font-weight: 600;
            padding: 1rem;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 0.5px;
        }

        .table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid rgba(0,0,0,0.05);
        }

        .table tbody tr {
            transition: var(--transition);
        }

        .table tbody tr:hover {
            background: rgba(102, 126, 234, 0.05);
            transform: scale(1.01);
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
            font-size: 0.8rem;
        }

        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--primary-gradient);
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: var(--success-gradient);
            box-shadow: 0 4px 15px rgba(79, 172, 254, 0.3);
        }

        .btn-warning {
            background: var(--warning-gradient);
            box-shadow: 0 4px 15px rgba(67, 233, 123, 0.3);
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #7f8c8d;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }

        .floating-action {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--primary-gradient);
            color: white;
            border: none;
            font-size: 1.5rem;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
            transition: var(--transition);
            z-index: 1000;
        }

        .floating-action:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(102, 126, 234, 0.6);
        }

        /* Mobile Phones (320px - 767px) */
        @media (max-width: 767px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                z-index: 1050;
                transition: var(--transition);
                transform: translateX(-100%);
                height: 100vh;
                overflow-y: auto;
            }
            
            .sidebar.show {
                left: 0;
                transform: translateX(0);
            }
            
            .mobile-navbar {
                display: block;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem 0.5rem;
                padding-top: 5rem; /* Account for mobile navbar */
            }
            
            .page-header {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .page-header h1 {
                font-size: 1.5rem;
            }
            
            .stats-card {
                margin-bottom: 1rem;
                padding: 1.5rem;
            }
            
            .stats-icon {
                font-size: 2rem;
            }
            
            .qr-scanner-section {
                padding: 1rem;
            }
            
            .qr-result {
                padding: 1rem;
                font-size: 0.9rem;
            }
            
            .table-responsive {
                font-size: 0.85rem;
            }
            
            .btn-sm {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }
            
            .card {
                margin-bottom: 1rem;
                border-radius: 8px;
            }
            
            .container-fluid {
                padding: 0;
            }
            
            .recent-notes-card {
                margin-bottom: 1rem;
            }
        }

        /* Large Mobile Phones (375px - 480px) */
        @media (min-width: 375px) and (max-width: 480px) {
            .main-content {
                padding: 1rem;
                padding-top: 5rem;
            }
            
            .page-header {
                padding: 1.75rem;
            }
            
            .stats-card {
                padding: 1.75rem;
            }
        }

        /* Tablets (481px - 1024px) */
        @media (min-width: 481px) and (max-width: 1024px) {
            .sidebar {
                width: 250px;
                position: fixed;
                top: 0;
                left: -100%;
                z-index: 1050;
                transition: var(--transition);
                height: 100vh;
                overflow-y: auto;
            }

            .sidebar.show {
                left: 0;
            }

            .mobile-navbar {
                display: block;
            }

            .main-content {
                margin-left: 0;
                padding: 1.5rem;
                padding-top: 5rem;
            }

            .card {
                margin-bottom: 1.5rem;
            }
            
            .page-header {
                padding: 2rem;
            }
            
            .stats-card {
                padding: 1.75rem;
            }
        }

        /* Small Laptops (768px - 1024px) */
        @media (min-width: 768px) and (max-width: 1024px) {
            .mobile-navbar {
                display: none;
            }
            
            .main-content {
                margin-left: 200px;
                padding: 2rem;
                padding-top: 2rem;
            }

            .sidebar {
                width: 200px;
                position: fixed;
                left: 0;
                top: 0;
                height: 100vh;
                overflow-y: auto;
                z-index: 1000;
            }
        }

        /* Large Laptops and Desktops (1025px+) */
        @media (min-width: 1025px) {
            .mobile-navbar {
                display: none;
            }
            
            .main-content {
                padding: 2rem 3rem;
            }
            
            .sidebar {
                position: fixed;
                left: 0;
                top: 0;
            }
        }

        /* Landscape orientation for mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .mobile-navbar {
                padding: 0.75rem 1rem;
            }

            .main-content {
                padding-top: 4rem;
            }
            
            .page-header {
                padding: 1rem;
                margin-bottom: 1rem;
            }
            
            .stats-card {
                padding: 1rem;
            }
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .gradient-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .note-files a {
            margin-right: 10px;
            position: relative;
        }
        .note-preview-img {
            display: none;
            position: fixed;
            z-index: 9999;
            pointer-events: none;
            max-width: 220px;
            max-height: 160px;
            border: 1px solid #ccc;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            padding: 2px;
        }

        /* Overlay for mobile menu */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.5);
            z-index: 999;
        }

        .mobile-overlay.show {
            display: block;
        }

        /* Animations */
        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        .fade-in {
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .gradient-text {
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .note-files a {
            margin-right: 10px;
            position: relative;
        }

        .note-preview-img {
            display: none;
            position: fixed;
            z-index: 9999;
            pointer-events: none;
            max-width: 220px;
            max-height: 160px;
            border: 1px solid #ccc;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            padding: 2px;
        }
    </style>
    <script>
        // Show image preview near mouse cursor, higher above the cursor
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('mousemove', function(e) {
                // Move any visible preview to follow the mouse
                var previews = document.querySelectorAll('.note-preview-img[style*="display: block"]');
                previews.forEach(function(preview) {
                    let img = preview.querySelector('img');
                    let w = img ? Math.min(img.naturalWidth, 220) : 200;
                    let h = img ? Math.min(img.naturalHeight, 160) : 140;
                    let x = e.clientX + 20;
                    let y = e.clientY - h - 20;
                    if (x + w > window.innerWidth) x = window.innerWidth - w - 10;
                    if (y < 0) y = 10;
                    preview.style.left = x + 'px';
                    preview.style.top = y + 'px';
                });
            });
            document.querySelectorAll('.note-files a.preview-hover').forEach(function(link) {
                link.addEventListener('mouseenter', function(e) {
                    var preview = this.querySelector('.note-preview-img');
                    if (preview) {
                        preview.style.display = 'block';
                    }
                });
                link.addEventListener('mouseleave', function() {
                    var preview = this.querySelector('.note-preview-img');
                    if (preview) preview.style.display = 'none';
                });
            });
        });
    </script>
</head>
<body>
    <nav class="mobile-navbar">
        <div class="mobile-navbar-content">
            <div>
                <h5>ห้องเรียนสีขาวดิจิตอล</h5>
                <small class="user-info">
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?> - 
                    <?php echo htmlspecialchars($roles[$_SESSION['user_role']]); ?>
                </small>
            </div>
            <button class="burger-menu" id="burgerToggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebar">
                <div class="position-sticky pt-3">
                    <div class="text-center text-white mb-3">
                        <h5>ห้องเรียนสีขาวดิจิตอล</h5>
                        <small><?php echo $_SESSION['full_name']; ?></small>
                        <br><small class="text-muted"><?php echo $roles[$_SESSION['user_role']]; ?></small>
                    </div>
                    <ul class="nav flex-column">
                        <?php
                        $nav_pages = [
                            'dashboard.php' => ['icon' => 'fas fa-tachometer-alt', 'label' => 'หน้าหลัก'],
                            'payments.php' => ['icon' => 'fas fa-money-bill-wave', 'label' => 'จัดการเงิน'],
                            'academic.php' => ['icon' => 'fas fa-graduation-cap', 'label' => 'เพิ่มการบ้าน'],
                            'subject_exchange.php' => ['icon' => 'fas fa-exchange-alt', 'label' => 'สลับวิชา'],
                            'discipline.php' => ['icon' => 'fas fa-shield-alt', 'label' => 'สารวัตร'],
                            'admin.php' => ['icon' => 'fas fa-users-cog', 'label' => 'จัดการผู้ใช้'],
                            'profile.php' => ['icon' => 'fas fa-user', 'label' => 'โปรไฟล์'],
                        ];
                        foreach ($nav_pages as $page => $info) {
                            if (canAccessPage($_SESSION['user_role'], $page)) {
                                $active = (basename($_SERVER['PHP_SELF']) == $page) ? ' active' : '';
                                echo '<li class="nav-item">
                                    <a class="nav-link' . $active . '" href="' . $page . '">
                                        <i class="' . $info['icon'] . '"></i> ' . $info['label'] . '
                                    </a>
                                </li>';
                            }
                        }
                        if ($_SESSION['user_role'] === 'admin') {
                            $active = (basename($_SERVER['PHP_SELF']) == 'admin_permissions.php') ? ' active' : '';
                            echo '<li class="nav-item">
                                <a class="nav-link' . $active . '" href="admin_permissions.php">
                                    <i class="fas fa-lock"></i> ตั้งค่าสิทธิ์
                                </a>
                            </li>';
                        }
                        ?>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> ออกจากระบบ
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            <div class="mobile-overlay" id="mobileOverlay"></div>
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="page-header mb-4">
                    <h1 class="h2">หน้าหลัก</h1>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-3">
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="stats-card text-center">
                            <div class="stats-icon text-primary">
                                <i class="fas fa-users"></i>
                            </div>
                            <h3><?php echo $total_users -1; ?></h3>
                            <p class="text-muted">ผู้ใช้งานทั้งหมด</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="stats-card text-center">
                            <div class="stats-icon text-success">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <h3><?php echo number_format($total_amount, 2); ?></h3>
                            <p class="text-muted">ยอดเงินรวม (บาท)</p>
                        </div>
                    </div>
                    
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="stats-card text-center">
                            <div class="stats-icon text-warning">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <h3><?php echo  $user_payments_count; ?></h3>
                            <p class="text-muted">รายการทั้งหมด</p>
                        </div>
                    </div>
                    
                    
                </div>

                <!-- Subject Exchange Announcements -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-exchange-alt"></i> ประกาศสลับวิชาสัปดาห์นี้
                                <!-- <a href="subject_exchange.php" class="btn btn-sm btn-light float-end">ดูทั้งหมด</a> -->
                            </div>
                            <div class="card-body ">
                                <?php if (empty($subject_exchanges)): ?>
                                    <div class="text-muted text-center">ไม่มีการสลับวิชาในสัปดาห์นี้</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th style="min-width:90px;">วันที่</th>
                                                    <th>คาบ</th>
                                                    <th>วิชาเดิม</th>
                                                    <th>เปลี่ยนเป็นวิชา</th>
                                                    <th>เหตุผล</th>
                                                    <th>โดย</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($subject_exchanges as $ex): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($ex['exchange_date']))); ?></td>
                                                    <td>
                                                        <?php
                                                        if (!empty($ex['periods'])) {
                                                            $period_arr = explode(',', $ex['periods']);
                                                            $period_labels = [];
                                                            foreach ($period_arr as $p) {
                                                                $p = intval($p);
                                                                if (isset($subject_periods[$p])) $period_labels[] = $subject_periods[$p];
                                                            }
                                                            echo implode('<br>', $period_labels);
                                                        } else {
                                                            echo '-';   
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($ex['subject_from']); ?></td>
                                                    <td><?php echo htmlspecialchars($ex['subject_to']); ?></td>
                                                    <td><?php echo htmlspecialchars($ex['reason']); ?></td>
                                                    <td><?php echo htmlspecialchars($ex['full_name']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User Payment Status Widget (always show for all users) -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-money-bill-wave"></i> สถานะการชำระเงินของคุณ
                    </div>
                    <div class="card-body">
                        <?php if (empty($user_payments)): ?>
                            <div class="text-muted text-center">ไม่มีข้อมูลการชำระเงินของคุณ</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>วันที่</th>
                                            <th>รายการ</th>
                                            <th>จำนวนเงิน</th>
                                            <th>สถานะ</th>
                                            <th>QR Code</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($user_payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                            <td><?php echo number_format($payment['amount'], 2); ?> บาท</td>
                                            <td>
                                                <span class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo $payment['status'] === 'completed' ? 'สำเร็จ' : 'รอดำเนินการ'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] !== 'completed'): ?>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="showQR('<?php echo $payment['qr_code_url']; ?>', '<?php echo $payment['reference_code']; ?>')">
                                                        <i class="fas fa-qrcode"></i> แสดง QR
                                                    </button>
                                                    <!-- Pass payment ID, amount, reference code, and treasurer info to Verify Slip Button -->
                                                    <button class="btn btn-sm btn-outline-success" onclick="openVerifySlipModal('<?php echo $payment['id']; ?>', '<?php echo $payment['amount']; ?>', '<?php echo $payment['reference_code']; ?>', '<?php echo htmlspecialchars($treasurer_name); ?>')">
                                                        <i class="fas fa-file-upload"></i> ตรวจสอบสลิป
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>


                <!-- QR Modal for user payment widget -->
                <div class="modal fade" id="qrModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">QR Code สำหรับชำระเงิน</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body text-center">
                                <img id="qrImage" src="" alt="QR Code" class="qr-code mb-3">
                                <p id="refCode"></p>
                                <p class="text-muted">สแกน QR Code เพื่อชำระเงินผ่าน PromptPay</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Add: Slip Verification Modal -->
                <div class="modal fade" id="verifySlipModal" tabindex="-1">
                    <div class="modal-dialog">
                        <form id="verifySlipForm" enctype="multipart/form-data">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">ตรวจสอบสลิปโอนเงิน</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>ผู้รับเงิน:</strong> <?php echo htmlspecialchars($treasurer_name); ?> (เหรัญญิก)
                                    </div>
                                    <div class="mb-3">
                                        <label for="slipImage" class="form-label">อัปโหลดรูปสลิป</label>
                                        <input type="file" class="form-control" id="slipImage" name="slipImage" accept="image/*" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="slipRefNbr" class="form-label">รหัสอ้างอิง</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="slipRefNbr" name="slipRefNbr" readonly required>
                                            <button type="button" class="btn btn-outline-primary" id="scanQrBtn">
                                                <i class="fas fa-camera"></i> สแกน QR
                                            </button>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="slipAmount" class="form-label">จำนวนเงิน</label>
                                        <input type="number" class="form-control" id="slipAmount" name="slipAmount" readonly required>
                                    </div>
                                    <div id="verifySlipResult" class="mt-2"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-success" id="verifySlipBtn" disabled>ตรวจสอบ</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- QR Scan Modal -->
                <div class="modal fade" id="qrScanModal" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">สแกน QR จากสลิป</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div id="qr-reader" style="width:100%"></div>
                                <div id="qr-reader-results" class="mt-2"></div>
                                <!-- Add file input for QR image upload -->
                                <div class="mt-3">
                                    <label for="qrImageUpload" class="form-label">หรืออัปโหลดรูปสลิปเพื่อสแกน QR</label>
                                    <input type="file" id="qrImageUpload" accept="image/*" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Homework Notes Widget -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-sticky-note"></i> โน้ต/การบ้านล่าสุด
                            </div>
                            <div class="card-body">
                                <?php if (empty($dashboard_notes)): ?>
                                    <div class="text-muted text-center">ไม่มีโน้ต/การบ้าน</div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered mb-0">
                                            <thead>
                                                <tr>
                                                    <th>หัวข้อ</th>
                                                    <th>รายละเอียด</th>
                                                    <th>ไฟล์แนบ</th>
                                                    <th>โดย</th>
                                                    <th>วันที่</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($dashboard_notes as $note): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($note['title']); ?></td>
                                                    <td><?php echo nl2br(htmlspecialchars($note['detail'])); ?></td>
                                                    <td class="note-files">
                                                        <?php
                                                        $stmt2 = $pdo->prepare("SELECT * FROM note_files WHERE note_id = ?");
                                                        $stmt2->execute([$note['id']]);
                                                        $files = $stmt2->fetchAll();
                                                        foreach ($files as $file) {
                                                            $is_img = preg_match('/\.(jpg|jpeg|png|gif)$/i', $file['file_name']);
                                                            $icon = $is_img ? '<i class="fas fa-image"></i>' : '<i class="fas fa-paperclip"></i>';
                                                            if ($is_img) {
                                                                echo '<a href="'.htmlspecialchars($file['file_path']).'" class="preview-hover" target="_blank">'.$icon.' '.htmlspecialchars($file['file_name']).
                                                                    '<span class="note-preview-img"><img src="'.htmlspecialchars($file['file_path']).'" style="max-width:220px;max-height:160px;"></span></a>';
                                                            } else {
                                                                echo '<a href="'.htmlspecialchars($file['file_path']).'" target="_blank">'.$icon.' '.htmlspecialchars($file['file_name']).'</a>';
                                                            }
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($note['full_name']); ?></td>
                                                    <td><?php echo date('d/m/Y H:i', strtotime($note['created_at'])); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Fallback Bootstrap CDN -->
    <script>
        if (typeof bootstrap === 'undefined') {
            document.write('<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.bundle.min.js"><\/script>');
        }
    </script>
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        // Mobile menu toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const burgerToggle = document.getElementById('burgerToggle');
            const sidebar = document.getElementById('sidebar');
            const mobileOverlay = document.getElementById('mobileOverlay');
            
            // Check if all required elements exist
            if (!burgerToggle || !sidebar || !mobileOverlay) {
                console.warn('Some navigation elements not found. Mobile menu may not work properly.');
                return;
            }
            
            // Toggle mobile menu
            burgerToggle.addEventListener('click', function() {
                if (sidebar) sidebar.classList.toggle('show');
                if (mobileOverlay) mobileOverlay.classList.toggle('show');
                this.classList.toggle('active');
            });
            
            // Close menu when clicking overlay
            mobileOverlay.addEventListener('click', function() {
                if (sidebar) sidebar.classList.remove('show');
                if (mobileOverlay) mobileOverlay.classList.remove('show');
                if (burgerToggle) burgerToggle.classList.remove('active');
            });
            
            // Close menu when clicking nav link on mobile
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            if (navLinks.length > 0) {
                navLinks.forEach(link => {
                    link.addEventListener('click', function() {
                        if (window.innerWidth <= 768) {
                            if (sidebar) sidebar.classList.remove('show');
                            if (mobileOverlay) mobileOverlay.classList.remove('show');
                            if (burgerToggle) burgerToggle.classList.remove('active');
                        }
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    if (sidebar) sidebar.classList.remove('show');
                    if (mobileOverlay) mobileOverlay.classList.remove('show');
                    if (burgerToggle) burgerToggle.classList.remove('active');
                }
            });

            // Image preview functionality (only if elements exist)
            const noteFiles = document.querySelectorAll('.note-files a.preview-hover');
            if (noteFiles.length > 0) {
                // Show image preview near mouse cursor
                document.body.addEventListener('mousemove', function(e) {
                    var previews = document.querySelectorAll('.note-preview-img[style*="display: block"]');
                    previews.forEach(function(preview) {
                        let img = preview.querySelector('img');
                        let w = img ? Math.min(img.naturalWidth, 220) : 200;
                        let h = img ? Math.min(img.naturalHeight, 160) : 140;
                        let x = e.clientX + 20;
                        let y = e.clientY - h - 20;
                        if (x + w > window.innerWidth) x = window.innerWidth - w - 10;
                        if (y < 0) y = 10;
                        preview.style.left = x + 'px';
                        preview.style.top = y + 'px';
                    });
                });
                
                noteFiles.forEach(function(link) {
                    link.addEventListener('mouseenter', function(e) {
                        var preview = this.querySelector('.note-preview-img');
                        if (preview) {
                            preview.style.display = 'block';
                        }
                    });
                    link.addEventListener('mouseleave', function() {
                        var preview = this.querySelector('.note-preview-img');
                        if (preview) preview.style.display = 'none';
                    });
                });
            }
        });

        // Add: Slip Verification JS
        function openVerifySlipModal(paymentId, amount, refNbr, treasurerName) {
            document.getElementById('verifySlipForm').reset();
            document.getElementById('verifySlipResult').innerHTML = '';
            // Set amount from payment record (not from QR)
            document.getElementById('slipAmount').value = amount;
            // Store payment ID for later use
            document.getElementById('slipAmount').setAttribute('data-payment-id', paymentId);
            // Clear reference number initially (will be filled from QR scan)
            document.getElementById('slipRefNbr').value = '';
            document.getElementById('verifySlipBtn').disabled = true;
            
            // Show treasurer info in modal
            console.log('Payment ID:', paymentId, 'Treasurer:', treasurerName);
            
            // Try to use Bootstrap, fallback to manual modal display
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    new bootstrap.Modal(document.getElementById('verifySlipModal')).show();
                } else {
                    // Fallback: manually show modal
                    var modal = document.getElementById('verifySlipModal');
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                    
                    // Add backdrop manually
                    var backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.id = 'manual-backdrop';
                    document.body.appendChild(backdrop);
                    
                    // Close modal when clicking backdrop or close button
                    backdrop.addEventListener('click', closeVerifySlipModal);
                    var closeBtn = modal.querySelector('.btn-close');
                    if (closeBtn) closeBtn.addEventListener('click', closeVerifySlipModal);
                }
            } catch (e) {
                console.error('Error opening modal:', e);
                alert('กรุณารอสักครู่แล้วลองใหม่อีกครั้ง (Bootstrap กำลังโหลด)');
            }
        }

        // Fallback function to close modal manually
        function closeVerifySlipModal() {
            var modal = document.getElementById('verifySlipModal');
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            
            // Remove manual backdrop
            var backdrop = document.getElementById('manual-backdrop');
            if (backdrop) {
                document.body.removeChild(backdrop);
            }
        }

        // QR Modal function with Bootstrap fallback
        function showQR(qrUrl, refCode) {
            document.getElementById('qrImage').src = qrUrl;
            document.getElementById('refCode').textContent = 'รหัสอ้างอิง: ' + refCode;
            
            // Try to use Bootstrap, fallback to manual modal display
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                    new bootstrap.Modal(document.getElementById('qrModal')).show();
                } else {
                    // Fallback: manually show modal
                    var modal = document.getElementById('qrModal');
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    modal.setAttribute('aria-hidden', 'false');
                    document.body.classList.add('modal-open');
                    
                    // Add backdrop manually
                    var backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show';
                    backdrop.id = 'manual-qr-backdrop';
                    document.body.appendChild(backdrop);
                    
                    // Close modal when clicking backdrop or close button
                    backdrop.addEventListener('click', function() {
                        modal.style.display = 'none';
                        modal.classList.remove('show');
                        modal.setAttribute('aria-hidden', 'true');
                        document.body.classList.remove('modal-open');
                        document.body.removeChild(backdrop);
                    });
                    
                    var closeBtn = modal.querySelector('.btn-close');
                    if (closeBtn) {
                        closeBtn.addEventListener('click', function() {
                            modal.style.display = 'none';
                            modal.classList.remove('show');
                            modal.setAttribute('aria-hidden', 'true');
                            document.body.classList.remove('modal-open');
                            document.body.removeChild(backdrop);
                        });
                    }
                }
            } catch (e) {
                console.error('Error opening QR modal:', e);
                alert('กรุณารอสักครู่แล้วลองใหม่อีกครั้ง (Bootstrap กำลังโหลด)');
            }
        }

        // Helper function to close QR scan modal safely
        function closeQrScanModal() {
            var qrScanModal = document.getElementById('qrScanModal');
            try {
                if (typeof bootstrap !== 'undefined' && bootstrap.Modal && bootstrap.Modal.getInstance(qrScanModal)) {
                    bootstrap.Modal.getInstance(qrScanModal).hide();
                } else {
                    // Manual close
                    qrScanModal.style.display = 'none';
                    qrScanModal.classList.remove('show');
                    qrScanModal.setAttribute('aria-hidden', 'true');
                    document.body.classList.remove('modal-open');
                    var backdrop = document.getElementById('manual-qr-scan-backdrop');
                    if (backdrop) {
                        document.body.removeChild(backdrop);
                    }
                }
            } catch (e) {
                console.error('Error closing QR scan modal:', e);
                // Force close manually
                qrScanModal.style.display = 'none';
                qrScanModal.classList.remove('show');
                document.body.classList.remove('modal-open');
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            var verifySlipForm = document.getElementById('verifySlipForm');
            var slipImageInput = document.getElementById('slipImage');
            var slipRefNbrInput = document.getElementById('slipRefNbr');
            var slipAmountInput = document.getElementById('slipAmount');
            var verifySlipBtn = document.getElementById('verifySlipBtn');
            // Disable manual editing
            slipRefNbrInput.readOnly = true;
            slipAmountInput.readOnly = true;
            verifySlipBtn.disabled = true;

            // Scan QR from uploaded slip image
            if (slipImageInput) {
                slipImageInput.addEventListener('change', function () {
                    slipRefNbrInput.value = '';
                    // Don't clear amount - it comes from payment record, not QR
                    verifySlipBtn.disabled = true;
                    document.getElementById('verifySlipResult').innerHTML = '';
                    
                    if (slipImageInput.files.length === 0) return;

                    const file = slipImageInput.files[0];
                    
                    // Show processing message
                    document.getElementById('verifySlipResult').innerHTML =
                        '<span class="text-info"><i class="fas fa-spinner fa-spin"></i> กำลังอ่าน QR Code จากรูปภาพ...</span>';

                    // Use html5-qrcode library to scan QR directly from file
                    const html5QrCode = new Html5Qrcode("qr-reader");
                    
                    html5QrCode.scanFile(file, true)
                        .then(qrCodeMessage => {
                            console.log('QR code detected:', qrCodeMessage);
                            
                            // Try to parse QR as JSON to get receiver info
                            let receiverInfo = null;
                            let refNbr = qrCodeMessage; // Default to full QR message
                            
                            try {
                                const qrData = JSON.parse(qrCodeMessage);
                                console.log('Parsed QR JSON:', qrData);
                                
                                // Extract receiver information from JSON
                                if (qrData.receiver) {
                                    receiverInfo = qrData.receiver;
                                }
                                
                                // Extract reference number from JSON
                                if (qrData.refNbr || qrData.reference || qrData.ref) {
                                    refNbr = qrData.refNbr || qrData.reference || qrData.ref;
                                }
                                
                                // Display receiver info if found
                                if (receiverInfo) {
                                    document.getElementById('verifySlipResult').innerHTML =
                                        '<span class="text-success"><i class="fas fa-check-circle"></i> พบ QR Code ในสลิปแล้ว!</span>' +
                                        '<br><span class="text-info"><strong>ผู้รับเงินจากสลิป:</strong> ' + receiverInfo + '</span>';
                                } else {
                                    document.getElementById('verifySlipResult').innerHTML =
                                        '<span class="text-success"><i class="fas fa-check-circle"></i> พบ QR Code ในสลิปแล้ว!</span>';
                                }
                                
                            } catch (e) {
                                // If not JSON, use the full QR message as reference
                                console.log('QR is not JSON format, using as reference number');
                                document.getElementById('verifySlipResult').innerHTML =
                                    '<span class="text-success"><i class="fas fa-check-circle"></i> พบ QR Code ในสลิปแล้ว!</span>';
                            }
                            
                            // Set the reference number
                            slipRefNbrInput.value = refNbr;
                            
                            // Upload the file to server
                            const formData = new FormData();
                            formData.append('file', file);
                            formData.append('qr_text', qrCodeMessage);

                            fetch('upload.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(res => res.json())
                            .then(uploadResult => {
                                if (uploadResult.success) {
                                    console.log('File uploaded successfully:', uploadResult.filePath);
                                    // Enable verify button
                                    verifySlipBtn.disabled = false;
                                } else {
                                    console.error('Upload error:', uploadResult.message);
                                    document.getElementById('verifySlipResult').innerHTML +=
                                        '<br><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + 
                                        'บันทึกไฟล์ไม่สำเร็จ แต่ยังสามารถตรวจสอบได้</span>';
                                    // Still enable verify button since we have QR data
                                    verifySlipBtn.disabled = false;
                                }
                            })
                            .catch(err => {
                                console.error('Error during file upload:', err);
                                document.getElementById('verifySlipResult').innerHTML +=
                                    '<br><span class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + 
                                    'บันทึกไฟล์ไม่สำเร็จ แต่ยังสามารถตรวจสอบได้</span>';
                                // Still enable verify button since we have QR data
                                verifySlipBtn.disabled = false;
                            });
                        })
                        .catch(err => {
                            console.error('Error scanning QR code:', err);
                            document.getElementById('verifySlipResult').innerHTML =
                                '<span class="text-danger"><i class="fas fa-times-circle"></i> ไม่พบ QR Code ในรูปภาพนี้</span>';
                        });
                });
            }

            if (verifySlipForm) {
                verifySlipForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    var resultDiv = document.getElementById('verifySlipResult');
                    resultDiv.innerHTML = '<span class="text-info">กำลังตรวจสอบ...</span>';
                    var amount = slipAmountInput.value.trim();
                    var refNbr = slipRefNbrInput.value.trim();
                    if (!amount || !refNbr) {
                        resultDiv.innerHTML = '<span class="text-danger">กรุณาอัปโหลดสลิปที่มี QR และจำนวนเงิน</span>';
                        return;
                    }
                    
                    // Show reminder about correct recipient
                    resultDiv.innerHTML = '<span class="text-info">กำลังตรวจสอบ... <br><small>* กรุณาตรวจสอบให้แน่ใจว่าโอนเงินให้ <?php echo htmlspecialchars($treasurer_name); ?> (เหรัญญิก)</small></span>';
                    
                    // Call OpenSlipVerify API
                    fetch('https://api.openslipverify.com', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            refNbr: refNbr,
                            amount: amount,
                            token: '9f608ce5-9b50-4791-9b9c-bf208d489ede'
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        console.log('OpenSlipVerify API response:', data); // Log the JSON response
                        if (data.success) {
                            let successMessage = '<span class="text-success"><i class="fas fa-check-circle"></i> ตรวจสอบสำเร็จ: ' + (data.statusMessage || 'สลิปถูกต้อง') + '</span>';
                            
                            // Extract receiver information from API response - check multiple possible field names
                            let receiverName = null;
                            let receiverAccount = '';
                            
                            // Debug: log the entire data object to see its structure
                            console.log('Full API response structure:', JSON.stringify(data, null, 2));
                            
                            // Check various possible field names for receiver information
                            if (data.receiver) {
                                receiverName = data.receiver.displayName || data.receiver.name || data.receiver;
                                receiverAccount = data.receiver.account ? data.receiver.account.value : '';
                            } else if (data.receiverDisplayName) {
                                receiverName = data.receiverDisplayName;
                            } else if (data.receiverName) {
                                receiverName = data.receiverName;
                            } else if (data.data && data.data.receiver) {
                                receiverName = data.data.receiver.displayName || data.data.receiver.name || data.data.receiver;
                                receiverAccount = data.data.receiver.account ? data.data.receiver.account.value : '';
                            } else if (data.transRef) {
                                // Some APIs return receiver info in transRef object
                                if (data.transRef.receiver) {
                                    receiverName = data.transRef.receiver.displayName || data.transRef.receiver.name;
                                    receiverAccount = data.transRef.receiver.account ? data.transRef.receiver.account.value : '';
                                }
                            }
                            
                            // Add receiver name directly under success message if found
                            if (receiverName) {
                                successMessage += '<br><strong class="text-primary"><i class="fas fa-user"></i> ผู้รับเงิน: ' + receiverName + '</strong>';
                                
                                // Private list of approved receivers (hardcoded for security)
                                const approvedReceivers = [
                                    'ภีรชญา ด้วงทองสุข',
                                    'ธราเทพ หลวงศิลป์',
                                    '<?php echo htmlspecialchars($treasurer_name); ?>'
                                ];
                                
                                // Check if receiver is in approved list
                                let isApprovedReceiver = false;
                                const receiverNameLower = receiverName.toLowerCase();
                                
                                // Check if receiver matches any approved name
                                isApprovedReceiver = approvedReceivers.some(approvedName => 
                                    approvedName && (receiverNameLower.includes(approvedName.toLowerCase()) || 
                                    approvedName.toLowerCase().includes(receiverNameLower))
                                );
                                
                                if (isApprovedReceiver) {
                                    // Receiver is approved - set payment to success
                                    successMessage += '<br><div class="alert alert-success mt-2">' +
                                        '<i class="fas fa-check-circle"></i> <strong>ผู้รับเงินถูกต้อง!</strong> กำลังอัปเดตสถานะการชำระเงิน...</div>';
                                    
                                    // Call API to update payment status
                                    const paymentId = document.getElementById('slipAmount').getAttribute('data-payment-id');
                                    fetch('update_payment_status.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            paymentId: paymentId,
                                            slipRefNbr: refNbr,
                                            amount: amount,
                                            receiverName: receiverName,
                                            status: 'completed'
                                        })
                                    })
                                    .then(res => res.json())
                                    .then(updateResult => {
                                        if (updateResult.success) {
                                            successMessage += '<br><div class="alert alert-success mt-1">' +
                                                '<i class="fas fa-check"></i> อัปเดตสถานะการชำระเงินสำเร็จ! หน้าจะรีเฟรชใน 3 วินาที</div>';
                                            
                                            // Refresh page after 3 seconds
                                            setTimeout(() => {
                                                window.location.reload();
                                            }, 3000);
                                        } else {
                                            successMessage += '<br><div class="alert alert-warning mt-1">' +
                                                '<i class="fas fa-exclamation-triangle"></i> ไม่สามารถอัปเดตสถานะได้: ' + (updateResult.message || 'เกิดข้อผิดพลาด') + '</div>';
                                        }
                                        resultDiv.innerHTML = successMessage;
                                    })
                                    .catch(err => {
                                        successMessage += '<br><div class="alert alert-warning mt-1">' +
                                            '<i class="fas fa-exclamation-triangle"></i> ไม่สามารถเชื่อมต่อระบบอัปเดตสถานะได้</div>';
                                        resultDiv.innerHTML = successMessage;
                                    });
                                    
                                } else {
                                    // Receiver not approved - show warning
                                    successMessage += '<br><div class="alert alert-warning mt-2">' +
                                        '<i class="fas fa-exclamation-triangle"></i> <strong>คำเตือน:</strong> ผู้รับเงินไม่ตรงกับรายชื่อที่อนุมัติ<br>' +
                                        '<strong>กรุณาติดต่อ ' + '<?php echo htmlspecialchars($treasurer_name); ?>' + ' (เหรัญญิก) เพื่อตรวจสอบ</strong></div>';
                                }
                                
                                // Add account number in alert box if available
                                if (receiverAccount) {
                                    successMessage += '<br><div class="alert alert-info mt-2">' +
                                        '<strong><i class="fas fa-credit-card"></i> เลขบัญชี:</strong> ' + receiverAccount + '</div>';
                                }
                            } else {
                                // If no receiver found, show debug info
                                successMessage += '<br><small class="text-muted">Debug: ไม่พบข้อมูลผู้รับเงินใน API response</small>';
                            }
                            
                            resultDiv.innerHTML = successMessage;
                        } else {
                            resultDiv.innerHTML = '<span class="text-danger"><i class="fas fa-times-circle"></i> ตรวจสอบไม่สำเร็จ: ' + (data.msg || 'ไม่พบข้อมูลสลิป') + '</span>';
                        }
                    })
                    .catch(err => {
                        resultDiv.innerHTML = '<span class="text-danger">เกิดข้อผิดพลาดในการเชื่อมต่อ API</span>';
                    });
                });
            }

            // QR Scan functionality - Updated to use html5-qrcode properly
            const scanQrBtn = document.getElementById('scanQrBtn');
            const qrScanModal = document.getElementById('qrScanModal');
            let qrScanner = null;

            if (scanQrBtn) {
                scanQrBtn.addEventListener('click', function() {
                    // Try to use Bootstrap, fallback to manual modal display
                    try {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            new bootstrap.Modal(qrScanModal).show();
                        } else {
                            // Fallback: manually show modal
                            qrScanModal.style.display = 'block';
                            qrScanModal.classList.add('show');
                            qrScanModal.setAttribute('aria-hidden', 'false');
                            document.body.classList.add('modal-open');
                            
                            // Add backdrop manually
                            var backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            backdrop.id = 'manual-qr-scan-backdrop';
                            document.body.appendChild(backdrop);
                            
                            // Close modal when clicking backdrop
                            backdrop.addEventListener('click', function() {
                                qrScanModal.style.display = 'none';
                                qrScanModal.classList.remove('show');
                                qrScanModal.setAttribute('aria-hidden', 'true');
                                document.body.classList.remove('modal-open');
                                document.body.removeChild(backdrop);
                                if (qrScanner) qrScanner.stop().catch(() => {});
                            });
                        }
                    } catch (e) {
                        console.error('Error opening QR scan modal:', e);
                        alert('กรุณารอสักครู่แล้วลองใหม่อีกครั้ง (Bootstrap กำลังโหลด)');
                        return;
                    }
                    
                    document.getElementById('qr-reader-results').innerHTML = '';
                    
                    // Initialize the scanner only if not already initialized
                    if (!qrScanner) {
                        qrScanner = new Html5Qrcode("qr-reader");
                    }
                    
                    // Start camera scanning
                    qrScanner.start(
                        { facingMode: "environment" }, // Use back camera
                        {
                            fps: 10,
                            qrbox: { width: 250, height: 250 }
                        },
                        (qrCodeMessage) => {
                            // Success callback
                            console.log('Camera QR detected:', qrCodeMessage);
                            
                            // Try to parse QR as JSON to get receiver info
                            let receiverInfo = null;
                            let refNbr = qrCodeMessage; // Default to full QR message
                            
                            try {
                                const qrData = JSON.parse(qrCodeMessage);
                                console.log('Parsed camera QR JSON:', qrData);
                                
                                // Extract receiver information from JSON
                                if (qrData.receiver) {
                                    receiverInfo = qrData.receiver;
                                }
                                
                                // Extract reference number from JSON
                                if (qrData.refNbr || qrData.reference || qrData.ref) {
                                    refNbr = qrData.refNbr || qrData.reference || qrData.ref;
                                }
                                
                                // Display receiver info if found
                                if (receiverInfo) {
                                    document.getElementById('qr-reader-results').innerHTML =
                                        '<div class="alert alert-success"><i class="fas fa-check-circle"></i> พบ QR Code: <br><code>' + qrCodeMessage + '</code>' +
                                        '<br><strong>ผู้รับเงินจากสลิป:</strong> ' + receiverInfo + '</div>';
                                } else {
                                    document.getElementById('qr-reader-results').innerHTML =
                                        '<div class="alert alert-success"><i class="fas fa-check-circle"></i> พบ QR Code: <br><code>' + qrCodeMessage + '</code></div>';
                                }
                                
                            } catch (e) {
                                // If not JSON, use the full QR message as reference
                                console.log('Camera QR is not JSON format, using as reference number');
                                document.getElementById('qr-reader-results').innerHTML =
                                    '<div class="alert alert-success"><i class="fas fa-check-circle"></i> พบ QR Code: <br><code>' + qrCodeMessage + '</code></div>';
                            }
                            
                            document.getElementById('slipRefNbr').value = refNbr;
                            
                            // Don't extract amount from QR - use the amount already set in modal
                            // The amount comes from the payment record, not from QR code
                            
                            // Enable verify button
                            document.getElementById('verifySlipBtn').disabled = false;
                            
                            // Stop scanner and close modal after 2 seconds
                            setTimeout(() => {
                                qrScanner.stop().then(() => {
                                    closeQrScanModal();
                                }).catch(() => {});
                            }, 2000);
                        },
                        (errorMessage) => {
                            // Error callback - usually just scanning errors, don't show to user
                        }
                    ).catch(err => {
                        document.getElementById('qr-reader-results').innerHTML =
                            '<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> ไม่สามารถเปิดกล้องได้: ' + err + '</div>';
                    });

                    // Handle QR scan from uploaded image
                    const qrImageUpload = document.getElementById('qrImageUpload');
                    if (qrImageUpload) {
                        qrImageUpload.value = ''; // Reset file input
                        qrImageUpload.onchange = function() {
                            if (qrImageUpload.files.length === 0) return;
                            
                            const file = qrImageUpload.files[0];
                            
                            // Show processing message
                            document.getElementById('qr-reader-results').innerHTML =
                                '<div class="alert alert-info"><i class="fas fa-spinner fa-spin"></i> กำลังอ่าน QR Code จากรูปภาพ...</div>';
                            
                            qrScanner.scanFile(file, true)
                                .then(qrCodeMessage => {
                                    document.getElementById('qr-reader-results').innerHTML =
                                        '<div class="alert alert-success"><i class="fas fa-check-circle"></i> พบ QR Code: <br><code>' + qrCodeMessage + '</code></div>';
                                    document.getElementById('slipRefNbr').value = qrCodeMessage;
                                    
                                    // Don't extract amount from QR - use the amount already set in modal
                                    // The amount comes from the payment record, not from QR code
                                    
                                    // Enable verify button
                                    document.getElementById('verifySlipBtn').disabled = false;
                                    
                                    // Close modal after 2 seconds
                                    setTimeout(() => {
                                        closeQrScanModal();
                                    }, 2000);
                                })
                                .catch(err => {
                                    document.getElementById('qr-reader-results').innerHTML =
                                        '<div class="alert alert-danger"><i class="fas fa-times-circle"></i> ไม่พบ QR Code ในรูปภาพนี้</div>';
                                });
                        };
                    }
                });
            }

            // Stop QR scanner when modal closes
            qrScanModal.addEventListener('hidden.bs.modal', function () {
                if (qrScanner) {
                    qrScanner.stop().catch(() => {});
                }
            });
        });
    </script>
</body>
</html>
