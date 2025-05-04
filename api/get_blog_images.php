<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Content-Type: application/json");

require_once '../config/db.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit();
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

$blog_id = $_GET['blog_id'] ?? null;
if (!$blog_id) {
  echo json_encode(['success' => false, 'error' => 'No blog_id provided']);
  exit;
}

try {
  $stmt = $conn->prepare("SELECT image_url FROM blog_images WHERE blog_id = ?");
  $stmt->bind_param("s", $blog_id);
  $stmt->execute();
  $result = $stmt->get_result();

  $images = [];
  while ($row = $result->fetch_assoc()) {
    $images[] = $row['image_url'];
  }

  echo json_encode(['success' => true, 'images' => $images]);
} catch (PDOException $e) {
  echo json_encode([
    'success' => false,
    'error' => 'Database error',
    'details' => $e->getMessage()
  ]);
}
