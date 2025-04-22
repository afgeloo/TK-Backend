<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

function resolveImageByUserId($memberId) {
    $extensions = ['jpg', 'jpeg', 'png'];
    $baseDir = realpath('../../tara-kabataan-webapp/uploads/members-images/');
    $baseUrl = '/tara-kabataan-webapp/uploads/members-images/';

    foreach ($extensions as $ext) {
        $file = $baseDir . '/' . $memberId . '.' . $ext;
        if (file_exists($file)) {
            return $baseUrl . $memberId . '.' . $ext;
        }
    }

    return null;
}

$query = "
    SELECT 
        members.member_id,
        members.member_name,
        members.role_id,
        roles.role_name 
    FROM tk_webapp.members 
    JOIN tk_webapp.roles ON members.role_id = roles.role_id
";

$result = $conn->query($query);

if ($result) {
    $council = [];
    while ($row = $result->fetch_assoc()) {
        $row['member_image'] = resolveImageByUserId($row['member_id']);
        $council[] = $row;
    }

    echo json_encode($council, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch council members."]);
}
?>
