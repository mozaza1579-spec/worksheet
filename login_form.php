<?php
require_once 'config.php';

$login_error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['login_submit'])) {
    // ❌ OLD: $username = $conn->real_escape_string($_POST['username']);
    $username = $_POST['username'];
    $password = $_POST['password'];

    // ✅ FIXED: ใช้ Prepared Statements สำหรับ Login
    $stmt = $conn->prepare("SELECT user_id, username, password, user_role, wallet_point FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['user_role'];
            $_SESSION['wallet_point'] = $user['wallet_point'];
            
            // ✅ FIXED: ใช้ JS Redirect เพื่อให้แน่ใจว่า Modal ปิดและหน้าหลักโหลดสถานะใหม่ทันที
            echo '<script>window.location.href = "' . BASE_URL . '";</script>';
            exit;
        } else {
            $login_error = "รหัสผ่านไม่ถูกต้อง";
        }
    } else {
        $login_error = "ชื่อผู้ใช้ไม่พบในระบบ";
    }
    $stmt->close();
}
?>

<form action="index.php" method="post">
    <?php if (!empty($login_error)): ?>
        <script>
            alert("⚠️ ข้อผิดพลาด: <?php echo htmlspecialchars($login_error); ?>");
            document.addEventListener('DOMContentLoaded', () => openModal('loginModal'));
        </script>
        <p class="error-message"><?php echo htmlspecialchars($login_error); ?></p>
    <?php endif; ?>

    <label for="login_username">ชื่อผู้ใช้:</label>
    <input type="text" id="login_username" name="username" required>

    <label for="login_password">รหัสผ่าน:</label>
    <input type="password" id="login_password" name="password" required>

    <div style="display: flex; gap: 10px; margin-bottom: 10px;">
        <button type="submit" name="login_submit" style="flex-grow: 1;">ล็อกอิน</button> 
        <button type="button" onclick="window.location.href='<?php echo BASE_URL; ?>'" 
                style="background-color: #6c757d; flex-grow: 1;">
            ย้อนกลับไปหน้าหลัก
        </button>
    </div>
    
    <div style="margin-top: 20px; text-align: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
        <p style="font-size: 0.9em;">
            ยังไม่มีบัญชี? <a href="#" onclick="closeModal('loginModal'); openModal('registerModal'); return false;" style="color: var(--accent-color); text-decoration: none;">ลงทะเบียนที่นี่</a>
        </p>
    </div>
</form>