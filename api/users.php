<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include '../config/db.php';

$sql = "
    SELECT 
        u.user_id,
        u.user_name,
        u.user_email,
        u.role_id,
        u.member_id,
        m.member_image AS user_image
    FROM tk_webapp.users u
    LEFT JOIN tk_webapp.members m ON u.member_id = m.member_id
";

$result = $conn->query($sql);

if ($result) {
    $users = [];

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    echo json_encode([
        "success" => true,
        "users" => $users
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Query failed: " . $conn->error
    ]);
}

$conn->close();
?>
