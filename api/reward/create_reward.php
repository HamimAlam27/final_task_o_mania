<?php
session_start();
require '../../src/config/db.php';
// Validate user session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['household_id'])) {
  $_SESSION['error'] = 'Unauthorized';
  header('Location: ../../sign-in.php');
  exit;
}
$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;

$reward_name = trim($_POST['reward_name'] ?? '');
$reward_description = trim($_POST['reward_description'] ?? '');
$reward_points = intval($_POST['reward_points'] ?? 0);

// Validate inputs
$reward_query = "INSERT INTO REWARDS_CATALOGUE (ID_HOUSEHOLD, REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT, IS_ACTIVE) VALUES (?, ?, ?, ?, 1)";
$reward_stmt = $conn->prepare($reward_query);
$reward_stmt->bind_param('isss', $household_id, $reward_name, $reward_description, $reward_points);
if ($reward_stmt->execute()) {
  $_SESSION['success'] = 'Reward created successfully';
    header('Location: ../../reward-store.php');
} else {
  $_SESSION['error'] = 'Failed to create reward';
}