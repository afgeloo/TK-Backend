<?php
ini_set('display_errors', 1);
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../config/db.php';

$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['blog_id'])) {
    echo json_encode(["success" => false, "message" => "Missing blog_id"]);
    exit;
}

$blog_id = $data['blog_id'];

$stmt = $conn->prepare("SELECT blog_image, blog_content FROM tk_webapp.blogs WHERE blog_id = ?");
$stmt->bind_param("s", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();

if (!$blog) {
    echo json_encode(["success" => false, "message" => "Blog not found"]);
    exit;
}

$imagesToDelete = [];

if (!empty($blog['blog_image'])) {
    $imagesToDelete[] = $_SERVER['DOCUMENT_ROOT'] . $blog['blog_image'];
}

if (!empty($blog['blog_content'])) {
    if (preg_match_all('/<img[^>]+src="([^">]+)"/', $blog['blog_content'], $matches)) {
        foreach ($matches[1] as $imgPath) {
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($imgPath, PHP_URL_PATH);
            $imagesToDelete[] = $fullPath;
        }
    }
}

foreach ($imagesToDelete as $imgPath) {
    if (file_exists($imgPath)) {
        unlink($imgPath);
    }
}

$deleteStmt = $conn->prepare("DELETE FROM tk_webapp.blogs WHERE blog_id = ?");
$deleteStmt->bind_param("s", $blog_id);
$success = $deleteStmt->execute();

echo json_encode(["success" => $success]);

$stmt->close();
$deleteStmt->close();
$conn->close();
?>
