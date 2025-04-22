<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    
    $query = "SELECT 
                event_id,
                event_image AS image_url,
                event_category AS category,
                event_title AS title,
                event_date,
                event_start_time,
                event_end_time,
                event_venue,
                event_content AS content,
                event_speakers,
                event_going,
                event_status,
                created_at,
                updated_at
              FROM tk_webapp.events
              WHERE event_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $event_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $event = $result->fetch_assoc();

        if ($event) {
            echo json_encode($event, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Event not found."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch event."]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

$category = isset($_GET['category']) ? strtoupper($_GET['category']) : 'ALL';

$response = [
    "pinned" => [],
    "events" => []
];

// Fetch pinned events
$pinnedQuery = "SELECT 
                  event_id,
                  event_image AS image_url,
                  event_category AS category,
                  event_title AS title,
                  event_date,
                  event_start_time,
                  event_end_time,
                  event_venue,
                  event_content AS content,
                  event_speakers,
                  event_going,
                  event_status,
                  created_at,
                  updated_at
                FROM tk_webapp.events
                WHERE event_status = 'UPCOMING'
                ORDER BY created_at DESC
                LIMIT 3";

$pinnedResult = $conn->query($pinnedQuery);
if ($pinnedResult) {
    $response["pinned"] = $pinnedResult->fetch_all(MYSQLI_ASSOC);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch pinned events."]);
    exit;
}

// Fetch all or filtered events
if ($category === 'ALL') {
    $query = "SELECT 
                event_id,
                event_image AS image_url,
                event_category AS category,
                event_title AS title,
                event_date,
                event_start_time,
                event_end_time,
                event_venue,
                event_content AS content,
                event_speakers,
                event_going,
                event_status,
                created_at,
                updated_at
              FROM tk_webapp.events
              ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT 
                event_id,
                event_image AS image_url,
                event_category AS category,
                event_title AS title,
                event_date,
                event_start_time,
                event_end_time,
                event_venue,
                event_content AS content,
                event_speakers,
                event_going,
                event_status,
                created_at,
                updated_at
              FROM tk_webapp.events
              WHERE event_category = ?
              ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $category);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $response["events"] = $result->fetch_all(MYSQLI_ASSOC);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch events."]);
    exit;
}

$stmt->close();
$conn->close();

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>
