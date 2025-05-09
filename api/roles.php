<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

include '../config/db.php';

$sql = "SELECT role_id, role_name FROM tk_webapp.roles ORDER BY role_name ASC";
$result = $conn->query($sql);

$roles = [];

if ($result && $result->num_rows > 0) {
  while ($row = $result->fetch_assoc()) {
    $roles[] = $row;
  }
  echo json_encode(["success" => true, "roles" => $roles]);
} else {
  echo json_encode(["success" => true, "roles" => []]); 
}

$conn->close();
?>
