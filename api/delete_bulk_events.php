<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require '../config/db.php';  // your mysqli $conn

$body = file_get_contents('php://input');
$data = json_decode($body, true);

if (!isset($data['event_ids']) || !is_array($data['event_ids']) || empty($data['event_ids'])) {
    echo json_encode([
      "success" => false,
      "message" => "You must provide a non-empty array of event_ids"
    ]);
    exit;
}

$ids = $data['event_ids'];
$placeholders = implode(',', array_fill(0, count($ids), '?'));

// 1) Fetch all images to delete
$sqlFetch = "
  SELECT event_image, event_content
    FROM tk_webapp.events
   WHERE event_id IN ($placeholders)
";
$stmtFetch = $conn->prepare($sqlFetch);
$stmtFetch->bind_param(str_repeat('s', count($ids)), ...$ids);
$stmtFetch->execute();
$res = $stmtFetch->get_result();

$toDelete = [];
while ($row = $res->fetch_assoc()) {
    // main image
    if (!empty($row['event_image'])) {
        $toDelete[] = $_SERVER['DOCUMENT_ROOT'] . $row['event_image'];
    }
    // inline <img> tags
    if (!empty($row['event_content']) &&
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $row['event_content'], $m)
    ) {
        foreach ($m[1] as $url) {
            $path = parse_url($url, PHP_URL_PATH);
            $toDelete[] = $_SERVER['DOCUMENT_ROOT'] . $path;
        }
    }
}
$stmtFetch->close();

// 2) Begin transaction
$conn->begin_transaction();

try {
    // unlink files
    foreach (array_unique($toDelete) as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    // 3) Delete the events themselves
    $sqlDel = "DELETE FROM tk_webapp.events WHERE event_id IN ($placeholders)";
    $stmtDel = $conn->prepare($sqlDel);
    $stmtDel->bind_param(str_repeat('s', count($ids)), ...$ids);
    if (! $stmtDel->execute()) {
        throw new Exception("Delete failed: " . $stmtDel->error);
    }
    $stmtDel->close();

    $conn->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("bulk delete_event error: " . $e->getMessage());
    echo json_encode([
      "success" => false,
      "message" => "Error during bulk delete: " . $e->getMessage()
    ]);
}

$conn->close();
