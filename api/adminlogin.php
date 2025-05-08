<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

header("Content-Type: application/json");
include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(["success" => false, "message" => "Email and password required."]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        u.user_id,
        u.user_name,
        u.user_email,
        u.password_hash,
        u.role_id,
        u.member_id,
        m.member_image AS user_image
    FROM tk_webapp.users u
    LEFT JOIN tk_webapp.members m ON u.member_id = m.member_id
    WHERE u.user_email = ?
");

$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($user = $result->fetch_assoc()) {
    if (password_verify($password, $user['password_hash'])) {
        unset($user['password_hash']);
        echo json_encode(["success" => true, "user" => $user]);
    } else {
        echo json_encode(["success" => false, "exists" => true, "message" => "Incorrect password."]);
    }
} else {
    echo json_encode(["success" => false, "exists" => false, "message" => "User not found."]);
}
?>
