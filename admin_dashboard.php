<?php
if (!isBackendLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$message = '';
$upload_dir = 'downloadable_works/'; 
$subpage = $_GET['subpage'] ?? 'user'; 

if (isset($_SESSION['admin_message'])) {
    $message = $_SESSION['admin_message'];
    unset($_SESSION['admin_message']);
}

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) { 
        $message .= "<p class='error-message'>❌ ข้อผิดพลาด: ไม่สามารถสร้างโฟลเดอร์ '{$upload_dir}' ได้ (ตรวจสอบสิทธิ์)</p>";
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'adjust_point_inline') { 
        $target_user_id = (int)$_POST['target_user_id'];
        $amount = floatval($_POST['amount']);
        $current_user = $conn->query("SELECT username FROM users WHERE user_id = {$target_user_id}")->fetch_assoc();
        
        if ($current_user) {
            $update_stmt = $conn->prepare("UPDATE users SET wallet_point = wallet_point + ? WHERE user_id = ?");
            $update_stmt->bind_param("di", $amount, $target_user_id);

            if ($update_stmt->execute()) {
                $_SESSION['admin_message'] = "<p class='success-message'>✅ ปรับ Point ของ {$current_user['username']} จำนวน {$amount} สำเร็จ</p>";
            } else {
                $_SESSION['admin_message'] = "<p class='error-message'>❌ Error DB: " . $update_stmt->error . "</p>";
            }
            $update_stmt->close();
        } else {
            $_SESSION['admin_message'] = "<p class='error-message'>❌ ไม่พบ User ID: {$target_user_id}</p>";
        }
        
        header("location: " . BACKEND_URL . "?subpage=user");
        exit;
    } 
    
    elseif ($action === 'add_product_with_file') { 
        $name = $conn->real_escape_string($_POST['name']);
        $price = floatval($_POST['price']);
        $stock = (int)$_POST['stock']; 
        $image = $conn->real_escape_string($_POST['image_url']);
        
        if (isset($_FILES['file_upload']) && $_FILES['file_upload']['error'] === UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['file_upload']['tmp_name'];
            $file_name = $_FILES['file_upload']['name'];
            
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $new_file_name = uniqid('file_', true) . '.' . $file_ext;
            $destination = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $destination)) {
                $file_path_db = $conn->real_escape_string($destination);
                
                $description = $conn->real_escape_string($_POST['description'] ?? ''); 
                $sql = "INSERT INTO products (name, price, stock, image_url, file_path, description) VALUES ('$name', $price, $stock, '$image', '$file_path_db', '$description')";
                
                if ($conn->query($sql)) {
                    $_SESSION['admin_message'] = "<p class='success-message'>✅ เพิ่มสินค้าสำเร็จ! ไฟล์: {$new_file_name}</p>";
                } else {
                    $_SESSION['admin_message'] = "<p class='error-message'>❌ Error DB: " . $conn->error . "</p>";
                    @unlink($destination);
                }
            } else {
                $_SESSION['admin_message'] = "<p class='error-message'>❌ ไม่สามารถย้ายไฟล์ไปยังปลายทางได้</p>";
            }
        } else {
             $_SESSION['admin_message'] = "<p class='error-message'>❌ กรุณาเลือกไฟล์งานเพื่ออัปโหลด</p>";
        }
        
        header("location: " . BACKEND_URL . "?subpage=product");
        exit;
    }
}

?>

<h1>⚙️ Admin Dashboard</h1>
<?php echo $message; ?>

