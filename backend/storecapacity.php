<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include "db_connect.php";

$data = json_decode(file_get_contents("php://input"), true);
$store = $data["store"];
$date = $data["date"];

$storeQuery = $conn->prepare("SELECT MaxBas FROM stores WHERE storeName=?");
$storeQuery->bind_param("s", $store);
$storeQuery->execute();
$storeResult = $storeQuery->get_result()->fetch_assoc();
$MaxBas = (int)$storeResult["MaxBas"];

$bookingQuery = $conn->prepare("
  SELECT SUM(JSON_LENGTH(no_of_BAs)) AS total
  FROM bookings
  WHERE storeName=? AND activationDate=?
");
$bookingQuery->bind_param("ss", $store, $date);
$bookingQuery->execute();
$total = $bookingQuery->get_result()->fetch_assoc()["total"] ?? 0;

echo json_encode([
  "current" => (int)$total,
  "max" => (int)$MaxBas
]);
