<?php
header("Access-Control-Allow-Origin: *"); 
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Content-Type: application/json");

require_once '../config/db.php'; 

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || !isset($data['member_id'])) {
    echo json_encode(["success" => false, "message" => "Invalid data."]);
    exit;
}

$member_id = $data['member_id'];
$member_name = $data['member_name'] ?? "";
$role_id = $data['role_id'] ?? "";
$member_image = $data['member_image'] ?? "";

$query = "UPDATE members SET member_name = ?, role_id = ?, member_image = ? WHERE member_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ssss", $member_name, $role_id, $member_image, $member_id);

if ($stmt->execute()) {
    $sql = "SELECT u.member_id, u.member_name, u.member_image, u.role_id, r.role_name 
            FROM members u 
            LEFT JOIN roles r ON u.role_id = r.role_id 
            WHERE u.member_id = ?";
    
    $fetchStmt = $conn->prepare($sql);
    $fetchStmt->bind_param("s", $member_id);
    $fetchStmt->execute();
    $result = $fetchStmt->get_result();
    $updatedMember = $result->fetch_assoc();

    echo json_encode(["success" => true, "member" => $updatedMember]);
} else {
    echo json_encode(["success" => false, "message" => $stmt->error]);
}

?>
