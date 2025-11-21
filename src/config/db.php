<?php
// Detect environment: local (XAMPP) or online (InfinityFree)
$is_local = ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === 'localhost:80' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

if ($is_local) {
    // Local XAMPP credentials
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "task_mania";
    $port = 3306; // Default MySQL port
} else {
    // Online InfinityFree credentials
    $host = "sql206.infinityfree.com";
    $user = "if0_40443147";
    $pass = "taskomania2025";
    $dbname = "if0_40443147_task_mania";
    $port = 3306; // InfinityFree default port
}

$conn = new mysqli($host, $user, $pass, $dbname, $port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>
