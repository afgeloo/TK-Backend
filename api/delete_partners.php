<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['partner_id'])) {
    echo json_encode(["success" => false, "message" => "Missing partner_id"]);
    exit;
}

$partner_id = $data['partner_id'];

$stmt = $conn->prepare("SELECT partner_image FROM tk_webapp.partnerships WHERE partner_id = ?");
$stmt->bind_param("s", $partner_id);
$stmt->execute();
$result = $stmt->get_result();
$partner = $result->fetch_assoc();

if (!$partner) {
    echo json_encode(["success" => false, "message" => "Partner not found"]);
    exit;
}

$imagePath = $partner['partner_image'];
if (!empty($imagePath)) {
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($imagePath, PHP_URL_PATH);
    if (file_exists($fullPath)) {
        unlink($fullPath); 
    }
}

$deleteStmt = $conn->prepare("DELETE FROM tk_webapp.partnerships WHERE partner_id = ?");
$deleteStmt->bind_param("s", $partner_id);
$success = $deleteStmt->execute();

echo json_encode(["success" => $success]);

$stmt->close();
$deleteStmt->close();
$conn->close();
?>
