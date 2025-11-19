<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "task_mania";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed"]));
}

$conn->set_charset("utf8");
?>
