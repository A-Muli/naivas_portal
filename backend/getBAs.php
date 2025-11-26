<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

include("db_connect.php");

try {
    $input = json_decode(file_get_contents("php://input"), true);
    $email = $input['email'] ?? '';

    // ✅ Prepare query
    if ($email) {
        // Supplier view: show only their BAs
        $sql = "SELECT 
                    ba.id AS ba_id,
                    ba.name, 
                    ba.idNumber, 
                    ba.phoneNumber, 
                    b.storeName, 
                    b.productCategory, 
                    b.productType,
                    b.productName,
                    b.activationDate, 
                    b.status
                FROM brand_ambassadors ba
                INNER JOIN bookings b ON ba.booking_id = b.id
                WHERE b.supplierEmail = ?
                ORDER BY b.activationDate DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Database prepare failed: " . $conn->error);
        $stmt->bind_param("s", $email);
    } else {
        // Admin view: show all BAs
        $sql = "SELECT 
                    ba.id AS ba_id,
                    ba.name, 
                    ba.idNumber, 
                    ba.phoneNumber, 
                    b.supplierEmail,
                    b.storeName, 
                    b.productCategory, 
                    b.productType,
                    b.productName,
                    b.activationDate, 
                    b.status
                FROM brand_ambassadors ba
                INNER JOIN bookings b ON ba.booking_id = b.id
                ORDER BY b.activationDate DESC";
        $stmt = $conn->prepare($sql);
        if (!$stmt) throw new Exception("Database prepare failed: " . $conn->error);
    }

    // ✅ Execute query
    $stmt->execute();
    $result = $stmt->get_result();

    // ✅ Collect results
    $bas = [];
    while ($row = $result->fetch_assoc()) {
        $bas[] = [
            "ba_id"           => $row["ba_id"],
            "name"            => $row["name"],
            "idNumber"        => $row["idNumber"],
            "phoneNumber"     => $row["phoneNumber"],
            "supplierEmail"   => $row["supplierEmail"] ?? null,
            "storeName"       => $row["storeName"],
            "productCategory" => $row["productCategory"],
            "productType"     => $row["productType"],
            "productName"     => $row["productName"],
            "activationDate"  => $row["activationDate"],
            "status"          => ucfirst($row["status"])
        ];
    }

    echo json_encode(["success" => true, "data" => $bas]);

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
