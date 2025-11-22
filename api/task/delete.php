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

// Verify task exists and user is the creator
$task_stmt = $conn->prepare("SELECT ID_USER FROM TASK WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?");
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

// Check if user is the creator
if ($task['ID_USER'] != $user_id) {
  $_SESSION['error'] = 'You can only delete tasks you created';
  header('Location: ../../task_list_detail.php?task_id=' . $task_id);
  exit;
}

// Delete the task (cascade delete will remove PROGRESS entries)
$delete_stmt = $conn->prepare("DELETE FROM TASK WHERE ID_TASK = ?");
$delete_stmt->bind_param('i', $task_id);

if ($delete_stmt->execute()) {
  $_SESSION['success'] = 'Task deleted successfully!';
  header('Location: ../../total_task_list_bt_columns.php');
} else {
  $_SESSION['error'] = 'Failed to delete task';
  header('Location: ../../task_list_detail.php?task_id=' . $task_id);
}
$delete_stmt->close();
exit;
?>
