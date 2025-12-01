<?php
// config.php

// Automatically set BASE_PATH based on the server environment
if (strpos($_SERVER['HTTP_HOST'], 'localhost') !== false) {
    define('BASE_PATH', '/final_task_o_mania-1'); // For localhost
} else {
    define('BASE_PATH', '/'); // For InfinityFree or other environments
}
?>