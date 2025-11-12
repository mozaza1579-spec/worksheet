<?php
// code_giveaway.php - รวม Coupon Giveaway และ Redeem Point Giveaway

require_once 'config.php';

if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$giveaway_message = '';

// 1. ดึงรายการโค้ด Coupon Giveaway
$coupon_sql = "SELECT coupon_id AS id, coupon_code AS code, discount_percent AS value, usage_limit, used_count, 'COUPON' AS type 
                 FROM coupons 
                 WHERE is_active = TRUE 
                 AND is_giveaway = 1
                 AND used_count < usage_limit";

// 2. ดึงรายการโค้ด Redeem Point Giveaway
$redeem_sql = "SELECT code_id AS id, redeem_code AS code, point_amount AS value, max_uses AS usage_limit, used_count, 'REDEEM' AS type 
               FROM redeem_codes 
               WHERE is_active = TRUE 
               AND is_giveaway = 1
               AND used_count < max_uses";

// รวมผลลัพธ์
$all_giveaways = [];

$coupon_result = $conn->query($coupon_sql);
if ($coupon_result) {
    while($row = $coupon_result->fetch_assoc()) {
        $all_giveaways[] = $row;
    }
}

$redeem_result = $conn->query($redeem_sql);
if ($redeem_result) {
    while($row = $redeem_result->fetch_assoc()) {
        $all_giveaways[] = $row;
    }
}
?>

<div style="max-width: 800px; margin: 0 auto; padding-top: 20px;">
    <h1>🎁 โค้ดพิเศษ (Giveaway)</h1>
    
    <div id="alert-message">
        <?php // ใช้นี่แสดงข้อความคัดลอกสำเร็จ ?>
    </div>

    <p style="margin-bottom: 20px;">โค้ดด้านล่างนี้สามารถคัดลอกและนำไปใช้ได้เลย (จำกัดจำนวนสิทธิ์)</p>

    <?php if (!empty($all_giveaways)): ?>
        <div class="product-grid" style="justify-content: flex-start; gap: 20px;">
            <?php foreach ($all_giveaways as $row): ?>
            <?php 
                $unique_id = htmlspecialchars($row['code']); 
                $used = (int)$row['used_count'];
                $max = (int)$row['usage_limit'];
                $is_redeem = $row['type'] === 'REDEEM';

                if ($is_redeem) {
                    $header_text = number_format($row['value'], 2) . ' Point ฟรี!';
                    $header_color = '#007bff';
                    $redirect_url = '?page=redeem';
                } else {
                    $header_text = 'ส่วนลด ' . number_format($row['value'], 0) . '%';
                    $header_color = '#28a745';
                    $redirect_url = '?page=checkout';
                }
            ?>
            
            <div style="background-color: var(--bg-content); padding: 25px; border-radius: 8px; box-shadow: var(--card-shadow); width: 350px; text-align: center;">
                
                <h3 style="color: <?php echo $header_color; ?>; margin-bottom: 15px; font-size: 1.5em; font-weight: bold;"><?php echo $header_text; ?></h3>
                
                <p style="margin-bottom: 20px;">
                    ใช้ไปแล้ว: <?php echo $used; ?> / <?php echo $max; ?> ครั้ง
                </p>
                
                <div id="code-revealed-<?php echo $unique_id; ?>" 
                     style="font-size: 1.5em; color: #dc3545; font-weight: bold; padding: 10px; border: 2px solid #dc3545; border-radius: 6px; margin-bottom: 15px;">
                    <?php echo $unique_id; ?>
                </div>
                
                <div style="margin-top: 15px;">
                    <button type="button" 
                            id="copy-btn-<?php echo $unique_id; ?>"
                            onclick="copyCodeAndRedirect('<?php echo $unique_id; ?>', '<?php echo $redirect_url; ?>')"
                            style="background-color: var(--accent-color);">
                        คัดลอกโค้ด
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: #dc3545; font-weight: bold;">❌ ขณะนี้ยังไม่มีโค้ดแจกพิเศษที่ใช้งานได้</p>
    <?php endif; ?>
</div>

<script>
    const alertBox = document.getElementById('alert-message');

    function showTemporaryAlert(message, type = 'success') {
        alertBox.innerHTML = `<p class='${type}-message' style='text-align: center;'>${message}</p>`;
        setTimeout(() => {
            alertBox.innerHTML = '';
        }, 3000);
    }
    
    // NEW FUNCTION: Copy code and guide the user to the next step
    function copyCodeAndRedirect(code, redirectUrl) {
        copyCode(code); // คัดลอก
        
        // แจ้งเตือนและแนะนำ User ไปยังหน้าถัดไป
        setTimeout(() => {
            if (confirm(`✅ โค้ด "${code}" ถูกคัดลอกแล้ว!\n\nกด OK เพื่อไปหน้า ${redirectUrl === '?page=redeem' ? 'แลก Point' : 'Checkout'}`)) {
                window.location.href = redirectUrl;
            }
        }, 500); 
    }

    // Function to copy text to clipboard (Logic from previous steps)
    function copyCode(code) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(() => {
                showTemporaryAlert(`✅ คัดลอกโค้ด "${code}" สำเร็จ!`, 'success');
            }).catch(err => {
                fallbackCopyText(code);
            });
        } else {
            fallbackCopyText(code);
        }
    }

    // Fallback function for older browsers (uses document.execCommand)
    function fallbackCopyText(code) {
        const textarea = document.createElement('textarea');
        textarea.value = code;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px'; 
        textarea.style.top = '0';
        
        document.body.appendChild(textarea);
        textarea.focus();
        textarea.select();
        
        try {
            const successful = document.execCommand('copy');
            if (successful) {
                 showTemporaryAlert(`✅ คัดลอกโค้ด "${code}" สำเร็จ! (Fallback)`, 'success');
            } else {
                 showTemporaryAlert('❌ ไม่สามารถคัดลอกอัตโนมัติได้ กรุณาคัดลอกด้วยตนเอง', 'error');
            }
        } catch (err) {
            showTemporaryAlert('❌ ไม่สามารถคัดลอกอัตโนมัติได้ กรุณาคัดลอกด้วยตนเอง', 'error');
        }
        document.body.removeChild(textarea);
    }
</script>