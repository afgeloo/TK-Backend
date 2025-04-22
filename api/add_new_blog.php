<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include '../config/db.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data['title']) || !isset($data['content']) || !isset($data['category']) ||
        !isset($data['blog_status']) || !isset($data['image_url']) || !isset($data['author'])
    ) {
        echo json_encode(["success" => false, "error" => "Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO tk_webapp.blogs (blog_title, blog_content, blog_category, blog_status, blog_image, blog_author_id) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "ssssss",
        $data['title'],
        $data['content'],
        $data['category'],
        $data['blog_status'],
        $data['image_url'],
        $data['author']
    );

    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $result = $conn->query("SELECT * FROM tk_webapp.blogs ORDER BY created_at DESC LIMIT 1");
        $blog = $result->fetch_assoc();

        echo json_encode(["success" => true, "blog" => $blog]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
