<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
include ("db_connect.php");

// Query stores table
$query = "SELECT storeName, region FROM stores ORDER BY storeName ASC";
$result = $conn->query($query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "Database query failed",
        "error"   => $conn->error
    ]);
    exit;
}

$stores = [];
while ($row = $result->fetch_assoc()) {
    $stores[] = [
        "storeName" => $row["storeName"],
        "region" => $row["region"]
    ];
}

echo json_encode([
    "success" => true,
    "data" => $stores
]);
?>
