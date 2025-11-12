<?php
require_once "config.php"; 

if (!isLoggedIn()) {
    http_response_code(403);
    echo json_encode(["error" => "not_logged_in"]);
    exit;
}

$ticket_id = isset($_GET['ticket_id']) ? (int)$_GET['ticket_id'] : 0;

if ($ticket_id <= 0) {
    echo json_encode(["error" => "invalid_ticket"]);
    exit;
}

$stmt = $conn->prepare("SELECT sender_role, message_text, timestamp FROM support_messages WHERE ticket_id = ? ORDER BY timestamp ASC");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();

$messages = [];
while ($row = $result->fetch_assoc()) {
    $messages[] = $row;
}

header('Content-Type: application/json');
echo json_encode($messages);
?>