<?php if ($subpage === 'user'): ?>
    <h2 style="margin-bottom: 20px; color: red;">⚠️ รายการแจ้งโอนเงินที่รออนุมัติ</h2>
    <?php 
    $pending_tx_sql = "SELECT t.tx_id, t.amount, t.slip_path, t.timestamp, u.username, u.user_id 
                       FROM transactions t 
                       JOIN users u ON t.user_id = u.user_id 
                       WHERE t.status = 'pending' 
                       ORDER BY t.timestamp ASC";
    $pending_tx_result = $conn->query($pending_tx_sql);
    
    if ($pending_tx_result && $pending_tx_result->num_rows > 0):
    ?>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px; margin-bottom: 40px;">
        <tr style="background-color: #f8d7da;">
            <th style="padding: 10px; border: 1px solid #dc3545; text-align: left;">TX ID / User</th>
            <th style="padding: 10px; border: 1px solid #dc3545; text-align: right;">จำนวนเงิน</th>
            <th style="padding: 10px; border: 1px solid #dc3545; text-align: center;">สลิป</th>
            <th style="padding: 10px; border: 1px solid #dc3545; text-align: center; width: 25%;">จัดการ</th>
        </tr>
        <?php while($tx = $pending_tx_result->fetch_assoc()): ?>
        <tr>
            <td style="padding: 10px; border: 1px solid var(--border-color);">
                #<?php echo $tx['tx_id']; ?>: <?php echo htmlspecialchars($tx['username']); ?> (ID: <?php echo $tx['user_id']; ?>)
                <br><span style="font-size: 0.8em; color: #6c757d;"><?php echo $tx['timestamp']; ?></span>
            </td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right; font-weight: bold; color: red;">
                <?php echo number_format($tx['amount'], 2); ?>
            </td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                 <?php if ($tx['slip_path']): ?>
                    <a href="<?php echo htmlspecialchars($tx['slip_path']); ?>" target="_blank" style="color: blue;">[ดูสลิป]</a>
                <?php else: ?>
                    <span style="color: #999;">-</span>
                <?php endif; ?>
            </td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                <a href="admin_actions.php?action=approve_tx&tx_id=<?php echo $tx['tx_id']; ?>" 
                   onclick="return confirm('ยืนยันอนุมัติ TX #<?php echo $tx['tx_id']; ?> และเติม <?php echo $tx['amount']; ?> Point หรือไม่?');"
                   style="color: green; margin-right: 5px;">
                    [✓ อนุมัติ]
                </a>
                <a href="admin_actions.php?action=reject_tx&tx_id=<?php echo $tx['tx_id']; ?>" 
                   onclick="return confirm('ยืนยันปฏิเสธ TX #<?php echo $tx['tx_id']; ?> หรือไม่?');"
                   style="color: red;">
                    [✕ ปฏิเสธ]
                </a>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
    <?php else: ?>
        <p style="margin-bottom: 40px; color: green;">ไม่มีรายการแจ้งโอนเงินที่รออนุมัติ</p>
    <?php endif; ?>


    <h2 style="margin-top: 40px; color: var(--accent-color);">📋 รายการ User & จัดการสิทธิ์</h2>
    <?php 
    $user_list = $conn->query("SELECT user_id, username, user_role, wallet_point FROM users ORDER BY user_role DESC, user_id ASC");
    if ($user_list->num_rows > 0): ?>
        <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <tr style="background-color: var(--bg-light);">
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">ID / Username</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: right;">Point ปัจจุบัน</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 250px;">ปรับ Point (+/-)</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">Role</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 30%;">จัดการสิทธิ์ & ลบ</th>
        </tr>
        <?php while($row = $user_list->fetch_assoc()): ?>
        <tr>
            <td style="padding: 10px; border: 1px solid var(--border-color);">
                <?php echo htmlspecialchars($row['username']); ?> (ID: <?php echo $row['user_id']; ?>)
            </td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right; font-weight: bold; color: var(--accent-color);">
                <?php echo number_format($row['wallet_point'], 2); ?>
            </td>
            
            <td style="padding: 5px; border: 1px solid var(--border-color); text-align: center;">
                <form action="<?php echo BACKEND_URL; ?>?subpage=user" method="post" style="display: flex; gap: 5px; margin: 0; padding: 0;">
                    <input type="hidden" name="action" value="adjust_point_inline">
                    <input type="hidden" name="target_user_id" value="<?php echo $row['user_id']; ?>">
                    
                    <input type="number" name="amount" step="0.01" placeholder="+/- Point" required 
                           style="width: 100px; padding: 5px; margin: 0;">
                    <button type="submit" style="padding: 5px 10px; font-size: 0.8em; margin: 0;">
                        ปรับ
                    </button>
                </form>
            </td>
            
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center; color: <?php echo ($row['user_role'] === 'admin' ? 'red' : 'green'); ?>;"><?php echo $row['user_role']; ?></td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                <?php if ($row['user_role'] === 'user'): ?>
                    <a href="admin_actions.php?action=change_role&user_id=<?php echo $row['user_id']; ?>&new_role=admin" style="color: blue; margin-right: 10px;">[ตั้งเป็น Admin]</a>
                <?php else: ?>
                    <?php if ($row['user_id'] !== ($_SESSION['user_id'] ?? 0)): ?>
                        <a href="admin_actions.php?action=change_role&user_id=<?php echo $row['user_id']; ?>&new_role=user" style="color: orange; margin-right: 10px;">[ลดสิทธิ์]</a>
                    <?php else: ?>
                        <span style="color: #999;">(ตัวคุณเอง)</span>
                    <?php endif; ?>
                <?php endif; ?>
                
                <?php if ($row['user_id'] !== ($_SESSION['user_id'] ?? 0)): ?>
                    <a href="admin_actions.php?action=delete_user&user_id=<?php echo $row['user_id']; ?>" 
                       onclick="return confirm('คุณแน่ใจที่จะลบ User: <?php echo htmlspecialchars($row['username']); ?>?');"
                       style="color: red;">[ลบ]</a>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; endif; ?>
    </table>

