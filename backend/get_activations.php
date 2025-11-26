<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
include("db_connect.php");

// Get POSTed data
$input = json_decode(file_get_contents("php://input"), true);
$email = $input['email'] ?? '';

if (!$email) {
    echo json_encode(["success" => false, "data" => [], "message" => "No email supplied"]);
    exit;
}

// Fetch activations
$sql = "SELECT productCategory, productName, productType, storeName, activationDate, status 
        FROM bookings 
        WHERE supplierEmail = ? 
        ORDER BY activationDate DESC";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "data" => [], "message" => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

$activations = [];
while ($row = $result->fetch_assoc()) {
    $row['activationDate'] = date("Y-m-d", strtotime($row['activationDate']));
    $activations[] = $row;
}

echo json_encode([
    "success" => true,
    "data" => $activations
]);

$stmt->close();
$conn->close();
?>