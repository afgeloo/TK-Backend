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

// parse JSON body
$body    = file_get_contents('php://input');
$data    = json_decode($body, true);
$eventId = $data['event_id'] ?? null;

if (!$eventId) {
    echo json_encode([
      "success" => false,
      "message" => "Missing event_id"
    ]);
    exit;
}

// fetch the row
if (! $stmt = $conn->prepare(
    "SELECT event_image, event_content 
     FROM tk_webapp.events 
     WHERE event_id = ?"
)) {
    echo json_encode([
      "success" => false,
      "message" => "Prepare failed: ".$conn->error
    ]);
    exit;
}

$stmt->bind_param("s", $eventId);
$stmt->execute();
$res   = $stmt->get_result();
$event = $res->fetch_assoc();
$stmt->close();

if (! $event) {
    echo json_encode([
      "success" => false,
      "message" => "Event not found"
    ]);
    exit;
}

// build list of files to delete
$toDelete = [];

// 1) main image
if (!empty($event['event_image'])) {
    $toDelete[] = $_SERVER['DOCUMENT_ROOT'] . $event['event_image'];
}

// 2) inline <img> in content
if (!empty($event['event_content']) &&
    preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"]/i', $event['event_content'], $m)
) {
    foreach ($m[1] as $url) {
        $path = parse_url($url, PHP_URL_PATH);
        $toDelete[] = $_SERVER['DOCUMENT_ROOT'] . $path;
    }
}

// start transaction
$conn->begin_transaction();

try {
    // unlink them (ignore if file missing)
    foreach (array_unique($toDelete) as $file) {
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    // delete the event row
    if (! $del = $conn->prepare(
        "DELETE FROM tk_webapp.events WHERE event_id = ?"
    )) {
        throw new Exception("Prepare delete failed: " . $conn->error);
    }
    $del->bind_param("s", $eventId);
    if (! $del->execute()) {
        throw new Exception("Execute delete failed: " . $del->error);
    }
    $del->close();

    $conn->commit();
    echo json_encode(["success" => true]);

} catch (Exception $e) {
    $conn->rollback();
    error_log("delete_event error: " . $e->getMessage());
    echo json_encode([
      "success" => false,
      "message" => "Error deleting event: " . $e->getMessage()
    ]);
}

$conn->close();
