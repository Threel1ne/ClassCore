<?php
require_once 'config.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$error = null;
$success = null;

// Fetch user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $line_user_id = trim($_POST['line_user_id'] ?? '');
    $password = $_POST['password'] ?? '';
    $update_fields = [];
    $params = [];

    if (!$full_name) {
        $error = "กรุณากรอกชื่อ-นามสกุล";
    } else {
        $update_fields[] = "full_name = ?";
        $params[] = $full_name;
        $update_fields[] = "line_user_id = ?";
        $params[] = $line_user_id ?: null;

        if ($password) {
            $update_fields[] = "password = ?";
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        $params[] = $user_id;

        $sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        if ($stmt->execute($params)) {
            $_SESSION['full_name'] = $full_name;
            $success = "อัปเดตโปรไฟล์เรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการอัปเดตโปรไฟล์";
        }
    }
    // Refresh user info
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>โปรไฟล์ของฉัน - ระบบจัดการห้องเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@100..900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            --border-radius: 15px;
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Noto Sans Thai', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .sidebar {
            background: var(--dark-gradient);
            min-height: 100vh;
            padding: 0;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }

        .sidebar .nav-link {
            color: #ecf0f1;
            padding: 1rem 1.5rem;
            border-radius: 0;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            font-weight: 500;
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: var(--primary-gradient);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .sidebar .nav-link i {
            margin-right: 0.75rem;
            width: 20px;
            text-align: center;
        }

        .main-content {
            background: transparent;
            min-height: 100vh;
            padding: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
            margin-top: 2rem;
        }

        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
        }

        .btn-primary {
            background: var(--primary-gradient);
            border: none;
        }

        .btn-primary:hover {
            filter: brightness(1.1);
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

        .mobile-navbar .user-info {
            font-size: 0.85rem;
            opacity: 0.9;
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

        /* Enhanced Responsive Design for All Devices */
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

            .card {
                margin: 0.5rem 0;
                border-radius: 8px;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            .container-fluid {
                padding: 0;
            }
        }

        /* Large Mobile Phones (375px - 480px) */
        @media (min-width: 375px) and (max-width: 480px) {
            .mobile-navbar h5 {
                font-size: 1rem;
            }

            .mobile-navbar .user-info {
                font-size: 0.8rem;
            }

            .main-content {
                padding: 1rem;
                padding-top: 5rem;
            }
        }

        /* Tablets (481px - 1024px) */
        @media (min-width: 481px) and (max-width: 1024px) {
            .sidebar {
                width: 250px;
            }

            .main-content {
                padding: 1.5rem;
            }

            .card {
                max-width: 600px;
                margin: 0 auto;
            }
        }

        /* Small Laptops (768px - 1024px) */
        @media (min-width: 768px) and (max-width: 1024px) {
            .main-content {
                margin-left: 200px;
                padding: 2rem;
            }

            .sidebar {
                width: 200px;
            }
        }

        /* Large Laptops and Desktops (1025px+) */
        @media (min-width: 1025px) {
            .main-content {
                padding: 2rem 3rem;
            }

            .card {
                max-width: 800px;
                margin: 0 auto;
            }
        }

        /* Ultra-wide screens (1440px+) */
        @media (min-width: 1440px) {
            .main-content {
                padding: 2rem 4rem;
            }

            .card {
                max-width: 900px;
            }
        }

        /* Landscape orientation for mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .mobile-navbar {
                padding: 0.75rem 1rem;
            }

            .mobile-navbar h5 {
                font-size: 1rem;
            }

            .main-content {
                padding-top: 4rem;
            }
        }
    </style>
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
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-user"></i> โปรไฟล์ของฉัน</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">ชื่อผู้ใช้</label>
                                <input type="text" class="form-control"
                                    value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" required
                                    value="<?php echo htmlspecialchars($user['full_name']); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="line_user_id" class="form-label">LINE User ID (ไม่บังคับ)</label>
                                <input type="text" class="form-control" id="line_user_id" name="line_user_id"
                                    value="<?php echo htmlspecialchars($user['line_user_id'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">รหัสผ่านใหม่ (ถ้าเปลี่ยน)</label>
                                <input type="password" class="form-control" id="password" name="password"
                                    placeholder="กรอกหากต้องการเปลี่ยนรหัสผ่าน">
                            </div>
                            <button type="submit" class="btn btn-primary">บันทึกการเปลี่ยนแปลง</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
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
                        if (window.innerWidth <= 767) {
                            if (sidebar) sidebar.classList.remove('show');
                            if (mobileOverlay) mobileOverlay.classList.remove('show');
                            if (burgerToggle) burgerToggle.classList.remove('active');
                        }
                    });
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 767) {
                    if (sidebar) sidebar.classList.remove('show');
                    if (mobileOverlay) mobileOverlay.classList.remove('show');
                    if (burgerToggle) burgerToggle.classList.remove('active');
                }
            });

            // Enhanced responsive form handling
            function handleFormResponsiveness() {
                const form = document.querySelector('form');
                const inputs = document.querySelectorAll('input, select, textarea');
                
                if (window.innerWidth <= 767) {
                    // Mobile optimizations
                    inputs.forEach(input => {
                        if (input.type === 'text' || input.type === 'email' || input.type === 'password') {
                            input.style.fontSize = '16px'; // Prevent zoom on iOS
                        }
                    });
                }
            }

            // Call on load and resize
            handleFormResponsiveness();
            window.addEventListener('resize', handleFormResponsiveness);

            // Smooth scrolling for form errors
            const alertElements = document.querySelectorAll('.alert');
            if (alertElements.length > 0) {
                setTimeout(() => {
                    alertElements[0].scrollIntoView({ 
                        behavior: 'smooth', 
                        block: 'center' 
                    });
                }, 100);
            }
        });

        // Touch-friendly interactions for mobile
        if ('ontouchstart' in window) {
            document.body.classList.add('touch-device');
            
            // Add touch feedback
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach(button => {
                button.addEventListener('touchstart', function() {
                    this.style.transform = 'scale(0.98)';
                });
                
                button.addEventListener('touchend', function() {
                    this.style.transform = 'scale(1)';
                });
            });
        }
    </script>
</body>

</html>