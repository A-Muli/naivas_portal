<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);
$bookingId = $data["bookingId"];
$action = $data["action"]; // approve or reject

if (!$bookingId || !$action) {
    echo json_encode(["success" => false, "message" => "Invalid request"]);
    exit;
}

$status = ($action == "approve") ? "approved" : "rejected";

// ✅ Update ONLY if it's still pending
$query = $conn->prepare("UPDATE bookings SET status = ? WHERE id = ? AND status = 'pending'");
$query->bind_param("si", $status, $bookingId);
$query->execute();

if ($query->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Status updated"]);
} else {
    echo json_encode(["success" => false, "message" => "This booking has already been processed or not found"]);
}
$conn->close();
?>
