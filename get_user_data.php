<?php
require_once 'config.php';
header('Content-Type: application/json');

$response = [
    'logged_in' => false,
    'username' => '',
    'wallet_point' => 0.00
];

if (isLoggedIn()) {
    $user_id = $_SESSION['user_id'];
    
    $sql = "SELECT username, wallet_point, user_role FROM users WHERE user_id = '$user_id'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user_data = $result->fetch_assoc();
        
        $_SESSION['username'] = $user_data['username'];
        $_SESSION['wallet_point'] = $user_data['wallet_point'];
        $_SESSION['user_role'] = $user_data['user_role'];
        
        $response = [
            'logged_in' => true,
            'username' => htmlspecialchars($user_data['username']),
            'wallet_point' => number_format($user_data['wallet_point'], 2),
            'user_role' => $user_data['user_role']
        ];
    }
}

echo json_encode($response);
$conn->close();
?>