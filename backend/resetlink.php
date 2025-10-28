<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include("db_connect.php");

$data = json_decode(file_get_contents("php://input"), true);
$email = $data["email"] ?? '';

if (!$email) {
    echo json_encode(["success" => false, "message" => "Email required."]);
    exit;
}

$token = bin2hex(random_bytes(16));
$expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

$stmt = $conn->prepare("UPDATE suppliers SET reset_token=?, reset_expires=? WHERE email=?");
$stmt->bind_param("sss", $token, $expires, $email);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    $resetLink = "http://localhost/naivas_portal/newpassword.html?token=$token";
    // Simulated email send:
    echo json_encode(["success" => true, "link" => $resetLink]);
} else {
    echo json_encode(["success" => false, "message" => "Email not found."]);
}
?>
