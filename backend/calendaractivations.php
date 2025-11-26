<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("db_connect.php");

try {
    $sql = "SELECT 
                id,
                supplierEmail,
                productName,
                productCategory,
                productType,
                storeName,
                activationDate,
                no_of_BAs,
                status
            FROM bookings
            WHERE status IN ('pending','approved')";

    $result = $conn->query($sql);

    $events = [];
    while ($row = $result->fetch_assoc()) {
        $events[] = [
            "id" => $row["id"],
            "supplierEmail" => $row["supplierEmail"],
            "productName" => $row["productName"],
            "productCategory" => $row["productCategory"],
            "productType" => $row["productType"],
            "storeName" => $row["storeName"],
            "activationDate" => $row["activationDate"],
            "no_of_BAs" => $row["no_of_BAs"],
            "status" => $row["status"]
        ];
    }

    echo json_encode($events);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>
