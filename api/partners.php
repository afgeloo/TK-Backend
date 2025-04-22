<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php'; 

$response = [];

$query = "SELECT partner_id, partner_image, partner_name, partner_dec, partner_contact_email, partner_phone_number 
          FROM tk_webapp.partnerships 
          ORDER BY partner_name ASC";

$result = $conn->query($query);

if ($result) {
    $partners = $result->fetch_all(MYSQLI_ASSOC);
    $response["partners"] = $partners;
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch partners."]);
}

$conn->close();
