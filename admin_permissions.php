<?php
require_once 'config.php';
requireLogin();

if ($_SESSION['user_role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

$all_pages = [
    'dashboard.php' => 'หน้าหลัก',
    'payments.php' => 'จัดการเงิน',
    'admin.php' => 'จัดการผู้ใช้',
    'academic.php' => 'ฝ่ายการเรียน',
    'subject_exchange.php' => 'สลับวิชา',
    'discipline.php' => 'สารวัตร',
    'profile.php' => 'โปรไฟล์',
];

$all_roles = array_keys($roles);
$role_permissions = getRolePermissions();
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_permissions = [];
    foreach ($all_pages as $page => $label) {
        $new_permissions[$page] = isset($_POST['roles'][$page]) ? $_POST['roles'][$page] : [];
    }
    saveRolePermissions($new_permissions);
    $role_permissions = $new_permissions;
    $success = "บันทึกการตั้งค่าสิทธิ์เรียบร้อยแล้ว";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ตั้งค่าสิทธิ์การเข้าถึง - ระบบจัดการห้องเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">ตั้งค่าสิทธิ์การเข้าถึงแต่ละหน้า</h2>
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    <form method="POST">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>หน้า</th>
                    <?php foreach ($all_roles as $role): ?>
                        <th><?php echo htmlspecialchars($roles[$role]); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_pages as $page => $label): ?>
                <tr>
                    <td><?php echo htmlspecialchars($label); ?></td>
                    <?php foreach ($all_roles as $role): ?>
                        <td class="text-center">
                            <input type="checkbox" name="roles[<?php echo $page; ?>][]" value="<?php echo $role; ?>"
                                <?php if (isset($role_permissions[$page]) && in_array($role, $role_permissions[$page])) echo 'checked'; ?>>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <button type="submit" class="btn btn-primary">บันทึกการตั้งค่า</button>
        <a href="dashboard.php" class="btn btn-secondary">กลับ</a>
    </form>
</div>
</body>
</html>
