<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    include("db_connect.php");

    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) throw new Exception("Invalid data received.");

    $supplierEmail   = $data["supplierEmail"] ?? '';
    $storeName       = $data["storeName"] ?? '';
    $productCategory = $data["productCategory"] ?? '';
    $productType     = $data["productType"] ?? '';
    $productName     = $data["productName"] ?? '';
    $activationDate  = $data["activationDate"] ?? '';
    $brandAmbassadors = $data["brandAmbassadors"] ?? [];

    if (!$supplierEmail || !$storeName || !$productCategory || !$productType || !$productName || !$activationDate) {
        throw new Exception("All booking fields are required.");
    }

    // ✅ 1. Check max BAs allowed for store
    $stmtStore = $conn->prepare("SELECT MaxBas, current_bas FROM stores WHERE storeName = ?");
    $stmtStore->bind_param("s", $storeName);
    $stmtStore->execute();
    $resultStore = $stmtStore->get_result();
    
    if ($resultStore->num_rows === 0) throw new Exception("Store not found.");
    
    $storeData = $resultStore->fetch_assoc();
    $maxBas = (int)$storeData["MaxBas"];
    $currentBas = (int)$storeData["current_bas"];

    // ❌ Store does not accept BAs
    if ($maxBas == 0) throw new Exception("This store does not accept BAs.");

    // ✅ Calculate new BA count
    $newBAs = count($brandAmbassadors);

    // ✅ 2. Count BAs already approved or pending
    $stmtCount = $conn->prepare("
        SELECT SUM(no_of_BAs) AS totalBAs 
        FROM bookings 
        WHERE storeName = ? AND activationDate = ? 
        AND (status = 'approved' OR status = 'pending')
    ");
    $stmtCount->bind_param("ss", $storeName, $activationDate);
    $stmtCount->execute();
    $totalBAs = (int)$stmtCount->get_result()->fetch_assoc()["totalBAs"];

    if ($totalBAs + $newBAs > $maxBas) {
        throw new Exception("Store capacity reached! No more BAs allowed for this date.");
    }

    // ✅ 3. Prevent duplicate booking (same supplier, same store, same date)
    $stmtDup = $conn->prepare("
        SELECT id FROM bookings 
        WHERE supplierEmail = ? AND storeName = ? AND activationDate = ?
    ");
    $stmtDup->bind_param("sss", $supplierEmail, $storeName, $activationDate);
    $stmtDup->execute();

    if ($stmtDup->get_result()->num_rows > 0) {
        throw new Exception("You already booked this store for that date.");
    }

    // ✅ 4. Insert booking with PENDING status
    $status = "pending";
    $stmtBooking = $conn->prepare("
        INSERT INTO bookings 
        (supplierEmail, storeName, productCategory, productType, productName, activationDate, no_of_BAs, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $no_of_BAs = $newBAs;
    $stmtBooking->bind_param("ssssssis", 
        $supplierEmail, $storeName, $productCategory, $productType, 
        $productName, $activationDate, $no_of_BAs, $status
    );
    $stmtBooking->execute();

    $bookingId = $stmtBooking->insert_id;

    // ✅ 5. Insert Brand Ambassadors for booking
    if ($newBAs > 0) {
        $stmtBA = $conn->prepare("
            INSERT INTO brand_ambassadors (booking_id, name, idNumber, phoneNumber)
            VALUES (?, ?, ?, ?)
        ");

        foreach ($brandAmbassadors as $ba) {
            $name = $ba["name"] ?? '';
            $idNumber = $ba["idNumber"] ?? '';
            $phone = $ba["phoneNumber"] ?? '';

            if (!$name || !$idNumber || !$phone) continue;

            $stmtBA->bind_param("isss", $bookingId, $name, $idNumber, $phone);
            $stmtBA->execute();
        }
        $stmtBA->close();
    }

    echo json_encode([
        "success" => true,
        "message" => "Booking submitted and pending approval!"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "message" => $e->getMessage()
    ]);
}

// Cleanup
if (isset($stmtStore)) $stmtStore->close();
if (isset($stmtCount)) $stmtCount->close();
if (isset($stmtDup)) $stmtDup->close();
if (isset($stmtBooking)) $stmtBooking->close();
$conn->close();
?>
