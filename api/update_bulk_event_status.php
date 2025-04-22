<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['event_ids']) || !isset($data['new_status'])) {
    echo json_encode(["success" => false, "error" => "Missing parameters"]);
    exit;
}

$ids = $data['event_ids'];
$status = strtoupper($data['new_status']);

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "UPDATE tk_webapp.events SET event_status = ? WHERE event_id IN ($placeholders)";
$stmt = $conn->prepare($sql);

$params = array_merge([$status], $ids);

if ($stmt->execute($params)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->errorInfo()]);
}
?>
