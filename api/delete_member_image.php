<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['image_url'])) {
    echo json_encode(["success" => false, "message" => "Image URL not provided."]);
    exit;
}

$imagePath = '../../' . ltrim($data['image_url'], '/'); 

if (file_exists($imagePath)) {
    if (unlink($imagePath)) {
        echo json_encode(["success" => true, "message" => "Image deleted."]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to delete image."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Image file does not exist."]);
}
