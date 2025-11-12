<?php
require_once 'config.php';

$error = '';

if (isBackendLoggedIn()) {
    header("location: " . BACKEND_URL . "?subpage=user");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT user_id, username, password, user_role, wallet_point FROM users WHERE username = '$username'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            
            if ($user['user_role'] === 'admin') {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['user_role'];
                $_SESSION['wallet_point'] = $user['wallet_point'];
                
                $_SESSION['backend_logged_in'] = true;

                header("location: " . BACKEND_URL . "?subpage=user");
                exit;
            } else {
                $error = "คุณไม่มีสิทธิ์เข้าถึงส่วน Admin (User Role: {$user['user_role']})";
            }
        } else {
            $error = "Username หรือ Password ไม่ถูกต้อง";
        }
    } else {
        $error = "Username หรือ Password ไม่ถูกต้อง";
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backend Login</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">

    <form action="backendz_login.php" method="post" style="max-width: 400px; background-color: var(--bg-content); padding: 30px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);">
        <h1 style="text-align: center; margin-bottom: 25px; color: red;">Admin Backend Login</h1>
        
        <?php if (!empty($error)): ?>
            <p class="error-message"><?php echo $error; ?></p>
        <?php endif; ?>
        
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>

        <button type="submit">เข้าสู่ระบบ Admin</button>
        
        <p style="text-align: center; margin-top: 15px;"><a href="<?php echo BASE_URL; ?>" style="color: var(--accent-color);">กลับหน้าหลัก</a></p>
    </form>

</body>
</html>