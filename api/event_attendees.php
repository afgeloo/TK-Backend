<?php
// participants.php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header('Content-Type: application/json');

include '/../config/db.php';

$event_id = $_GET['event_id'] ?? '';
if (! $event_id) {
  echo json_encode(['participants'=>[]]);
  exit;
}

$sql = "SELECT participant_id, name, email, contact, expectations, created_at
        FROM event_participants_view
        WHERE event_id = ?
        ORDER BY created_at DESC
        ";
$stmt = $pdo->prepare($sql);
$stmt->execute([$event_id]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['participants' => $participants]);
