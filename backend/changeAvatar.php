<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
include("db_connect.php");

if(!isset($_POST['email']) || !isset($_FILES['avatar'])){
    echo json_encode(["success"=>false,"message"=>"Email or avatar missing"]);
    exit;
}

$email = $_POST['email'];
$avatar = $_FILES['avatar'];

$allowed = ["jpg","jpeg","png","gif"];
$ext = strtolower(pathinfo($avatar["name"], PATHINFO_EXTENSION));

if(!in_array($ext, $allowed)){
    echo json_encode(["success"=>false,"message"=>"Invalid file type"]);
    exit;
}

$targetDir = "../uploads/avatars/"; // ✅ correct folder path from backend
if(!is_dir($targetDir)) mkdir($targetDir, 0777, true);

$filename = uniqid() . "." . $ext;
$targetFile = $targetDir . $filename;

if(move_uploaded_file($avatar["tmp_name"], $targetFile)){

    $dbPath = "uploads/avatars/" . $filename; // ✅ what is stored in DB

    $stmt = $conn->prepare("UPDATE suppliers SET profilePic=? WHERE email=?");
    $stmt->bind_param("ss", $dbPath, $email);
    $stmt->execute();
    $stmt->close();

    echo json_encode(["success"=>true,"avatarUrl"=>$dbPath]);
} else {
    echo json_encode(["success"=>false,"message"=>"Failed to upload file"]);
}

$conn->close();
?>
