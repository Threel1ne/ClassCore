<?php
// payments.php
require_once 'config.php';
requireLogin();

if (!hasRole('เหรัญญิก') && !hasRole('admin') && !hasRole('หัวหน้าห้อง') && !hasRole('รองหัวหน้าห้อง') && !hasRole('เลขานุการ')) {
    header('Location: dashboard.php');
    exit();
}

// Handle payment creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_payment'])) {
    $user_id = $_POST['user_id'];
    $amount = $_POST['amount'];
    $description = $_POST['description'];
    $payment_type = $_POST['payment_type'];

    if ($user_id === 'all') {
        // Get all students
        $stmt = $pdo->prepare("SELECT id, full_name, line_user_id FROM users WHERE role NOT IN ('admin')");
        $stmt->execute();
        $students = $stmt->fetchAll();

        foreach ($students as $student) {
            $reference_code = 'PAY' . date('YmdHis') . rand(1000, 9999);
            $qr_code_url = generatePromptPayQR($amount, $reference_code);

            $stmt = $pdo->prepare("
                INSERT INTO payments (user_id, amount, description, payment_type, reference_code, qr_code_url) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$student['id'], $amount, $description, $payment_type, $reference_code, $qr_code_url]);

            if ($student['line_user_id']) {
                $message = "💰 แจ้งเตือนการชำระเงิน\n";
                $message .= "รายการ: $description\n";
                $message .= "จำนวน: " . number_format($amount, 2) . " บาท\n";
                $message .= "รหัสอ้างอิง: $reference_code";
                sendLineNotification($message, $student['line_user_id']);
            }
        }
        $success = "สร้างรายการชำระเงินสำหรับนักเรียนทั้งหมดเรียบร้อยแล้ว";
    } else {
        $reference_code = 'PAY' . date('YmdHis') . rand(1000, 9999);
        $qr_code_url = generatePromptPayQR($amount, $reference_code);

        $stmt = $pdo->prepare("
            INSERT INTO payments (user_id, amount, description, payment_type, reference_code, qr_code_url) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        if ($stmt->execute([$user_id, $amount, $description, $payment_type, $reference_code, $qr_code_url])) {
            $stmt = $pdo->prepare("SELECT full_name, line_user_id FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            $message = "💰 แจ้งเตือนการชำระเงิน\n";
            $message .= "รายการ: " . $description . "\n";
            $message .= "จำนวน: " . number_format($amount, 2) . " บาท\n";
            $message .= "รหัสอ้างอิง: " . $reference_code;

            if ($user['line_user_id']) {
                sendLineNotification($message, $user['line_user_id']);
            }
            $success = "สร้างรายการชำระเงินเรียบร้อยแล้ว";
        } else {
            $error = "เกิดข้อผิดพลาดในการสร้างรายการ";
        }
    }

}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_payment_id'])) {
    $payment_id = $_POST['delete_payment_id'];
    $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
    if ($stmt->execute([$payment_id])) {
        $success = "ลบรายการชำระเงินเรียบร้อยแล้ว";
    } else {
        $error = "ไม่สามารถลบรายการได้";
    }
}


// Get all users for dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role != 'admin' ORDER BY id");
$stmt->execute();
$users = $stmt->fetchAll();

// Get all payments
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name 
    FROM payments p 
    JOIN users u ON p.user_id = u.id 
    ORDER BY p.created_at DESC
