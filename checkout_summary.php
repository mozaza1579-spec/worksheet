<?php
if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$checkout_message = '';
$coupon_code = $_SESSION['applied_coupon']['code'] ?? '';
$discount_percent = $_SESSION['applied_coupon']['percent'] ?? 0;

$sql = "SELECT 
            c.product_id, 
            c.quantity, 
            p.name, 
            p.price,
            p.stock
        FROM cart_items c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = '$user_id'";
        
$cart_result = $conn->query($sql);
$cart_items = [];
$total_price_before_discount = 0;

if ($cart_result->num_rows === 0) {
    header("location: ?page=cart"); 
    exit;
}

while($item = $cart_result->fetch_assoc()) {
    if ($item['stock'] < $item['quantity']) {
        $_SESSION['purchase_msg'] = "<p class='error-message'>❌ สินค้า " . htmlspecialchars($item['name']) . " มีจำนวนไม่พอในคลัง</p>";
        header("location: ?page=cart");
        exit;
    }
    $item['sub_total'] = $item['price'] * $item['quantity'];
    $total_price_before_discount += $item['sub_total'];
    $cart_items[] = $item;
}

// ====== ตรวจสอบและใช้คูปอง ======
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['apply_coupon'])) {
    $input_code = trim($conn->real_escape_string($_POST['coupon_code']));
    
    $coupon_check = $conn->prepare("
        SELECT discount_percent, usage_limit, used_count 
        FROM coupons 
        WHERE coupon_code = ? AND is_active = TRUE
    ");
    $coupon_check->bind_param("s", $input_code);
    $coupon_check->execute();
    $coupon_data = $coupon_check->get_result()->fetch_assoc();
    
    if ($coupon_data) {
        if ($coupon_data['used_count'] < $coupon_data['usage_limit']) {
            $discount_percent = $coupon_data['discount_percent'];
            $_SESSION['applied_coupon'] = [
                'code' => $input_code,
                'percent' => $discount_percent
            ];
            $checkout_message = "<p class='success-message'>✅ ใช้คูปอง {$input_code} ส่วนลด {$discount_percent}% สำเร็จ!</p>";
            $coupon_code = $input_code;
        } else {
            unset($_SESSION['applied_coupon']);
            $checkout_message = "<p class='error-message'>❌ คูปองนี้ถูกใช้งานครบจำนวนแล้ว</p>";
            $discount_percent = 0;
        }
    } else {
        unset($_SESSION['applied_coupon']);
        $checkout_message = "<p class='error-message'>❌ โค้ดคูปองไม่ถูกต้องหรือไม่สามารถใช้งานได้</p>";
        $discount_percent = 0;
    }
} elseif (isset($_POST['remove_coupon'])) {
    unset($_SESSION['applied_coupon']);
    $checkout_message = "<p class='success-message'>✅ ลบคูปองแล้ว</p>";
    $coupon_code = '';
    $discount_percent = 0;
}

// ====== คำนวณราคา ======
$discount_amount = $total_price_before_discount * ($discount_percent / 100);
$final_price = $total_price_before_discount - $discount_amount;
$current_points = $_SESSION['wallet_point'];
$can_purchase = $current_points >= $final_price;

// ====== ยืนยันคำสั่งซื้อ ======
if (isset($_GET['action']) && $_GET['action'] === 'confirm_purchase') {
    if ($can_purchase) {
        $result = purchaseCart($conn, $user_id, $total_price_before_discount, $discount_amount, $cart_items);
        
        if ($result === 'success') {

            // ✅ อัปเดต used_count +1 ถ้ามีใช้คูปอง
            if (!empty($_SESSION['applied_coupon']['code'])) {
                $coupon_code = $_SESSION['applied_coupon']['code'];
                
                $update_coupon = $conn->prepare("
                    UPDATE coupons 
                    SET used_count = used_count + 1,
                        is_active = CASE 
                            WHEN used_count + 1 >= usage_limit THEN 0 
                            ELSE is_active 
                        END
                    WHERE coupon_code = ? 
                      AND used_count < usage_limit
                ");
                $update_coupon->bind_param("s", $coupon_code);
                $update_coupon->execute();
            }

            unset($_SESSION['applied_coupon']); // ล้างคูปองหลังใช้
            header("location: " . BASE_URL . "?page=purchase_history"); 
            exit;
        } elseif ($result === 'not_enough_points') {
            $checkout_message = "<p class='error-message'>❌ Point ไม่เพียงพอสำหรับการซื้อ ($final_price Point)</p>";
        } else {
            $checkout_message = "<p class='error-message'>❌ เกิดข้อผิดพลาดในการทำรายการ</p>";
        }
    } else {
        $checkout_message = "<p class='error-message'>❌ Point ไม่เพียงพอสำหรับการซื้อ</p>";
    }
}
?>

<h1>💰 สรุปคำสั่งซื้อ (Checkout)</h1>
<?php echo $checkout_message; ?>

<div style="display: flex; gap: 40px; margin-top: 20px;">
    
    <div style="flex: 3; padding: 20px; background-color: var(--bg-content); border: 1px solid var(--border-color); border-radius: 8px;">
        <h2 style="margin-bottom: 20px; color: var(--text-dark);">รายการสินค้า (<?php echo count($cart_items); ?> ชิ้น)</h2>
        <?php foreach ($cart_items as $item): ?>
            <div style="border-bottom: 1px dashed var(--border-color); padding: 10px 0; display: flex; justify-content: space-between;">
                <span><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></span>
                <span style="font-weight: bold;"><?php echo number_format($item['sub_total'], 2); ?> Point</span>
            </div>
        <?php endforeach; ?>
        
        <div style="margin-top: 20px;">
            <a href="?page=cart" style="color: var(--accent-color);">« กลับไปแก้ไขตะกร้า</a>
        </div>
    </div>

    <div style="flex: 2; padding: 20px; background-color: var(--bg-content); border: 1px solid var(--border-color); border-radius: 8px;">
        <h2 style="margin-bottom: 20px; color: var(--text-dark);">สรุปยอดชำระ</h2>
        
        <div style="font-size: 1.1em;">
            <p style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span>ยอดรวมสินค้า:</span>
                <span style="font-weight: bold;"><?php echo number_format($total_price_before_discount, 2); ?> Point</span>
            </p>
            
            <p style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #dc3545;">
                <span>ส่วนลดคูปอง (<?php echo $discount_percent; ?>%):</span>
                <span style="font-weight: bold;">- <?php echo number_format($discount_amount, 2); ?> Point</span>
            </p>
            
            <hr style="border-color: var(--border-color); margin: 15px 0;">
            
            <p style="display: flex; justify-content: space-between; font-size: 1.4em; font-weight: bold; color: var(--accent-color);">
                <span>ยอดชำระสุทธิ:</span>
                <span><?php echo number_format($final_price, 2); ?> Point</span>
            </p>
        </div>
        
        <h3 style="margin-top: 25px; margin-bottom: 10px;">🎫 คูปองส่วนลด</h3>
        <form action="?page=checkout" method="post" style="display: flex; gap: 10px;">
            <?php if (empty($coupon_code)): ?>
                <input type="hidden" name="apply_coupon" value="1">
                <input type="text" name="coupon_code" placeholder="กรอกโค้ดคูปอง" required style="flex-grow: 1; margin: 0;">
                <button type="submit" style="background-color: #28a745; padding: 10px;">ใช้</button>
            <?php else: ?>
                <input type="text" value="<?php echo htmlspecialchars($coupon_code); ?>" disabled style="flex-grow: 1; margin: 0; background-color: #f1f1f1;">
                <input type="hidden" name="remove_coupon" value="1">
                <button type="submit" style="background-color: #6c757d; padding: 10px;">ยกเลิก</button>
            <?php endif; ?>
        </form>

        <h3 style="margin-top: 25px; margin-bottom: 10px;">ยอด Point ปัจจุบัน: <span style="color: var(--accent-color);"><?php echo number_format($current_points, 2); ?></span> Point</h3>

        <?php if ($can_purchase): ?>
            <a href="?page=checkout&action=confirm_purchase" style="text-decoration: none;">
                <button style="width: 100%; padding: 15px; margin-top: 15px; background-color: var(--accent-color);">
                    ✅ ยืนยันคำสั่งซื้อและตัด Point
                </button>
            </a>
        <?php else: ?>
            <button disabled style="width: 100%; padding: 15px; margin-top: 15px; background-color: #dc3545; cursor: not-allowed;">
                ❌ Point ไม่เพียงพอ (ต้องการ <?php echo number_format($final_price, 2); ?> Point)
            </button>
            <p style="text-align: center; margin-top: 10px;"><a href="?page=topup" style="color: #dc3545;">เติม Point ที่นี่</a></p>
        <?php endif; ?>
    </div>
</div>
