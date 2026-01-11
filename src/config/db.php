<?php
// Detect environment: local (XAMPP) or online (InfinityFree)
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'localhost:80' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($is_local) {
    // Local XAMPP credentials
    $host = "localhost";
    $user = "root";
    $pass = "";

    // $dbname = "task_mania";

    $dbname = "is_project";

    $port = 3306; // Default MySQL port
} else {
    // Online InfinityFree credentials
    $host = "task-o-mania-server.mysql.database.azure.com";
    $user = "mfwncmhgvf";
    $pass = "KSmU0PggkZ\$kNuH\$"; // Escape $ to prevent variable interpretation
    $dbname = "task-o-mania-database";
    $port = 3306; // InfinityFree default port
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>
