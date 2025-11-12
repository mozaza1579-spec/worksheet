<?php
require_once 'config.php';

if (!isBackendLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$action = $_GET['action'] ?? '';
$message = '';

if ($action === 'delete_user' && isset($_GET['user_id'])) {
    $redirect_to = BACKEND_URL . '?subpage=user';
    $user_id = (int)$_GET['user_id'];
    
    if ($user_id === ($_SESSION['user_id'] ?? 0)) {
        $message = "❌ ไม่สามารถลบบัญชี Admin ที่กำลังใช้งานอยู่ได้";
    } else {
        $conn->begin_transaction();
        try {
            $conn->query("DELETE FROM user_purchases WHERE user_id = {$user_id}");
            $conn->query("DELETE FROM transactions WHERE user_id = {$user_id}");
            $conn->query("DELETE FROM cart_items WHERE user_id = {$user_id}");
            $conn->query("DELETE FROM reviews WHERE user_id = {$user_id}");
            $conn->query("DELETE FROM user_redeems WHERE user_id = {$user_id}"); 
            $ticket_ids = $conn->query("SELECT ticket_id FROM support_tickets WHERE user_id = {$user_id}")->fetch_all(MYSQLI_ASSOC);
            foreach($ticket_ids as $ticket) {
                $conn->query("DELETE FROM support_messages WHERE ticket_id = {$ticket['ticket_id']}");
            }
            $conn->query("DELETE FROM support_tickets WHERE user_id = {$user_id}");
            
            $delete_user = $conn->query("DELETE FROM users WHERE user_id = {$user_id}");
            
            if ($delete_user && $conn->affected_rows > 0) {
                $conn->commit();
                $message = "✅ ลบ User ID: {$user_id} สำเร็จ!";
            } else {
                $conn->rollback();
                $message = "❌ ไม่พบ User ID: {$user_id} หรือเกิดข้อผิดพลาดในการลบ";
            }
        } catch (Exception $e) {
            $conn->rollback();
            $message = "❌ เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
        }
    }
    
    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;

} elseif ($action === 'change_role' && isset($_GET['user_id'], $_GET['new_role'])) {
    $redirect_to = BACKEND_URL . '?subpage=user';
    $user_id = (int)$_GET['user_id'];
    $new_role = ($_GET['new_role'] === 'admin') ? 'admin' : 'user';
    
    if ($user_id === ($_SESSION['user_id'] ?? 0) && $new_role === 'user') {
        $message = "❌ ไม่สามารถลดสิทธิ์ Admin ตัวเองได้!";
    } else {
        $update_role = $conn->prepare("UPDATE users SET user_role = ? WHERE user_id = ?");
        $update_role->bind_param("si", $new_role, $user_id);
        
        if ($update_role->execute() && $conn->affected_rows > 0) {
            $message = "✅ เปลี่ยนสิทธิ์ User ID: {$user_id} เป็น '{$new_role}' สำเร็จ!";
        } else {
            $message = "❌ ไม่พบ User ID: {$user_id} หรือสิทธิ์เดิมอยู่แล้ว";
        }
    }

    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;


} elseif ($action === 'approve_tx' && isset($_GET['tx_id'])) {
    $redirect_to = BACKEND_URL . '?subpage=user';
    $tx_id = (int)$_GET['tx_id'];

    $conn->begin_transaction();
    try {
        $tx_data = $conn->query("SELECT user_id, amount, status FROM transactions WHERE tx_id = {$tx_id} AND status = 'pending'")->fetch_assoc();
        
        if (!$tx_data) {
            throw new Exception("Transaction ID ไม่ถูกต้อง หรือได้รับการดำเนินการไปแล้ว");
        }
        
        $user_id = $tx_data['user_id'];
        $amount = $tx_data['amount'];

        $update_user = $conn->prepare("UPDATE users SET wallet_point = wallet_point + ? WHERE user_id = ?");
        $update_user->bind_param("di", $amount, $user_id);
        if (!$update_user->execute()) {
            throw new Exception("Failed to update user points.");
        }

        $update_tx = $conn->prepare("UPDATE transactions SET status = 'success' WHERE tx_id = ?");
        $update_tx->bind_param("i", $tx_id);
        if (!$update_tx->execute()) {
            throw new Exception("Failed to update transaction status.");
        }
        
        $conn->commit();
        $message = "✅ อนุมัติ TX #{$tx_id} สำเร็จ! เติม Point {$amount} ให้ User ID: {$user_id}";
    
        $_SESSION['purchase_msg'] = "<p class='success-message'>🎉 รายการเติมเงิน #{$tx_id} ได้รับการอนุมัติแล้ว! Point เข้าสู่บัญชี {$amount} Point</p>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "❌ อนุมัติล้มเหลว: " . $e->getMessage();
    }
    
    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;

} elseif ($action === 'reject_tx' && isset($_GET['tx_id'])) {
    $redirect_to = BACKEND_URL . '?subpage=user';
    $tx_id = (int)$_GET['tx_id'];
    
    $tx_user_id_res = $conn->query("SELECT user_id, amount FROM transactions WHERE tx_id = {$tx_id}")->fetch_assoc();

    $update_tx = $conn->prepare("UPDATE transactions SET status = 'failed' WHERE tx_id = ? AND status = 'pending'");
    $update_tx->bind_param("i", $tx_id);
    
    if ($update_tx->execute() && $conn->affected_rows > 0) {
        $message = "✅ ปฏิเสธ TX #{$tx_id} สำเร็จ!";
        
        if ($tx_user_id_res) {
             $_SESSION['purchase_msg'] = "<p class='error-message'>❌ รายการเติมเงิน #{$tx_id} ถูกปฏิเสธ (โปรดตรวจสอบหลักฐานการโอน)</p>";
        }
        
    } else {
        $message = "❌ ปฏิเสธ TX #{$tx_id} ล้มเหลว (อาจดำเนินการไปแล้ว)";
    }
    
    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;

} elseif ($action === 'update_product' && isset($_POST['product_id'])) {
    $redirect_to = BACKEND_URL . '?subpage=product';
    $product_id = (int)$_POST['product_id'];
    $name = $conn->real_escape_string($_POST['name']);
    $price = floatval($_POST['price']);
    $stock = (int)$_POST['stock'];
    $image_url = $conn->real_escape_string($_POST['image_url']);
    $description = $conn->real_escape_string($_POST['description']);
    $upload_dir = 'downloadable_works/'; 
    $file_path_db = $conn->real_escape_string($_POST['current_file_path']); 

    $conn->begin_transaction();
    try {
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK && $_FILES['file_upload']['size'] > 0) {
            
            if (!empty($_POST['current_file_path']) && file_exists($_POST['current_file_path'])) {
                @unlink($_POST['current_file_path']);
            }
            
            $file_tmp = $_FILES['file_upload']['tmp_name'];
            $file_ext = pathinfo($_FILES['file_upload']['name'], PATHINFO_EXTENSION);
            $new_file_name = uniqid('file_', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $file_path_db = $conn->real_escape_string($destination);
            } else {
                throw new Exception("ไม่สามารถย้ายไฟล์ใหม่ได้");
            }
        }

        $update_stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, image_url=?, description=?, file_path=? WHERE product_id=?");
        $update_stmt->bind_param("sdisssi", $name, $price, $stock, $image_url, $description, $file_path_db, $product_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Execute Update Failed: " . $update_stmt->error);
        }
        $update_stmt->close();
        
        $conn->commit();
        $message = "✅ อัปเดตสินค้า ID: {$product_id} สำเร็จ!";
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "❌ เกิดข้อผิดพลาดในการอัปเดต: " . $e->getMessage();
    }
    
    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;

} elseif ($action === 'delete_product' && isset($_GET['product_id'])) {
    $redirect_to = BACKEND_URL . '?subpage=product';
    $product_id = (int)$_GET['product_id'];

    $product_data = $conn->query("SELECT file_path FROM products WHERE product_id = {$product_id}")->fetch_assoc();
    
    $conn->begin_transaction();
    try {
        $conn->query("DELETE FROM user_purchases WHERE product_id = {$product_id}");
        $conn->query("DELETE FROM cart_items WHERE product_id = {$product_id}");
        $conn->query("DELETE FROM reviews WHERE product_id = {$product_id}");
        $delete_product = $conn->query("DELETE FROM products WHERE product_id = {$product_id}");
        
        if ($delete_product && $conn->affected_rows > 0) {
            $conn->commit();
            $message = "✅ ลบสินค้า ID: {$product_id} สำเร็จ!";
            
            if ($product_data && file_exists($product_data['file_path'])) {
                unlink($product_data['file_path']);
                $message .= " (ไฟล์ถูกลบแล้ว)";
            }
        } else {
            $conn->rollback();
            $message = "❌ ไม่พบสินค้า ID: {$product_id} หรือเกิดข้อผิดพลาดในการลบ";
        }
    } catch (Exception $e) {
        $conn->rollback();
        $message = "❌ เกิดข้อผิดพลาดในการลบข้อมูล: " . $e->getMessage();
    }
    
    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;

} elseif ($action === 'close_ticket' && isset($_GET['ticket_id']) && isBackendLoggedIn()) {
    $ticket_id = (int)$_GET['ticket_id'];
    
    $update_ticket = $conn->prepare("UPDATE support_tickets SET status = 'closed' WHERE ticket_id = ?");
    $update_ticket->bind_param("i", $ticket_id);
    
    $redirect_url = BASE_URL . '?page=support_tickets&ticket_id=' . $ticket_id;
    
    if ($update_ticket->execute() && $conn->affected_rows > 0) {
        $_SESSION['reg_message'] = "<p class='success-message'>✅ Ticket #{$ticket_id} ถูกปิดแล้ว</p>";
    } else {
        $_SESSION['reg_message'] = "<p class='error-message'>❌ ไม่พบ Ticket #{$ticket_id} หรือเกิดข้อผิดพลาดในการปิด</p>";
    }

    header("location: " . $redirect_url);
    exit;
    
} elseif ($action === 'create_coupon' && isset($_POST['discount_percent'], $_POST['usage_limit'])) { 
    $redirect_to = BACKEND_URL . '?subpage=coupon';
    $discount_percent = floatval($_POST['discount_percent']);
    $usage_limit = (int)$_POST['usage_limit']; 
    $input_code = trim($_POST['coupon_code'] ?? ''); 
    $user_id = $_SESSION['user_id'];
    $is_giveaway = (int)$_POST['is_giveaway'] ?? 0;
    
    if (empty($input_code)) {
        $coupon_code = 'CPN' . strtoupper(substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 7)); 
    } else {
        $coupon_code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $input_code)); 
    }
    
    $check_stmt = $conn->prepare("SELECT coupon_id FROM coupons WHERE coupon_code = ?");
    $check_stmt->bind_param("s", $coupon_code);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['admin_message'] = "<p class='error-message'>❌ ไม่สามารถสร้างคูปองได้: โค้ด {$coupon_code} ถูกใช้ไปแล้ว</p>";
        header("location: " . $redirect_to);
        exit;
    }
    $check_stmt->close();
    
    $insert_coupon = $conn->prepare("INSERT INTO coupons (coupon_code, discount_percent, usage_limit, is_giveaway, created_by) VALUES (?, ?, ?, ?, ?)");
    $insert_coupon->bind_param("sddii", $coupon_code, $discount_percent, $usage_limit, $is_giveaway, $user_id);
    
    if ($insert_coupon->execute()) {
        $message = "✅ สร้างคูปอง {$coupon_code} ลด {$discount_percent}% (จำกัด {$usage_limit} ครั้ง) สำเร็จ!";
    } else {
        $message = "❌ เกิดข้อผิดพลาดในการสร้างคูปอง: " . $conn->error;
    }

    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;
    
} elseif ($action === 'create_redeem_code' && isset($_POST['point_amount'], $_POST['max_uses'])) {
    $redirect_to = BACKEND_URL . '?subpage=redeem';
    $point_amount = floatval($_POST['point_amount']);
    $max_uses = (int)$_POST['max_uses'];
    $input_code = trim($_POST['redeem_code'] ?? '');
    $is_giveaway = (int)$_POST['is_giveaway'] ?? 0; // รับค่า is_giveaway (0 หรือ 1)
    
    if (empty($input_code)) {
        $redeem_code = 'RD' . strtoupper(substr(base_convert(sha1(uniqid(mt_rand())), 16, 36), 0, 8));
    } else {
        $redeem_code = strtoupper(preg_replace('/[^A-Z0-9]/', '', $input_code));
    }
    
    $check_stmt = $conn->prepare("SELECT code_id FROM redeem_codes WHERE redeem_code = ?");
    $check_stmt->bind_param("s", $redeem_code);
    $check_stmt->execute();
    if ($check_stmt->get_result()->num_rows > 0) {
        $_SESSION['admin_message'] = "<p class='error-message'>❌ โค้ด {$redeem_code} ถูกใช้ไปแล้ว</p>";
        header("location: " . $redirect_to);
        exit;
    }
    $check_stmt->close();
    
    // บันทึกโค้ดลง DB (เพิ่ม is_giveaway)
    $insert_code = $conn->prepare("INSERT INTO redeem_codes (redeem_code, point_amount, max_uses, is_giveaway) VALUES (?, ?, ?, ?)");
    $insert_code->bind_param("sdii", $redeem_code, $point_amount, $max_uses, $is_giveaway);
    
    if ($insert_code->execute()) {
        $message = "✅ สร้างโค้ดแลก Point {$redeem_code} ({$point_amount} Point, ใช้ได้ {$max_uses} ครั้ง) สำเร็จ!";
    } else {
        $message = "❌ เกิดข้อผิดพลาด: " . $conn->error;
    }

    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;
    
} elseif ($action === 'toggle_redeem_status' && isset($_GET['code_id'], $_GET['status'])) { 
    $redirect_to = BACKEND_URL . '?subpage=redeem';
    $code_id = (int)$_GET['code_id'];
    $new_status = (int)$_GET['status'];
    
    $update_stmt = $conn->prepare("UPDATE redeem_codes SET is_active = ? WHERE code_id = ?");
    $update_stmt->bind_param("ii", $code_id);
    
    if ($update_stmt->execute() && $conn->affected_rows > 0) {
        $status_text = $new_status ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $message = "✅ อัปเดตสถานะโค้ด ID: {$code_id} เป็น {$status_text} สำเร็จ!";
    } else {
        $message = "❌ อัปเดตสถานะโค้ดล้มเหลว";
    }

    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;

} elseif ($action === 'delete_review' && isset($_GET['review_id'])) { 
    $redirect_to = BACKEND_URL . '?subpage=review';
    $review_id = (int)$_GET['review_id'];
    
    $delete_review = $conn->query("DELETE FROM reviews WHERE review_id = {$review_id}");
    
    if ($delete_review && $conn->affected_rows > 0) {
        $message = "✅ ลบรีวิว ID: {$review_id} สำเร็จ!";
    } else {
        $message = "❌ ไม่พบรีวิว ID: {$review_id} หรือเกิดข้อผิดพลาดในการลบ";
    }
    
    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;

} elseif ($action === 'toggle_coupon_status' && isset($_GET['code'], $_GET['status'])) { 
    $redirect_to = BACKEND_URL . '?subpage=coupon';
    $code = $_GET['code'];
    $new_status = (int)$_GET['status'];
    
    $update_stmt = $conn->prepare("UPDATE coupons SET is_active = ? WHERE coupon_code = ?");
    $update_stmt->bind_param("is", $new_status, $code);
    
    if ($update_stmt->execute() && $conn->affected_rows > 0) {
        $status_text = $new_status ? 'เปิดใช้งาน' : 'ปิดใช้งาน';
        $message = "✅ อัปเดตสถานะคูปอง {$code} เป็น {$status_text} สำเร็จ!";
    } else {
        $message = "❌ อัปเดตสถานะคูปองล้มเหลว";
    }

    $_SESSION['admin_message'] = $message;
    header("location: " . $redirect_to);
    exit;
}


header("location: " . BACKEND_URL);
exit;
?>