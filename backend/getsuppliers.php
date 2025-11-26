<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
include("db_connect.php");

try {
    // Fetch suppliers
    $sql = "SELECT SupplierName, email, CompanyName, PhoneNumber FROM suppliers ORDER BY SupplierName ASC";
    $result = $conn->query($sql);

    $suppliers = [];

    while ($row = $result->fetch_assoc()) {
        $suppliers[] = [
            "name"    => $row["SupplierName"],
            "email"   => $row["email"],
            "company" => $row["CompanyName"],
            "phone"   => $row["PhoneNumber"]
        ];
    }

    echo json_encode([
        "success" => true,
        "data" => $suppliers
    ]);

    $conn->close();

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}
?>
