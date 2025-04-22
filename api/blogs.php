<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit;
}

include '../config/db.php';

if (isset($_GET['blog_id'])) {
    $blog_id = $_GET['blog_id'];
    
    $query = "SELECT b.blog_id, b.blog_title as title, b.blog_content as content, 
              b.blog_image as image_url, b.blog_category as category, 
              b.created_at, u.user_name as author, b.blog_status 
              FROM tk_webapp.blogs b
              LEFT JOIN tk_webapp.users u ON b.blog_author_id = u.user_id
              WHERE b.blog_id = ?";
              
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $blog_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $blog = $result->fetch_assoc();

        if ($blog) {
            echo json_encode($blog, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(404);
            echo json_encode(["error" => "Blog not found."]);
        }
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Failed to fetch blog."]);
    }

    $stmt->close();
    $conn->close();
    exit;
}

$category = isset($_GET['category']) ? strtoupper($_GET['category']) : 'ALL';

$response = [
    "pinned" => [],
    "blogs" => []
];

$pinnedQuery = "SELECT b.blog_id, b.blog_title as title, b.blog_content as content, 
                b.blog_image as image_url, b.blog_category as category, 
                b.created_at, u.user_name as author
                FROM tk_webapp.blogs b
                LEFT JOIN tk_webapp.users u ON b.blog_author_id = u.user_id
                WHERE b.blog_status = 'PINNED' 
                ORDER BY b.created_at DESC LIMIT 3";
                
$pinnedResult = $conn->query($pinnedQuery);
if ($pinnedResult) {
    $response["pinned"] = $pinnedResult->fetch_all(MYSQLI_ASSOC);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch pinned blogs."]);
    exit;
}

if ($category === 'ALL') {
    $query = "SELECT b.blog_id, b.blog_title as title, b.blog_content as content, 
              b.blog_image as image_url, b.blog_category as category, 
              b.created_at, u.user_name as author, b.blog_status
              FROM tk_webapp.blogs b
              LEFT JOIN tk_webapp.users u ON b.blog_author_id = u.user_id
              ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($query);
} else {
    $query = "SELECT b.blog_id, b.blog_title as title, b.blog_content as content, 
              b.blog_image as image_url, b.blog_category as category, 
              b.created_at, u.user_name as author, b.blog_status
              FROM tk_webapp.blogs b
              LEFT JOIN tk_webapp.users u ON b.blog_author_id = u.user_id
              WHERE b.blog_category = ?
              ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $category);
}

if ($stmt->execute()) {
    $result = $stmt->get_result();
    $response["blogs"] = $result->fetch_all(MYSQLI_ASSOC);
} else {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch blogs."]);
    exit;
}

$stmt->close();
$conn->close();

echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
?>