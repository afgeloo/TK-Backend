<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

$status = isset($_GET['status']) ? strtoupper($_GET['status']) : 'ALL';

$response = [];
$today = date('Y-m-d');

// ✅ Step 1: Assign statuses to rows with empty/null event_status
$autoAssignQuery = "UPDATE tk_webapp.events
    SET event_status = CASE
        WHEN event_date < ? THEN 'COMPLETED'
        ELSE 'UPCOMING'
    END
    WHERE event_status IS NULL OR event_status = ''";
$autoAssignStmt = $conn->prepare($autoAssignQuery);
$autoAssignStmt->bind_param("s", $today);
$autoAssignStmt->execute();
$autoAssignStmt->close();

// ✅ Step 2: Ensure existing statuses are updated properly
$updateDoneQuery = "UPDATE tk_webapp.events 
    SET event_status = 'COMPLETED' 
    WHERE UPPER(event_status) = 'UPCOMING' AND event_date < ?";
$updateDoneStmt = $conn->prepare($updateDoneQuery);
$updateDoneStmt->bind_param("s", $today);
$updateDoneStmt->execute();
$updateDoneStmt->close();

$updateUpcomingQuery = "UPDATE tk_webapp.events 
    SET event_status = 'UPCOMING' 
    WHERE UPPER(event_status) = 'COMPLETED' AND event_date >= ?";
$updateUpcomingStmt = $conn->prepare($updateUpcomingQuery);
$updateUpcomingStmt->bind_param("s", $today);
$updateUpcomingStmt->execute();
$updateUpcomingStmt->close();

if ($status === 'ALL') {
    $query = "SELECT * FROM tk_webapp.events ORDER BY event_date DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT * FROM tk_webapp.events WHERE UPPER(event_status) = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $response = $result->fetch_all(MYSQLI_ASSOC);

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to execute SQL query."]);
}

$stmt->close();
$conn->close();
?>
