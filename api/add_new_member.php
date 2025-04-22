<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include '../config/db.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['member_name']) || !isset($data['role_id'])) {
        echo json_encode(["success" => false, "error" => "Missing fields: member_name or role_id"]);
        exit;
    }

    $member_name = trim($data['member_name']);
    $role_id = trim($data['role_id']);
    $member_image = isset($data['member_image']) ? trim($data['member_image']) : "";

    $stmt = $conn->prepare("
        INSERT INTO tk_webapp.members (
            member_name, member_image, role_id
        ) VALUES (?, ?, ?)
    ");

    $stmt->bind_param("sss", $member_name, $member_image, $role_id);

    if ($stmt->execute()) {
        $result = $conn->query("
            SELECT m.*, r.role_name
            FROM tk_webapp.members m
            LEFT JOIN tk_webapp.roles r ON m.role_id = r.role_id
            ORDER BY m.member_id DESC
            LIMIT 1
        ");

        $member = $result->fetch_assoc();

        if ($member) {
            $formatted = [
                "member_id" => $member["member_id"],
                "member_name" => $member["member_name"],
                "member_image" => $member["member_image"],
                "role_id" => $member["role_id"],
                "role_name" => $member["role_name"] ?? "N/A",
            ];

            echo json_encode(["success" => true, "member" => $formatted]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to retrieve new member"]);
        }
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
