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

$targetDir = "../../tara-kabataan-webapp/uploads/blogs-images/";
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

if ($_FILES["image"]["error"] === UPLOAD_ERR_OK) {
    $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
    $i = 1;
    do {
        $newName = "blogs." . $i . "." . $extension;
        $targetFile = $targetDir . $newName;
        $i++;
    } while (file_exists($targetFile));

    if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
        $fullPath = realpath($targetFile);
        $mime = mime_content_type($fullPath);
        $base64 = base64_encode(file_get_contents($fullPath));
        $base64Image = "data:$mime;base64,$base64";
    
        echo json_encode([
            "success" => true,
            "image_url" => "/tara-kabataan/tara-kabataan-webapp/uploads/blogs-images/" . basename($targetFile),
            "base64" => $base64Image
        ]);
        exit;
    }    
}

echo json_encode(["success" => false, "error" => "Upload failed."]);
