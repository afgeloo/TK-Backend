<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

header("Content-Type: application/json");
include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);
$email = $data['email'] ?? '';
$new_password = $data['new_password'] ?? '';

if (!$email || !$new_password) {
    echo json_encode(["success" => false, "message" => "Missing fields."]);
    exit;
}

$new_hash = password_hash($new_password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE tk_webapp.users SET password_hash = ? WHERE user_email = ?");
$stmt->bind_param("ss", $new_hash, $email);

if ($stmt->execute() && $stmt->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Password updated successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "Email not found or update failed."]);
}
?>