");
$stmt->execute();
$payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>จัดการเงิน - ระบบจัดการห้องเรียน</title>
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

            .page-header h1 {
                font-size: 1.5rem;
            }

            .stats-card {
                margin-bottom: 1rem;
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
        }

        /* Large Mobile Phones (375px - 480px) */
        @media (min-width: 375px) and (max-width: 480px) {
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
                margin-bottom: 1.5rem;
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
        }

        /* Landscape orientation for mobile */
        @media (max-width: 767px) and (orientation: landscape) {
            .mobile-navbar {
                padding: 0.75rem 1rem;
            }

            .main-content {
                padding-top: 4rem;
            }
        }

        .qr-code {
            max-width: 200px;
            height: auto;
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
                <div class="pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">จัดการเงิน</h1>
                </div>

                <?php if (isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <!-- Create Payment Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>สร้างรายการชำระเงิน</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="user_id" class="form-label">เลือกผู้ใช้</label>
                                        <select class="form-select" name="user_id" required>
                                            <option value="">-- เลือกผู้ใช้ --</option>
                                            <option value="all">-- นักเรียนทั้งหมด --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="amount" class="form-label">จำนวนเงิน (บาท)</label>
                                        <input type="number" class="form-control" name="amount" step="0.01" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_type" class="form-label">ประเภทการชำระ</label>
                                        <select class="form-select" name="payment_type" required>
                                            <option value="">-- เลือกประเภท --</option>
                                            <option value="class_fee">ค่าธรรมเนียมห้องเรียน</option>
                                            <option value="activity_fee">ค่าใช้จ่ายกิจกรรม</option>
                                            <option value="material_fee">ค่าอุปกรณ์การเรียน</option>
                                            <option value="other">อื่นๆ</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="description" class="form-label">รายละเอียด</label>
                                        <input type="text" class="form-control" name="description">
                                    </div>
                                </div>
                            </div>
                            <button type="submit" name="create_payment" class="btn btn-primary">
                                <i class="fas fa-plus"></i> สร้างรายการชำระเงิน
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Payments List -->
                <div class="card">
                    <div class="card-header">
                        <h5>รายการชำระเงินทั้งหมด</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>ผู้ใช้</th>
                                        <th>รายการ</th>
                                        <th>จำนวนเงิน</th>
                                        <th>สถานะ</th>
                                        <th>QR Code</th>
                                        <th>จัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo date('d/m/Y H:i', strtotime($payment['created_at'])); ?></td>
                                            <td><?php echo htmlspecialchars($payment['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                            <td><?php echo number_format($payment['amount'], 2); ?> บาท</td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo $payment['status'] === 'completed' ? 'success' : 'warning'; ?>">
                                                    <?php echo $payment['status'] === 'completed' ? 'สำเร็จ' : 'รอดำเนินการ'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] !== 'completed'): ?>
                                                    <button class="btn btn-sm btn-outline-primary"
                                                        onclick="showQR('<?php echo $payment['qr_code_url']; ?>', '<?php echo $payment['reference_code']; ?>')">
                                                        <i class="fas fa-qrcode"></i> แสดง QR
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($payment['status'] !== 'completed'): ?>
                                                    <button class="btn btn-sm btn-success"
                                                        onclick="markPaid(<?php echo $payment['id']; ?>)">
                                                        <i class="fas fa-check"></i> ชำระแล้ว
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form method="POST" onsubmit="return confirm('ยืนยันการลบรายการนี้?');">
                                                    <input type="hidden" name="delete_payment_id"
                                                        value="<?php echo $payment['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> ลบ
                                                    </button>
                                                </form>
                                            </td>
                                            <?php if (!empty($payment['slip_image_url'])): ?>
                                                <td>
                                                    <a href="<?php echo $payment['slip_image_url']; ?>" target="_blank"
                                                        class="btn btn-sm btn-outline-secondary">
                                                        ดูสลิป
                                                    </a>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- QR Code Modal -->
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
        });

        function showQR(qrUrl, refCode) {
            document.getElementById('qrImage').src = qrUrl;
            document.getElementById('refCode').textContent = 'รหัสอ้างอิง: ' + refCode;
            new bootstrap.Modal(document.getElementById('qrModal')).show();
        }

        function markPaid(paymentId) {
            fetch('mark_paid.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ payment_id: paymentId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด');
                    }
                });
        }

    </script>
</body>

</html>