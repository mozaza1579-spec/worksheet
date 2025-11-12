<?php
session_start();

define('BASE_URL', 'http://worksheet.great-site.net/'); 
define('BACKEND_URL', BASE_URL . 'backendz.php'); 
define('PRODUCT_EDIT_URL', BASE_URL . 'product_edit.php'); 

define('DB_SERVER', 'sql304.infinityfree.com');
define('DB_USERNAME', 'if0_40399677'); 
define('DB_PASSWORD', 'pbYz6vB5vZinX'); 
define('DB_NAME', 'if0_40399677_work_shop'); 

define('TMWEASY_USER', 'YOUR_TMWEASY_USER'); 
define('TMWEASY_PASS', 'YOUR_TMWEASY_PASSWORD'); 
define('TMWEASY_CON_ID', 'YOUR_TMWEASY_CON_ID'); 
define('TMWEASY_API_URL', 'http://tmwallet.thaighost.net/api_pph.php'); 

$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}
function isBackendLoggedIn() { 
    return isAdmin();
}

function sendNotification($type, $message) {
    if ($type === 'user') {
        $_SESSION['purchase_msg'] = $message;
    } elseif ($type === 'admin') {
        $_SESSION['admin_message'] = $message;
    }
}

function timeUntilMidnight() {
    $now = new DateTime();
    $midnight = new DateTime('tomorrow midnight');
    $interval = $now->diff($midnight);
    
    return $interval->format('%h ชม. %i น. %s วินาที');
}


function purchaseProduct($conn, $user_id, $product_id, $price) {
    if (!isLoggedIn()) return false;
    
    $conn->begin_transaction();
    try {
        $update_user = $conn->prepare("UPDATE users SET wallet_point = wallet_point - ? WHERE user_id = ? AND wallet_point >= ?");
        $update_user->bind_param("dii", $price, $user_id, $price);
        $update_user->execute();

        if ($update_user->affected_rows === 0) {
            $conn->rollback();
            return 'not_enough_points';
        }
        
        $conn->query("UPDATE products SET stock = stock - 1 WHERE product_id = {$product_id}");

        $insert_purchase = $conn->prepare("INSERT INTO user_purchases (user_id, product_id, price_paid) VALUES (?, ?, ?)");
        $insert_purchase->bind_param("iid", $user_id, $product_id, $price);
        $insert_purchase->execute();

        $conn->commit();
        $_SESSION['wallet_point'] -= $price;
        sendNotification('user', "<p class='success-message'>✅ ซื้อสินค้าสำเร็จ! หัก Point: " . number_format($price, 2) . "</p>");
        return 'success';

    } catch (Exception $e) {
        $conn->rollback();
        return 'error';
    }
}

function purchaseCart($conn, $user_id, $total_price, $discount_amount, $cart_items) { 
    if (!isLoggedIn()) return false;
    
    $conn->begin_transaction();
    try {
        $final_price = $total_price - $discount_amount; 

        $update_user = $conn->prepare("UPDATE users SET wallet_point = wallet_point - ? WHERE user_id = ? AND wallet_point >= ?");
        $update_user->bind_param("dii", $final_price, $user_id, $final_price);
        $update_user->execute();

        if ($update_user->affected_rows === 0) {
            $conn->rollback();
            return 'not_enough_points';
        }
        
        foreach ($cart_items as $item) {
            $product_id = $item['product_id'];
            $price_paid = $item['price'] * $item['quantity']; 

            $conn->query("UPDATE products SET stock = stock - {$item['quantity']} WHERE product_id = {$product_id}");

            $insert_purchase = $conn->prepare("INSERT INTO user_purchases (user_id, product_id, price_paid) VALUES (?, ?, ?)");
            $insert_purchase->bind_param("iid", $user_id, $product_id, $price_paid);
            $insert_purchase->execute();
        }

        $conn->query("DELETE FROM cart_items WHERE user_id = {$user_id}");
        
        // FIX LOGIC: อัปเดต Coupon Used Count และตรวจสอบ Limit
        if (isset($_SESSION['applied_coupon']['code_id'])) {
            $coupon_id = $_SESSION['applied_coupon']['code_id'];
            
            // a. เพิ่ม used_count
            $update_coupon = $conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE coupon_id = ?");
            $update_coupon->bind_param("i", $coupon_id);
            $update_coupon->execute();
            
            // b. ตรวจสอบ Limit หลังใช้ เพื่อเปลี่ยนสถานะเป็น Inactive/ลบ
            $check_limit = $conn->query("SELECT coupon_code, usage_limit, used_count, is_giveaway FROM coupons WHERE coupon_id = {$coupon_id}")->fetch_assoc();
            
            // ใช้ used_count *หลัง* การอัปเดต (ซึ่งใน DB จะเป็นค่าปัจจุบัน)
            if ($check_limit && $check_limit['used_count'] >= $check_limit['usage_limit']) {
                $coupon_code = $check_limit['coupon_code'];
                
                if ($check_limit['is_giveaway'] == 1) {
                    // ถ้าเป็น Giveaway และครบ Limit ให้ลบโค้ดออกทันที
                    $conn->query("DELETE FROM coupons WHERE coupon_id = {$coupon_id}");
                } else {
                    // ถ้าเป็นโค้ดทั่วไปและครบ Limit ให้ตั้งสถานะเป็น Inactive
                    $conn->query("UPDATE coupons SET is_active = 0 WHERE coupon_id = {$coupon_id}");
                }
            }
        }
        
        $conn->commit();
        $_SESSION['wallet_point'] -= $final_price;
        $_SESSION['purchase_data'] = [
            'total_items' => count($cart_items),
            'final_price' => $final_price
        ];
        sendNotification('user', "<p class='success-message'>✅ สั่งซื้อสินค้า " . count($cart_items) . " รายการสำเร็จ! หัก Point: " . number_format($final_price, 2) . "</p>");
        return 'success';

    } catch (Exception $e) {
        $conn->rollback();
        error_log("Cart Purchase Error: " . $e->getMessage());
        return 'error';
    }
}
?>