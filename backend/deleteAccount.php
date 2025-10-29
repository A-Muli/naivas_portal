<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';

if (!$email) {
    echo json_encode(['success'=>false, 'message'=>'Email required']);
    exit;
}

// Delete user record
$stmt = $conn->prepare("DELETE FROM users WHERE email=?");
$stmt->bind_param("s", $email);

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Account deleted successfully']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Failed to delete account']);
}

$stmt->close();
$conn->close();
?>
