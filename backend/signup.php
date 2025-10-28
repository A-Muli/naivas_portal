<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// Prevent PHP from sending HTML error messages
ini_set('display_errors', 0);
error_reporting(E_ALL);

include("db_connect.php");

try {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        throw new Exception("Invalid or missing data.");
    }

    $name = $data["SupplierName"] ?? '';
    $email = $data["email"] ?? '';
    $company = $data["CompanyName"] ?? '';
    $phone = $data["PhoneNumber"] ?? '';
    $password = $data["password"] ?? '';

    if (!$name || !$email || !$company || !$phone || !$password) {
        throw new Exception("All fields are required.");
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Fixed: correct number of placeholders (5)
    $stmt = $conn->prepare("
        INSERT INTO suppliers (SupplierName, email, CompanyName, PhoneNumber, password)
        VALUES (?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("sssss", $name, $email, $company, $phone, $hashedPassword);

    if (!$stmt->execute()) {
        throw new Exception("Error saving supplier: " . $stmt->error);
    }

    echo json_encode(["success" => true, "message" => "Signup successful!"]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
