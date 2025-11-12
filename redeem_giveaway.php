<?php
// redeem_giveaway.php - หน้าแสดงโค้ด Redeem Point สำหรับ Giveaway

require_once 'config.php';

if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$claim_message = '';

// ดึงรายการโค้ด Redeem ที่เป็น Giveaway, Active, และยังมีสิทธิ์เหลือ
$redeem_sql = "SELECT code_id, redeem_code, point_amount, max_uses, used_count 
               FROM redeem_codes 
               WHERE is_active = TRUE 
               AND is_giveaway = 1 
               AND used_count < max_uses";
$redeem_result = $conn->query($redeem_sql);
?>

<div style="max-width: 800px; margin: 0 auto; padding-top: 20px;">
    <h1>💎 โค้ดแลก Point พิเศษ (Giveaway)</h1>
    
    <div id="alert-message">
        <?php // แสดงข้อความแจ้งเตือนต่างๆ ?>
    </div>

    <p style="margin-bottom: 20px;">โค้ด Point พิเศษด้านล่างนี้ สามารถกดรับเพื่อนำไปกรอกในหน้า แลก Point (Redeem) ได้เลย</p>

    <?php if ($redeem_result && $redeem_result->num_rows > 0): ?>
        <div class="product-grid" style="justify-content: flex-start; gap: 20px;">
            <?php while($row = $redeem_result->fetch_assoc()): ?>
            <?php 
                $code_id = $row['code_id'];
                $unique_id = htmlspecialchars($row['redeem_code']); 
                $used = (int)$row['used_count'];
                $max = (int)$row['max_uses'];
                $point = number_format($row['point_amount'], 2);
            ?>
            
            <div style="background-color: var(--bg-content); padding: 25px; border-radius: 8px; box-shadow: var(--card-shadow); width: 350px; text-align: center;">
                <h3 style="color: #dc3545; margin-bottom: 15px; font-size: 1.5em;"><?php echo $point; ?> Point</h3>
                
                <p style="margin-bottom: 10px;">จำกัดสิทธิ์: <?php echo $used; ?> / <?php echo $max; ?> ครั้ง</p>
                
                <div id="code-hidden-box-<?php echo $unique_id; ?>" 
                     style="font-size: 1.2em; color: #dc3545; font-weight: bold; padding: 10px; border: 1px dashed #ccc; margin-bottom: 15px;">
                    <span id="code-hidden-<?php echo $unique_id; ?>"></span>
                    <span id="code-revealed-<?php echo $unique_id; ?>" style="display: none;"><?php echo $unique_id; ?></span>
                </div>
                
                <div style="margin-top: 15px;">
                    <button type="button" 
                            id="reveal-btn-<?php echo $unique_id; ?>"
                            onclick="revealCode('<?php echo $unique_id; ?>')"
                            style="background-color: var(--primary-btn);">
                        กดแสดงโค้ด
                    </button>
                    
                    <button type="button" 
                            id="copy-btn-<?php echo $unique_id; ?>"
                            onclick="copyCode('<?php echo $unique_id; ?>')"
                            style="background-color: var(--accent-color); margin-left: 10px; display: none;">
                        คัดลอกโค้ด
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p style="color: #dc3545; font-weight: bold;">❌ ขณะนี้ยังไม่มีโค้ดแลก Point พิเศษ</p>
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
    
    // Function to reveal the code and show the copy button
    function revealCode(code) {
        const hiddenText = document.getElementById(`code-hidden-${code}`);
        const revealedText = document.getElementById(`code-revealed-${code}`);
        const revealBtn = document.getElementById(`reveal-btn-${code}`);
        const copyBtn = document.getElementById(`copy-btn-${code}`);

        // 1. สลับการแสดงผล
        hiddenText.style.display = 'none';
        revealedText.style.display = 'inline';

        // 2. ซ่อนปุ่ม Reveal
        revealBtn.style.display = 'none';
        
        // 3. แสดงปุ่ม Copy
        copyBtn.style.display = 'inline-block';
        
        showTemporaryAlert(`✅ โค้ดแสดงแล้ว! กรุณาคัดลอก`, 'success');
    }

    // Function to copy text to clipboard
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