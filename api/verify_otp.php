<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$otp   = trim($data['otp'] ?? '');

if (!$email || !$otp) {
  echo json_encode(["success" => false, "message" => "Email and OTP are required."]);
  exit;
}

try {
  $pdo = new PDO("mysql:host=localhost;dbname=tk_webapp", "root", "");
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $stmt = $pdo->prepare("SELECT * FROM users WHERE user_email = ?");
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user) {
    echo json_encode(["success" => false, "message" => "Email not registered."]);
    exit;
  }

  $stmt = $pdo->prepare("SELECT otp, expires_at FROM admin_otp WHERE email = ?");
  $stmt->execute([$email]);
  $row = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$row) {
    echo json_encode(["success" => false, "message" => "No OTP found for this email."]);
    exit;
  }

  if (strtotime($row['expires_at']) < time()) {
    echo json_encode(["success" => false, "message" => "OTP has expired."]);
    exit;
  }

  if ($row['otp'] !== $otp) {
    echo json_encode(["success" => false, "message" => "Incorrect OTP."]);
    exit;
  }

  $pdo->prepare("DELETE FROM admin_otp WHERE email = ?")->execute([$email]);

  echo json_encode(["success" => true, "user" => $user]);
} catch (PDOException $e) {
  echo json_encode(["success" => false, "message" => "Server error", "error" => $e->getMessage()]);
}
