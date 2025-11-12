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
$is_admin = isAdmin();
$action = $_REQUEST['action'] ?? '';

if ($action === 'get_messages' && isset($_GET['ticket_id'])) {
    $ticket_id = (int)$_GET['ticket_id'];
    $last_id = (int)($_GET['last_id'] ?? 0); 

    $stmt_check = $conn->prepare("SELECT user_id, status FROM support_tickets WHERE ticket_id = ?");
    $stmt_check->bind_param("i", $ticket_id);
    $stmt_check->execute();
    $ticket_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$ticket_data || (!$is_admin && (int)$ticket_data['user_id'] !== $session_user_id)) {
        $response['message'] = 'Access denied or Ticket not found.';
        echo json_encode($response);
        exit;
    }

    $messages = [];
    $query = "SELECT message_id, sender_role, message_text, timestamp FROM support_messages WHERE ticket_id = ?";
    
    if ($last_id > 0) {
         $query .= " AND message_id > ?";
    }
    $query .= " ORDER BY message_id ASC";
    
    $stmt_msg = $conn->prepare($query);
    if ($stmt_msg === false) {
        $response['message'] = 'Prepare statement failed: ' . $conn->error;
        echo json_encode($response);
        exit;
    }
    
    if ($last_id > 0) {
        $stmt_msg->bind_param("ii", $ticket_id, $last_id);
    } else {
        $stmt_msg->bind_param("i", $ticket_id);
    }
    
    $stmt_msg->execute();
    $result_msg = $stmt_msg->get_result();

    while ($row = $result_msg->fetch_assoc()) {
        $messages[] = [
            'message_id' => $row['message_id'], 
            'sender_role' => $row['sender_role'],
            'message_text' => htmlspecialchars($row['message_text']), 
            'timestamp' => $row['timestamp']
        ];
    }
    $stmt_msg->close();

    $response = [
        'status' => 'success',
        'messages' => $messages,
        'ticket_status' => $ticket_data['status']
    ];
}

elseif ($action === 'send_reply' && isset($_POST['ticket_id'], $_POST['message_text'], $_POST['sender_role'])) {
    $ticket_id = (int)$_POST['ticket_id'];
    $message_text = $_POST['message_text'];
    $sender_role = $_POST['sender_role'];
    $owner_user_id_from_form = (int)$_POST['user_id']; 

    $stmt_check = $conn->prepare("SELECT user_id FROM support_tickets WHERE ticket_id = ?");
    $stmt_check->bind_param("i", $ticket_id);
    $stmt_check->execute();
    $ticket_data = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();

    if (!$ticket_data || (!isAdmin() && (int)$ticket_data['user_id'] !== $session_user_id)) {
        $response['message'] = 'Access denied: You are not authorized to reply to this ticket.';
        echo json_encode($response);
        exit;
    }
    
    if (($sender_role === 'admin' && !isAdmin()) || ($sender_role === 'user' && isAdmin())) {
        $sender_role = isAdmin() ? 'admin' : 'user';
    }


    $conn->begin_transaction();
    try {
        $insert_reply = $conn->prepare("INSERT INTO support_messages (ticket_id, user_id, sender_role, message_text) VALUES (?, ?, ?, ?)");
        if ($insert_reply === false) {
             throw new Exception("Prepare statement failed: " . $conn->error);
        }

        $insert_reply->bind_param("iiss", $ticket_id, $session_user_id, $sender_role, $message_text);
        
        if (!$insert_reply->execute()) {
             throw new Exception("Execute Reply Failed: " . $insert_reply->error);
        }
        $insert_reply->close();
        
        $new_status = ($sender_role === 'admin') ? 'answered' : 'open';
        $update_ticket = $conn->prepare("UPDATE support_tickets SET status = ?, last_updated = NOW() WHERE ticket_id = ?");
        $update_ticket->bind_param("si", $new_status, $ticket_id);
        
        if (!$update_ticket->execute()) {
            throw new Exception("Execute Update Ticket Failed: " . $update_ticket->error);
        }
        $update_ticket->close();
        
        $conn->commit();
        $response = ['status' => 'success', 'message' => 'Message sent.'];
        
    } catch (Exception $e) {
        $conn->rollback();
        $response = ['status' => 'error', 'message' => 'DB Error: ' . $e->getMessage()];
    }
}

echo json_encode($response);
$conn->close();
?>