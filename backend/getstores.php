<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

// Fetch all stores
$sql = "SELECT storeName, region FROM stores ORDER BY region, storeName";
$result = $conn->query($sql);

$stores = [];
while ($row = $result->fetch_assoc()) {
  $stores[] = $row;
}

echo json_encode($stores);
$conn->close();
?>
