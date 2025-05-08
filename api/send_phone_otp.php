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
$phone = $data['phone'] ?? '';

if (!$phone) {
    echo json_encode(["success" => false, "message" => "Phone number is required."]);
    exit;
}

// Get email using phone number
$stmt = $conn->prepare("SELECT user_email FROM tk_webapp.users WHERE user_contact = ?");
$stmt->bind_param("s", $phone);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $email = $row['user_email'];
    $otp = rand(100000, 999999);
    $expiresAt = date("Y-m-d H:i:s", strtotime("+5 minutes"));

    // Clear existing OTPs
    $stmt = $conn->prepare("DELETE FROM admin_otp WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();

    // Insert new OTP
    $stmt = $conn->prepare("INSERT INTO admin_otp (email, otp, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $email, $otp, $expiresAt);
    $stmt->execute();

    // Send SMS via Globe Labs
    $shortcode = "21668606";
    $accessToken = "b9XksdnBaetq5cdp7nTBnLtKG9x7sdaz";
    $smsMessage = "Your Tara Kabataan OTP is $otp. Expires in 5 minutes.";

    $smsData = [
        "outboundSMSMessageRequest" => [
            "senderAddress" => "tel:$shortcode",
            "outboundSMSTextMessage" => [ "message" => $smsMessage ],
            "address" => "tel:+63" . ltrim($phone, "0")
        ]
    ];

    $ch = curl_init("https://devapi.globelabs.com.ph/smsmessaging/v1/outbound/$shortcode/requests?access_token=$accessToken");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($smsData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);

    if ($response === false) {
        echo json_encode([
            "success" => false,
            "message" => "CURL Error: " . curl_error($ch)
        ]);
        curl_close($ch);
        exit;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "SMS sending failed.",
            "response" => $response
        ]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Phone number not registered."]);
}
