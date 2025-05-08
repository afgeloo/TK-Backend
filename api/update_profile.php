<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

$user_id = $data['user_id'] ?? null;
$email = $data['email'] ?? null;
$phone = $data['phone'] ?? null;
$password = $data['password'] ?? null;

if (!$user_id || !$email || (empty($phone) && empty($password))) {
  echo json_encode(["success" => false, "message" => "Missing required fields."]);
  exit;
}

$fields = [];
$params = [];

if (!empty($phone)) {
  $fields[] = "user_contact = ?";
  $params[] = $phone;
}

if (!empty($password)) {
  $fields[] = "password_hash = ?";
  $params[] = password_hash($password, PASSWORD_DEFAULT);
}

if (empty($fields)) {
  echo json_encode(["success" => false, "message" => "No fields to update."]);
  exit;
}

$params[] = $user_id;
$sql = "UPDATE users SET " . implode(", ", $fields) . " WHERE user_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param(str_repeat('s', count($params)), ...$params);

if (!$stmt->execute()) {
  echo json_encode(["success" => false, "message" => "Update failed"]);
  exit;
}

$stmt = $conn->prepare("
  SELECT u.user_id, u.user_name, u.user_email, u.user_contact, m.member_image AS user_image
  FROM users u
  LEFT JOIN members m ON u.member_id = m.member_id
  WHERE u.user_id = ?
");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode(["success" => true, "user" => $user]);
exit;
