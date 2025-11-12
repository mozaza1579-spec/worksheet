<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$file_path = $_GET['file'] ?? '';
$product_id = (int)($_GET['product_id'] ?? 0);
$today = date("Y-m-d"); 

if (empty($file_path) || $product_id === 0) {
    die("Error: Missing file or product ID.");
}

$safe_file_path = urldecode($file_path);
$check_sql = $conn->prepare("SELECT COUNT(*) FROM user_purchases WHERE user_id = ? AND product_id = ?");
$check_sql->bind_param("ii", $user_id, $product_id);
$check_sql->execute();
$has_purchased = $check_sql->get_result()->fetch_row()[0] > 0;
$check_sql->close();

$absolute_path = realpath($safe_file_path); 
$file_name = basename($safe_file_path);

if (!$has_purchased || !file_exists($absolute_path)) {
    die("Error: Access denied or file not found on server ({$file_name}).");
}

$limit_stmt = $conn->prepare("SELECT download_count FROM download_limits WHERE user_id = ? AND product_id = ? AND download_date = ?");
$limit_stmt->bind_param("iis", $user_id, $product_id, $today);
$limit_stmt->execute();
$limit_result = $limit_stmt->get_result();
$daily_limit = $limit_result->fetch_assoc();
$download_count = $daily_limit['download_count'] ?? 0;
$limit_stmt->close();

const MAX_DAILY_DOWNLOADS_PER_FILE = 1; 

if ($download_count >= MAX_DAILY_DOWNLOADS_PER_FILE) {
    $time_left = timeUntilMidnight(); 
    die("Error: Download limit exceeded. คุณดาวน์โหลดไฟล์นี้ครบ 1 ครั้งวันนี้แล้ว (รีเซ็ตใน {$time_left}).");
}

$conn->begin_transaction();
try {
    $update_limit = $conn->prepare("INSERT INTO download_limits (user_id, product_id, download_date, download_count) VALUES (?, ?, ?, 1) ON DUPLICATE KEY UPDATE download_count = download_count + 1");
    $update_limit->bind_param("iis", $user_id, $product_id, $today);
    $update_limit->execute();
    $update_limit->close();
    $insert_history = $conn->prepare("INSERT INTO download_history (user_id, product_id) VALUES (?, ?)");
    $insert_history->bind_param("ii", $user_id, $product_id);
    $insert_history->execute();
    $insert_history->close();

    $conn->commit();
} catch (Exception $e) {
    $conn->rollback();
    error_log("Failed to record download history or update limit: " . $e->getMessage());
}


header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file_name . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($absolute_path));

readfile($absolute_path);

exit; 
?>