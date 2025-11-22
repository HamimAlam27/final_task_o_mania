<?php
session_start();
require '../../src/config/db.php';

header('Content-Type: application/json');

// Validate user session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user_id_to_remove = intval($input['user_id'] ?? 0);
$household_id = intval($input['household_id'] ?? 0);

if ($user_id_to_remove === $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You cannot remove yourself']);
    exit;
}

// Remove member from household
$stmt = $conn->prepare("DELETE FROM HOUSEHOLD_MEMBER WHERE ID_USER = ? AND ID_HOUSEHOLD = ?");
$stmt->bind_param('ii', $user_id_to_remove, $household_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Member removed successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to remove member']);
}
$stmt->close();
?>
