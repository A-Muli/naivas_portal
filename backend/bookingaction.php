<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");
include("db_connect.php");

try {
    $data = json_decode(file_get_contents("php://input"), true);

    $bookingId = $data['bookingId'] ?? null;
    $action = strtolower($data['action'] ?? '');

    if (!$bookingId || !in_array($action, ['approve', 'reject', 'cancel'])) {
        throw new Exception("Invalid request. Must send bookingId & action (approve | reject | cancel)");
    }

    // Get booking
    $stmt = $conn->prepare("SELECT id, status FROM bookings WHERE id=?");
    if (!$stmt) throw new Exception("Prepare failed: " . $conn->error);

    $stmt->bind_param("i", $bookingId);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) throw new Exception("Booking not found");

    $booking = $res->fetch_assoc();

    if ($booking['status'] !== 'Pending') 
        throw new Exception("Booking already processed");

    // Determine new status
    $newStatus = match($action) {
        'approve' => 'Approved',
        'reject'  => 'Rejected',
        'cancel'  => 'Cancelled',
        default   => throw new Exception("Unexpected action")
    };

    $update = $conn->prepare("UPDATE bookings SET status=?, reviewed_at=NOW() WHERE id=?");
    if (!$update) throw new Exception("Prepare failed update: " . $conn->error);

    $update->bind_param("si", $newStatus, $bookingId);
    $update->execute();

    // BA visibility
    $setActive = ($newStatus === 'Approved') ? 1 : 0;
    $conn->query("UPDATE brand_ambassadors SET active=$setActive WHERE booking_id=$bookingId");

    echo json_encode([
        "success" => true,
        "message" => "Booking $newStatus successfully",
        "status" => $newStatus
    ]);

} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => $e->getMessage()]);
}
?>
