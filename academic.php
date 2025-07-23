<?php
// academic.php
require_once 'config.php';
requireLogin();

if (!hasRole('ฝ่ายการเรียน') && !hasRole('admin')) {
    header('Location: dashboard.php');
    exit();
}

// Add: Subject period mapping
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

// Handle add subject exchange
$success = null;
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_exchange'])) {
    $exchange_date = $_POST['exchange_date'] ?? '';
    $subject_from = trim($_POST['subject_from'] ?? '');
    $subject_to = trim($_POST['subject_to'] ?? '');
    $reason = trim($_POST['reason'] ?? '');
    $periods = isset($_POST['periods']) && is_array($_POST['periods']) ? $_POST['periods'] : [];

    if ($exchange_date && $subject_from && $subject_to && count($periods) > 0) {
        $periods_str = implode(',', array_map('intval', $periods));
        $stmt = $pdo->prepare("INSERT INTO subject_exchanges (exchange_date, subject_from, subject_to, reason, periods, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$exchange_date, $subject_from, $subject_to, $reason, $periods_str, $_SESSION['user_id']])) {
            $success = "บันทึกการสลับวิชาสำเร็จ";
        } else {
            $error = "เกิดข้อผิดพลาดในการบันทึก";
        }
    } else if (isset($_POST['add_exchange'])) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// Fetch subject exchanges for today only
$stmt = $pdo->prepare("SELECT se.*, u.full_name FROM subject_exchanges se JOIN users u ON se.created_by = u.id WHERE se.exchange_date = CURDATE() ORDER BY se.exchange_date DESC, se.id DESC");
$stmt->execute();
$subject_exchanges = $stmt->fetchAll();

// --- Homework Notes Section ---
$hw_success = null;
$hw_error = null;

// Create tables if not exists (run once, or move to migration)
$pdo->exec("CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    detail TEXT,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE IF NOT EXISTS note_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    note_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Handle add note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_note'])) {
    $title = trim($_POST['title'] ?? '');
    $detail = trim($_POST['detail'] ?? '');

    if ($title) {
        $stmt = $pdo->prepare("INSERT INTO notes (title, detail, created_by) VALUES (?, ?, ?)");
        if ($stmt->execute([$title, $detail, $_SESSION['user_id']])) {
            $note_id = $pdo->lastInsertId();
            // Handle file uploads
            if (!empty($_FILES['attachments']['name'][0])) {
                $upload_dir = __DIR__ . '/uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                foreach ($_FILES['attachments']['name'] as $idx => $filename) {
                    $tmp_name = $_FILES['attachments']['tmp_name'][$idx];
                    if ($tmp_name && is_uploaded_file($tmp_name)) {
                        $ext = pathinfo($filename, PATHINFO_EXTENSION);
                        $new_name = uniqid('note_') . '.' . $ext;
                        $dest = $upload_dir . $new_name;
                        if (move_uploaded_file($tmp_name, $dest)) {
                            $stmt2 = $pdo->prepare("INSERT INTO note_files (note_id, file_name, file_path) VALUES (?, ?, ?)");
                            $stmt2->execute([$note_id, $filename, 'uploads/' . $new_name]);
                        }
                    }
                }
            }
            $hw_success = "บันทึกโน้ต/การบ้านสำเร็จ";
        } else {
            $hw_error = "เกิดข้อผิดพลาดในการบันทึก";
        }
    } else {
        $hw_error = "กรุณากรอกหัวข้อ";
    }
}

