<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$name = $data['name'] ?? '';
$phone = $data['phone'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$name || !$phone) {
    echo json_encode(['success'=>false, 'message'=>'Required fields missing']);
    exit;
}

if ($password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET SupplierName=?, phone=?, password=? WHERE email=?");
    $stmt->bind_param("ssss", $name, $phone, $hashedPassword, $email);
} else {
    $stmt = $conn->prepare("UPDATE users SET SupplierName=?, phone=? WHERE email=?");
    $stmt->bind_param("sss", $name, $phone, $email);
}

if ($stmt->execute()) {
    echo json_encode(['success'=>true, 'message'=>'Profile updated successfully']);
} else {
    echo json_encode(['success'=>false, 'message'=>'Failed to update profile']);
}

$stmt->close();
$conn->close();
?>
