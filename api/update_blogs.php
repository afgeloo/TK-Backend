<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON or no data received"]);
    exit;
}

if (!isset($data['blog_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "blog_id is required"]);
    exit;
}

$blog_id = $data['blog_id'];
$fields = [];
$params = [];
$types = "";

if (isset($data['title'])) {
    $fields[] = "blog_title = ?";
    $params[] = $data['title'];
    $types .= "s";
}
if (isset($data['content'])) {
    $fields[] = "blog_content = ?";
    $params[] = $data['content'];
    $types .= "s";
}
if (isset($data['category'])) {
    $fields[] = "blog_category = ?";
    $params[] = $data['category'];
    $types .= "s";
}
if (isset($data['blog_status'])) {
    $fields[] = "blog_status = ?";
    $params[] = $data['blog_status'];
    $types .= "s";
}
if (isset($data['image_url'])) {
    $fields[] = "blog_image = ?";
    $params[] = $data['image_url'];
    $types .= "s";
}

if (count($fields) === 0) {
    http_response_code(400);
    echo json_encode(["error" => "No fields provided for update"]);
    exit;
}

$fields[] = "updated_at = CURRENT_TIMESTAMP";

$params[] = $blog_id;
$types .= "s";

$sql = "UPDATE tk_webapp.blogs SET " . implode(", ", $fields) . " WHERE blog_id = ?";
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
