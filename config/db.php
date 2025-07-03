<?php
$host = "tk-webapp.cdy2k86gkg9l.ap-southeast-2.rds.amazonaws.com";
$user = "admin";
$pass = "xDLmjlGtvflLYlkottKJ";
$dbname = "tk_webapp";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

header("Content-Type: application/json");
$conn->set_charset("utf8mb4");
?>