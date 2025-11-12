<?php
require_once 'config.php'; 

if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$today = date("Y-m-d");
$max_limit = 1; 
$sql = "SELECT 
            p.product_id, 
            p.name, 
            p.image_url, 
            p.file_path,
            up.price_paid, 
            up.purchase_date 
        FROM user_purchases up
        JOIN products p ON up.product_id = p.product_id
        WHERE up.user_id = '$user_id'
        ORDER BY up.purchase_date DESC";

$result = $conn->query($sql);
$limit_status_map = [];
$limit_map_sql = "SELECT product_id, download_count FROM download_limits WHERE user_id = ? AND download_date = ?";
$limit_stmt_map = $conn->prepare($limit_map_sql);
$limit_stmt_map->bind_param("is", $user_id, $today);
$limit_stmt_map->execute();
$limit_map_result = $limit_stmt_map->get_result();

while ($row = $limit_map_result->fetch_assoc()) {
    $limit_status_map[$row['product_id']] = $row['download_count'];
}
$limit_stmt_map->close();
$history_sql = "SELECT 
                    dh.timestamp, 
                    p.name AS product_name 
                FROM download_history dh
                JOIN products p ON dh.product_id = p.product_id
                WHERE dh.user_id = '$user_id'
                ORDER BY dh.timestamp DESC";
                
$history_result = $conn->query($history_sql);
$midnight = new DateTime('tomorrow midnight');
$time_diff_seconds = $midnight->getTimestamp() - time();
?>

<h1>📦 สินค้าที่ฉันซื้อ</h1>
<p style="margin-bottom: 20px;">รายการสรุปไฟล์งานและสไลด์ที่คุณเคยซื้อ</p>

<div style="background-color: #fff3cd; border: 1px solid #ffeeba; padding: 15px; margin-bottom: 25px; border-radius: 6px;">
    <p style="font-weight: bold; color: #856404;">
        ⬇️ ขีดจำกัด: คุณสามารถดาวน์โหลดแต่ละไฟล์ได้ 1 ครั้งต่อวัน (รีเซ็ตใน: <span id="countdown-timer"><?php echo timeUntilMidnight(); ?></span> )
    </p>
</div>


<?php if ($result && $result->num_rows > 0): ?>
    
    <div style="
        background-color: var(--bg-content); 
        border: 1px solid var(--border-color); 
        border-radius: 8px; 
        overflow: hidden; 
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08); 
        margin-bottom: 30px; 
    ">
        <table style="width: 100%; border-collapse: collapse;">
            <tr style="background-color: var(--bg-light);">
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left; width: 5%;">รูป</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left; width: 40%;">สินค้า</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: right; width: 15%;">ราคาที่ซื้อ</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 30%;">วันที่/เวลาที่ซื้อ</th>
                <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 10%;">ดาวน์โหลด</th>
            </tr>
            <?php while($row = $result->fetch_assoc()): 
                $product_download_count = $limit_status_map[$row['product_id']] ?? 0;
                $is_limit_reached = $product_download_count >= $max_limit;
            ?>
            <tr>
                <td style="padding: 10px; border: 1px solid var(--border-color);"><img src="<?php echo htmlspecialchars($row['image_url'] ?? 'placeholder.jpg'); ?>" style="height: 40px; width: 40px; object-fit: cover; border-radius: 4px;"></td>
                <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($row['name']); ?></td>
                <td style="padding: 10px; border: 1px solid var(--border-color); text-align: right; color: #dc3545; font-weight: bold;"><?php echo number_format($row['price_paid'], 2); ?></td>
                <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;"><?php echo date('d/m/Y H:i:s', strtotime($row['purchase_date'])); ?></td>
                <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center;">
                    <?php if (!empty($row['file_path'])): ?>
                        <?php 
                            $status_text = $is_limit_reached ? '[Limit]' : "[Download {$product_download_count}/{$max_limit}]"; 
                            $status_color = $is_limit_reached ? '#999' : 'green';
                        ?>
                        <a href="dowload_handler.php?file=<?php echo urlencode($row['file_path']); ?>&product_id=<?php echo $row['product_id']; ?>" 
                           style="color: <?php echo $status_color; ?>; text-decoration: none; font-weight: bold;"
                           onclick="<?php echo $is_limit_reached ? 'return false;' : 'setTimeout(function(){ window.location.reload(); }, 100);'; ?>">
                            <?php echo $status_text; ?>
                        </a>
                    <?php else: ?>
                        <span style="color: #999;">-</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div> 
<?php else: ?>
    <p style="margin-top: 20px; margin-bottom: 30px;">คุณยังไม่เคยซื้อสินค้าใด ๆ</p>
<?php endif; ?>


<h2 style="color: var(--accent-color); margin-top: 30px;">📜 ประวัติการดาวน์โหลดล่าสุด</h2>

<?php 
if ($history_result && $history_result->num_rows > 0): ?>
    <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
        <tr style="background-color: var(--bg-light);">
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: left; width: 60%;">สินค้าที่ดาวน์โหลด</th>
            <th style="padding: 10px; border: 1px solid var(--border-color); text-align: center; width: 40%;">วันที่/เวลาดาวน์โหลด</th>
        </tr>
        <?php while($row = $history_result->fetch_assoc()): ?>
        <tr>
            <td style="padding: 10px; border: 1px solid var(--border-color);"><?php echo htmlspecialchars($row['product_name']); ?></td>
            <td style="padding: 10px; border: 1px solid var(--border-color); text-align: center; font-size: 0.9em;"><?php echo $row['timestamp']; ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
<?php else: ?>
    <p style="margin-top: 15px;">ยังไม่มีประวัติการดาวน์โหลด</p>
<?php endif; ?>

<script>
    let timeLeft = <?php echo $time_diff_seconds; ?>;
    const timerElement = document.getElementById('countdown-timer');

    function formatTime(seconds) {
        const hours = Math.floor(seconds / 3600);
        const minutes = Math.floor((seconds % 3600) / 60);
        const remainingSeconds = seconds % 60;
        
        return `${hours} ชม. ${minutes} น. ${remainingSeconds} วินาที`;
    }

    function updateCountdown() {
        if (timeLeft <= 0) {
            timerElement.textContent = '0 ชม. 0 น. 0 วินาที';
            clearInterval(countdownInterval);
            location.reload(); 
            return;
        }
        
        timerElement.textContent = formatTime(timeLeft);
        timeLeft--;
    }

    updateCountdown();
    const countdownInterval = setInterval(updateCountdown, 1000);
</script>