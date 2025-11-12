<?php

require_once 'config.php';

if (!isBackendLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$product_id = (int)$_GET['product_id'];

$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header("location: " . BACKEND_URL . "?subpage=product&msg=notfound");
    exit;
}

$upload_dir = 'downloadable_works/'; 
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Worksheet Shop - แก้ไขสินค้า</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="sidebar">
        <h2 style="color: red;">Admin Panel</h2>
        <ul style="margin-top: 30px;">
            <li><a href="<?php echo BACKEND_URL; ?>?subpage=product">« กลับไปจัดการสินค้า</a></li>
            <li><a href="<?php echo BACKEND_URL; ?>?subpage=user">จัดการ User</a></li>
            <hr style="border-color: var(--border-color); margin: 15px 0;">
            <li><a href="<?php echo BASE_URL; ?>">กลับหน้าหลัก</a></li>
        </ul>
    </div>
    
    <div class="content">
        <h1>⚙️ แก้ไขสินค้า: <?php echo htmlspecialchars($product['name']); ?> (ID: <?php echo $product_id; ?>)</h1>
        
        <div style="max-width: 600px;">
            <form action="admin_actions.php?action=update_product" method="post" enctype="multipart/form-data" style="background-color: var(--bg-content); padding: 25px; border: 1px solid var(--border-color);">
                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                <input type="hidden" name="current_file_path" value="<?php echo htmlspecialchars($product['file_path']); ?>">
                
                <label for="name">ชื่อสินค้า:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                
                <label for="price">ราคา:</label>
                <input type="number" name="price" step="0.01" value="<?php echo number_format($product['price'], 2, '.', ''); ?>" required>
                
                <label for="stock">จำนวนสินค้าคงคลัง (Stock):</label>
                <input type="number" name="stock" value="<?php echo (int)($product['stock'] ?? 0); ?>" required>
                
                <label for="image_url">URL รูปภาพ (ปกสินค้า):</label>
                <input type="text" name="image_url" value="<?php echo htmlspecialchars($product['image_url']); ?>">
                
                <label for="description">รายละเอียดสินค้า:</label>
                <textarea name="description" rows="5"><?php echo htmlspecialchars($product['description']); ?></textarea>
                
                <h3 style="margin-top: 20px; margin-bottom: 15px; color: #dc3545;">อัปโหลดไฟล์งาน (ถ้าต้องการเปลี่ยน)</h3>

                <p style="font-size: 0.9em; margin-bottom: 5px;">ไฟล์ปัจจุบัน: <?php echo basename(htmlspecialchars($product['file_path'])); ?></p>
                <input type="file" name="file_upload" style="border: none; padding: 0;">
                <p style="font-size: 0.8em; color: #888; margin-bottom: 15px;">อัปโหลดไฟล์ใหม่เพื่อแทนที่ไฟล์เดิม (รองรับไฟล์ทุกประเภท)</p>
                
                <button type="submit" style="background-color: var(--accent-color);">บันทึกการแก้ไข</button>
            </form>
        </div>
    </div>
</body>
</html>