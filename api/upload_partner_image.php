<?php
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

$targetDir = "../../tara-kabataan-webapp/uploads/partners-images/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if (
    !isset($_FILES["image"]) ||
    $_FILES["image"]["error"] !== UPLOAD_ERR_OK ||
    !isset($_POST["partner_id"])
) {
    echo json_encode(["success" => false, "error" => "Missing image or partner_id."]);
    exit;
}

$partnerId = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["partner_id"]);
$extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];

if (!in_array($extension, $allowedExtensions)) {
    echo json_encode(["success" => false, "error" => "Invalid file type."]);
    exit;
}

foreach ($allowedExtensions as $ext) {
    $existingFile = $targetDir . $partnerId . '.' . $ext;
    if (file_exists($existingFile)) {
        unlink($existingFile);
    }
}

$newFileName = $partnerId . '.' . $extension;
$targetFile = $targetDir . $newFileName;

if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
    echo json_encode([
        "success" => true,
        "image_url" => "/tara-kabataan-webapp/uploads/partners-images/" . $newFileName
    ]);
} else {
    echo json_encode(["success" => false, "error" => "Failed to move uploaded file."]);
}
?>
