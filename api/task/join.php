<?php
session_start();
require '../../src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
  $_SESSION['error'] = 'Unauthorized';
  header('Location: ../../total_task_list_bt_columns.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;
$task_id = intval($_POST['task_id'] ?? 0);

// Validate inputs
if (!$household_id || !$task_id) {
  $_SESSION['error'] = 'Invalid task';
  header('Location: ../../total_task_list_bt_columns.php');
  exit;
}

// Verify task belongs to this household
$task_stmt = $conn->prepare("SELECT ID_TASK, TASK_STATUS FROM TASK WHERE ID_TASK = ? AND ID_HOUSEHOLD = ? LIMIT 1");
$task_stmt->bind_param('ii', $task_id, $household_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
$task = $task_result->fetch_assoc();
$task_stmt->close();

if (!$task) {
  $_SESSION['error'] = 'Task not found';
  header('Location: ../../total_task_list_bt_columns.php');
  exit;
}

// Insert user into PROGRESS
$insert_stmt = $conn->prepare("INSERT INTO PROGRESS (ID_TASK, ID_USER) VALUES (?, ?)");
$insert_stmt->bind_param('ii', $task_id, $user_id);

if ($insert_stmt->execute()) {

  // ---------------------------------------------------
  // 🔥 NEW: Update task status to "in_progress"
  // ---------------------------------------------------
  $update_stmt = $conn->prepare("
      UPDATE TASK 
      SET TASK_STATUS = 'in_progress'
      WHERE ID_TASK = ? AND TASK_STATUS = 'todo'
  ");
  $update_stmt->bind_param('i', $task_id);
  $update_stmt->execute();
  $update_stmt->close();
  // ---------------------------------------------------

  $_SESSION['success'] = 'You are now working on this task!';
} else {
  $_SESSION['error'] = 'Failed to join task';
}

$insert_stmt->close();

// Redirect to task detail
header('Location: ../../task_list_detail.php?task_id=' . $task_id);
exit;
?>
