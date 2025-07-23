<?php
$logFile = __DIR__ . '/upload_debug.log'; // Log file for debugging

function logMessage($message) {
    global $logFile;
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, FILE_APPEND);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = __DIR__ . '/uploads/slips/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            logMessage('Failed to create upload directory.');
            echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
            exit;
        }
    }

    $fileName = uniqid() . '_' . basename($_FILES['file']['name']);
    $filePath = $uploadDir . $fileName;

    // Validate file type
    $fileType = mime_content_type($_FILES['file']['tmp_name']);
    if (!in_array($fileType, ['image/jpeg', 'image/png', 'image/gif'])) {
        logMessage('Invalid file type: ' . $fileType);
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only images are allowed.']);
        exit;
    }

    if (move_uploaded_file($_FILES['file']['tmp_name'], $filePath)) {
        logMessage('File uploaded successfully: ' . $filePath);

        // Check if QR text is provided from client-side detection
        $qrText = isset($_POST['qr_text']) ? $_POST['qr_text'] : null;

        if ($qrText) {
            logMessage('QR code detected (client-side): ' . $qrText);
            echo json_encode(['success' => true, 'filePath' => 'uploads/slips/' . $fileName, 'qrText' => $qrText]);
        } else {
            logMessage('No QR code found in the uploaded image.');
            echo json_encode(['success' => false, 'message' => 'No QR code found in the uploaded image.']);
        }
    } else {
        logMessage('Failed to move uploaded file.');
        echo json_encode(['success' => false, 'message' => 'Failed to upload file.']);
    }
} else {
    logMessage('Invalid request.');
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
?>