<?php elseif ($subpage === 'product'): ?>
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 350px;">
            <h2 style="margin-bottom: 20px; color: var(--accent-color);">+ เพิ่มสินค้าใหม่พร้อมไฟล์งาน</h2>
            <form action="<?php echo BACKEND_URL; ?>?subpage=product" method="post" enctype="multipart/form-data" style="background-color: var(--bg-content); padding: 20px; border: 1px solid var(--border-color);">
                <input type="hidden" name="action" value="add_product_with_file">
                
                <label for="name">ชื่อสินค้า:</label>
                <input type="text" name="name" required>
                
                <label for="price">ราคา:</label>
                <input type="number" name="price" step="0.01" required>
                
                <label for="stock">จำนวนสินค้าคงคลัง (Stock):</label>
                <input type="number" name="stock" required value="1"> 
                
                <label for="image_url">URL รูปภาพ (ปกสินค้า):</label>
                <input type="text" name="image_url" value="placeholder.jpg">
                
                <label for="description">รายละเอียดสินค้า:</label> 
                <textarea name="description" rows="5"></textarea> 
                
                <label for="file_upload">ไฟล์งาน:</label>
                <input type="file" name="file_upload" required style="border: none; padding: 0;">
                <p style="font-size: 0.8em; color: #888; margin-bottom: 15px;">ไฟล์จะถูกบันทึกในโฟลเดอร์ `downloadable_works/` (รองรับทุกประเภท)</p>
                
                <button type="submit">เพิ่มสินค้า</button>
            </form>
        </div>
        
        <div style="flex: 2; min-width: 500px;">
            <h2 style="margin-bottom: 20px; color: var(--accent-color);">📝 รายการสินค้าปัจจุบัน</h2>
            <?php
            $product_list = $conn->query("SELECT * FROM products");
            if ($product_list->num_rows > 0):
            ?>
                <table style="width: 100%; border-collapse: collapse; margin-top: 15px; background-color: var(--bg-content); box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);">
                    <tr style="background-color: var(--bg-light);">
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">ID</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">สินค้า</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: right;">ราคา</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">Stock</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">ไฟล์</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">จัดการ</th>
                    </tr>
                    <?php while($row = $product_list->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo $row['product_id']; ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($row['name']); ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right;"><?php echo number_format($row['price'], 2); ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center; color: <?php echo (isset($row['stock']) && $row['stock'] > 0) ? 'green' : 'red'; ?>;">
                            <?php echo $row['stock'] ?? 0; ?>
                        </td> 
                        <td style="padding: 10px; border: 1px solid var(--border-color); font-size: 0.9em;"><?php echo basename(htmlspecialchars($row['file_path'])); ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                            <a href="<?php echo PRODUCT_EDIT_URL; ?>?product_id=<?php echo $row['product_id']; ?>" style="color: blue; margin-right: 10px;">แก้ไข</a> | 
                            <a href="admin_actions.php?action=delete_product&product_id=<?php echo $row['product_id']; ?>" 
                               onclick="return confirm('คุณแน่ใจที่จะลบสินค้า: <?php echo htmlspecialchars($row['name']); ?>?');"
                               style="color: red;">ลบ</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>ยังไม่มีสินค้าในฐานข้อมูล.</p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($subpage === 'coupon'): ?>
    <h2 style="margin-bottom: 20px; color: var(--accent-color);">🎫 จัดการคูปองส่วนลด</h2>
    
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <h3 style="margin-bottom: 15px;">+ สร้างคูปองใหม่ (กำหนดเอง/อัตโนมัติ)</h3>
            <form action="admin_actions.php?action=create_coupon" method="post" style="background-color: var(--bg-content); padding: 20px; border: 1px solid var(--border-color);">
                <label for="coupon_code">โค้ดคูปอง (ว่าง = สร้างอัตโนมัติ):</label>
                <input type="text" name="coupon_code" placeholder="เช่น SALE2025" maxlength="20">
                
                <label for="discount_percent">เปอร์เซ็นต์ส่วนลด (เช่น 10.00):</label>
                <input type="number" name="discount_percent" step="0.01" min="1" max="100" required>
                
                <label for="usage_limit">จำกัดการใช้งาน (ครั้ง):</label>
                <input type="number" name="usage_limit" min="1" value="100" required>
                
                <label for="is_giveaway" style="margin-top: 10px;">ประเภทโค้ด:</label>
                <select name="is_giveaway" style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <option value="0">คูปองส่วนลดทั่วไป</option>
                    <option value="1">โค้ดแจก (Giveaway) - ซ่อนโค้ดก่อนกดรับ</option>
                </select>

                <button type="submit">สร้างคูปอง</button>
            </form>
        </div>
        <div style="flex: 2; min-width: 400px;">
            <h3 style="margin-bottom: 15px;">รายการคูปองปัจจุบัน</h3>
            <?php
            $coupon_list = $conn->query("SELECT coupon_code, discount_percent, is_active, usage_limit, is_giveaway, used_count FROM coupons ORDER BY created_at DESC");
            
            if ($coupon_list && $coupon_list->num_rows > 0):
            ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background-color: var(--bg-light);">
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">Code</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: right;">ส่วนลด (%)</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">ใช้/จำกัด</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">ประเภท</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">สถานะ</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">จัดการ</th>
                    </tr>
                    <?php while($row = $coupon_list->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid var(--border-color); font-weight: bold;"><?php echo htmlspecialchars($row['coupon_code']); ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right;"><?php echo number_format($row['discount_percent'], 2); ?>%</td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;"><?php echo $row['used_count'] . ' / ' . $row['usage_limit']; ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center; font-size: 0.9em; color: <?php echo $row['is_giveaway'] ? '#008cba' : 'var(--text-dark)'; ?>;">
                            <?php echo $row['is_giveaway'] ? 'Giveaway' : 'ทั่วไป'; ?>
                        </td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center; color: <?php echo $row['is_active'] ? 'green' : 'red'; ?>;"><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                            <a href="admin_actions.php?action=toggle_coupon_status&code=<?php echo urlencode($row['coupon_code']); ?>&status=<?php echo $row['is_active'] ? '0' : '1'; ?>" 
                               onclick="return confirm('คุณแน่ใจที่จะ<?php echo $row['is_active'] ? 'ปิด' : 'เปิด'; ?>ใช้งานคูปอง <?php echo htmlspecialchars($row['coupon_code']); ?>?');"
                               style="color: <?php echo $row['is_active'] ? 'red' : 'green'; ?>; margin-right: 10px;">
                                <?php echo $row['is_active'] ? '[ปิดใช้งาน]' : '[เปิดใช้งาน]'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p style="color: red; font-weight: bold;">❌ ไม่พบตาราง 'coupons' หรือยังไม่มีคูปอง.</p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($subpage === 'redeem'): ?>
    <h2 style="margin-bottom: 20px; color: var(--accent-color);">💎 จัดการโค้ดแลก Point</h2>
    
    <div style="display: flex; gap: 30px; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <h3 style="margin-bottom: 15px;">+ สร้างโค้ดใหม่ (แลก Point)</h3>
            <form action="admin_actions.php?action=create_redeem_code" method="post" style="background-color: var(--bg-content); padding: 20px; border: 1px solid var(--border-color);">
                <label for="redeem_code">โค้ด (ว่าง = สร้างอัตโนมัติ):</label>
                <input type="text" name="redeem_code" placeholder="เช่น FREE100" maxlength="30">
                
                <label for="point_amount">จำนวน Point ที่จะได้รับ:</label>
                <input type="number" name="point_amount" step="0.01" min="1" required>
                
                <label for="max_uses">จำกัดจำนวนคนใช้ (ครั้ง):</label>
                <input type="number" name="max_uses" min="1" value="1" required>
                
                <label for="is_giveaway" style="margin-top: 10px;">ประเภทโค้ด:</label>
                <select name="is_giveaway" style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 4px;">
                    <option value="0">0 - โค้ดแลกทั่วไป (กรอกเอง)</option>
                    <option value="1">1 - โค้ดแจก (Giveaway) - แสดงในหน้าแจกโค้ด</option>
                </select>

                <button type="submit">สร้างโค้ดแลก Point</button>
            </form>
        </div>
        <div style="flex: 2; min-width: 400px;">
            <h3 style="margin-bottom: 15px;">รายการโค้ดแลก Point ปัจจุบัน</h3>
            <?php
            $redeem_list = $conn->query("SELECT code_id, redeem_code, point_amount, max_uses, used_count, is_active FROM redeem_codes ORDER BY created_at DESC");
            
            if ($redeem_list && $redeem_list->num_rows > 0):
            ?>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="background-color: var(--bg-light);">
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">Code</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: right;">Point</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">ใช้/จำกัด</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">สถานะ</th>
                        <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">จัดการ</th>
                    </tr>
                    <?php while($row = $redeem_list->fetch_assoc()): ?>
                    <tr>
                        <td style="padding: 10px; border: 1px solid var(--border-color); font-weight: bold;"><?php echo htmlspecialchars($row['redeem_code']); ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right; color: var(--accent-color); font-weight: bold;"><?php echo number_format($row['point_amount'], 2); ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;"><?php echo $row['used_count'] . ' / ' . $row['max_uses']; ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center; color: <?php echo $row['is_active'] ? 'green' : 'red'; ?>;"><?php echo $row['is_active'] ? 'Active' : 'Inactive'; ?></td>
                        <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                            <a href="admin_actions.php?action=toggle_redeem_status&code_id=<?php echo $row['code_id']; ?>&status=<?php echo $row['is_active'] ? '0' : '1'; ?>" 
                               onclick="return confirm('คุณแน่ใจที่จะ<?php echo $row['is_active'] ? 'ปิด' : 'เปิด'; ?>ใช้งานโค้ด ID: <?php echo $row['code_id']; ?>?');"
                               style="color: <?php echo $row['is_active'] ? 'red' : 'green'; ?>; margin-right: 10px;">
                                <?php echo $row['is_active'] ? '[ปิด]' : '[เปิด]'; ?>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <p>ยังไม่มีโค้ดแลก Point ในระบบ.</p>
            <?php endif; ?>
        </div>
    </div>
<?php elseif ($subpage === 'review'): ?> 
    <h2 style="margin-bottom: 20px; color: var(--accent-color);">⭐ จัดการรีวิวสินค้า</h2>
    <?php
    $review_list = $conn->query("SELECT r.*, u.username, p.name AS product_name 
                                 FROM reviews r 
                                 JOIN users u ON r.user_id = u.user_id
                                 JOIN products p ON r.product_id = p.product_id
                                 ORDER BY r.created_at DESC");
    if ($review_list && $review_list->num_rows > 0):
    ?>
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background-color: var(--bg-light);">
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left; width: 10%;">ID/Rating</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left; width: 20%;">User/Product</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">Review</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 10%;">จัดการ</th>
            </tr>
            <?php while($row = $review_list->fetch_assoc()): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid var(--border-color);">
                    #<?php echo $row['review_id']; ?><br>
                    <span style="color: gold; font-weight: bold; font-size: 1.2em;"><?php echo str_repeat('★', $row['rating']); ?></span>
                </td>
                <td style="padding: 10px; border: 1px solid var(--border-color);">
                    <?php echo htmlspecialchars($row['username']); ?><br>
                    <span style="font-size: 0.8em; color: #666;"><?php echo htmlspecialchars($row['product_name']); ?></span>
                </td>
                <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo nl2br(htmlspecialchars($row['review_text'])); ?></td>
                <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                    <a href="admin_actions.php?action=delete_review&review_id=<?php echo $row['review_id']; ?>" 
                       onclick="return confirm('ยืนยันลบรีวิวนี้หรือไม่?');"
                       style="color: red;">[ลบ]</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>ยังไม่มีรีวิวในระบบ</p>
    <?php endif; ?>
<?php elseif ($subpage === 'admin_chat'): ?>
    <?php include 'chat.php'; ?>
<?php else: ?>
    <p>ยินดีต้อนรับ Admin, กรุณาเลือกเมนูทางด้านซ้าย</p>
<?php endif; ?>