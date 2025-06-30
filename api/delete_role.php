<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

// read the raw POST body
$input = json_decode(file_get_contents('php://input'), true);

if (
    !isset($input['role_id']) ||
    !is_string($input['role_id']) ||
    trim($input['role_id']) === ''
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "role_id is required."
    ]);
    exit;
}

$role_id = $conn->real_escape_string(trim($input['role_id']));

// optionally: check if role is in use before deletion
// e.g. SELECT COUNT(*) FROM tk_webapp.members WHERE role_id = $role_id

$sql = "DELETE FROM tk_webapp.roles WHERE role_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("s", $role_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode([
                "success" => true,
                "message" => "Role deleted."
            ]);
        } else {
            // no such role
            http_response_code(404);
            echo json_encode([
                "success" => false,
                "message" => "Role not found."
            ]);
        }
    } else {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => "Execute failed: " . $stmt->error
        ]);
    }

    $stmt->close();
} else {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Prepare failed: " . $conn->error
    ]);
}

$conn->close();
