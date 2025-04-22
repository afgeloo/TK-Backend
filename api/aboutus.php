<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include '../config/db.php';

if (isset($_GET['aboutus_id'])) {
    $aboutus_id = $_GET['aboutus_id'];
    $query = "SELECT * FROM tk_webapp.aboutus WHERE aboutus_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $aboutus_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $about = $result->fetch_assoc();

        if ($about) {
            echo json_encode($about, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "AboutUs record not found."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch aboutus record."]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

$query = "SELECT * FROM tk_webapp.aboutus LIMIT 1";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $aboutus = $result->fetch_assoc();
    echo json_encode($aboutus, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(404);
    echo json_encode(["error" => "AboutUs content not found."]);
}

$conn->close();
?>
