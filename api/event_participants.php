<?php
// Allow CORS and preflight
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

header("Content-Type: application/json");

// adjust path as needed
require_once '../config/db.php'; 
// $conn = new mysqli($host, $user, $pass, $db); // from your config

// 1) Decode JSON
$raw = file_get_contents("php://input");
$data = json_decode($raw, true);
if (json_last_error() !== JSON_ERROR_NONE) {
  http_response_code(400);
  echo json_encode([
    "error"   => "Invalid JSON",
    "message" => json_last_error_msg()
  ]);
  exit;
}

// 2) Validate
foreach (['event_id','name','email'] as $f) {
  if (empty($data[$f])) {
    http_response_code(400);
    echo json_encode(["error"=>"Missing $f"]);
    exit;
  }
}

$eventId      = $conn->real_escape_string($data['event_id']);
$name         = $conn->real_escape_string($data['name']);
$email        = $conn->real_escape_string($data['email']);
$contact      = isset($data['contact'])
                ? $conn->real_escape_string($data['contact'])
                : null;
$expectations = isset($data['expectations'])
                ? $conn->real_escape_string($data['expectations'])
                : null;

// 3) Insert participant
$sql = "
  INSERT INTO participants
    (event_id, name, email, contact, expectations)
  VALUES
    (?, ?, ?, ?, ?)
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  http_response_code(500);
  echo json_encode(["error"=>"Prepare failed (insert)"]);
  exit;
}
$stmt->bind_param(
  "sssss",
  $eventId,
  $name,
  $email,
  $contact,
  $expectations
);
if (!$stmt->execute()) {
  http_response_code(500);
  echo json_encode([
    "error"   => "DB insert failed",
    "message" => $stmt->error
  ]);
  exit;
}
$stmt->close();

// 4) Success
echo json_encode(["success" => true]);
