<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

try {
    // ✅ Fetch all bookings (latest first)
    $sql = "SELECT 
                id AS booking_id,
                supplierEmail,
                storeName,
                productCategory,
                productType,
                productName,
                activationDate,
                no_of_BAs,
                status
            FROM bookings
            ORDER BY activationDate DESC";

    $result = $conn->query($sql);
    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $bookings = [];

    while ($row = $result->fetch_assoc()) {
        $bookingId = $row["booking_id"];

        // ✅ Fetch related Brand Ambassadors for each booking
        $stmtBA = $conn->prepare("SELECT name, idNumber, phoneNumber FROM brand_ambassadors WHERE booking_id = ?");
        if (!$stmtBA) {
            throw new Exception("Prepare failed (brand ambassadors): " . $conn->error);
        }

        $stmtBA->bind_param("i", $bookingId);
        $stmtBA->execute();
        $resBA = $stmtBA->get_result();

        $bas = [];
        while ($ba = $resBA->fetch_assoc()) {
            $bas[] = [
                "name"        => $ba["name"],
                "idNumber"    => $ba["idNumber"],
                "phoneNumber" => $ba["phoneNumber"]
            ];
        }

        $stmtBA->close();

        // ✅ Add booking data with nested BAs
        $bookings[] = [
            "id"              => (int)$row["booking_id"],
            "supplierEmail"   => $row["supplierEmail"],
            "storeName"       => $row["storeName"],
            "productCategory" => $row["productCategory"],
            "productType"     => $row["productType"],
            "productName"     => $row["productName"],
            "activationDate"  => $row["activationDate"],
            "no_of_BAs"       => (int)$row["no_of_BAs"],
            "status"          => strtolower($row["status"]),
            "brandAmbassadors"=> $bas
        ];
    }

    echo json_encode($bookings);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

$conn->close();
?>
