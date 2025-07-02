<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include '../config/db.php'; // should define $conn

$event_id = $_GET['event_id'] ?? '';
if (!$event_id) {
  echo json_encode(['participants' => []]);
  exit;
}

$sql = "SELECT participant_id, name, email, contact, expectations, created_at FROM participants WHERE event_id = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $event_id); // 's' = string
$stmt->execute();
$result = $stmt->get_result();
$participants = [];

while ($row = $result->fetch_assoc()) {
    $participants[] = $row;
}

echo json_encode(['participants' => $participants]);