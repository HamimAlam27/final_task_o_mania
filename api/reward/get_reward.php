<?php
session_start();
require '../../src/config/db.php';
// Validate user session
if (!isset($_SESSION['user_id'])) {
  http_response_code(401);
  echo json_encode(['error' => 'Unauthorized']);
  exit;
}
$user_id = $_SESSION['user_id'];
$reward_id = $_GET['reward_id'] ?? null;
if (!$reward_id) {
  http_response_code(400);
  echo json_encode(['error' => 'Bad Request: reward_id is required']);
  exit;
}
$stmt = $conn->prepare("
    SELECT ID_REWARD, REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT AS REWARD_POINTS, ID_USER
    FROM REWARDS_CATALOGUE
    WHERE ID_REWARD = ?");
$stmt->bind_param("i", $reward_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
  http_response_code(404);
  echo json_encode(['error' => 'Reward not found']);
  exit;
}

$point_cost = $result->fetch_assoc()['REWARD_POINTS'];
// Check if user has enough points
$stmt_points = $conn->prepare("
    SELECT TOTAL_POINTS
    FROM POINTS
    WHERE ID_USER = ?");
$stmt_points->bind_param("i", $user_id);
$stmt_points->execute();
$result_points = $stmt_points->get_result();
if ($result_points->num_rows === 0) {
  http_response_code(400);
  echo json_encode(['error' => 'User points not found']);
  exit;
}
$user_points = $result_points->fetch_assoc()['TOTAL_POINTS'];
if ($user_points < $point_cost) {
  http_response_code(400);
  echo json_encode(['error' => 'Insufficient points']);
  exit;
}
// Deduct points and process reward redemption
$stmt_deduct = $conn->prepare("
    UPDATE USER_POINTS
    SET POINTS = TOTAL_POINTS - ?
    WHERE ID_USER = ?");
$stmt_deduct->bind_param("ii", $point_cost, $user_id);
if ($stmt_deduct->execute()) {
  echo json_encode(['success' => 'Reward redeemed successfully']);
  header('Location: ../../reward-store.php');
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to redeem reward']);
}