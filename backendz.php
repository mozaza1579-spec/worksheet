<?php

require_once 'config.php';

if (!isBackendLoggedIn()) { 
    header("location: " . BASE_URL . "backendz_login.php"); 
    exit;
}

$subpage = $_GET['subpage'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BackendZ</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="sidebar">
        <h2 style="color: red;">Admin Panel</h2>
        <ul style="margin-top: 30px;">
            <li><a href="<?php echo BASE_URL; ?>">กลับหน้าหลัก</a></li>
            <hr style="border-color: var(--border-color); margin: 15px 0;">

            <p style="font-size: 1em; color: var(--text-dark); margin: 15px 0 5px 0; padding: 0 0; font-weight: 600;">Admin Tools</p>
            
            <li><a href="?subpage=user" style="display: block; padding: 10px 0; font-weight: <?php echo $subpage === 'user' ? 'bold' : 'normal'; ?>; color: <?php echo $subpage === 'user' ? 'var(--accent-color)' : 'var(--text-dark)'; ?>; text-decoration: none;">จัดการ User (Point)</a></li>
            
            <li><a href="?subpage=product" style="display: block; padding: 10px 0; font-weight: <?php echo $subpage === 'product' ? 'bold' : 'normal'; ?>; color: <?php echo $subpage === 'product' ? 'var(--accent-color)' : 'var(--text-dark)'; ?>; text-decoration: none;">จัดการสินค้า/ไฟล์</a></li>
            
            <li><a href="?subpage=coupon" style="display: block; padding: 10px 0; font-weight: <?php echo $subpage === 'coupon' ? 'bold' : 'normal'; ?>; color: <?php echo $subpage === 'coupon' ? 'var(--accent-color)' : 'var(--text-dark)'; ?>; text-decoration: none;">จัดการคูปอง</a></li>
            
            <li><a href="?subpage=redeem" style="display: block; padding: 10px 0; font-weight: <?php echo $subpage === 'redeem' ? 'bold' : 'normal'; ?>; color: <?php echo $subpage === 'redeem' ? 'var(--accent-color)' : 'var(--text-dark)'; ?>; text-decoration: none;">จัดการโค้ด Redeem</a></li>
            
            <li><a href="?subpage=review" style="display: block; padding: 10px 0; font-weight: <?php echo $subpage === 'review' ? 'bold' : 'normal'; ?>; color: <?php echo $subpage === 'review' ? 'var(--accent-color)' : 'var(--text-dark)'; ?>; text-decoration: none;">จัดการรีวิว</a></li> 
            
            <hr style="border-color: var(--border-color); margin: 15px 0;">
            
            <p style="font-size: 1em; color: red; margin: 15px 0 5px 0; padding: 0 0; font-weight: 600;">Support</p>
            
            <!-- <li><a href="?subpage=tickets" style="display: block; padding: 10px 0; font-weight: <?php echo $subpage === 'tickets' ? 'bold' : 'normal'; ?>; color: red; text-decoration: none;">Support / Ticket</a></li> -->
            
            <!-- <li><a href="?subpage=admin_chat" style="display: block; padding: 10px 0; font-weight: <?php echo $subpage === 'admin_chat' ? 'bold' : 'normal'; ?>; color: red; text-decoration: none;">Live Chat</a></li> -->
        </ul>
        <a href="logout.php" style="position: absolute; bottom: 20px; color: #999; font-size: 0.9em;">ออกจากระบบ</a>
    </div>
    
    <div class="content">
        <div class="header" style="justify-content: flex-start; margin-bottom: 40px; border-bottom: none;">
            ยินดีต้อนรับ Admin, <?php echo htmlspecialchars($_SESSION['username'] ?? 'N/A'); ?> | Point: <span id="header-point" style="color: var(--accent-color); font-weight: bold;"><?php echo number_format($_SESSION['wallet_point'] ?? 0, 2); ?></span>
        </div>

        <?php
        // FIX: ใช้ Logic เดิมในการ Include ไฟล์
        if ($subpage === 'tickets') {
            include 'support_tickets.php'; 
        } elseif ($subpage === 'admin_chat') {
            include 'chat.php';
        } elseif ($subpage === 'coupon' || $subpage === 'redeem' || $subpage === 'review' || $subpage === 'user' || $subpage === 'product') { 
            // Include admin_dashboard.php สำหรับทุกเมนูหลักที่ไม่ใช่แชท/ตั๋ว
            include 'admin_dashboard.php'; 
        } else {
             include 'admin_dashboard.php'; 
        }
        ?>
    </div>
    
    <script>
        function updateUserData() {
            fetch('<?php echo BASE_URL; ?>get_user_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.logged_in) {
                        document.getElementById('header-point').textContent = data.wallet_point;
                    } 
                })
                .catch(error => console.error('Error fetching user data:', error));
        }

        updateUserData(); 
        setInterval(updateUserData, 5000);
    </script>
</body>
</html>