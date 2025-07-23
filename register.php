<?php
require_once 'config.php';

$success = null;
$error = null;

// Fetch roles except admin for registration
$register_roles = $roles;
unset($register_roles['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $role = $_POST['role'] ?? '';
    $line_user_id = trim($_POST['line_user_id'] ?? '');

    // Basic validation
    if (!$username || !$password || !$full_name || !$role) {
        $error = "กรุณากรอกข้อมูลให้ครบถ้วน";
    } else {
        // Check if username exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "ชื่อผู้ใช้นี้ถูกใช้แล้ว";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, role, line_user_id) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$username, $password_hash, $full_name, $role, $line_user_id ?: null])) {
                $success = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                header("Location: login.php?register=success");
                exit();
            } else {
                $error = "เกิดข้อผิดพลาดในการสมัครสมาชิก";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>สมัครสมาชิก - ระบบจัดการห้องเรียน</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .register-card { background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); padding: 2rem; }
        .logo { text-align: center; margin-bottom: 2rem; }
        .logo h2 { color: #667eea; font-weight: bold; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="register-card">
                <div class="logo">
                    <h2>สมัครสมาชิก</h2>
                    <p class="text-muted">Smart Class Management System</p>
                </div>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" name="username" id="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <input type="password" class="form-control" name="password" id="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">ชื่อ-นามสกุล</label>
                        <input type="text" class="form-control" name="full_name" id="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="role" class="form-label">ตำแหน่ง</label>
                        <select class="form-select" name="role" id="role" required>
                            <option value="">-- เลือกตำแหน่ง --</option>
                            <?php foreach ($register_roles as $role_key => $role_name): ?>
                                <option value="<?php echo $role_key; ?>" <?php echo (($_POST['role'] ?? '') === $role_key) ? 'selected' : ''; ?>>
                                    <?php echo $role_name; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="line_user_id" class="form-label">LINE User ID (ไม่บังคับ)</label>
                        <input type="text" class="form-control" name="line_user_id" id="line_user_id" value="<?php echo htmlspecialchars($_POST['line_user_id'] ?? ''); ?>">
                    </div>
                    <button type="submit" class="btn btn-primary w-100">สมัครสมาชิก</button>
                </form>
                <div class="mt-3 text-center">
                    <a href="login.php">เข้าสู่ระบบ</a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
