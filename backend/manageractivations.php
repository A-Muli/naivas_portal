<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
include("db_connect.php");

// ✅ Ensure store name is passed
if (!isset($_GET['store']) || empty($_GET['store'])) {
    echo json_encode(["success" => false, "message" => "Store name required"]);
    exit;
}

$storeName = $_GET['store'];

// ✅ Fetch activations for that store only
$sql = "
    SELECT 
        id,
        supplierEmail,
        productName,
        productCategory,
        productType,
        activationDate,
        no_of_BAs,
        status
    FROM bookings
    WHERE storeName = ?
    ORDER BY activationDate ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $storeName);
$stmt->execute();
$result = $stmt->get_result();

$activations = [];

while ($row = $result->fetch_assoc()) {
    $activations[] = $row;
}

echo json_encode($activations);

$stmt->close();
$conn->close();
?>
