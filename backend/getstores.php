<?php
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
?>
