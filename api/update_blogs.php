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

if (!isset($data['blog_id'])) {
    http_response_code(400);
    echo json_encode(["error" => "blog_id is required"]);
    exit;
}

$blog_id = $data['blog_id'];
$fields = [];
$params = [];
$types = "";

if (isset($data['title'])) {
    $fields[] = "blog_title = ?";
    $params[] = $data['title'];
    $types .= "s";
}
if (isset($data['content'])) {
    $fields[] = "blog_content = ?";
    $params[] = $data['content'];
    $types .= "s";
}
if (isset($data['category'])) {
    $fields[] = "blog_category = ?";
    $params[] = $data['category'];
    $types .= "s";
}
if (isset($data['blog_status'])) {
    $fields[] = "blog_status = ?";
    $params[] = $data['blog_status'];
    $types .= "s";
}
if (isset($data['image_url'])) {
    $fields[] = "blog_image = ?";
    $params[] = $data['image_url'];
    $types .= "s";
}

if (count($fields) === 0 && !isset($data['more_images'])) {
    http_response_code(400);
    echo json_encode(["error" => "No fields provided for update"]);
    exit;
}

$updateSuccess = true;

if (count($fields) > 0) {
    $fields[] = "updated_at = CURRENT_TIMESTAMP";
    $params[] = $blog_id;
    $types .= "s";

    $sql = "UPDATE tk_webapp.blogs SET " . implode(", ", $fields) . " WHERE blog_id = ?";
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
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode([
            "error" => "Execute failed",
            "sql_error" => $stmt->error
        ]);
        $updateSuccess = false;
    }

    $stmt->close();
}

if (isset($data['more_images']) && is_array($data['more_images'])) {
    $deleteStmt = $conn->prepare("DELETE FROM tk_webapp.blog_images WHERE blog_id = ?");
    $deleteStmt->bind_param("s", $blog_id);
    if (!$deleteStmt->execute()) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to delete old images", "sql_error" => $deleteStmt->error]);
        $deleteStmt->close();
        $conn->close();
        exit;
    }
    $deleteStmt->close();

    $insertStmt = $conn->prepare("INSERT INTO tk_webapp.blog_images (blog_id, image_url) VALUES (?, ?)");
    foreach ($data['more_images'] as $image_url) {
        $insertStmt->bind_param("ss", $blog_id, $image_url);
        if (!$insertStmt->execute()) {
            http_response_code(500);
            echo json_encode(["error" => "Failed to insert new image", "image_url" => $image_url, "sql_error" => $insertStmt->error]);
            $insertStmt->close();
            $conn->close();
            exit;
        }
    }
    $insertStmt->close();
}

$conn->close();
echo json_encode(["success" => $updateSuccess]);
