<?php
session_start();
require 'src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
  header('Location: sign-in.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;
$task_id = intval($_GET['task_id'] ?? 0);

// If no household selected or invalid task_id, redirect
if (!$household_id || !$task_id) {
  header('Location: total_task_list_bt_columns.php');
  exit;
}

// Fetch task details
$task_stmt = $conn->prepare("
  SELECT ID_TASK, TASK_NAME, TASK_DESCRIPTION, TASK_POINT, TASK_IMAGE, TASK_STATUS, ID_USER, TASK_CREATED
  FROM TASK 
  WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?
");
$task_stmt->bind_param('ii', $task_id, $household_id);
$task_stmt->execute();
$task_result = $task_stmt->get_result();
$task = $task_result->fetch_assoc();
$task_stmt->close();

// If task not found, redirect
if (!$task) {
  header('Location: total_task_list_bt_columns.php');
  exit;
}

// Check if current user is the task creator
$is_creator = ($task['ID_USER'] == $user_id);

// Check if user is already in progress on this task
$progress_stmt = $conn->prepare("
  SELECT ID_PROGRESS FROM PROGRESS 
  WHERE ID_TASK = ? AND ID_USER = ?
");
$progress_stmt->bind_param('ii', $task_id, $user_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$already_joined = $progress_result->num_rows > 0;
$progress_stmt->close();

// Fetch task creator's name
$creator_stmt = $conn->prepare("SELECT USER_NAME FROM USER WHERE ID_USER = ?");
$creator_stmt->bind_param('i', $task['ID_USER']);
$creator_stmt->execute();
$creator_result = $creator_stmt->get_result();
$creator = $creator_result->fetch_assoc();
$creator_name = $creator['USER_NAME'] ?? 'Unknown';
$creator_stmt->close();

// Fetch people working on this task (if status is in_progress)
$workers = [];
if ($task['TASK_STATUS'] === 'in_progress') {
  $workers_stmt = $conn->prepare("
    SELECT u.ID_USER, u.USER_NAME 
    FROM PROGRESS p
    JOIN USER u ON p.ID_USER = u.ID_USER
    WHERE p.ID_TASK = ?
  ");
  $workers_stmt->bind_param('i', $task_id);
  $workers_stmt->execute();
  $workers_result = $workers_stmt->get_result();
  while ($worker = $workers_result->fetch_assoc()) {
    $workers[] = $worker;
  }
  $workers_stmt->close();
}

$error_message = '';
$success_message = '';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Task Details - Task-o-Mania</title>
    <meta name="description" content="Task details and actions." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_task_list.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        lucide.createIcons();
      });
    </script>
    <style>
      .task-detail {
        max-width: 600px;
        margin: 40px auto;
        padding: 30px;
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      }
      .task-detail__header {
        margin-bottom: 30px;
        border-bottom: 2px solid #eee;
        padding-bottom: 20px;
      }
      .task-detail__title {
        font-size: 28px;
        font-weight: 700;
        margin: 0 0 10px 0;
        color: #1a1a1a;
      }
      .task-detail__meta {
        display: flex;
        gap: 20px;
        font-size: 14px;
        color: #666;
      }
      .task-detail__section {
        margin-bottom: 25px;
      }
      .task-detail__label {
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
        color: #999;
        margin-bottom: 8px;
      }
      .task-detail__value {
        font-size: 16px;
        color: #333;
        line-height: 1.6;
      }
      .task-detail__points {
        display: inline-block;
        background: linear-gradient(135deg, #7c65ff, #9087ff);
        color: white;
        padding: 6px 12px;
        border-radius: 6px;
        font-weight: 600;
      }
      .task-detail__image {
        width: 100%;
        max-height: 300px;
        object-fit: cover;
        border-radius: 8px;
        margin: 15px 0;
      }
      .task-detail__actions {
        display: flex;
        gap: 10px;
        margin-top: 30px;
        padding-top: 20px;
        border-top: 2px solid #eee;
      }
      .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        text-decoration: none;
        display: inline-block;
        transition: all 0.3s ease;
      }
      .btn-primary {
        background: linear-gradient(135deg, #7c65ff, #9087ff);
        color: white;
      }
      .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(124, 101, 255, 0.3);
      }
      .btn-danger {
        background: #ff6b6b;
        color: white;
      }
      .btn-danger:hover {
        background: #ff5252;
      }
      .btn-secondary {
        background: #f0f0f0;
        color: #333;
      }
      .btn-secondary:hover {
        background: #e0e0e0;
      }
      .workers {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
      }
      .worker-tag {
        background: #f0f0f0;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        color: #666;
      }
      .message {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
        font-size: 14px;
      }
      .message.error {
        background: #fee;
        color: #c00;
        border: 1px solid #fcc;
      }
      .message.success {
        background: #efe;
        color: #060;
        border: 1px solid #cfc;
      }
    </style>
  </head>
  <body>
    <div class="background" aria-hidden="true"></div>

    <div class="dashboard-shell">
      <?php include 'sidebar.php'; ?>

      <div class="content">
        <header class="topbar">
          <div class="topbar__greeting">
            <p class="subtitle">Task Details</p>
            <h1><?php echo htmlspecialchars($task['TASK_NAME']); ?></h1>
          </div>

          <?php include 'header.php'; ?>
        </header>

        <main class="page" role="main">
          <?php if (!empty($error_message)): ?>
            <div class="message error"><?php echo htmlspecialchars($error_message); ?></div>
          <?php endif; ?>
          <?php if (!empty($success_message)): ?>
            <div class="message success"><?php echo htmlspecialchars($success_message); ?></div>
          <?php endif; ?>

          <div class="task-detail">
            <div class="task-detail__header">
              <h2 class="task-detail__title"><?php echo htmlspecialchars($task['TASK_NAME']); ?></h2>
              <div class="task-detail__meta">
                <span><strong><?php echo htmlspecialchars($creator_name); ?></strong> created this</span>
                <span><?php echo htmlspecialchars($task['TASK_STATUS']); ?></span>
                <span class="task-detail__points"><?php echo intval($task['TASK_POINT']); ?> pts</span>
              </div>
            </div>

            <?php if ($task['TASK_IMAGE']): ?>
              <div class="task-detail__section">
                <img src="data:image/jpeg;base64,<?php echo base64_encode($task['TASK_IMAGE']); ?>" alt="Task image" class="task-detail__image" />
              </div>
            <?php endif; ?>

            <div class="task-detail__section">
              <div class="task-detail__label">Description</div>
              <div class="task-detail__value">
                <?php echo htmlspecialchars($task['TASK_DESCRIPTION'] ?? 'No description provided'); ?>
              </div>
            </div>

            <?php if ($task['TASK_STATUS'] === 'in_progress' && !empty($workers)): ?>
              <div class="task-detail__section">
                <div class="task-detail__label">People working on this</div>
                <div class="workers">
                  <?php foreach ($workers as $worker): ?>
                    <div class="worker-tag"><?php echo htmlspecialchars($worker['USER_NAME']); ?></div>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="task-detail__actions">
              <a href="total_task_list_bt_columns.php" class="btn btn-secondary">← Back</a>

              <?php if ($is_creator): ?>
                <!-- Task creator view: Show delete button -->
                <form method="POST" action="api/task/delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                  <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                  <button type="submit" class="btn btn-danger">Delete Task</button>
                </form>
              <?php else: ?>
                <!-- Other users view: Show "Do this task" button if in todo status and not already joined -->
                <?php if ($task['TASK_STATUS'] === 'todo' && !$already_joined): ?>
                  <form method="POST" action="api/task/join.php" style="display: inline;">
                    <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                    <button type="submit" class="btn btn-primary">Do this task</button>
                  </form>
                <?php elseif ($task['TASK_STATUS'] === 'todo' && $already_joined): ?>
                  <button class="btn btn-secondary" disabled>Already in progress</button>
                <?php elseif ($task['TASK_STATUS'] === 'in_progress' && !$already_joined): ?>
                  <form method="POST" action="api/task/join.php" style="display: inline;">
                    <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                    <button type="submit" class="btn btn-primary">Join this task</button>
                  </form>
                <?php elseif ($task['TASK_STATUS'] === 'in_progress' && $already_joined): ?>
                  <button class="btn btn-secondary" disabled>You're working on this</button>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
