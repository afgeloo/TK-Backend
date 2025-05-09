<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['email']) || !isset($data['old_password'])) {
    echo json_encode(["success" => false, "message" => "Missing email or old password"]);
    exit;
}

$email = trim($data['email']);
$oldPassword = $data['old_password'];

$sql = "SELECT password_hash FROM tk_webapp.users WHERE user_email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "User not found"]);
    exit;
}

$user = $result->fetch_assoc();
$existingHash = $user['password_hash'];
$isCorrect = password_verify($oldPassword, $existingHash);

echo json_encode([
    "success" => true,
    "valid" => $isCorrect
]);

$stmt->close();
$conn->close();
