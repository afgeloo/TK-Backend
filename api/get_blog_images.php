<?php
require_once '../config.php'; 

header("Content-Type: application/json");

$blog_id = $_GET['blog_id'] ?? null;

if (!$blog_id) {
  echo json_encode(['success' => false, 'error' => 'No blog_id provided']);
  exit;
}

$stmt = $pdo->prepare("SELECT image_url FROM blog_images WHERE blog_id = ?");
$stmt->execute([$blog_id]);
$images = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo json_encode(['success' => true, 'images' => $images]);
