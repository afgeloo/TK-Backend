<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

include '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !is_array($data)) {
    echo json_encode(["success" => false, "message" => "No input data provided."]);
    exit;
}

$fields = [];
$params = [];
$types = '';

$allowedFields = [
    'background', 'overview',
    'core_kapwa', 'core_kalinangan', 'core_kaginhawaan',
    'mission', 'vision', 'council',
    'adv_kalusugan', 'adv_kalikasan', 'adv_karunungan', 'adv_kultura', 'adv_kasarian',
    'contact_no', 'about_email', 'address', 'facebook', 'instagram'
];

foreach ($allowedFields as $field) {
    if (isset($data[$field]) && $data[$field] !== '') {
        $fields[] = "$field = ?";
        $params[] = $data[$field];
        $types .= 's'; 
    }
}

if (empty($fields)) {
    echo json_encode(["success" => false, "message" => "No valid fields to update."]);
    exit;
}

$sql = "UPDATE tk_webapp.aboutus SET " . implode(", ", $fields) . " WHERE aboutus_id = 'aboutus-2025-000001'";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(["success" => false, "message" => "Prepare failed: " . $conn->error]);
    exit;
}

$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Execute failed: " . $stmt->error]);
}

$stmt->close();
$conn->close();

?>
