<?php
require __DIR__ . '/../vendor/autoload.php';
include '../config/db.php'; // use $conn from your db.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST");
header("Content-Type: application/json");

$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');

if (!$email) {
  echo json_encode(["success" => false, "message" => "Email is required"]);
  exit;
}

$otp = rand(100000, 999999);
$expires_at = date("Y-m-d H:i:s", strtotime("+5 minutes"));

// Verify email in users table
$emailEscaped = $conn->real_escape_string($email);
$checkUser = $conn->prepare("SELECT * FROM users WHERE user_email = ?");
$checkUser->bind_param("s", $emailEscaped);
$checkUser->execute();
$userResult = $checkUser->get_result();

if ($userResult->num_rows === 0) {
  echo json_encode(["success" => false, "message" => "Email not registered in users table."]);
  exit;
}

// Delete existing OTPs for this email
$deleteOtp = $conn->prepare("DELETE FROM admin_otp WHERE email = ?");
$deleteOtp->bind_param("s", $emailEscaped);
$deleteOtp->execute();

// Insert new OTP
$otp_id = uniqid("otp_", true);
$insertOtp = $conn->prepare("INSERT INTO admin_otp (otp_id, email, otp, expires_at) VALUES (?, ?, ?, ?)");
$insertOtp->bind_param("ssss", $otp_id, $emailEscaped, $otp, $expires_at);

if (!$insertOtp->execute()) {
  echo json_encode(["success" => false, "message" => "Failed to store OTP."]);
  exit;
}

// Send email
try {
  $mail = new PHPMailer(true);
  $mail->isSMTP();
  $mail->Host = 'smtp.gmail.com';
  $mail->SMTPAuth = true;
  $mail->Username = 'afgeloo@gmail.com';
  $mail->Password = 'ujgj kyjo enqv ziwt';
  $mail->SMTPSecure = 'tls';
  $mail->Port = 587;

  $mail->setFrom('afgeloo@gmail.com', 'Tara Kabataan');
  $mail->addAddress($email);
  $mail->isHTML(true);
  $mail->Subject = 'Your OTP Code';
  $mail->Body = "
    <div style='font-family: Arial, sans-serif; color: #333;'>
      <img src=\"https://imgur.com/Rx2TW4K.jpg\" 
          alt=\"Tara Kabataan Cover\" 
          style=\"width: 100%; max-height: 200px; object-fit: cover; border-radius: 8px; margin-bottom: 20px;\">
      <h2 style='color: #4DB1E3;'>Tara Kabataan Admin Panel</h2>
      <p>Hello,</p>
      <p>We received a request to log in to your admin account. Please use the following one-time password (OTP):</p>
      <p style='font-size: 24px; font-weight: bold; color: #000;'>$otp</p>
      <p>This code will expire in 5 minutes.</p>
      <p style='margin-top: 20px;'>If you didn't initiate this, please ignore this message.</p>
      <hr style='margin-top: 40px;' />
      <p style='font-size: 12px; color: #888;'>Tara Kabataan System – Automated Message</p>
    </div>
  ";

  $mail->send();

  echo json_encode(["success" => true, "message" => "OTP sent to $email"]);
} catch (Exception $e) {
  http_response_code(500);
  echo json_encode([
    "success" => false,
    "message" => "Email failed",
    "phpmailer_error" => $mail->ErrorInfo,
    "exception" => $e->getMessage()
  ]);
}
