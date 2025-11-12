<?php

if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = (int)$_GET['product_id'];

$stmt = $conn->prepare("SELECT product_id, name, price, description, image_url, stock FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    echo "<h1>404 Not Found</h1><p>ไม่พบสินค้าที่คุณต้องการ</p>";
    exit;
}

$is_out_of_stock = ($product['stock'] <= 0);
$has_purchased = false;
$check_purchase_stmt = $conn->prepare("SELECT COUNT(*) FROM user_purchases WHERE user_id = ? AND product_id = ?");
$check_purchase_stmt->bind_param("ii", $user_id, $product_id);
$check_purchase_stmt->execute();
$check_purchase_stmt->bind_result($purchase_count);
$check_purchase_stmt->fetch();
$check_purchase_stmt->close();

if ($purchase_count > 0) {
    $has_purchased = true;
}

if (isset($_GET['action']) && $_GET['action'] === 'add_to_cart') {
    $stmt = $conn->prepare("INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, 1) ON DUPLICATE KEY UPDATE quantity = quantity + 1");
    $stmt->bind_param("ii", $user_id, $product_id);
    
    if ($stmt->execute()) {
        $_SESSION['purchase_msg'] = "<p class='success-message'>✅ เพิ่ม " . htmlspecialchars($product['name']) . " ลงในตะกร้าแล้ว</p>";
    } else {
        $_SESSION['purchase_msg'] = "<p class='error-message'>❌ เกิดข้อผิดพลาดในการเพิ่มลงตะกร้า</p>";
    }
    
    header("location: " . BASE_URL . "?page=cart"); 
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_review'])) {
    if (!$has_purchased) {
        $_SESSION['purchase_msg'] = "<p class='error-message'>❌ คุณต้องซื้อสินค้านี้ก่อนจึงจะสามารถรีวิวได้</p>";
    } else {
        $rating = (int)$_POST['rating'];
        $review_text = $conn->real_escape_string($_POST['review_text']);
        
        if ($rating >= 1 && $rating <= 5) {
            $stmt_review = $conn->prepare("INSERT INTO reviews (user_id, product_id, rating, review_text) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?, review_text = ?");
            $stmt_review->bind_param("iisiss", $user_id, $product_id, $rating, $review_text, $rating, $review_text);
            
            if ($stmt_review->execute()) {
                $_SESSION['purchase_msg'] = "<p class='success-message'>✅ บันทึกรีวิว/คะแนนของคุณแล้ว</p>";
            } else {
                $_SESSION['purchase_msg'] = "<p class='error-message'>❌ ข้อผิดพลาด: " . $stmt_review->error . "</p>";
            }
        } else {
             $_SESSION['purchase_msg'] = "<p class='error-message'>❌ กรุณาให้คะแนนระหว่าง 1 ถึง 5</p>";
        }
    }
    
    header("location: " . BASE_URL . "?page=product&product_id={$product_id}");
    exit;
}

$avg_rating_result = $conn->query("SELECT AVG(rating) AS avg_rating, COUNT(review_id) AS total_reviews FROM reviews WHERE product_id = {$product_id}");
$review_stats = $avg_rating_result->fetch_assoc();
$avg_rating = round($review_stats['avg_rating'] ?? 0, 1);
$total_reviews = $review_stats['total_reviews'];

$user_review_stmt = $conn->prepare("SELECT rating, review_text FROM reviews WHERE user_id = ? AND product_id = ?");
$user_review_stmt->bind_param("ii", $user_id, $product_id);
$user_review_stmt->execute();
$user_review_data = $user_review_stmt->get_result()->fetch_assoc();
$user_review_stmt->close();

