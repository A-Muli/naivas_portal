<?php
// Database connection details (Adjust these if your server/credentials are different)
$host = "localhost";
$user = "root"; // Default user for XAMPP/WAMP
$pass = "";     // Default password for XAMPP/WAMP
$db   = "naivas_portal"; // Your database name

// Establish the connection
$conn = new mysqli($host, $user, $pass, $db);

// Check for connection errors
if ($conn->connect_error) {
    // If the connection fails, terminate the script and output a JSON error message.
    // NOTE: If you are seeing a "SyntaxError: Unexpected token '<'" error on the client side,
    // it usually means your PHP is printing an HTML error message before this JSON output.
    // Check that your MySQL/MariaDB service is running and the credentials above are 100% correct.
    http_response_code(500);
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

// Optional: Set character set to UTF-8
if (!$conn->set_charset("utf8")) {
    error_log("Error loading character set utf8: " . $conn->error);
}

// Connection is successful, $conn variable is now ready for use in other scripts.
?>