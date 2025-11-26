<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
include ("db_connect.php");

try {
    // --- 1. Fetch only bookings where status is 'Pending' ---
    $sql = "SELECT 
                b.id AS booking_id,
                b.supplierEmail,
                b.storeName,
                b.productCategory,
                b.productType,
                b.productName,
                b.activationDate,
                b.no_of_BAs,
                b.status
            FROM bookings b
            WHERE b.status = 'Pending' 
            ORDER BY b.activationDate DESC";

    $result = $conn->query($sql);

    if (!$result) {
        throw new Exception("Database query failed: " . $conn->error);
    }

    $bookings = [];

    // 2. Prepare statements for related data (stores and BAs)
    // Query the junction table: bookings_stores
    $stmtStores = $conn->prepare("SELECT storeName FROM bookings_stores WHERE booking_id = ?");
    
    // Query the brand_ambassadors table
    $stmtBA = $conn->prepare("SELECT name, idNumber, phoneNumber, storeAssigned FROM brand_ambassadors WHERE booking_id = ?");

    if (!$stmtStores || !$stmtBA) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        $bookingId = $row["booking_id"];

        // --- FETCH STORES from Junction Table ---
        $stmtStores->bind_param("i", $bookingId);
        $stmtStores->execute();
        $resStores = $stmtStores->get_result();

        $stores = [];
        while ($store = $resStores->fetch_assoc()) {
            $stores[] = $store["storeName"];
        }
        $resStores->free(); 
        $stmtStores->reset(); 

        // --- FETCH BRAND AMBASSADORS (BA) ---
        $stmtBA->bind_param("i", $bookingId);
        $stmtBA->execute();
        $resBA = $stmtBA->get_result();

        $bas = [];
        while ($ba = $resBA->fetch_assoc()) {
            $bas[] = [
                "name"              => $ba["name"],
                "idNumber"          => $ba["idNumber"],
                "phoneNumber"       => $ba["phoneNumber"],
                "storeAssigned"     => $ba["storeAssigned"] ?? 'N/A' 
            ];
        }
        $resBA->free(); 
        $stmtBA->reset(); 

        // --- BUILD FINAL OBJECT ---
        $bookings[] = [
            "id"                => (int)$row["booking_id"],
            "supplierEmail"     => $row["supplierEmail"],
            "storeName"         => $row["storeName"], 
            "stores"            => $stores,
            "productCategory"   => $row["productCategory"],
            "productType"       => $row["productType"],
            "productName"       => $row["productName"],
            "activationDate"    => $row["activationDate"],
            "no_of_BAs"         => (int)$row["no_of_BAs"],
            "status"            => strtolower($row["status"]),
            "brandAmbassadors"  => $bas
        ];
    }
    $result->free(); 
    $stmtStores->close();
    $stmtBA->close();
    $conn->close();

    // 3. Output results
    echo json_encode($bookings);

} catch (Exception $e) {
    // Clean up connections if an error occurred
    if (isset($stmtStores) && $stmtStores) $stmtStores->close();
    if (isset($stmtBA) && $stmtBA) $stmtBA->close();
    if (isset($conn) && $conn) $conn->close();
    
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Runtime Error: " . $e->getMessage() 
    ]);
}
?>