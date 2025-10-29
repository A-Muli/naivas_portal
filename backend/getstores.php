<?php
<<<<<<< HEAD
include "db_connect.php";

$query = "SELECT storeName, region, MaxBas, current_bas FROM stores"; 
$result = $conn->query($query);

$stores = [];

while($row = $result->fetch_assoc()) {
    $stores[] = [
        "storeName" => $row["storeName"],
        "region" => $row["region"],
        "MaxBas" => (int)$row["MaxBas"],
        "current_bas" => (int)$row["current_bas"]
    ];
}

echo json_encode($stores);
=======
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
>>>>>>> 2dadd2d40fee14ef98360b1dc9f671f4539368ba
?>
