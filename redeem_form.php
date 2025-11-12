<?php
if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$redeem_message = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['redeem_submit'])) {
    $redeem_code = strtoupper(trim($conn->real_escape_string($_POST['redeem_code'])));

    $conn->begin_transaction();
    try {

        $stmt = $conn->prepare("SELECT code_id, point_amount, max_uses, used_count FROM redeem_codes WHERE redeem_code = ? AND is_active = TRUE AND used_count < max_uses");
        $stmt->bind_param("s", $redeem_code);
        $stmt->execute();
        $code_data = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        if (!$code_data) {
            throw new Exception("โค้ดไม่ถูกต้อง, ถูกใช้งานครบแล้ว, หรือหมดอายุ");
        }
        
        $check_used = $conn->query("SELECT COUNT(*) FROM user_redeems WHERE user_id = {$user_id} AND code_id = {$code_data['code_id']}")->fetch_row()[0];
        if ($check_used > 0) {
            throw new Exception("คุณเคยใช้โค้ดนี้ไปแล้ว");
        }
        
        $point_to_add = $code_data['point_amount'];
        $update_user = $conn->prepare("UPDATE users SET wallet_point = wallet_point + ? WHERE user_id = ?");
        $update_user->bind_param("di", $point_to_add, $user_id);
        $update_user->execute();
        
        $conn->query("INSERT INTO user_redeems (user_id, code_id) VALUES ({$user_id}, {$code_data['code_id']})");
        
        $conn->query("UPDATE redeem_codes SET used_count = used_count + 1 WHERE code_id = {$code_data['code_id']}");
        
        $conn->commit();
        $_SESSION['wallet_point'] += $point_to_add;
        $redeem_message = "<p class='success-message'>🎉 แลก Point สำเร็จ! ได้รับ " . number_format($point_to_add, 2) . " Point</p>";
        
    } catch (Exception $e) {
        $conn->rollback();
        $redeem_message = "<p class='error-message'>❌ ข้อผิดพลาด: " . $e->getMessage() . "</p>";
    }
}
?>

<div style="max-width: 500px; margin: 0 auto; padding-top: 20px;">
    <h1>💎 แลก Point ด้วย Redeem Code</h1>
    <?php echo $redeem_message; ?>

    <div style="background-color: var(--bg-content); padding: 30px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
        <form action="?page=redeem" method="post">
            <label for="redeem_code">กรอก Redeem Code:</label>
            <input type="text" id="redeem_code" name="redeem_code" required maxlength="30" placeholder="เช่น FREE100">
            <button type="submit" name="redeem_submit">แลก Point</button>
        </form>
    </div>
</div>