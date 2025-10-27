<?php
$host = "localhost";
$user = "root"; // default for XAMPP
$pass = "";     // leave empty unless you set a password
$db   = "naivas_portal";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
  die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}
?>