// Fetch all notes (latest first)
$stmt = $pdo->prepare("SELECT n.*, u.full_name FROM notes n JOIN users u ON n.created_by = u.id ORDER BY n.created_at DESC");
$stmt->execute();
$notes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ฝ่ายการเรียน - ระบบจัดการห้องเรียน</title>
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            position: relative;
            overflow: hidden;
        }
        .sidebar::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
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
        .sidebar-header h5 { color: #fff; font-weight: 600; margin-bottom: 0.5rem; font-size: 1.2rem; }
        .sidebar-header .user-info { color: #ecf0f1; font-size: 0.9rem; opacity: 0.9; }
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
        .card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            border: 1px solid rgba(255,255,255,0.2);
            transition: var(--transition);
            overflow: hidden;
        }
        .card:hover { box-shadow: var(--card-hover-shadow); }
        .card-header {
            background: var(--primary-gradient);
            color: white;
            padding: 1.5rem;
            border-bottom: none;
            position: relative;
        }
        .card-header h5 { font-weight: 600; margin: 0; font-size: 1.2rem; }
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
        .card-body { padding: 2rem; }
        .btn {
            border-radius: 10px;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            transition: var(--transition);
            border: none;
        }
        .btn:hover { transform: translateY(-2px); }
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
        /* Subject Exchange Styles */
        .subject-exchange-table th, .subject-exchange-table td {
            vertical-align: middle;
            text-align: center;
        }
        .subject-exchange-table th {
            background: #e3f2fd;
            color: #1565c0;
        }
        .subject-exchange-table tr {
            transition: background 0.2s;
        }
        .subject-exchange-table tr:hover {
            background: #f1f8e9;
        }
        .subject-exchange-form .form-label {
            font-weight: 500;
        }
        .subject-exchange-form .form-select, .subject-exchange-form .form-control {
            border-radius: 10px;
        }
        .subject-exchange-form .btn-primary {
            background: linear-gradient(135deg, #42a5f5 0%, #7e57c2 100%);
            border: none;
        }
        .subject-exchange-form .btn-primary:hover {
            filter: brightness(1.1);
        }
        .subject-exchange-section .card-header {
            background: linear-gradient(135deg, #42a5f5 0%, #7e57c2 100%);
            color: #fff;
            font-weight: 600;
        }
        .subject-exchange-section .card {
            border-radius: 15px;
            box-shadow: 0 4px 16px rgba(66,165,245,0.09);
        }
        /* Homework Notes Styles */
        .note-files a {
            margin-right: 10px;
            position: relative;
        }
        .note-preview-img {
            display: none;
            position: absolute;
            z-index: 100;
            left: 50%;
            transform: translateX(-50%);
            bottom: 110%;
            max-width: 220px;
            max-height: 160px;
            border: 1px solid #ccc;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            padding: 2px;
        }
        .note-files a.preview-hover:hover .note-preview-img {
            display: block;
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

            .card {
                margin-bottom: 1rem;
                border-radius: 8px;
            }

            .form-group {
                margin-bottom: 1rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
            }

            .table-responsive {
                font-size: 0.85rem;
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
                            'academic.php' => ['icon' => 'fas fa-graduation-cap', 'label' => 'ฝ่ายการเรียน'],
                            'subject_exchange.php' => ['icon' => 'fas fa-exchange-alt', 'label' => 'สลับวิชา'],
                            'discipline.php' => ['icon' => 'fas fa-shield-alt', 'label' => 'สารวัตร'],
                            'admin.php' => ['icon' => 'fas fa-users-cog', 'label' => 'จัดการผู้ใช้'],
                            'profile.php' => ['icon' => 'fas fa-user', 'label' => 'โปรไฟล์'],
                        ];
                        foreach ($nav_pages as $page => $info) {
                            if (canAccessPage($_SESSION['user_role'], $page)) {
                                $active = (basename($_SERVER['PHP_SELF']) == $page) ? ' active' : '';
                                echo '<li class="nav-item">
                                    <a class="nav-link'.$active.'" href="'.$page.'">
                                        <i class="'.$info['icon'].'"></i> '.$info['label'].'
                                    </a>
                                </li>';
                            }
                        }
                        if ($_SESSION['user_role'] === 'admin') {
                            $active = (basename($_SERVER['PHP_SELF']) == 'admin_permissions.php') ? ' active' : '';
                            echo '<li class="nav-item">
                                <a class="nav-link'.$active.'" href="admin_permissions.php">
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
                    <h1 class="h2">ฝ่ายการเรียน</h1>
                </div>

                
                <!-- Subject Exchange Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        
                        

                        
                    </div>
                </div>

                <!-- Homework Notes Section -->
                <div class="row mb-4 homework-section">
                    <div class="col-12">
                        <?php if ($hw_success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($hw_success); ?></div>
                        <?php endif; ?>
                        <?php if ($hw_error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($hw_error); ?></div>
                        <?php endif; ?>

                        <div class="card mb-4">
                            <div class="card-header">
                                <h5><i class="fas fa-sticky-note"></i> เพิ่มโน้ต/การบ้าน</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-4 mb-3">
                                            <label for="title" class="form-label">หัวข้อ</label>
                                            <input type="text" class="form-control" id="title" name="title" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="attachments" class="form-label">แนบไฟล์/รูปภาพ (เลือกได้หลายไฟล์)</label>
                                            <input type="file" class="form-control" id="attachments" name="attachments[]" multiple>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label for="detail" class="form-label">รายละเอียด</label>
                                            <textarea class="form-control" id="detail" name="detail" rows="1"></textarea>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_note" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> เพิ่มโน้ต/การบ้าน
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-sticky-note"></i> รายการโน้ต/การบ้าน
                            </div>
                            <div class="card-body ">
                                <?php if (empty($notes)): ?>
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
                                                <?php foreach ($notes as $note): ?>
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

                <!-- ...existing code... -->
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
    </script>
</body>
</html>