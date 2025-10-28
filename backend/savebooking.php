<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    include("db_connect.php"); // Ensure this path is correct

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

    // 1️⃣ Check max BAs allowed for store
    $stmtStore = $conn->prepare("SELECT MaxBas FROM stores WHERE storeName = ?");
    if (!$stmtStore) throw new Exception("Database prepare failed (stores): " . $conn->error);
    $stmtStore->bind_param("s", $storeName);
    $stmtStore->execute();
    $resultStore = $stmtStore->get_result();
    if ($resultStore->num_rows === 0) throw new Exception("Store not found.");
    $maxBas = (int)$resultStore->fetch_assoc()["MaxBas"];

    // 2️⃣ Count existing BAs for that store/date/category/type
    $stmtCount = $conn->prepare("SELECT SUM(no_of_BAs) AS totalBAs FROM bookings 
                                 WHERE storeName = ? AND activationDate = ? AND productCategory = ? AND productType = ?");
    if (!$stmtCount) throw new Exception("Database prepare failed (bookings count): " . $conn->error);
    $stmtCount->bind_param("ssss", $storeName, $activationDate, $productCategory, $productType);
    $stmtCount->execute();
    $totalBAsRow = $stmtCount->get_result()->fetch_assoc();
    $totalBAs = (int)$totalBAsRow["totalBAs"];
    $newBAs = count($brandAmbassadors);

    if ($totalBAs + $newBAs > $maxBas) throw new Exception("Booking declined! Max number of Brand Ambassadors reached.");

    // 3️⃣ Check for duplicate booking
    $stmtDup = $conn->prepare("SELECT id FROM bookings 
                               WHERE supplierEmail = ? AND storeName = ? AND activationDate = ? AND productCategory = ? AND productType = ?");
    if (!$stmtDup) throw new Exception("Database prepare failed (duplicate check): " . $conn->error);
    $stmtDup->bind_param("sssss", $supplierEmail, $storeName, $activationDate, $productCategory, $productType);
    $stmtDup->execute();
    if ($stmtDup->get_result()->num_rows > 0) throw new Exception("Duplicate booking detected.");

    // 4️⃣ Insert booking as PENDING
    $status = "pending"; // <-- change here
    $stmtBooking = $conn->prepare("INSERT INTO bookings 
        (supplierEmail, storeName, productCategory, productType, productName, activationDate, no_of_BAs, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmtBooking) throw new Exception("Database prepare failed (insert booking): " . $conn->error);

    $no_of_BAs = $newBAs;
    $stmtBooking->bind_param("ssssssis", $supplierEmail, $storeName, $productCategory, $productType, $productName, $activationDate, $no_of_BAs, $status);
    if (!$stmtBooking->execute()) throw new Exception("Error saving booking: " . $conn->error);

    $bookingId = $stmtBooking->insert_id;

    // 5️⃣ Insert Brand Ambassadors
    if (count($brandAmbassadors) > 0) {
        $stmtBA = $conn->prepare("INSERT INTO brand_ambassadors (booking_id, name, idNumber, phoneNumber) VALUES (?, ?, ?, ?)");
        if (!$stmtBA) throw new Exception("Database prepare failed (brand ambassadors): " . $conn->error);

        foreach ($brandAmbassadors as $ba) {
            $name     = $ba["name"] ?? '';
            $idNumber = $ba["idNumber"] ?? '';
            $phone    = $ba["phoneNumber"] ?? '';

            if (!$name || !$idNumber || !$phone) continue; // skip incomplete BAs
            $stmtBA->bind_param("isss", $bookingId, $name, $idNumber, $phone);
            if (!$stmtBA->execute()) throw new Exception("Error saving Brand Ambassador: " . $conn->error);
        }
        $stmtBA->close();
    }

    echo json_encode(["success" => true, "message" => "Booking successfully submitted and is pending approval!"]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}

// Close statements and connection
if (isset($stmtStore)) $stmtStore->close();
if (isset($stmtCount)) $stmtCount->close();
if (isset($stmtDup)) $stmtDup->close();
if (isset($stmtBooking)) $stmtBooking->close();
$conn->close();
?>
