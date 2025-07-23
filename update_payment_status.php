<?php
// update_payment_status.php
require_once 'config.php';
requireLogin();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ถูกต้อง']);
    exit();
}

// Validate required fields
$paymentId = $input['paymentId'] ?? '';
$slipRefNbr = $input['slipRefNbr'] ?? '';
$amount = $input['amount'] ?? '';
$receiverName = $input['receiverName'] ?? '';
$status = $input['status'] ?? '';

if (empty($paymentId) || empty($status)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ข้อมูลไม่ครบถ้วน']);
    exit();
}

// Function to check and store used slip references
function checkAndStoreSlipReference($slipRefNbr) {
    if (empty($slipRefNbr)) return true; // Allow if no reference provided
    
    $usedRefsFile = __DIR__ . '/used_slip_references.json';
    $usedRefs = [];
    
    // Load existing used references
    if (file_exists($usedRefsFile)) {
        $jsonContent = file_get_contents($usedRefsFile);
        if ($jsonContent) {
            $usedRefs = json_decode($jsonContent, true) ?? [];
        }
    }
    
    // Clean up old references (older than 30 days)
    $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
    foreach ($usedRefs as $ref => $data) {
        if (isset($data['used_at']) && $data['used_at'] < $thirtyDaysAgo) {
            unset($usedRefs[$ref]);
        }
    }
    
    // Check if reference has been used before
    if (isset($usedRefs[$slipRefNbr])) {
        return false; // Reference already used
    }
    
    // Store the new reference with timestamp
    $usedRefs[$slipRefNbr] = [
        'used_at' => date('Y-m-d H:i:s'),
        'user_id' => $_SESSION['user_id'],
        'user_name' => $_SESSION['full_name']
    ];
    
    // Save back to file
    file_put_contents($usedRefsFile, json_encode($usedRefs, JSON_PRETTY_PRINT));
    
    return true; // Reference is new and now stored
}

// Check for slip reference reuse
if (!empty($slipRefNbr) && !checkAndStoreSlipReference($slipRefNbr)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'สลิปนี้ถูกใช้ไปแล้ว กรุณาใช้สลิปใหม่']);
    exit();
}

try {
    // Find payment by ID
    // Admin and treasurer can update any payment, others can only update their own
    if (hasRole('เหรัญญิก') || hasRole('admin')) {
        // Admin/treasurer can update any payment
        $stmt = $pdo->prepare("
            SELECT * FROM payments 
            WHERE id = ? AND status != 'completed'
            LIMIT 1
        ");
        $stmt->execute([$paymentId]);
    } else {
        // Regular users can only update their own payments
        $stmt = $pdo->prepare("
            SELECT * FROM payments 
            WHERE id = ? AND user_id = ? AND status != 'completed'
            LIMIT 1
        ");
        $stmt->execute([$paymentId, $_SESSION['user_id']]);
    }
    
    $payment = $stmt->fetch();
    
    if (!$payment) {
        echo json_encode(['success' => false, 'message' => 'ไม่พบรายการชำระเงินที่ตรงกัน']);
        exit();
    }
    
    // Update payment status using existing table structure
    $stmt = $pdo->prepare("
        UPDATE payments 
        SET status = ?
        WHERE id = ?
    ");
    
    $success = $stmt->execute([$status, $payment['id']]);
    
    if ($success) {
        // Send notification to user if they have LINE (same as mark_paid.php)
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name, u.line_user_id 
            FROM payments p 
            JOIN users u ON p.user_id = u.id 
            WHERE p.id = ?
        ");
        $stmt->execute([$payment['id']]);
        $updated_payment = $stmt->fetch();
        
        if ($updated_payment && $updated_payment['line_user_id']) {
            $message = "✅ การชำระเงินสำเร็จ\n";
            $message .= "รายการ: " . $updated_payment['description'] . "\n";
            $message .= "จำนวน: " . number_format($updated_payment['amount'], 2) . " บาท\n";
            $message .= "รหัสอ้างอิง: " . $updated_payment['reference_code'] . "\n";
            $message .= "ยืนยันโดย: " . $_SESSION['full_name'] . " (ตรวจสอบสลิป)";
            
            sendLineNotification($message, $updated_payment['line_user_id']);
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'อัปเดตสถานะการชำระเงินสำเร็จ',
            'payment_id' => $payment['id'],
            'slip_reference' => $slipRefNbr ? 'บันทึกสลิปแล้ว' : 'ไม่มีข้อมูลสลิป'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'ไม่สามารถอัปเดตสถานะได้']);
    }
    
} catch (PDOException $e) {
    error_log("Database error in update_payment_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในฐานข้อมูล']);
} catch (Exception $e) {
    error_log("General error in update_payment_status.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในระบบ']);
}
?>
