<?php
$img = $_GET['img'] ?? '';
$fullPath = "../../tara-kabataan-webapp/uploads/members-images/$img";

if (!file_exists($fullPath)) {
  http_response_code(404);
  exit;
}

$info = getimagesize($fullPath);
header("Content-Type: {$info['mime']}");

switch ($info['mime']) {
  case 'image/jpeg':
    $src = imagecreatefromjpeg($fullPath);
    break;
  case 'image/png':
    $src = imagecreatefrompng($fullPath);
    break;
  case 'image/webp':
    $src = imagecreatefromwebp($fullPath);
    break;
  default:
    http_response_code(415);
    exit("Unsupported image format");
}

$resized = imagescale($src, 400); // Resize width to 400px
imagejpeg($resized, null, 80);    // Output directly as JPEG (quality 80)
imagedestroy($src);
imagedestroy($resized);

?>
