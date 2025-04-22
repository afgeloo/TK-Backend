<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

include '../config/db.php';

try {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        !isset($data['partner_name']) ||
        !isset($data['partner_dec']) ||
        !isset($data['partner_contact_email']) ||
        !isset($data['partner_phone_number']) ||
        !isset($data['partner_image'])
    ) {
        echo json_encode(["success" => false, "error" => "Missing fields"]);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO tk_webapp.partnerships (
            partner_name, partner_dec, partner_contact_email, partner_phone_number, partner_image
        ) VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->bind_param(
        "sssss",
        $data['partner_name'],
        $data['partner_dec'],
        $data['partner_contact_email'],
        $data['partner_phone_number'],
        $data['partner_image']
    );

    if ($stmt->execute()) {
        $result = $conn->query("SELECT * FROM tk_webapp.partnerships ORDER BY partner_id DESC LIMIT 1");
        $partner = $result->fetch_assoc();

        if ($partner) {
            $formatted = [
                "partner_id" => $partner["partner_id"],
                "partner_image" => $partner["partner_image"],
                "partner_name" => $partner["partner_name"],
                "partner_dec" => $partner["partner_dec"],
                "partner_contact_email" => $partner["partner_contact_email"],
                "partner_phone_number" => $partner["partner_phone_number"]
            ];

            echo json_encode(["success" => true, "partner" => $formatted]);
        } else {
            echo json_encode(["success" => false, "error" => "Failed to retrieve new partner"]);
        }

    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>
