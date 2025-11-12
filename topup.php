<?php
if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$topup_message = '';
$upload_slip_dir = 'transactions_slips/';
$show_success_popup = false; 


$bank_account = [
    'bank_name' => 'ธนาคารกสิกรไทย (KBank)',
    'account_number' => '132-3-98765-1',
    'account_name' => 'Worksheet Shop'
];


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['set_amount'])) {
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $topup_message = "<p class='error-message'>❌ กรุณากรอกจำนวนเงินที่ถูกต้อง</p>";
    } elseif (!isset($_FILES['slip_image']) || $_FILES['slip_image']['error'] !== UPLOAD_ERR_OK) {
        $topup_message = "<p class='error-message'>❌ กรุณาแนบไฟล์สลิปหลักฐานการโอน</p>";
    } else {
        $transfer_amount = $amount;
        $file_tmp = $_FILES['slip_image']['tmp_name'];
        $file_name = $_FILES['slip_image']['name'];
        
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $new_file_name = uniqid('slip_', true) . '.' . $file_ext;
        $destination = $upload_slip_dir . $new_file_name;
        
        if (!is_dir($upload_slip_dir)) { mkdir($upload_slip_dir, 0755, true); }
        
        if (move_uploaded_file($file_tmp, $destination)) {
            $slip_path_db = $conn->real_escape_string($destination);
            $internal_ref = 'BANK' . time() . $user_id; 
            
            $insert_tx = $conn->prepare("INSERT INTO transactions (user_id, amount, status, slip_path, tmweasy_ref) VALUES (?, ?, 'pending', ?, ?)");
            
            if ($insert_tx) {
                $insert_tx->bind_param("idss", $user_id, $transfer_amount, $slip_path_db, $internal_ref);
                if ($insert_tx->execute()) {
                    $_SESSION['topup_success_amount'] = $transfer_amount;
                    header("location: ?page=topup"); 
                    exit;
                } else {
                    $topup_message = "<p class='error-message'>❌ เกิดข้อผิดพลาดในการบันทึกคำขอ: " . $insert_tx->error . "</p>";
                    @unlink($destination);
                }
                $insert_tx->close();
            }
        } else {
            $topup_message = "<p class='error-message'>❌ ไม่สามารถอัปโหลดไฟล์สลิปได้ (ตรวจสอบสิทธิ์โฟลเดอร์)</p>";
        }
    }
}

if (isset($_SESSION['topup_success_amount'])) {
    $transfer_amount = $_SESSION['topup_success_amount'];
    $show_success_popup = true;
    unset($_SESSION['topup_success_amount']);
}
?>

<h1>💲 เติม Point (แจ้งโอนเงิน)</h1>

<div style="display: flex; justify-content: center; width: 100%;"> 
    <div style="max-width: 600px; width: 100%;">
        <?php echo $topup_message; ?>

        <h2 style="color: var(--accent-color); margin-bottom: 10px;">ข้อมูลการโอนเงิน</h2>
        
        <div style="background-color: var(--bg-content); padding: 25px; border: 1px solid var(--border-color); border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <p style="font-weight: bold; margin-bottom: 10px;">โปรดโอนเงินไปยังบัญชีด้านล่างนี้:</p>
            <p style="font-size: 1.2em; margin-bottom: 5px;"><?php echo $bank_account['bank_name']; ?></p>
            <p style="font-size: 1.8em; font-weight: bold; color: #dc3545;"><?php echo $bank_account['account_number']; ?></p>
            <p style="font-size: 1.1em; color: #6c757d;">ชื่อบัญชี: <?php echo $bank_account['account_name']; ?></p>
        </div>

        <form action="?page=topup" method="post" enctype="multipart/form-data" style="background-color: var(--bg-content); padding: 20px; border: 1px solid var(--border-color);">
            <input type="hidden" name="set_amount" value="1">
            
            <label for="amount">จำนวนเงินที่โอน:</label>
            <input type="number" id="amount" name="amount" step="1" min="1" required placeholder="ขั้นต่ำ 1 บาท">
            
            <label for="slip_image">แนบสลิปหลักฐาน (รูปภาพ):</label>
            <input type="file" id="slip_image" name="slip_image" accept="image/*" required style="border: none; padding: 0;">
            
            <button type="submit" style="margin-top: 15px;">แจ้งโอนเงิน</button>
        </form>
        
        <h2 style="margin-top: 40px; color: var(--accent-color);">ประวัติการเติมล่าสุด</h2>
        <?php 
        $tx_sql = "SELECT amount, status, timestamp, slip_path FROM transactions WHERE user_id = '$user_id' ORDER BY timestamp DESC LIMIT 5";
        $tx_result = $conn->query($tx_sql);
        if ($tx_result && $tx_result->num_rows > 0): ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
            <tr style="background-color: var(--bg-light);">
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">Point</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">สถานะ</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">สลิป</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left;">วันที่/เวลา</th>
            </tr>
            <?php while($row = $tx_result->fetch_assoc()): ?>
            <tr>
                <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo number_format($row['amount'], 2); ?></td>
                <td style="padding: 10px; border: 1px solid var(--border-color);">
                    <?php 
                        $status = $row['status'];
                        $color = ($status === 'success') ? 'green' : (($status === 'failed') ? 'red' : 'orange');
                        echo "<span style='color: {$color}; font-weight: bold;'>{$status}</span>";
                    ?>
                </td>
                <td style="padding: 10px; border: 1px solid var(--border-color);">
                    <?php if ($row['slip_path']): ?>
                        <a href="<?php echo htmlspecialchars($row['slip_path']); ?>" target="_blank" style="color: blue;">ดูสลิป</a>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo $row['timestamp']; ?></td>
            </tr>
            <?php endwhile; ?>
        </table>
        <?php else: ?>
            <p>ยังไม่มีประวัติการเติมเงิน</p>
        <?php endif; ?>

    </div>
</div>

<div id="transferSuccessModal" class="modal">
    <div class="modal-content" style="max-width: 400px; text-align: center;">
        <span class="close-btn" onclick="closeModal('transferSuccessModal')">&times;</span>
        <h2 style="color: #28a745; margin-bottom: 15px;">✅ แจ้งโอนสำเร็จ!</h2>
        <p style="font-size: 1.1em; margin-bottom: 10px;">
            บันทึกคำขอเติมเงินจำนวน <strong id="transfer-amount-display"></strong> บาทแล้ว
        </p>
        <p style="font-size: 1em; color: #6c757d;">
            แอดมินจะตรวจสอบหลักฐานและดำเนินการเติม Point ให้ท่านโดยเร็วที่สุด
        </p>
        
        <a href="?page=support_tickets" style="text-decoration: none;">
            <button style="margin-top: 20px;">ดูสถานะใน Ticket Support</button>
        </a>
    </div>
</div>

<?php if ($show_success_popup): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const amount = "<?php echo number_format($transfer_amount, 2); ?>";
            document.getElementById('transfer-amount-display').textContent = amount;
            openModal('transferSuccessModal');
        });
    </script>
<?php endif; ?>