?>
<style>
    /* NEW CSS for Rating Stars */
    .rating-container {
        display: flex;
        flex-direction: row-reverse; /* จัดให้ดาว 5 อยู่ซ้ายสุด */
        justify-content: flex-end;
        gap: 2px;
        margin-bottom: 15px;
    }
    .rating-star {
        font-size: 2em;
        cursor: pointer;
        color: #ccc; /* สีดาวเมื่อยังไม่เลือก */
        transition: color 0.2s;
    }
    .rating-star:hover,
    .rating-container input:checked ~ .rating-star {
        color: gold;
    }
    .rating-container input {
        display: none; /* ซ่อน radio input */
    }
    /* ปรับปรุงการแสดงผลรีวิวให้สวยงามขึ้น */
    .review-item {
        border-bottom: 1px dashed var(--border-color);
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
    .review-username {
        font-weight: bold;
        color: var(--text-dark);
        display: inline-block; /* ปรับให้เป็นบรรทัดเดียวกันกับวันที่ */
    }
    .review-text {
        font-size: 0.9em;
        margin-top: 5px;
        line-height: 1.4;
    }
    .review-date {
        font-size: 0.7em;
        color: #666;
        margin-left: 10px; /* เพิ่มระยะห่างจากชื่อผู้ใช้ */
    }
    .review-stars {
        color: gold;
        float: right;
        font-size: 1.2em;
    }
</style>

<div style="display: flex; gap: 40px; max-width: 900px;">
    <div style="flex-shrink: 0;">
        <img src="<?php echo htmlspecialchars($product['image_url'] ?? 'placeholder.jpg'); ?>" 
             alt="<?php echo htmlspecialchars($product['name']); ?>" 
             class="product-detail-img" 
             style="border-radius: 8px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);"> 
    </div>

    <div style="flex-grow: 1;">
        <h1><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <p style="margin-bottom: 10px; font-weight: bold;">
            คะแนนเฉลี่ย: <span style="color: gold; font-size: 1.2em;"><?php echo str_repeat('★', floor($avg_rating)); ?></span> (<?php echo $avg_rating; ?> จาก <?php echo $total_reviews; ?> รีวิว)
        </p>

        <p style="font-size: 1.5em; color: var(--accent-color); margin-bottom: 20px; font-weight: bold;">
            ราคา: <?php echo number_format($product['price'], 2); ?> Point
        </p>

        <?php if ($is_out_of_stock): ?>
            <button disabled style="background-color: #dc3545; cursor: not-allowed;">สินค้าหมดชั่วคราว</button>
        <?php else: ?>
            <a href="?page=product&product_id=<?php echo $product['product_id']; ?>&action=add_to_cart" style="text-decoration: none;">
                <button style="padding: 10px 20px; font-size: 1.1em; background-color: #28a745;">
                    🛒 เพิ่มลงตะกร้า
                </button>
            </a>
        <?php endif; ?>
        
        <p style="margin-top: 15px; font-size: 0.9em; color: #666;">
            สินค้าคงเหลือ: <?php echo $product['stock'] ?? 'N/A'; ?>
        </p>
        
        <h3 style="margin-top: 30px; margin-bottom: 10px; color: var(--text-dark);">รายละเอียด:</h3>
        <div style="line-height: 1.6;">
            <?php echo nl2br(htmlspecialchars($product['description'] ?? 'ไม่มีรายละเอียดเพิ่มเติม')); ?>
        </div>
    </div>
</div>

<div style="max-width: 900px; margin-top: 40px;">
    <h2 style="margin-bottom: 20px; color: var(--accent-color);">⭐ รีวิวสินค้า</h2>
    
    <div style="display: flex; gap: 30px;">
        <div style="flex: 1; min-width: 300px; background-color: var(--bg-light); padding: 20px; border-radius: 8px;">
            <h3>เขียนรีวิวของคุณ</h3>
            
            <?php if ($has_purchased): ?>
                <form action="?page=product&product_id=<?php echo $product_id; ?>" method="post">
                    <input type="hidden" name="submit_review" value="1">
                    
                    <label>คะแนน (1-5):</label>
                    <div class="rating-container">
                        <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" id="star<?php echo $i; ?>" name="rating" value="<?php echo $i; ?>" 
                                <?php echo (isset($user_review_data['rating']) && $user_review_data['rating'] == $i) ? 'checked' : ''; ?> required />
                            <label for="star<?php echo $i; ?>" class="rating-star">★</label>
                        <?php endfor; ?>
                    </div>
                    
                    <label for="review_text">ข้อความรีวิว:</label>
                    <textarea name="review_text" rows="4" required><?php echo htmlspecialchars($user_review_data['review_text'] ?? ''); ?></textarea>
                    
                    <button type="submit" style="background-color: var(--accent-color);">ส่งรีวิว</button>
                </form>
            <?php else: ?>
                <p style="color: #dc3545; font-weight: bold;">
                    ⚠️ คุณต้องซื้อสินค้าชิ้นนี้ก่อนจึงจะสามารถเขียนรีวิวได้
                </p>
            <?php endif; ?>
        </div>
        
        <div style="flex: 2; min-width: 500px;">
            <?php
            $reviews_result = $conn->query("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.user_id WHERE r.product_id = {$product_id} ORDER BY r.created_at DESC");
            if ($reviews_result->num_rows > 0):
                while($review = $reviews_result->fetch_assoc()):
            ?>
            <div class="review-item">
                <p>
                    <span class="review-username"><?php echo htmlspecialchars($review['username']); ?></span>
                    <span class="review-stars"><?php echo str_repeat('★', $review['rating']); ?></span>
                </p>
                <p class="review-text">
                    <?php 
                        $review_text_display = htmlspecialchars($review['review_text']);
                        if (!empty($review_text_display)) {
                            echo nl2br($review_text_display);
                        } else {
                            echo 'ผู้ใช้ไม่ได้พิมพ์ข้อความรีวิว'; 
                        }
                    ?>
                </p>
                <span class="review-date">เมื่อ: <?php echo $review['created_at']; ?></span>
            </div>
            <?php endwhile; else: ?>
                <p>ยังไม่มีรีวิวสำหรับสินค้านี้</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
</script>