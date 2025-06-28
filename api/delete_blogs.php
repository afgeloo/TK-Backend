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

// 1) Fetch main image and content images from blogs table
$stmt = $conn->prepare("
    SELECT blog_image, blog_content 
    FROM tk_webapp.blogs 
    WHERE blog_id = ?
");
$stmt->bind_param("s", $blog_id);
$stmt->execute();
$result = $stmt->get_result();
$blog = $result->fetch_assoc();
$stmt->close();

if (!$blog) {
    echo json_encode(["success" => false, "message" => "Blog not found"]);
    exit;
}

$imagesToDelete = [];

// -- main image --
if (!empty($blog['blog_image'])) {
    $imagesToDelete[] = $_SERVER['DOCUMENT_ROOT'] . $blog['blog_image'];
}

// -- images in content HTML --
if (!empty($blog['blog_content'])) {
    if (preg_match_all('/<img[^>]+src="([^">]+)"/', $blog['blog_content'], $matches)) {
        foreach ($matches[1] as $imgPath) {
            // strip domain if present
            $fullPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($imgPath, PHP_URL_PATH);
            $imagesToDelete[] = $fullPath;
        }
    }
}

// 2) Fetch all “more” images from blog_images table
$stmtImgs = $conn->prepare("
    SELECT image_url 
    FROM tk_webapp.blog_images 
    WHERE blog_id = ?
");
$stmtImgs->bind_param("s", $blog_id);
$stmtImgs->execute();
$resImgs = $stmtImgs->get_result();
while ($row = $resImgs->fetch_assoc()) {
    if (!empty($row['image_url'])) {
        $imagesToDelete[] = $_SERVER['DOCUMENT_ROOT'] . $row['image_url'];
    }
}
$stmtImgs->close();

// 3) Delete the files from disk
foreach ($imagesToDelete as $imgPath) {
    if (file_exists($imgPath)) {
        unlink($imgPath);
    }
}

// 4) Delete the blog itself; blog_images rows will be automatically removed by your FK ON DELETE CASCADE
$deleteStmt = $conn->prepare("
    DELETE FROM tk_webapp.blogs 
    WHERE blog_id = ?
");
$deleteStmt->bind_param("s", $blog_id);
$success = $deleteStmt->execute();
$deleteStmt->close();
$conn->close();

echo json_encode(["success" => (bool)$success]);
