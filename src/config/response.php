<?php
function jsonResponse($status, $msg, $data = null) {
    echo json_encode([
        "status" => $status,
        "message" => $msg,
        "data" => $data
    ]);
    exit;

}
?>