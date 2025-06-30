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
    !isset($input['role_name']) ||
    !is_string($input['role_id']) ||
    !is_string($input['role_name']) ||
    trim($input['role_name']) === ''
) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "role_id and non-empty role_name are required."
    ]);
    exit;
}

$role_id   = $conn->real_escape_string(trim($input['role_id']));
$role_name = trim($input['role_name']);

$sql = "UPDATE tk_webapp.roles
        SET role_name = ?
        WHERE role_id = ?";

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("ss", $role_name, $role_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            // fetch the updated record to return
            $res = $conn->query(
                "SELECT role_id, role_name
                 FROM tk_webapp.roles
                 WHERE role_id = '{$role_id}'"
            );
            $updated = $res->fetch_assoc() ?: null;

            echo json_encode([
                "success" => true,
                "role"    => $updated
            ]);
        } else {
            // no rows changed (maybe same name)
            echo json_encode([
                "success" => true,
                "message" => "No changes detected.",
                "role"    => [
                    "role_id"   => $role_id,
                    "role_name" => $role_name
                ]
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
