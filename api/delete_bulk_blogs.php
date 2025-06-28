<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/db.php';

$input = json_decode(file_get_contents("php://input"), true);
$ids   = $input['blog_ids'] ?? [];

if (!is_array($ids) || empty($ids)) {
    echo json_encode(["success" => false, "error" => "No blog IDs provided"]);
    exit;
}

// 1) Build placeholders and fetch blog_image + blog_content for all IDs
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$sqlBlogs     = "
  SELECT blog_id, blog_image, blog_content
  FROM tk_webapp.blogs
  WHERE blog_id IN ($placeholders)
";
$stmt         = $conn->prepare($sqlBlogs);
// bind each ID as string
$stmt->bind_param(str_repeat('s', count($ids)), ...$ids);
$stmt->execute();
$resBlogs     = $stmt->get_result();
$blogs        = $resBlogs->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// 2) Fetch all “more images” from blog_images for these blogs
$sqlMoreImages = "
  SELECT image_url
  FROM tk_webapp.blog_images
  WHERE blog_id IN ($placeholders)
";
$stmtImgs      = $conn->prepare($sqlMoreImages);
$stmtImgs->bind_param(str_repeat('s', count($ids)), ...$ids);
$stmtImgs->execute();
$resImgs        = $stmtImgs->get_result();
$moreImages     = $resImgs->fetch_all(MYSQLI_ASSOC);
$stmtImgs->close();

// 3) Collect every file path to delete
$toUnlink = [];

// main + content-embedded images
foreach ($blogs as $b) {
    // main image
    if (!empty($b['blog_image'])) {
        $toUnlink[] = $_SERVER['DOCUMENT_ROOT'] . $b['blog_image'];
    }
    // images in blog_content
    if (!empty($b['blog_content']) &&
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $b['blog_content'], $m)
    ) {
        foreach ($m[1] as $srcUrl) {
            $toUnlink[] = $_SERVER['DOCUMENT_ROOT'] . parse_url($srcUrl, PHP_URL_PATH);
        }
    }
}

// more_images rows
foreach ($moreImages as $row) {
    if (!empty($row['image_url'])) {
        $toUnlink[] = $_SERVER['DOCUMENT_ROOT'] . $row['image_url'];
    }
}

// dedupe and unlink
foreach (array_unique($toUnlink) as $path) {
    if (file_exists($path)) {
        @unlink($path);
    }
}

// 4) Finally delete blogs; blog_images entries cascade away per FK :contentReference[oaicite:0]{index=0}
$deleteSql = "DELETE FROM tk_webapp.blogs WHERE blog_id IN ($placeholders)";
$delStmt   = $conn->prepare($deleteSql);
$delStmt->bind_param(str_repeat('s', count($ids)), ...$ids);
$success   = $delStmt->execute();
$delStmt->close();
$conn->close();

echo json_encode(["success" => (bool)$success]);
