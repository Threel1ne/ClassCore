<?php
$logFile = __DIR__ . '/upload_debug.log'; // Log file for debugging

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if QR text is provided
    if (!isset($_POST['qr_text']) || empty($_POST['qr_text'])) {
        logMessage('No QR text provided.');
        echo json_encode(['success' => false, 'message' => 'No QR text provided.']);
        exit;
    }

    $qrText = $_POST['qr_text'];
    $uploadDir = __DIR__ . '/uploads/';
    
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            logMessage('Failed to create upload directory.');
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit;
        }
    }

    // Save the file if provided
    $fileName = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileName = uniqid() . '_qr_result.png';
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
            logMessage('QR result file saved: ' . $filePath);
        } else {
            logMessage('Failed to save QR result file.');
            $fileName = null;
        }
    }

    // Log the QR code detection
    logMessage('QR code detected via client-side: ' . $qrText);

    // Return success response
    echo json_encode([
        'success' => true, 
        'message' => 'QR code processed successfully',
        'qrText' => $qrText,
        'filePath' => $fileName ? 'uploads/' . $fileName : null
    ]);

} else {
    logMessage('Invalid request method.');
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
