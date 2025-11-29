<?php
// Detect environment: local (docker) or online (InfinityFree)
$is_local = (isset($_SERVER['HTTP_HOST']) && (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false || $_SERVER['HTTP_HOST'] === '127.0.0.1'));

if ($is_local) {
    // Local / Docker credentials
    $db_host = getenv('DB_HOST') ?: 'db'; // docker-compose service name
    $db_user = getenv('DB_USER') ?: 'user';
    $db_pass = getenv('DB_PASSWORD') ?: 'userpassword';
    $db_name = getenv('DB_NAME') ?: 'taskomania';
    $db_port = getenv('DB_PORT') ?: 3306;
} else {
    // Online InfinityFree credentials
    $db_host = 'sql206.infinityfree.com';
    $db_user = 'if0_40443147';
    $db_pass = 'taskomania2025';
    $db_name = 'if0_40443147_task_mania';
    $db_port = 3306; // InfinityFree default port
}

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB connection failed: " . $conn->connect_error]));
}

$conn->set_charset("utf8mb4");
?>
