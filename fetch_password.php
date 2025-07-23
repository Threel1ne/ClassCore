<?php
require_once 'config.php';

$password_hash = null;
$error = null;
$fetched_text = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $row = $stmt->fetch();
    if ($row) {
        $password_hash = $row['password'];
    } else {
        $error = "ไม่พบชื่อผู้ใช้นี้";
    }
    $fetched_text = $_POST['fetched_text'] ?? '';
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ดูรหัสผ่าน (hash) - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4">ดูรหัสผ่าน (hash) ของผู้ใช้</h2>
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <form method="POST" class="mb-3">
        <div class="mb-3">
            <label for="username" class="form-label">ชื่อผู้ใช้</label>
            <input type="text" class="form-control" name="username" id="username" required>
        </div>
        <div class="mb-3">
            <label for="fetched_text" class="form-label">กรอกข้อความ</label>
            <textarea class="form-control" name="fetched_text" id="fetched_text" rows="3"><?php echo isset($_POST['fetched_text']) ? htmlspecialchars($_POST['fetched_text']) : ''; ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">ค้นหา</button>
    </form>
    <?php if ($password_hash): ?>
        <div class="alert alert-success">
            <strong>Hash:</strong>
            <code><?php echo htmlspecialchars($password_hash); ?></code>
        </div>
    <?php endif; ?>
    <?php if ($fetched_text !== null): ?>
        <div class="alert alert-info mt-3">
            <strong>ข้อความที่กรอก:</strong>
            <div><?php echo nl2br(htmlspecialchars($fetched_text)); ?></div>
        </div>
    <?php endif; ?>
    <a href="admin.php" class="btn btn-secondary">กลับ</a>
</div>
</body>
</html>
