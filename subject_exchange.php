<?php
require_once 'config.php';
requireLogin();

if (!hasRole('ฝ่ายการเรียน') && !hasRole('admin') && !hasRole('หัวหน้าห้อง') && !hasRole('รองหัวหน้าห้อง') && !hasRole('เลขานุการ')) {
    header('Location: dashboard.php');
    exit();
}

// Create table if not exists (run once, or move to migration)
$pdo->exec("CREATE TABLE IF NOT EXISTS subject_exchanges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    exchange_date DATE NOT NULL,
    subject_from VARCHAR(100) NOT NULL,
    subject_to VARCHAR(100) NOT NULL,
    reason VARCHAR(255),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Add: Subject period mapping
$subject_periods = [
    0 => "คาบ 0 (07:30 - 08:20)",
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

// Handle add exchange
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
    } else {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// Fetch recent exchanges (this week)
$stmt = $pdo->prepare("SELECT se.*, u.full_name FROM subject_exchanges se JOIN users u ON se.created_by = u.id WHERE YEARWEEK(se.exchange_date, 1) = YEARWEEK(CURDATE(), 1) ORDER BY se.exchange_date DESC, se.id DESC");
$stmt->execute();
$exchanges = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>แจ้งสลับวิชา - ระบบจัดการห้องเรียน</title>
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
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
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
                    <h1 class="h2"> แจ้ง/ประกาศการสลับวิชาในสัปดาห์นี้</h1>
                </div>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <?php if (hasRole('ฝ่ายการเรียน') || hasRole('admin') || hasRole('หัวหน้าห้อง') || hasRole('รองหัวหน้าห้อง') || hasRole('เลขานุการ')): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5>เพิ่มการสลับวิชา</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="row">
                                    <div class="col-md-2 mb-3">
                                        <label for="exchange_date" class="form-label">วันที่</label>
                                        <input type="date" class="form-control" name="exchange_date" id="exchange_date"
                                            required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="periods" class="form-label">คาบ (เลือกได้หลายคาบ)</label>
                                        <select class="form-select" name="periods[]" id="periods" multiple required
                                            size="5">
                                            <?php foreach ($subject_periods as $key => $label): ?>
                                                <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">กด Ctrl หรือ Cmd เพื่อเลือกหลายคาบ</small>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="subject_from" class="form-label">วิชาเดิม</label>
                                        <input type="text" class="form-control" name="subject_from" id="subject_from"
                                            required>
                                    </div>
                                    <div class="col-md-2 mb-3">
                                        <label for="subject_to" class="form-label">เปลี่ยนเป็นวิชา</label>
                                        <input type="text" class="form-control" name="subject_to" id="subject_to" required>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label for="reason" class="form-label">เหตุผล (ถ้ามี)</label>
                                        <input type="text" class="form-control" name="reason" id="reason">
                                    </div>
                                </div>
                                <button type="submit" name="add_exchange" class="btn btn-primary"><i
                                        class="fas fa-plus"></i> เพิ่มการสลับวิชา</button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5>รายการสลับวิชาสัปดาห์นี้</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>วันที่</th>
                                        <th>คาบ</th>
                                        <th>วิชาเดิม</th>
                                        <th>เปลี่ยนเป็นวิชา</th>
                                        <th>เหตุผล</th>
                                        <th>โดย</th>
                                        <th>บันทึกเมื่อ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($exchanges) === 0): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">ไม่มีการสลับวิชาในสัปดาห์นี้</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($exchanges as $ex): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($ex['exchange_date']))); ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    if (!empty($ex['periods'])) {
                                                        $period_arr = explode(',', $ex['periods']);
                                                        $period_labels = [];
                                                        foreach ($period_arr as $p) {
                                                            $p = intval($p);
                                                            if (isset($subject_periods[$p]))
                                                                $period_labels[] = $subject_periods[$p];
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
                                                <td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($ex['created_at']))); ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <a href="dashboard.php" class="btn btn-secondary mt-3">กลับหน้าหลัก</a>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>