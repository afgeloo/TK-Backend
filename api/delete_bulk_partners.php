<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit(0);

require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['partner_ids']) || !is_array($data['partner_ids'])) {
    echo json_encode(["success" => false, "error" => "Invalid or missing IDs"]);
    exit;
}

$ids = array_filter(array_map('trim', $data['partner_ids']));
if (empty($ids)) {
    echo json_encode(["success" => false, "error" => "No IDs provided"]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('s', count($ids));

// Step 1: Select partner images to delete
$selectSql = "SELECT partner_image FROM tk_webapp.partnerships WHERE partner_id IN ($placeholders)";
$stmtSelect = $conn->prepare($selectSql);
$stmtSelect->bind_param($types, ...$ids);
$stmtSelect->execute();
$result = $stmtSelect->get_result();

while ($row = $result->fetch_assoc()) {
    if (!empty($row['partner_image'])) {
        $imagePath = realpath(__DIR__ . '/../../' . ltrim($row['partner_image'], '/'));
        if ($imagePath && file_exists($imagePath)) {
            unlink($imagePath);
        }
    }
}
$stmtSelect->close();

// Step 2: Delete partners from the database
$deleteSql = "DELETE FROM tk_webapp.partnerships WHERE partner_id IN ($placeholders)";
$stmtDelete = $conn->prepare($deleteSql);
$stmtDelete->bind_param($types, ...$ids);
$success = $stmtDelete->execute();
$stmtDelete->close();

if ($success) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $conn->error]);
}

$conn->close();
