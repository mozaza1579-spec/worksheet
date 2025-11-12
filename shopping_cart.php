<?php
if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart_message = '';

if (isset($_GET['action'])) {
    $product_id = (int)($_GET['product_id'] ?? 0);
    $redirect_to = BASE_URL . '?page=cart';

    if ($_GET['action'] === 'remove' && $product_id > 0) {
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        $stmt->execute();
        $cart_message = "<p class='success-message'>✅ ลบสินค้าออกจากตะกร้าแล้ว</p>";
    } elseif ($_GET['action'] === 'update_qty' && $product_id > 0 && $_SERVER["REQUEST_METHOD"] == "POST") {
        $qty = (int)($_POST['quantity'] ?? 1);
        $qty = max(1, $qty); 
        $stmt = $conn->prepare("UPDATE cart_items SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $qty, $user_id, $product_id);
        $stmt->execute();
        $cart_message = "<p class='success-message'>✅ อัปเดตจำนวนสินค้าแล้ว</p>";
    }
    
    if (isset($_GET['action'])) {
        $_SESSION['purchase_msg'] = $cart_message; 
        header("location: " . $redirect_to);
        exit;
    }
}

$sql = "SELECT 
            c.product_id, 
            c.quantity, 
            p.name, 
            p.price, 
            p.image_url 
        FROM cart_items c
        JOIN products p ON c.product_id = p.product_id
        WHERE c.user_id = '$user_id'";
        
$cart_result = $conn->query($sql);
$total_cart_price = 0;
?>

<h1>🛒 ตะกร้าสินค้าของคุณ</h1>

<?php if ($cart_result->num_rows > 0): ?>
    <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
        <tr style="background-color: var(--bg-light);">
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left; width: 40%;">สินค้า</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: right; width: 15%;">ราคา/หน่วย (Point)</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 15%;">จำนวน</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: right; width: 15%;">ราคารวม</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 15%;">จัดการ</th>
        </tr>
        <?php while($item = $cart_result->fetch_assoc()): 
            $sub_total = $item['price'] * $item['quantity'];
            $total_cart_price += $sub_total;
        ?>
        <tr>
            <td style="padding: 10px; border: 1px solid var(--border-color);">
                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'placeholder.jpg'); ?>" style="height: 50px; width: 50px; object-fit: cover; border-radius: 4px; margin-right: 10px; vertical-align: middle;">
                <a href="?page=product&product_id=<?php echo $item['product_id']; ?>" style="color: var(--text-dark); text-decoration: none;">
                    <?php echo htmlspecialchars($item['name']); ?>
                </a>
            </td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right;"><?php echo number_format($item['price'], 2); ?></td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                <form action="?page=cart&action=update_qty&product_id=<?php echo $item['product_id']; ?>" method="post" style="display: inline-block;">
                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" style="width: 60px; padding: 5px; margin: 0; display: inline-block;">
                    <button type="submit" style="padding: 5px 10px; font-size: 0.8em;">อัปเดต</button>
                </form>
            </td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right; font-weight: bold; color: var(--accent-color);"><?php echo number_format($sub_total, 2); ?></td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                <a href="?page=cart&action=remove&product_id=<?php echo $item['product_id']; ?>" style="color: red;">ลบ</a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    
    <div style="text-align: right; margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 4px;">
        <h3 style="margin-bottom: 10px;">ยอดรวมก่อนส่วนลด: <span style="color: var(--accent-color);"><?php echo number_format($total_cart_price, 2); ?> Point</span></h3>
        <a href="?page=checkout" style="text-decoration: none;">
            <button style="padding: 15px 30px; font-size: 1.2em; background-color: #dc3545;">
                ดำเนินการชำระเงิน (Checkout)
            </button>
        </a>
    </div>

<?php else: ?>
    <p style="margin-top: 20px;">ตะกร้าสินค้าว่างเปล่า</p>
    <a href="index.php" style="color: var(--accent-color); text-decoration: none;">กลับไปเลือกซื้อสินค้า</a>
<?php endif; ?>