<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$targetDir = "../../uploads/partners-images/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(["success" => false, "error" => "No image uploaded or upload error"]);
    exit;
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($_FILES['image']['type'], $allowedTypes)) {
    echo json_encode(["success" => false, "error" => "Only JPG, PNG, and GIF files are allowed."]);
    exit;
}

$imageName = uniqid("partner_") . "_" . basename($_FILES["image"]["name"]);
$targetFile = $targetDir . $imageName;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
    echo json_encode([
        "success" => true,
        "image_url" => "/tara-kabataan-webapp/uploads/partners-images/" . $imageName
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Upload failed"]);
}
?>
