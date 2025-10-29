<?php
header("Content-Type: application/json; charset=UTF-8");
include "db_connect.php";

// Show PHP errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Query stores table
$query = "SELECT storeName, region FROM stores ORDER BY storeName ASC";
$result = $conn->query($query);

if (!$result) {
    echo json_encode([
        "success" => false,
        "message" => "DB Error",
        "error"   => $conn->error
    ]);
    exit;
}

$stores = [];
while ($row = $result->fetch_assoc()) {
    $stores[] = [
        "storeName" => $row["storeName"],
        "region"    => $row["region"],
    //    "MaxBas" => (int)$row["MaxBas"],
      //  "current_bas" => (int)$row["current_bas"]
    ];
}

echo json_encode([
    "success" => true,
    "data" => $stores
]);
?>
