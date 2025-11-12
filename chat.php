<?php
if (!isLoggedIn()) {
    header("location: " . BASE_URL);
    exit;
}

$session_role = $_SESSION['user_role'];
$session_username = $_SESSION['username'];
$session_user_id = $_SESSION['user_id'];
$is_admin = $session_role === 'admin';
$chat_title = $is_admin ? 'Admin Live Chat Dashboard' : 'Live Chat (คุยกับแอดมิน)';
?>

<h1>💬 <?php echo $chat_title; ?></h1>

<div style="max-width: 800px; margin: 0 auto; display: flex; flex-direction: column;">

    <div id="live-chat-box" style="height: 500px; overflow-y: scroll; padding: 15px; border: 1px solid var(--border-color); background-color: var(--bg-content); border-radius: 8px;">
        <p style="text-align: center; color: #999;">กำลังโหลดข้อความ...</p>
    </div>

    <form id="chat-form" onsubmit="sendLiveMessage(event, '<?php echo $session_role; ?>')" action="javascript:void(0)" 
          style="margin-top: 15px; display: flex; gap: 10px; padding: 0;">
        
        <input type="hidden" name="action" value="send_message">
        <input type="hidden" name="sender_id" value="<?php echo $session_user_id; ?>">
        <input type="hidden" name="sender_username" value="<?php echo htmlspecialchars($session_username); ?>">

        <textarea name="message_text" placeholder="พิมพ์ข้อความที่นี่..." rows="2" required 
                  style="flex-grow: 1; resize: none;"></textarea>
        
        <button type="submit" style="padding: 10px 20px; background-color: <?php echo $is_admin ? 'red' : 'var(--accent-color)'; ?>;">
            ส่ง
        </button>
    </form>
</div>

<audio id="chat-alert-sound" src="<?php echo BASE_URL; ?>alert.mp3" preload="auto"></audio> 


<script>
    const liveChatBox = document.getElementById('live-chat-box');
    const chatAlertSound = document.getElementById('chat-alert-sound');
    let liveLastMessageId = 0; 
    let livePollingInterval;
    const currentSessionRole = '<?php echo $session_role; ?>';

    function renderLiveMessages(messages) {
        if (!liveChatBox) return;
        let maxId = liveLastMessageId;
        let newMessagesReceived = false;

        if (liveLastMessageId === 0) {
            liveChatBox.innerHTML = '';
        }

        messages.forEach(msg => {
            const msgId = parseInt(msg.message_id);

            if (msgId > liveLastMessageId) {
                newMessagesReceived = true;
                const is_admin_msg = msg.sender_role === 'admin';
                const sender_class = is_admin_msg ? 'admin' : 'user';
                const sender_name_display = msg.sender_username;

                const messageHtml = `
                    <div class="chat-message ${sender_class}" style="width: 80%; float: ${is_admin_msg ? 'left' : 'right'}; margin-bottom: 20px;">
                        <strong class="chat-sender-${sender_class}">
                            ${sender_class === 'admin' ? 'แอดมิน: ' : 'User: '} (${sender_name_display})
                        </strong>
                        <p style="margin: 5px 0;">${msg.message_text.replace(/\n/g, '<br>')}</p>
                        <span style="font-size: 0.7em; color: #666; display: block; margin-top: 5px;">${msg.timestamp}</span>
                    </div>
                    <div style="clear: both;"></div>`;

                liveChatBox.insertAdjacentHTML('beforeend', messageHtml);

                if (msgId > maxId) {
                    maxId = msgId;
                }
                
                if (msg.sender_role !== currentSessionRole) {
                     chatAlertSound.play().catch(e => console.log("Audio playback blocked:", e));
                }
            }
        });

        if (maxId > liveLastMessageId) {
            liveLastMessageId = maxId;
            liveChatBox.scrollTop = liveChatBox.scrollHeight;
        }
    }

    function fetchLiveMessages() {
        fetch(`get_live_chat.php?action=get_messages&last_id=${liveLastMessageId}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderLiveMessages(data.messages);
                } else {
                    console.error("Failed to fetch live messages:", data.message);
                }
            })
            .catch(error => console.error('Network Error:', error));
    }

    function sendLiveMessage(event, sender_role) {
        event.preventDefault();
        
        const form = document.getElementById('chat-form');
        const textarea = form.querySelector('textarea');
        const messageText = textarea.value.trim();
        if (messageText === '') return;

        const formData = new FormData(form);
        formData.append('sender_role', sender_role);

        fetch('get_live_chat.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                textarea.value = ''; 
                fetchLiveMessages(); 
            } else {
                alert("เกิดข้อผิดพลาดในการส่งข้อความ: " + data.message);
            }
        })
        .catch(error => alert("Network error: " + error));
    }

    fetchLiveMessages(); 
    livePollingInterval = setInterval(fetchLiveMessages, 3000); 
    
    window.addEventListener('beforeunload', () => {
        if (livePollingInterval) {
            clearInterval(livePollingInterval);
        }
    });
</script>