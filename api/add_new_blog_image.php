<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$targetDir = "../../tara-kabataan-webapp/uploads/blogs-images/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "No image uploaded or upload error"]);
    exit;
}

$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
$ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $allowedExtensions)) {
    echo json_encode(["success" => false, "error" => "Only JPG, PNG, and GIF files are allowed."]);
    exit;
}

$imageName = uniqid() . "_" . basename($_FILES["image"]["name"]);
$targetFile = $targetDir . $imageName;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
    echo json_encode([
        "success" => true,
        "image_url" => "/tara-kabataan/tara-kabataan-webapp/uploads/blogs-images/" . basename($targetFile)
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Upload failed"]);
}

?>
