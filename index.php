<?php
require_once 'config.php';

// ... (โค้ดดึงข้อมูลสินค้าและ User เดิม) ...

$products = [];
$sql = "SELECT 
            p.product_id, 
            p.name, 
            p.price, 
            p.image_url,
            COALESCE(AVG(r.rating), 0) AS avg_rating,
            COUNT(r.review_id) AS total_reviews,
            -- นับจำนวนรายการที่ถูกซื้อในตาราง user_purchases
            (SELECT COUNT(up.product_id) FROM user_purchases up WHERE up.product_id = p.product_id) AS total_sold_qty
        FROM products p
        LEFT JOIN reviews r ON p.product_id = r.product_id
        GROUP BY p.product_id, p.name, p.price, p.image_url
        ORDER BY p.product_id ASC";
        
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    $sql_update = "SELECT wallet_point FROM users WHERE user_id = '$user_id'";
    $result_update = $conn->query($sql_update);
    if ($result_update && $result_update->num_rows > 0) {
        $wallet_data = $result_update->fetch_assoc();
        $_SESSION['wallet_point'] = $wallet_data['wallet_point'];
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worksheet Shop - สไลด์ที่ทุกคนเข้าใจ</title>
    <link rel="stylesheet" href="styles.css">
    <style> 
        .rating-stars {
            color: gold;
            font-size: 1.1em;
            display: inline-block;
        }
        .product-stats {
            font-size: 0.9em;
            color: #6c757d;
            margin-top: 5px;
            display: flex; 
            justify-content: space-between; 
            align-items: center;
            text-decoration: none !important; 
        }
        .product-info p, .product-info h3 {
            text-decoration: none !important;
        }
        .product-card {
            text-decoration: none !important;
        }
        /* NEW CSS: ปรับปรุงเมนู Redeem/Giveaway */
        .sidebar-group-title {
            font-size: 1em;
            color: var(--text-dark);
            margin: 15px 0 5px 0;
            padding: 0 10px;
            font-weight: 600;
        }
        .sidebar-group-box {
            /* NEW: Box สำหรับรวมเมนูย่อย */
            padding: 5px 0;
        }
        .sidebar li a.special-link {
            /* NEW: ปรับให้รายการเมนูชิดซ้ายและไม่เน้นมาก */
            padding: 8px 10px; 
            font-weight: 400 !important;
        }
        .sidebar hr {
            /* NEW: เอาเส้นออก */
            display: none; 
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h2>Worksheet Shop</h2>
        <ul>
            <li><a href="index.php" class="active">หน้าหลัก (สินค้า)</a></li>
            <?php if (isLoggedIn()): ?>
                
                <p class="sidebar-group-title" style="margin-top: 15px;">Wallet & Code</p>
                <div class="sidebar-group-box">
                    <li><a href="?page=topup" class="special-link">เติม Point (แจ้งโอน)</a></li> 
                    <li><a href="?page=redeem" class="special-link" style="color: #dc3545;">แลก Point (Redeem)</a></li> 
                    <li><a href="?page=giveaway" class="special-link" style="color: #008cba;">โค้ดส่วนลดพิเศษ</a></li>
                </div>
                
                <?php if (!isAdmin()): ?> 
                    <p class="sidebar-group-title">Support</p>
                    <div class="sidebar-group-box">
                        <li><a href="?page=support_tickets">Support / Ticket</a></li> 
                        <li><a href="?page=live_chat">Live Chat</a></li> 
                    </div>
                <?php endif; ?>
                
                <?php if (isAdmin()): ?>
                    <p class="sidebar-group-title" style="margin-top: 20px;">Admin Tools</p>
                    <div class="sidebar-group-box">
                        <li><a href="<?php echo BACKEND_URL; ?>?subpage=user">จัดการ User (Point)</a></li>
                        <li><a href="<?php echo BACKEND_URL; ?>?subpage=product">จัดการสินค้า/ไฟล์</a></li>
                        <li><a href="<?php echo BACKEND_URL; ?>?subpage=coupon">จัดการคูปอง</a></li> 
                        <li><a href="<?php echo BACKEND_URL; ?>?subpage=redeem">จัดการโค้ด Redeem</a></li> 
                        <li><a href="<?php echo BACKEND_URL; ?>?subpage=review">จัดการรีวิว</a></li> 
                        <p class="sidebar-group-title" style="margin-top: 15px; margin-bottom: 5px; color: #dc3545;">Support</p>
                        <li><a href="?page=support_tickets" class="special-link" style="color: #dc3545;">Support / Ticket</a></li> 
                        <li><a href="<?php echo BACKEND_URL; ?>?subpage=admin_chat" class="special-link" style="color: #dc3545;">Live Chat</a></li> 
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </ul>
    </div>

    <div class="content">
        <div class="header">
            <?php if (isLoggedIn()): ?>
                <div class="user-info">
                    <a href="?page=cart" style="margin-right: 15px; text-decoration: none; color: #dc3545; font-weight: 600;"> 🛒 ตะกร้าสินค้า
                    </a>
                    <a href="?page=purchase_history" style="margin-right: 15px; text-decoration: none; color: var(--accent-color); font-weight: 600;">
                        📦 สินค้าที่ฉันซื้อ
                    </a>
                    
                    ยินดีต้อนรับ, <span id="header-username"><?php echo htmlspecialchars($_SESSION['username']); ?></span> | Point: <span id="header-point" style="color: var(--accent-color); font-weight: bold;"><?php echo number_format($_SESSION['wallet_point'], 2); ?></span>
                    <a href="logout.php" class="logout-btn">ออกจากระบบ</a>
                </div>
            <?php else: ?>
                <a href="#" onclick="openModal('loginModal')" class="logout-btn" style="border: 1px solid var(--accent-color); color: var(--accent-color);">
                    เข้าสู่ระบบ
                </a>
            <?php endif; ?>
        </div>

        <?php
        $page = $_GET['page'] ?? 'home';
        $product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
        if (isset($_SESSION['purchase_msg'])) { 
             echo $_SESSION['purchase_msg'];
             unset($_SESSION['purchase_msg']);
        }

        if (isset($_SESSION['reg_message'])) {
             echo $_SESSION['reg_message'];
             unset($_SESSION['reg_message']);
        }
        
        if ($page === 'product' && $product_id > 0):
            include 'product_detail.php';
        
        elseif ($page === 'cart' && isLoggedIn()): 
            include 'shopping_cart.php';
            
        elseif ($page === 'checkout' && isLoggedIn()): 
            include 'checkout_summary.php';
            
        elseif ($page === 'redeem' && isLoggedIn()): 
            include 'redeem_form.php';
            
        elseif ($page === 'giveaway' && isLoggedIn()): 
            include 'code_giveaway.php';
        
        elseif ($page === 'topup' && isLoggedIn()):
            include 'topup.php';
        elseif ($page === 'purchase_history' && isLoggedIn()):
            include 'purchase_history.php';
            
        elseif ($page === 'support_tickets' && isLoggedIn()):
            include 'support_tickets.php';
            
        elseif ($page === 'live_chat' && isLoggedIn() && !isAdmin()): // NEW: Live Chat สำหรับ User เท่านั้น
            include 'chat.php';
            
        elseif ($page === 'home'):
            ?>
            <h1>🛒 สินค้าของเรา</h1>
            <div class="product-grid">
                <?php foreach ($products as $product): ?>
                    <a href="?page=product&product_id=<?php echo $product['product_id']; ?>" class="product-card">
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'placeholder.jpg'); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <div class="product-info">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                            
                            <?php 
                                $avg_rating = round($product['avg_rating'], 1);
                                $star_count = floor($avg_rating);
                                $total_reviews = (int)$product['total_reviews'];
                                $total_sold = (int)$product['total_sold_qty'];
                            ?>

                            <div class="product-stats">
                                <span style="display: flex; align-items: center; text-decoration: none !important;">
                                    <span class="rating-stars"><?php echo str_repeat('★', $star_count); ?></span>
                                    <span style="font-weight: bold; margin-left: 3px;"><?php echo number_format($avg_rating, 1); ?></span>
                                    (<?php echo number_format($total_reviews); ?> รีวิว)
                                </span>
                                
                                <span style="color: #28a745; font-weight: bold; text-decoration: none !important;">
                                    ขายได้ <?php echo number_format($total_sold); ?> ชิ้น
                                </span>
                            </div>
                            
                            <p style="margin-top: 5px; color: var(--accent-color); font-weight: bold; font-size: 1.1em; text-decoration: none;">
                                ฿<?php echo number_format($product['price'], 0); ?>
                            </p>
                        </div>
                    </a>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                    <p>ยังไม่มีสินค้าในระบบ</p>
                <?php endif; ?>
            </div>
            <?php
        else:
            echo "<h1>404 Not Found</h1>";
        endif;
        ?>

        <footer style="margin-top: 50px; text-align: center; font-size: 0.8em; color: #888; padding: 20px 0;">
            <p>© 2025 Worksheet Shop. สไลด์ที่ทุกคนเข้าใจ</p>
        </footer>
    </div>
    
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('loginModal')">&times;</span>
            <h2 style="margin-bottom: 20px;">เข้าสู่ระบบ</h2>
            <?php include 'login_form.php'; ?>
        </div>
    </div>
    
    <div id="registerModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('registerModal')">&times;</span>
            <h2 style="margin-bottom: 20px;">ลงทะเบียน</h2>
            <?php include 'register_form.php'; ?>
        </div>
    </div>

    <div id="successModal" class="modal">
        <div class="modal-content" style="text-align: center;">
            <span class="close-btn" onclick="closeModal('successModal')">&times;</span>
            <h2 style="color: #28a745;">✅ ซื้อสินค้าสำเร็จ!</h2>
            <p style="margin-top: 20px;" id="success-message-content">คุณได้ซื้อสินค้าแล้ว</p>
            <button onclick="closeModal('successModal')" style="margin-top: 20px;">ปิด</button>
        </div>
    </div>
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function updateUserData() {
            if (!document.getElementById('header-point')) {
                return;
            }

            fetch('<?php echo BASE_URL; ?>get_user_data.php')
                .then(response => response.json())
                .then(data => {
                    if (data.logged_in) {
                        document.getElementById('header-point').textContent = data.wallet_point;
                        document.getElementById('header-username').textContent = data.username;
                    } 
                })
                .catch(error => console.error('Error fetching user data:', error));
        }
        function showSuccessModal(totalItems, finalPrice) { 
            document.getElementById('success-message-content').innerHTML = 
                `คุณได้ซื้อสินค้า ${totalItems} รายการ แล้ว<br>เสีย Point ไปจำนวน ${finalPrice} บาท`;
            openModal('successModal');
            updateUserData(); 
        }

        <?php if (isset($_SESSION['purchase_data'])): ?>
            showSuccessModal(
                "<?php echo htmlspecialchars($_SESSION['purchase_data']['total_items']); ?>",
                "<?php echo number_format($_SESSION['purchase_data']['final_price'], 2); ?>"
            );
            <?php unset($_SESSION['purchase_data']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['open_login_modal']) && $_SESSION['open_login_modal'] === true): ?>
            document.addEventListener('DOMContentLoaded', () => {
                openModal('loginModal');
            });
            <?php 
            unset($_SESSION['open_login_modal']); 
            unset($_SESSION['reg_message']);
            ?>
        <?php endif; ?>

        if (document.getElementById('header-point')) {
            setInterval(updateUserData, 5000);
        }
    </script>
</body>
</html>
