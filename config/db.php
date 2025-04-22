<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "tk_webapp";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

header("Content-Type: application/json");
?>