<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header("Content-Type: application/json");

include '../config/db.php';

$input = json_decode(file_get_contents('php://input'), true);
$role_name = trim($input['role_name'] ?? '');

if ($role_name === '') {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "role_name is required"]);
    exit;
}

$stmt = $conn->prepare("INSERT INTO tk_webapp.roles (role_name) VALUES (?)");
$stmt->bind_param("s", $role_name);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Database error: " . $stmt->error
    ]);
    exit;
}

$newId = $stmt->insert_id;
$stmt->close();
$conn->close();

echo json_encode([
    "success"   => true,
    "role"      => [
        "role_id"   => (string)$newId,
        "role_name" => $role_name
    ]
]);
