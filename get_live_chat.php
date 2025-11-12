<?php
require_once 'config.php'; 
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Invalid Request'];

if (!isLoggedIn()) {
    $response['message'] = 'User not authenticated.';
    echo json_encode($response);
    exit;
}

$session_user_id = $_SESSION['user_id'];
$session_username = $_SESSION['username'];
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_messages') {
    $last_id = (int)($_GET['last_id'] ?? 0);
    $messages = [];
    $query = "SELECT message_id, sender_role, sender_username, message_text, timestamp FROM live_chat WHERE message_id > ? ORDER BY message_id ASC";
    
    $stmt_msg = $conn->prepare($query);
    $stmt_msg->bind_param("i", $last_id);
    $stmt_msg->execute();
    $result_msg = $stmt_msg->get_result();

    while ($row = $result_msg->fetch_assoc()) {
        $messages[] = [
            'message_id' => $row['message_id'], 
            'sender_role' => $row['sender_role'],
            'sender_username' => htmlspecialchars($row['sender_username']), 
            'message_text' => htmlspecialchars($row['message_text']), 
            'timestamp' => $row['timestamp']
        ];
    }
    $stmt_msg->close();

    $response = ['status' => 'success', 'messages' => $messages];
}

elseif ($action === 'send_message' && isset($_POST['message_text'], $_POST['sender_role'])) {
    $message_text = $_POST['message_text'];
    $sender_role = $_POST['sender_role'];
    
    $sender_id = $session_user_id;
    $sender_username = $session_username;
    
    $insert_reply = $conn->prepare("INSERT INTO live_chat (sender_id, sender_username, sender_role, message_text) VALUES (?, ?, ?, ?)");
    $insert_reply->bind_param("isss", $sender_id, $sender_username, $sender_role, $message_text);
    
    if ($insert_reply->execute()) {
        $response = ['status' => 'success', 'message' => 'Message sent.'];
    } else {
        $response = ['status' => 'error', 'message' => 'DB Error: ' . $conn->error];
    }
    $insert_reply->close();
}

echo json_encode($response);
$conn->close();
?>