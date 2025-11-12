<?php
require_once 'config.php';

$register_error = '';
$register_success = false;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_submit'])) {
    $username = $conn->real_escape_string($_POST['reg_username']);
    $email = $conn->real_escape_string($_POST['reg_email']);
    $password = $_POST['reg_password'];
    $confirm_password = $_POST['reg_confirm_password'];

    if ($password !== $confirm_password) {
        $register_error = "รหัสผ่านไม่ตรงกัน";
    }

    $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    if ($check_stmt) {
        $check_stmt->bind_param("s", $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $register_error = "Username นี้ถูกใช้แล้ว"; 
        }
        $check_stmt->close();
    } else {
        $register_error = "Error DB Preparation (Check): " . $conn->error;
    }


    if (empty($register_error)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $default_role = 'user';
        $default_point = 0.00; 

        $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password, user_role, wallet_point) VALUES (?, ?, ?, ?, ?)");

        if ($insert_stmt) {
            $insert_stmt->bind_param("ssssd", $username, $email, $hashed_password, $default_role, $default_point); 

            if ($insert_stmt->execute() === TRUE) {
                // ปรับปรุง: แจ้งเตือนสำเร็จ แล้ว Redirect ไปหน้าหลัก
                $_SESSION['reg_message'] = "ลงทะเบียนสำเร็จ! กรุณาเข้าสู่ระบบ";
                $_SESSION['open_login_modal'] = true; 
                // $register_success = true; // ไม่จำเป็นต้องใช้ตัวแปรนี้แล้ว
                
                // ใช้ alert ด้วย JS ก่อน แล้ว redirect
                echo '<script>alert("✅ ลงทะเบียนสำเร็จ! กรุณาล็อกอิน"); window.location.href = "' . BASE_URL . '";</script>';
                exit; // สำคัญมากเพื่อหยุดการทำงานหลังจากส่ง header หรือ script redirect
            } else {
                $register_error = "Error DB (Insert): " . $insert_stmt->error;
            }
            $insert_stmt->close();
        } else {
            $register_error = "Error DB Preparation (Insert): " . $conn->error;
        }
    }
}
?>

<form action="index.php" method="post">
    <?php if (!empty($register_error)): ?>
        <script>
            alert("⚠️ ข้อผิดพลาด: <?php echo $register_error; ?>");
            document.addEventListener('DOMContentLoaded', () => openModal('registerModal'));
        </script>
        <p class="error-message" style="margin-bottom: 20px;">พบข้อผิดพลาด กรุณาตรวจสอบข้อมูล</p>

    <?php endif; ?>

    <?php //if (!$register_success): // ลบเงื่อนไข $register_success ออก เพราะมีการ redirect ไปแล้ว ?>
    
        <label for="reg_username">ชื่อผู้ใช้:</label>
        <input type="text" id="reg_username" name="reg_username" required value="<?php echo htmlspecialchars($_POST['reg_username'] ?? ''); ?>">
        
        <label for="reg_email">อีเมล:</label>
        <input type="email" id="reg_email" name="reg_email" required value="<?php echo htmlspecialchars($_POST['reg_email'] ?? ''); ?>">

        <label for="reg_password">รหัสผ่าน:</label>
        <input type="password" id="reg_password" name="reg_password" required>
        
        <label for="reg_confirm_password">ยืนยันรหัสผ่าน:</label>
        <input type="password" id="reg_confirm_password" name="reg_confirm_password" required>

        <div style="display: flex; gap: 10px; margin-bottom: 10px;">
            <button type="submit" name="register_submit" style="flex-grow: 1;">ลงทะเบียน</button>
            <button type="button" onclick="window.location.href='<?php echo BASE_URL; ?>'" 
                    style="background-color: #6c757d; flex-grow: 1;">
                ย้อนกลับไปหน้าหลัก
            </button>
        </div>
        
        <div style="margin-top: 20px; text-align: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
            <p style="font-size: 0.9em;">
                มีบัญชีอยู่แล้ว? <a href="#" onclick="closeModal('registerModal'); openModal('loginModal'); return false;" style="color: var(--accent-color); text-decoration: none;">เข้าสู่ระบบ</a>
            </p>
        </div>
        
    <?php //endif; ?>
</form>