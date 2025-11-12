<?php
if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$is_admin_user = isAdmin();
$ticket_message = '';
$current_ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_ticket'])) {
    $subject = $conn->real_escape_string($_POST['subject']);
    $initial_message = $conn->real_escape_string($_POST['initial_message']);

    $conn->begin_transaction();
    try {
        $insert_ticket = $conn->prepare("INSERT INTO support_tickets (user_id, subject) VALUES (?, ?)");
        $insert_ticket->bind_param("is", $user_id, $subject);
        $insert_ticket->execute();
        $new_ticket_id = $conn->insert_id;

        $insert_msg = $conn->prepare("INSERT INTO support_messages (ticket_id, user_id, sender_role, message_text) VALUES (?, ?, 'user', ?)");
        $insert_msg->bind_param("iis", $new_ticket_id, $user_id, $initial_message);
        $insert_msg->execute();

        $conn->commit();
        $ticket_message = "<p class='success-message'>✅ เปิด Ticket #{$new_ticket_id} สำเร็จ!</p>";
        $current_ticket_id = $new_ticket_id;
        sendNotification('admin', "<p class='error-message'>🔔 NEW TICKET: User {$username} เปิด Ticket #{$new_ticket_id} เรื่อง: " . htmlspecialchars($subject) . "</p>");

    } catch (Exception $e) {
        $conn->rollback();
        $ticket_message = "<p class='error-message'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['reply_ticket']) && $current_ticket_id > 0) {
    $reply_text = $conn->real_escape_string($_POST['reply_text']);

    $conn->begin_transaction();
    try {
        $insert_reply = $conn->prepare("INSERT INTO support_messages (ticket_id, user_id, sender_role, message_text) VALUES (?, ?, 'user', ?)");
        $insert_reply->bind_param("iis", $current_ticket_id, $user_id, $reply_text);
        $insert_reply->execute();

        $update_ticket = $conn->prepare("UPDATE support_tickets SET status = 'open' WHERE ticket_id = ? AND user_id = ?");
        $update_ticket->bind_param("ii", $current_ticket_id, $user_id);
        $update_ticket->execute();

        $conn->commit();
        $ticket_message = "<p class='success-message'>✅ ส่งข้อความสำเร็จ!</p>";
    } catch (Exception $e) {
        $conn->rollback();
        $ticket_message = "<p class='error-message'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['admin_reply_ticket']) && $current_ticket_id > 0 && $is_admin_user) {
    $reply_text = $conn->real_escape_string($_POST['admin_reply_text']);

    $conn->begin_transaction();
    try {
        $insert_reply = $conn->prepare("INSERT INTO support_messages (ticket_id, user_id, sender_role, message_text) VALUES (?, ?, 'admin', ?)");
        $insert_reply->bind_param("iis", $current_ticket_id, $user_id, $reply_text);
        $insert_reply->execute();

        $update_ticket = $conn->prepare("UPDATE support_tickets SET status = 'answered' WHERE ticket_id = ?");
        $update_ticket->bind_param("i", $current_ticket_id);
        $update_ticket->execute();

        $conn->commit();
        $ticket_message = "<p class='success-message'>✅ แอดมินตอบกลับเรียบร้อย!</p>";
    } catch (Exception $e) {
        $conn->rollback();
        $ticket_message = "<p class='error-message'>❌ เกิดข้อผิดพลาด: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

$ticket_info = null;
$messages = null;
$ticket_owner_username = '';

if ($current_ticket_id > 0) {
    $stmt = $conn->prepare("SELECT t.subject, t.status, u.username FROM support_tickets t JOIN users u ON t.user_id = u.user_id WHERE t.ticket_id = ?");
    $stmt->bind_param("i", $current_ticket_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        if ($is_admin_user || ($result['username'] === $username)) {
            $ticket_info = $result;
            $ticket_owner_username = $result['username'];

            $messages_sql = $conn->prepare("SELECT sender_role, message_text, timestamp FROM support_messages WHERE ticket_id = ? ORDER BY timestamp ASC");
            $messages_sql->bind_param("i", $current_ticket_id);
            $messages_sql->execute();
            $messages = $messages_sql->get_result();
        }
    }
}
?>

<h1>💬 Support / Ticket System</h1>
<?php echo $ticket_message; ?>

<div style="display: flex; gap: 30px;">
    <div style="flex: 1; max-width: 300px; background-color: var(--bg-content); padding: 15px; border-radius: 8px;">
        <h2 style="color: var(--accent-color); margin-bottom: 15px;">
            <?php echo $is_admin_user ? 'Ticket ทั้งหมด' : 'Ticket ของฉัน'; ?>
        </h2>

        <?php if (!$is_admin_user): ?>
            <a href="?page=support_tickets" style="display: block; margin-bottom: 15px; color: var(--accent-color); font-weight: bold;">+ เปิด Ticket ใหม่</a>
        <?php endif; ?>

        <?php 
        $list_sql = $is_admin_user 
            ? "SELECT ticket_id, subject, status FROM support_tickets ORDER BY last_updated DESC"
            : "SELECT ticket_id, subject, status FROM support_tickets WHERE user_id = '$user_id' ORDER BY last_updated DESC";
        $list_result = $conn->query($list_sql);
        if ($list_result->num_rows > 0):
            while($ticket = $list_result->fetch_assoc()):
                $status_color = ($ticket['status'] === 'closed') ? '#dc3545' : (($ticket['status'] === 'answered') ? '#ffc107' : '#007bff');
        ?>
        <a href="?page=support_tickets&ticket_id=<?php echo $ticket['ticket_id']; ?>" 
           style="display: block; padding: 10px; margin-bottom: 5px; background-color: <?php echo ($ticket['ticket_id'] == $current_ticket_id) ? '#e9ecef' : '#f8f9fa'; ?>; border-radius: 4px; text-decoration: none; color: var(--text-dark); border-left: 5px solid <?php echo $status_color; ?>;">
            #<?php echo $ticket['ticket_id']; ?>: <?php echo htmlspecialchars($ticket['subject']); ?> 
            <span style="float: right; font-size: 0.8em; color: <?php echo $status_color; ?>;"><?php echo $ticket['status']; ?></span>
        </a>
        <?php endwhile; endif; ?>
    </div>

    <div style="flex: 2;">
        <?php if ($current_ticket_id > 0 && $ticket_info): ?>
            <h2 style="color: var(--accent-color); margin-bottom: 10px;">
                Ticket #<?php echo $current_ticket_id; ?>: <?php echo htmlspecialchars($ticket_info['subject']); ?> 
                (<?php echo $is_admin_user ? 'User: ' : 'ของคุณ: '; ?><?php echo $ticket_owner_username; ?>)
            </h2>
            <p style="margin-bottom: 20px;">สถานะ: 
                <span style="font-weight: bold; color: <?php echo ($ticket_info['status'] === 'closed') ? '#dc3545' : '#007bff'; ?>;">
                    <?php echo $ticket_info['status']; ?>
                </span>
            </p>

            <div id="chat-box" style="height: 400px; overflow-y: scroll; padding: 15px; border: 1px solid var(--border-color); background-color: var(--bg-content); margin-bottom: 20px;">
                <?php 
                if ($messages) {
                    while($msg = $messages->fetch_assoc()): 
                        $is_admin_msg = $msg['sender_role'] === 'admin';
                        $sender_name_display = $is_admin_msg ? 'แอดมิน:' : "คุณ:";
                        $sender_class = $is_admin_msg ? 'admin' : 'user';
                ?>
                <div class="chat-message <?php echo $sender_class; ?>">
                    <strong class="chat-sender-<?php echo $sender_class; ?>"><?php echo $sender_name_display; ?></strong>
                    <p style="margin: 5px 0;"><?php echo nl2br(htmlspecialchars($msg['message_text'])); ?></p>
                    <span style="font-size: 0.7em; color: #666;"><?php echo $msg['timestamp']; ?></span>
                </div>
                <?php endwhile; } ?>
            </div>

            <?php if ($ticket_info['status'] !== 'closed'): ?>
                <?php if ($is_admin_user): ?>
                    <form action="?page=support_tickets&ticket_id=<?php echo $current_ticket_id; ?>" method="post">
                        <input type="hidden" name="admin_reply_ticket" value="1">
                        <textarea name="admin_reply_text" rows="4" required></textarea>
                        <button type="submit" style="background-color: red;">ส่งข้อความ (แอดมิน)</button>
                        <a href="admin_actions.php?action=close_ticket&ticket_id=<?php echo $current_ticket_id; ?>" 
                           onclick="return confirm('ยืนยันปิด Ticket #<?php echo $current_ticket_id; ?>?');"
                           style="padding: 10px 20px; background-color: #6c757d; color: white; border-radius: 4px; text-decoration: none; display: inline-block;">
                            ปิด Ticket
                        </a>
                    </form>
                <?php else: ?>
                    <form action="?page=support_tickets&ticket_id=<?php echo $current_ticket_id; ?>" method="post">
                        <input type="hidden" name="reply_ticket" value="1">
                        <textarea name="reply_text" rows="4" required></textarea>
                        <button type="submit">ส่งข้อความ</button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <p style="font-weight: bold; color: #dc3545;">Ticket นี้ถูกปิดแล้ว</p>
            <?php endif; ?>
        <?php elseif (!$is_admin_user): ?>
            <h2 style="color: var(--accent-color); margin-bottom: 20px;">เปิด Ticket ใหม่</h2>
            <form action="?page=support_tickets" method="post">
                <input type="hidden" name="create_ticket" value="1">
                <label>หัวข้อเรื่อง:</label>
                <input type="text" name="subject" required>
                <label>รายละเอียด:</label>
                <textarea name="initial_message" rows="6" required></textarea>
                <button type="submit">เปิด Ticket</button>
            </form>
        <?php else: ?>
            <p>กรุณาเลือก Ticket จากรายการด้านซ้าย</p>
        <?php endif; ?>
    </div>
</div>

<?php if ($current_ticket_id > 0 && $ticket_info): ?>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const chatBox = document.getElementById("chat-box");
    const ticketId = <?php echo $current_ticket_id; ?>;
    let lastData = "";

    async function fetchMessages() {
        try {
            const res = await fetch(`get_ticket_messages.php?ticket_id=${ticketId}`);
            const data = await res.json();
            const newHTML = data.map(msg => {
                const isAdmin = msg.sender_role === 'admin';
                const sender = isAdmin ? 'แอดมิน:' : 'คุณ:';
                const cls = isAdmin ? 'admin' : 'user';
                return `
                    <div class="chat-message ${cls}">
                        <strong class="chat-sender-${cls}">${sender}</strong>
                        <p style="margin:5px 0;">${msg.message_text.replace(/\n/g, '<br>')}</p>
                        <span style="font-size:0.7em;color:#666;">${msg.timestamp}</span>
                    </div>
                `;
            }).join('');

            if (newHTML !== lastData) {
                chatBox.innerHTML = newHTML;
                chatBox.scrollTop = chatBox.scrollHeight;
                lastData = newHTML;
            }
        } catch (err) {
            console.error(err);
        }
    }

    fetchMessages();
    setInterval(fetchMessages, 3000);
});
</script>
<?php endif; ?>