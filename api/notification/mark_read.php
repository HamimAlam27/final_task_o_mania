<?php
/**
 * Mark Notification as Read/Unread
 * 
 * POST endpoint to update IS_READ status in the notification table
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../src/config/db.php';

$userId = intval($_SESSION['user_id']);

// Get JSON payload
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['notification_id']) || !isset($input['is_read'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$notificationId = intval($input['notification_id']);
$isRead = intval($input['is_read']) === 1 ? 1 : 0;

// Update the notification's IS_READ status (ensure it belongs to the current user)
$stmt = $conn->prepare("UPDATE notification SET IS_READ = ? WHERE ID_NOTIFICATION = ? AND ID_USER = ?");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$stmt->bind_param('iii', $isRead, $notificationId, $userId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Notification updated']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update notification']);
}

$stmt->close();
$conn->close();
?>
