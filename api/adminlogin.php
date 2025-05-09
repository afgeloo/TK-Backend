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
$contact = $data['phone'] ?? ''; 
$password = $data['password'] ?? '';

if ((!$email && !$contact) || !$password) {
    echo json_encode(["success" => false, "message" => "Email or phone and password required."]);
    exit;
}

try {
    if ($email) {
        $stmt = $conn->prepare("
            SELECT u.user_id, u.user_name, u.user_email, u.user_contact,
                   u.password_hash, u.role_id, u.member_id,
                   m.member_image AS user_image
            FROM tk_webapp.users u
            LEFT JOIN tk_webapp.members m ON u.member_id = m.member_id
            WHERE u.user_email = ?
        ");
        $stmt->bind_param("s", $email);
    } else {
        $stmt = $conn->prepare("
            SELECT u.user_id, u.user_name, u.user_email, u.user_contact,
                   u.password_hash, u.role_id, u.member_id,
                   m.member_image AS user_image
            FROM tk_webapp.users u
            LEFT JOIN tk_webapp.members m ON u.member_id = m.member_id
            WHERE u.user_contact = ?
        ");
        $stmt->bind_param("s", $contact);
    }

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
} catch (Throwable $e) {
    echo json_encode(["success" => false, "message" => "Server error", "error" => $e->getMessage()]);
}
?>
