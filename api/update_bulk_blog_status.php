<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

require_once '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['blog_ids']) || !isset($data['new_status'])) {
    echo json_encode(["success" => false, "error" => "Invalid input"]);
    exit;
}

$ids = $data['blog_ids'];
$newStatus = strtoupper(trim($data['new_status']));

if (empty($ids) || !$newStatus) {
    echo json_encode(["success" => false, "error" => "Empty data"]);
    exit;
}

$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sql = "UPDATE tk_webapp.blogs SET blog_status = ? WHERE blog_id IN ($placeholders)";
$stmt = $conn->prepare($sql);

$params = array_merge([$newStatus], $ids);
if ($stmt->execute($params)) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "error" => $stmt->errorInfo()]);
}
?>
