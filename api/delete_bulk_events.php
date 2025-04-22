<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/db.php'; 

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['event_ids']) || !is_array($data['event_ids'])) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

$ids = $data['event_ids'];

if (empty($ids)) {
    echo json_encode(["success" => false, "error" => "No event IDs provided"]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "DELETE FROM tk_webapp.events WHERE event_id IN ($placeholders)";
$stmt = $conn->prepare($sql);

if ($stmt->execute($ids)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->errorInfo()]);
}
?>
