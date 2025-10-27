<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);

$name = $data["SupplierName"];
$email = $data["email"];
$phone = $data["PhoneNumber"];
$password = password_hash($data["password"], PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO suppliers (SupplierName, email, PhoneNumber, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $phone, $password);

if ($stmt->execute()) {
  echo json_encode(["success" => true, "message" => "Signup successful!"]);
} else {
  echo json_encode(["success" => false, "message" => "Error: " . $conn->error]);
}

$stmt->close();
$conn->close();
?>
