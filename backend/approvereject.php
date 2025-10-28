<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("db_connect.php");

try {
    // Decode JSON input
    $input = json_decode(file_get_contents("php://input"), true);
    $bookingId = $input['bookingId'] ?? null;
    $action = strtolower(trim($input['action'] ?? ''));

    if (!$bookingId || !in_array($action, ['approve', 'reject'])) {
        throw new Exception("Invalid request. Provide bookingId and valid action ('approve' or 'reject').");
    }

    // Verify booking exists
    $checkStmt = $conn->prepare("SELECT id, status FROM bookings WHERE id = ?");
    if (!$checkStmt) throw new Exception("Database prepare failed (check booking): " . $conn->error);
    $checkStmt->bind_param("i", $bookingId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Booking not found.");
    }

    $booking = $result->fetch_assoc();
    if ($booking['status'] !== 'pending') {
        throw new Exception("This booking has already been processed.");
    }

    // Update booking status
    $newStatus = ($action === 'approve') ? 'approved' : 'rejected';
    $updateStmt = $conn->prepare("UPDATE bookings SET status = ?, reviewed_at = NOW() WHERE id = ?");
    if (!$updateStmt) throw new Exception("Database prepare failed (update status): " . $conn->error);
    $updateStmt->bind_param("si", $newStatus, $bookingId);

    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update booking: " . $conn->error);
    }

    // Optionally, update related Brand Ambassadors' visibility if approved
    if ($newStatus === 'approved') {
        $conn->query("UPDATE brand_ambassadors SET active = 1 WHERE booking_id = " . intval($bookingId));
    } else {
        $conn->query("UPDATE brand_ambassadors SET active = 0 WHERE booking_id = " . intval($bookingId));
    }

    echo json_encode([
        "success" => true,
        "message" => "Booking has been successfully " . $newStatus . ".",
        "status"  => $newStatus
    ]);

    // Cleanup
    $checkStmt->close();
    $updateStmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
