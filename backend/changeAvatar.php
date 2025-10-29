<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

if (!isset($_POST['email']) || !isset($_FILES['avatar'])) {
    echo json_encode(['success' => false, 'message' => 'Email or avatar missing']);
    exit;
}

$email = $_POST['email'];
$avatar = $_FILES['avatar'];

// Validate file type
$allowed = ['jpg','jpeg','png','gif'];
$ext = strtolower(pathinfo($avatar['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowed)) {
    echo json_encode(['success'=>false, 'message'=>'Invalid file type']);
    exit;
}

// Save file
$targetDir = "uploads/avatars/";
if (!is_dir($targetDir)) mkdir($targetDir, 0777, true);

$filename = uniqid() . "." . $ext;
$targetFile = $targetDir . $filename;

if (move_uploaded_file($avatar['tmp_name'], $targetFile)) {
    $stmt = $conn->prepare("UPDATE users SET profilePic=? WHERE email=?");
    $stmt->bind_param("ss", $targetFile, $email);
    if ($stmt->execute()) {
        echo json_encode(['success'=>true, 'avatarUrl'=>$targetFile]);
    } else {
        echo json_encode(['success'=>false, 'message'=>'DB update failed']);
    }
    $stmt->close();
} else {
    echo json_encode(['success'=>false, 'message'=>'Failed to upload file']);
}

$conn->close();
?>
