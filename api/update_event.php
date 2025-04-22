<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON or no data received"]);
    exit;
}

if (!isset($data['event_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "event_id is required"]);
    exit;
}

$event_id = $data['event_id'];
$fields = [];
$params = [];
$types = "";

if (isset($data['title'])) {
    $fields[] = "event_title = ?";
    $params[] = $data['title'];
    $types .= "s";
}
if (isset($data['content'])) {
    $fields[] = "event_content = ?";
    $params[] = $data['content'];
    $types .= "s";
}
if (isset($data['category'])) {
    $fields[] = "event_category = ?";
    $params[] = $data['category'];
    $types .= "s";
}
if (isset($data['event_status'])) {
    $fields[] = "event_status = ?";
    $params[] = $data['event_status'];
    $types .= "s";
}
if (isset($data['image_url'])) {
    $fields[] = "event_image = ?";
    $params[] = $data['image_url'];
    $types .= "s";
}
if (isset($data['event_date'])) {
    $fields[] = "event_date = ?";
    $params[] = $data['event_date'];
    $types .= "s";
}
if (isset($data['event_start_time'])) {
    $fields[] = "event_start_time = ?";
    $params[] = $data['event_start_time'];
    $types .= "s";
}
if (isset($data['event_end_time'])) {
    $fields[] = "event_end_time = ?";
    $params[] = $data['event_end_time'];
    $types .= "s";
}
if (isset($data['event_venue'])) {
    $fields[] = "event_venue = ?";
    $params[] = $data['event_venue'];
    $types .= "s";
}
if (isset($data['event_speakers'])) {
    $fields[] = "event_speakers = ?";
    $params[] = $data['event_speakers'];
    $types .= "s";
}

if (count($fields) === 0) {
    http_response_code(400);
    echo json_encode(["error" => "No fields provided for update"]);
    exit;
}

$sql = "UPDATE tk_webapp.events SET " . implode(", ", $fields) . " WHERE event_id = ?";
$params[] = $event_id;
$types .= "s";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "error" => "Prepare failed",
        "sql" => $sql,
        "params" => $params,
        "sql_error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    http_response_code(500);
    echo json_encode([
        "error" => "Execute failed",
        "sql" => $sql,
        "params" => $params,
        "sql_error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
