<?php
// ----------------------
// CORS FIX (MUST BE FIRST)
// ----------------------
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight request (OPTIONS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
// ----------------------
// END CORS FIX
// ----------------------

include("db_connect.php"); 

try {
    // --- 1. HANDLE POST REQUEST (SAVE BOOKING) ---
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Read JSON data from the request body
        $json_data = file_get_contents("php://input");
        $data = json_decode($json_data, true);

        // Basic validation
        if (empty($data) || !isset($data['supplierEmail'], $data['stores'], $data['productCategory'], $data['activationDate'], $data['brandAmbassadors'])) {
            throw new Exception("Invalid or incomplete data received.");
        }

        // Extract main booking data
        $supplierEmail = $data['supplierEmail'];
        $productCategory = $data['productCategory'];
        $productType = $data['productType'] ?? ''; 
        $productName = $data['productName'] ?? ''; 
        $activationDate = $data['activationDate'];
        $noOfBAs = count($data['brandAmbassadors']);
        $storesList = $data['stores']; // Array of store names
        $brandAmbassadors = $data['brandAmbassadors']; // Array of BA objects

        // Start Transaction
        $conn->begin_transaction();

        // --- 1.1 Insert into `bookings` table ---
        // Set storeName to 'Multiple Stores' or the first store name for summary purposes.
        $mainStoreName = (count($storesList) > 1) ? 'Multiple Stores' : ($storesList[0] ?? 'N/A');

        $stmt = $conn->prepare("INSERT INTO bookings (supplierEmail, storeName, productCategory, productType, productName, activationDate, no_of_BAs, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("ssssssi", $supplierEmail, $mainStoreName, $productCategory, $productType, $productName, $activationDate, $noOfBAs);
        
        if (!$stmt->execute()) {
            throw new Exception("Error inserting main booking: " . $stmt->error);
        }

        $bookingId = $conn->insert_id;
        $stmt->close();
        
        // --- 1.2 Insert into `bookings_stores` Junction Table (NEW LOGIC) ---
        // This is the junction table for the many-to-many relationship.
        $stmtStores = $conn->prepare("INSERT INTO bookings_stores (booking_id, storeName) VALUES (?, ?)");
        foreach ($storesList as $storeName) {
            $stmtStores->bind_param("is", $bookingId, $storeName);
            if (!$stmtStores->execute()) {
                throw new Exception("Error inserting store link into junction table: " . $stmtStores->error);
            }
        }
        $stmtStores->close();

        // --- 1.3 Insert into `brand_ambassadors` table ---
        $stmtBA = $conn->prepare("INSERT INTO brand_ambassadors (booking_id, name, idNumber, phoneNumber, storeAssigned) VALUES (?, ?, ?, ?, ?)");
        foreach ($brandAmbassadors as $ba) {
            $stmtBA->bind_param("issss", $bookingId, $ba['name'], $ba['idNumber'], $ba['phoneNumber'], $ba['storeAssigned']);
            if (!$stmtBA->execute()) {
                throw new Exception("Error inserting Brand Ambassador: " . $stmtBA->error);
            }
        }
        $stmtBA->close();

        // Commit transaction
        $conn->commit();
        
        // Success response
        echo json_encode([
            "success" => true,
            "message" => "Booking submitted successfully! ID: " . $bookingId
        ]);
        
        // CRITICAL FIX: Ensure the script terminates here.
        exit(); 
    } 

    // Handle non-POST requests explicitly (e.g., unauthorized access)
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Method not allowed. Only POST is accepted."]);
    exit();


} catch (Exception $e) {
    if (isset($conn) && $conn->in_transaction) $conn->rollback();
    // Close statements and connection only if they were opened
    if (isset($stmt) && $stmt) $stmt->close();
    if (isset($stmtStores) && $stmtStores) $stmtStores->close();
    if (isset($stmtBA) && $stmtBA) $stmtBA->close();
    if (isset($conn) && $conn) $conn->close();
    
    http_response_code(500); 
    echo json_encode([
        "success" => false,
        "message" => "Server Error: " . $e->getMessage()
    ]);
}
?>