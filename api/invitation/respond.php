<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    http_response_code(401);
    exit();
}

require_once '../../src/config/db.php';

$user_id = $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['invitation_id']) || !isset($input['response'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Missing invitation_id or response']);
    http_response_code(400);
    exit();
}

$invitation_id = intval($input['invitation_id']);
$response = strtolower($input['response']);

if (!in_array($response, ['accept', 'reject'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid response. Must be accept or reject']);
    http_response_code(400);
    exit();
}

// Get invitation details and verify it's for this user
$query = "SELECT i.ID_INVITATION, i.ID_HOUSEHOLD, i.INVITED_EMAIL, u.USER_EMAIL
          FROM invitation i
          LEFT JOIN user u ON i.INVITED_BY = u.ID_USER
          WHERE i.ID_INVITATION = ? AND i.STATUS = 'pending'";

$stmt = $conn->prepare($query);
if (!$stmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    http_response_code(500);
    exit();
}

$stmt->bind_param("i", $invitation_id);
$stmt->execute();
$result = $stmt->get_result();
$invitation = $result->fetch_assoc();
$stmt->close();

if (!$invitation) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invitation not found or already processed']);
    http_response_code(404);
    exit();
}

// Verify the invitation is for the current user
$userQuery = "SELECT USER_EMAIL FROM user WHERE ID_USER = ?";
$userStmt = $conn->prepare($userQuery);
if (!$userStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    http_response_code(500);
    exit();
}

$userStmt->bind_param("i", $user_id);
$userStmt->execute();
$userResult = $userStmt->get_result();
$user = $userResult->fetch_assoc();
$userStmt->close();

if ($user['USER_EMAIL'] !== $invitation['INVITED_EMAIL']) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'This invitation is not for you']);
    http_response_code(403);
    exit();
}

// Update invitation status
$newStatus = ($response === 'accept') ? 'accepted' : 'rejected';
$updateQuery = "UPDATE invitation SET STATUS = ? WHERE ID_INVITATION = ?";
$updateStmt = $conn->prepare($updateQuery);
if (!$updateStmt) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    http_response_code(500);
    exit();
}

$updateStmt->bind_param("si", $newStatus, $invitation_id);
if (!$updateStmt->execute()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to update invitation']);
    http_response_code(500);
    $updateStmt->close();
    exit();
}
$updateStmt->close();

// If accepted, add user to household_member table
// Triggers will automatically:
// - tr_create_points_on_household_join: Create POINTS record with 0 points
// - tr_notify_on_household_invitation: Create notification for this invitation (already done on INSERT)
if ($response === 'accept') {
    $role = 'member'; // Default role
    $memberQuery = "INSERT INTO household_member (ID_HOUSEHOLD, ID_USER, ROLE) VALUES (?, ?, ?)";
    $memberStmt = $conn->prepare($memberQuery);
    if (!$memberStmt) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        http_response_code(500);
        exit();
    }

    $memberStmt->bind_param("iis", $invitation['ID_HOUSEHOLD'], $user_id, $role);
    if (!$memberStmt->execute()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Failed to add user to household']);
        http_response_code(500);
        $memberStmt->close();
        exit();
    }
    $memberStmt->close();
}

header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => 'Invitation ' . $response . 'ed successfully']);
http_response_code(200);
