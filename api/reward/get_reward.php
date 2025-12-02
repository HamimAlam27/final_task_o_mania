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
    SELECT ID_REWARD, ID_USER, REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT AS REWARD_POINTS
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

$reward_data = $result->fetch_assoc();
$point_cost = $reward_data['REWARD_POINTS'] ?? null;
$reward_by = $reward_data['ID_USER'] ?? null;

if (is_null($point_cost) || is_null($reward_by)) {
  http_response_code(500);
  echo json_encode(['error' => 'Invalid reward data']);
  exit;
}

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
  header('Location: ../../reward-store.php?mode=error');
  exit;
}

// Deduct points and process reward redemption
$stmt_deduct = $conn->prepare("
    UPDATE POINTS
    SET TOTAL_POINTS = TOTAL_POINTS - ?
    WHERE ID_USER = ?");
$stmt_deduct->bind_param("ii", $point_cost, $user_id);

if ($stmt_deduct->execute()) {
  $notify_stmt = $conn->prepare("
    INSERT INTO NOTIFICATION (ID_USER, NOTIFICATION_TITLE, NOTIFICATION_MESSAGE, IS_READ, NOTIFICATION_CREATED, NOTIFICATION_TYPE, REFERENCE_ID)
    VALUES (?, 'Reward Redeemed', ?, 0, NOW(), 'reward', ?)");
  $notification_message = $reward_data['REWARD_NAME'] . " was redeemed by " . ($_SESSION['user_name'] ?? 'a user');
  $notify_stmt->bind_param("isi", $reward_by, $notification_message, $reward_id);
  $notify_stmt->execute();

  echo json_encode(['success' => 'Reward redeemed successfully']);
  header('Location: ../../reward-store.php?reward_id=' . $reward_id);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to redeem reward']);
  header('Location: ../../reward-store.php?mode=error');
}
?>