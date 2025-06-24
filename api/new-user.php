<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include '../config/db.php';  // expects $conn = new mysqli(...);

try {
    $data = json_decode(file_get_contents("php://input"), true);

    // require member_id, email, phone
    if (empty($data['member_id']) || empty($data['email']) || empty($data['phone'])) {
        echo json_encode([
            "success" => false,
            "error" => "Missing fields: member_id, email, and phone are required"
        ]);
        exit;
    }

    $member_id = trim($data['member_id']);
    $email     = trim($data['email']);
    $phone     = trim($data['phone']);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            "success" => false,
            "error" => "Invalid email format"
        ]);
        exit;
    }

    // 1) fetch member_name & role_id from members table
    $stmt = $conn->prepare("
        SELECT member_name, role_id 
        FROM tk_webapp.members 
        WHERE member_id = ?
    ");
    $stmt->bind_param("s", $member_id);
    $stmt->execute();
    $stmt->bind_result($member_name, $role_id);
    if (!$stmt->fetch()) {
        echo json_encode([
            "success" => false,
            "error" => "Member not found"
        ]);
        exit;
    }
    $stmt->close();

    // 2) hash default password
    $default_pw = '@Admin123';
    $password_hash = password_hash($default_pw, PASSWORD_BCRYPT);

    // 3) insert into users
    $stmt = $conn->prepare("
        INSERT INTO tk_webapp.users
          (user_name, user_email, user_contact, password_hash, role_id, member_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "ssssss",
        $member_name,
        $email,
        $phone,
        $password_hash,
        $role_id,
        $member_id
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "success" => false,
            "error" => "Failed to create user: " . $stmt->error
        ]);
        exit;
    }
    $stmt->close();

    // 4) fetch and return the new user
    $sql = "
      SELECT user_id, user_name, user_email, user_contact, role_id, member_id
      FROM tk_webapp.users
      WHERE user_email = ?
      LIMIT 1
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    echo json_encode([
        "success" => true,
        "user"    => $user
    ]);

    $conn->close();
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error"   => $e->getMessage()
    ]);
}
