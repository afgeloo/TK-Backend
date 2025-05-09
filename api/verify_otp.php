<?php
include '../config/db.php'; // Uses the existing $conn

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

// Escape for safety
$emailEscaped = $conn->real_escape_string($email);

try {
  // Fetch user
  $userQuery = "SELECT * FROM users WHERE user_email = ?";
  $stmt = $conn->prepare($userQuery);
  $stmt->bind_param("s", $emailEscaped);
  $stmt->execute();
  $userResult = $stmt->get_result();

  if ($userResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Email not registered."]);
    exit;
  }

  $user = $userResult->fetch_assoc();

  // Fetch OTP
  $otpQuery = "SELECT otp, expires_at FROM admin_otp WHERE email = ?";
  $stmt = $conn->prepare($otpQuery);
  $stmt->bind_param("s", $emailEscaped);
  $stmt->execute();
  $otpResult = $stmt->get_result();

  if ($otpResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "No OTP found for this email."]);
    exit;
  }

  $row = $otpResult->fetch_assoc();

  if (strtotime($row['expires_at']) < time()) {
    echo json_encode(["success" => false, "message" => "OTP has expired."]);
    exit;
  }

  if ($row['otp'] !== $otp) {
    echo json_encode(["success" => false, "message" => "Incorrect OTP."]);
    exit;
  }

  // Delete OTP after success
  $deleteStmt = $conn->prepare("DELETE FROM admin_otp WHERE email = ?");
  $deleteStmt->bind_param("s", $emailEscaped);
  $deleteStmt->execute();

  echo json_encode(["success" => true, "user" => $user]);
} catch (Exception $e) {
  echo json_encode([
    "success" => false,
    "message" => "Server error",
    "error" => $e->getMessage()
  ]);
}
