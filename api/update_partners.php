<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(["error" => "Invalid JSON or no data received"]);
    exit;
}

if (!isset($data['partner_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "partner_id is required"]);
    exit;
}

$partner_id = $data['partner_id'];
$fields = [];
$params = [];
$types = "";

if (isset($data['partner_name'])) {
    $fields[] = "partner_name = ?";
    $params[] = $data['partner_name'];
    $types .= "s";
}
if (isset($data['partner_dec'])) {
    $fields[] = "partner_dec = ?";
    $params[] = $data['partner_dec'];
    $types .= "s";
}
if (isset($data['partner_contact_email'])) {
    $fields[] = "partner_contact_email = ?";
    $params[] = $data['partner_contact_email'];
    $types .= "s";
}
if (isset($data['partner_phone_number'])) {
    $fields[] = "partner_phone_number = ?";
    $params[] = $data['partner_phone_number'];
    $types .= "s";
}
if (isset($data['partner_image'])) {
    $fields[] = "partner_image = ?";
    $params[] = $data['partner_image'];
    $types .= "s";
}

if (count($fields) === 0) {
    http_response_code(400);
    echo json_encode(["error" => "No fields provided for update"]);
    exit;
}

$params[] = $partner_id;
$types .= "s";

$sql = "UPDATE tk_webapp.partnerships SET " . implode(", ", $fields) . " WHERE partner_id = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    echo json_encode([
        "error" => "Prepare failed",
        "sql" => $sql,
        "params" => $params,
        "sql_error" => $conn->error
    ]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    http_response_code(500);
    echo json_encode([
        "error" => "Execute failed",
        "sql" => $sql,
        "params" => $params,
        "sql_error" => $stmt->error
    ]);
}

$stmt->close();
$conn->close();
