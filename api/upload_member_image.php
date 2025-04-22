<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

$targetDir = "../../tara-kabataan-webapp/uploads/members-images/";
if (!file_exists($targetDir)) {
    mkdir($targetDir, 0755, true);
}

if (isset($_FILES['image']) && isset($_POST['member_id'])) {
    $image = $_FILES['image'];
    $memberId = preg_replace('/[^a-zA-Z0-9\-]/', '', $_POST['member_id']);
    $extension = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));

    $existingExtensions = ['jpg', 'jpeg', 'png'];
    foreach ($existingExtensions as $ext) {
        $existingFile = $targetDir . $memberId . '.' . $ext;
        if (file_exists($existingFile)) {
            unlink($existingFile); 
        }
    }

    $newFileName = $memberId . '.' . $extension;
    $imagePath = $targetDir . $newFileName;

    if (move_uploaded_file($image['tmp_name'], $imagePath)) {
        echo json_encode([
            "success" => true,
            "image_url" => "/tara-kabataan-webapp/uploads/members-images/" . $newFileName
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to save image."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Missing image or member_id."]);
}
?>
