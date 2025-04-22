<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../config/db.php'; 

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['event_id'])) {
    echo json_encode(["success" => false, "message" => "Missing event_id"]);
    exit;
}

$event_id = $data['event_id'];

$stmt = $conn->prepare("SELECT event_image, event_content FROM tk_webapp.events WHERE event_id = ?");
$stmt->bind_param("s", $event_id);
$stmt->execute();
$result = $stmt->get_result();
$event = $result->fetch_assoc();

if (!$event) {
    echo json_encode(["success" => false, "message" => "Event not found"]);
    exit;
}

$imagesToDelete = [];

if (!empty($event['event_image'])) {
    $imagesToDelete[] = $_SERVER['DOCUMENT_ROOT'] . $event['event_image'];
}

if (preg_match_all('/<img[^>]+src="([^">]+)"/', $event['event_content'], $matches)) {
    foreach ($matches[1] as $imgPath) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($imgPath, PHP_URL_PATH);
        $imagesToDelete[] = $fullPath;
    }
}

foreach ($imagesToDelete as $imgPath) {
    if (file_exists($imgPath)) {
        unlink($imgPath);
    }
}

$deleteStmt = $conn->prepare("DELETE FROM tk_webapp.events WHERE event_id = ?");
$deleteStmt->bind_param("s", $event_id);
$deleteStmt->execute();

echo json_encode(["success" => true]);
?>
