<?php
// Simple PHP wrapper to serve the same landing page when deployed on PHP hosting.
// Keeps CSS and assets identical to the HTML version.
header('Content-Type: text/html; charset=utf-8');
readfile(__DIR__ . DIRECTORY_SEPARATOR . 'index.html');
?>

