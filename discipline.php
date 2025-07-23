<?php
// discipline.php
require_once 'config.php';
requireLogin();

if (!hasRole('สารวัตร') && !hasRole('admin') && !hasRole('ฝ่ายการเรียน') && !hasRole('หัวหน้าห้อง') && !hasRole('รองหัวหน้าห้อง')) {
    header('Location: dashboard.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สารวัตร - ระบบจัดการห้องเรียน</title>
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
            --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            --card-hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
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
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
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
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
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
            background: rgba(255, 255, 255, 0.2);
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
        }

        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.1);
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

        .main-content {
            background: transparent;
            min-height: 100vh;
            padding: 2rem;
        }

        .card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255, 255, 255, 0.2);
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
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            transition: var(--transition);
        }

        .card-header .btn:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
        }

        .card-body {
            padding: 2rem;
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

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 280px;
                z-index: 1050;
                transition: var(--transition);
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .page-header h1 {
                font-size: 2rem;
            }

            .stats-card {
                margin-bottom: 1rem;
            }
        }
    </style>
</head>

<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 d-md-block sidebar">
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

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">สารวัตร</h1>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-user-shield fa-3x text-primary mb-3"></i>
                                <h5>บันทึกความประพฤติ</h5>
                                <p class="text-muted">บันทึกและติดตามความประพฤติของนักเรียน</p>
                                <button class="btn btn-primary">เข้าสู่ระบบ</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <div class="card">
                            <div class="card-body text-center">
                                <i class="fas fa-clipboard-list fa-3x text-success mb-3"></i>
                                <h5>รายงานความประพฤติ</h5>
                                <p class="text-muted">ดูรายงานและสถิติความประพฤติ</p>
                                <button class="btn btn-success">เข้าสู่ระบบ</button>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>