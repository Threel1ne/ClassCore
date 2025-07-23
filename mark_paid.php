<?php
// mark_paid.php
require_once 'config.php';
requireLogin();

if (!hasRole('เหรัญญิก') && !hasRole('admin')) {
    http_response_code(403);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
$payment_id = $input['payment_id'];

$stmt = $pdo->prepare("UPDATE payments SET status = 'completed' WHERE id = ?");
$success = $stmt->execute([$payment_id]);

// Send notification
if ($success) {
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name, u.line_user_id 
        FROM payments p 
        JOIN users u ON p.user_id = u.id 
        WHERE p.id = ?
    ");
    $stmt->execute([$payment_id]);
    $payment = $stmt->fetch();
    
    if ($payment) {
        $message = "✅ การชำระเงินสำเร็จ\n";
        $message .= "รายการ: " . $payment['description'] . "\n";
        $message .= "จำนวน: " . number_format($payment['amount'], 2) . " บาท\n";
        $message .= "รหัสอ้างอิง: " . $payment['reference_code'];
        
        if ($payment['line_user_id']) {
            sendLineNotification($message, $payment['line_user_id']);
        }
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => $success]);
?>
