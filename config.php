<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'smartclass_db');

// LINE configuration
define('LINE_CHANNEL_ACCESS_TOKEN', 'your_line_channel_access_token');
define('LINE_CHANNEL_SECRET', 'your_line_channel_secret');

// Payment configuration (PromptPay)
define('PROMPTPAY_ID', '1102004107712'); // Phone number or ID for PromptPay

// Role permissions config file
define('ROLE_PERMISSIONS_FILE', __DIR__ . '/role_permissions.json');

// Connect to database
try {
    $pdo = new PDO("mysql:host=".DB_HOST.";dbname=".DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// User roles
$roles = [
    'admin' => 'ผู้ดูแลระบบ',
    'หัวหน้าห้อง' => 'หัวหน้าห้อง',
    'รองหัวหน้าห้อง' => 'รองหัวหน้าห้อง',
    'เลขานุการ' => 'เลขานุการ',
    'เหรัญญิก' => 'เหรัญญิก',
    'ฝ่ายการงาน' => 'ฝ่ายการงาน',
    'ฝ่ายการเรียน' => 'ฝ่ายการเรียน',
    'ฝ่ายกิจกรรม' => 'ฝ่ายกิจกรรม',
    'สารวัตร' => 'สารวัตร'
];

// Default permissions (if file not found)
$default_role_permissions = [
    'dashboard.php' => ['admin', 'หัวหน้าห้อง', 'รองหัวหน้าห้อง', 'เลขานุการ', 'เหรัญญิก', 'ฝ่ายการงาน', 'ฝ่ายการเรียน', 'ฝ่ายกิจกรรม', 'สารวัตร'],
    'payments.php' => ['admin', 'เหรัญญิก'],
    'admin.php' => ['admin'],
    'academic.php' => ['admin', 'ฝ่ายการเรียน'],
    'subject_exchange.php' => ['admin', 'ฝ่ายการเรียน'],
    'discipline.php' => ['admin', 'สารวัตร'],
    'profile.php' => ['admin', 'หัวหน้าห้อง', 'รองหัวหน้าห้อง', 'เลขานุการ', 'เหรัญญิก', 'ฝ่ายการงาน', 'ฝ่ายการเรียน', 'ฝ่ายกิจกรรม', 'สารวัตร'],
];

// Load role permissions from file or use default
function getRolePermissions() {
    $file = ROLE_PERMISSIONS_FILE;
    global $default_role_permissions;
    if (file_exists($file)) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (is_array($data)) return $data;
    }
    return $default_role_permissions;
}

// Save role permissions to file
function saveRolePermissions($permissions) {
    $file = ROLE_PERMISSIONS_FILE;
    file_put_contents($file, json_encode($permissions, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Check if a role can access a page
function canAccessPage($role, $page) {
    $permissions = getRolePermissions();
    return isset($permissions[$page]) && in_array($role, $permissions[$page]);
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check user role
function hasRole($required_role) {
    if (!isLoggedIn()) return false;
    return $_SESSION['user_role'] === $required_role || $_SESSION['user_role'] === 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

// Generate PromptPay QR Code
function generatePromptPayQR($amount, $ref = '') {
    $promptpay_id = PROMPTPAY_ID;
    // Use promptpay.io for QR code generation
    // $promptpay_id should be a phone number or citizen ID (no dashes or spaces)
    // $amount should be a float or string
    $qr_url = "https://promptpay.io/" . urlencode($promptpay_id) ;
    if ($amount > 0) {
        $qr_url .= "/" . urlencode($amount);
    }
    return $qr_url;
}

// Send LINE notification
function sendLineNotification($message, $user_line_id = null) {
    $access_token = LINE_CHANNEL_ACCESS_TOKEN;
    
    if ($user_line_id) {
        // Send to specific user
        $url = 'https://api.line.me/v2/bot/message/push';
        $data = [
            'to' => $user_line_id,
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];
    } else {
        // Broadcast to all users
        $url = 'https://api.line.me/v2/bot/message/broadcast';
        $data = [
            'messages' => [
                [
                    'type' => 'text',
                    'text' => $message
                ]
            ]
        ];
    }
    
    $headers = [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
}
?>