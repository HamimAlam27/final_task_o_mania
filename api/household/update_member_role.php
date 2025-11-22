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
$user_id_to_update = intval($input['user_id'] ?? 0);
$new_role = strtolower($input['role'] ?? '');
$household_id = intval($input['household_id'] ?? 0);

if (!in_array($new_role, ['admin', 'member'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid role']);
    exit;
}

// Update member role
$stmt = $conn->prepare("UPDATE HOUSEHOLD_MEMBER SET ROLE = ? WHERE ID_USER = ? AND ID_HOUSEHOLD = ?");
$stmt->bind_param('sii', $new_role, $user_id_to_update, $household_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Role updated successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to update role']);
}
$stmt->close();
?>
