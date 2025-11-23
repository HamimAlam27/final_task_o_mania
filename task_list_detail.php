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

$is_creator = ($task['ID_USER'] == $user_id);

// Normalize task status (spaces and hyphens -> underscores)
$raw_status = $task['TASK_STATUS'] ?? '';
$normalized_status = strtolower(str_replace([' ', '-'], '_', $raw_status));

// Check if user is already in progress on this task
$progress_stmt = $conn->prepare("
  SELECT ID_PROGRESS FROM PROGRESS 
  WHERE ID_TASK = ? AND ID_USER = ?
");$progress_stmt->bind_param('ii', $task_id, $user_id);
$progress_stmt->execute();
$progress_result = $progress_stmt->get_result();
$already_joined = $progress_result->num_rows > 0;
  $progress_stmt->close();

// Flash helpers
$error_message = '';
$success_message = '';
if (isset($_SESSION['error'])) {
  $error_message = $_SESSION['error'];
  unset($_SESSION['error']);
}
if (isset($_SESSION['success'])) {
  $success_message = $_SESSION['success'];
  unset($_SESSION['success']);
}

// Handle submission / approval from this page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['submit_completion'])) {
    if (!$already_joined) {
      $_SESSION['error'] = 'You must be working on this task before submitting proof.';
      header('Location: task_list_detail.php?task_id=' . $task_id);
      exit;
    }

    if (!empty($_FILES['completion_image']['name']) && $_FILES['completion_image']['error'] === UPLOAD_ERR_OK) {
      $uploadsDir = __DIR__ . '/uploads';
      if (!is_dir($uploadsDir)) {
        @mkdir($uploadsDir, 0775, true);
      }
      $ext = pathinfo($_FILES['completion_image']['name'], PATHINFO_EXTENSION);
      $safeExt = $ext ? preg_replace('/[^a-zA-Z0-9]/', '', $ext) : 'jpg';
      if ($safeExt === '') {
        $safeExt = 'jpg';
      }
      $filename = sprintf('task_%d_user_%d_%d.%s', $task_id, $user_id, time(), $safeExt);
      move_uploaded_file($_FILES['completion_image']['tmp_name'], $uploadsDir . '/' . $filename);
    }

    $status = 'pending';
    $update_stmt = $conn->prepare(" 
      UPDATE TASK
      SET TASK_STATUS = ?
      WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?
    ");
    $update_stmt->bind_param('sii', $status, $task_id, $household_id);
    if ($update_stmt->execute()) {
      $_SESSION['success'] = 'Task submitted successfully and is pending approval.';
    } else {
      $_SESSION['error'] = 'Failed to submit the task.';
    }

    $update_stmt->close();
  }

  if (isset($_POST['approve_task']) || isset($_POST['reject_task'])) {
    if (!$is_creator) {
      $_SESSION['error'] = 'Only the creator can verify this task.';
      header('Location: task_list_detail.php?task_id=' . $task_id);
      exit;
    }

    $new_status = isset($_POST['approve_task']) ? 'completed' : 'todo';

    // When approving, optionally split points among selected assignees
    if ($new_status === 'completed') {
      $selected_assignees = isset($_POST['assignees']) && is_array($_POST['assignees'])
        ? array_map('intval', $_POST['assignees'])
        : [];

      if (!empty($selected_assignees)) {
        $total_points = (int)($task['TASK_POINT'] ?? 0);
        $share = $total_points > 0 ? intdiv($total_points, count($selected_assignees)) : 0;

        if ($share > 0) {
          $points_stmt = $conn->prepare("
            INSERT INTO POINTS (ID_USER, ID_HOUSEHOLD, TOTAL_POINTS)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE TOTAL_POINTS = TOTAL_POINTS + VALUES(TOTAL_POINTS)
          ");

          foreach ($selected_assignees as $assignee_id) {
            $points_stmt->bind_param('iii', $assignee_id, $household_id, $share);
            $points_stmt->execute();
          }

          $points_stmt->close();
        }
      }
    }

    $verify_stmt = $conn->prepare(" 
      UPDATE TASK
      SET TASK_STATUS = ?
      WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?
    ");
    $verify_stmt->bind_param('sii', $new_status, $task_id, $household_id);
    if ($verify_stmt->execute()) {
      $_SESSION['success'] = $new_status === 'completed'
        ? 'Task marked as completed and points awarded.'
        : 'Task rejected and returned to To Do.';
    } else {
      $_SESSION['error'] = 'Failed to update task status.';
    }

    $verify_stmt->close();
  }

  header('Location: task_list_detail.php?task_id=' . $task_id);
  exit;
}

// Fetch task creator's name
$creator_stmt = $conn->prepare("SELECT USER_NAME FROM USER WHERE ID_USER = ?");
$creator_stmt->bind_param('i', $task['ID_USER']);
$creator_stmt->execute();
$creator_result = $creator_stmt->get_result();
$creator = $creator_result->fetch_assoc();
$creator_name = $creator['USER_NAME'] ?? 'Unknown';
$creator_stmt->close();

// Flash state for join/submission flows + submission handling

if (isset($_SESSION['error'])) {
  $error_message = $_SESSION['error'];
  unset($_SESSION['error']);
}

if (isset($_SESSION['success'])) {
  $success_message = $_SESSION['success'];
  unset($_SESSION['success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_completion'])) {
  if (!$already_joined) {
    $_SESSION['error'] = 'Join the task before submitting proof.';
    header('Location: task_list_detail.php?task_id=' . $task_id);
    exit;
  }

  if (!empty($_FILES['completion_image']['name']) && $_FILES['completion_image']['error'] === UPLOAD_ERR_OK) {
    $uploadsDir = __DIR__ . '/uploads';
    if (!is_dir($uploadsDir)) {
      @mkdir($uploadsDir, 0775, true);
    }

    $ext = pathinfo($_FILES['completion_image']['name'], PATHINFO_EXTENSION);
    $safeExt = $ext ? preg_replace('/[^a-zA-Z0-9]/', '', $ext) : 'jpg';
    if ($safeExt === '') {
      $safeExt = 'jpg';
    }

    $filename = sprintf('task_%d_user_%d_%d.%s', $task_id, $user_id, time(), $safeExt);
    $targetPath = $uploadsDir . '/' . $filename;
    move_uploaded_file($_FILES['completion_image']['tmp_name'], $targetPath);
  }

  $update_stmt = $conn->prepare("
    UPDATE TASK
    SET TASK_STATUS = 'pending'
    WHERE ID_TASK = ? AND ID_HOUSEHOLD = ?
  ");
  $update_stmt->bind_param('ii', $task_id, $household_id);

  if ($update_stmt->execute()) {
    $_SESSION['success'] = 'Task submitted successfully and marked as pending.';
  } else {
    $_SESSION['error'] = 'Failed to submit the task.';
  }

  $update_stmt->close();

  header('Location: task_list_detail.php?task_id=' . $task_id);
  exit;
}

// Fetch people working on this task (for in-progress or pending review)
$workers = [];
if (in_array($normalized_status, ['in_progress', 'pending'], true)) {
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

            <?php if (in_array($normalized_status, ['in_progress', 'pending'], true) && !empty($workers)): ?>
              <div class="task-detail__section">
                <div class="task-detail__label">
                  <?php echo $normalized_status === 'pending' ? 'Submitted by' : 'People working on this'; ?>
                </div>
                <div class="workers">
                  <?php foreach ($workers as $worker): ?>
                    <?php if ($normalized_status === 'pending' && $is_creator): ?>
                      <label class="worker-tag">
                        <input
                          type="checkbox"
                          name="assignees[]"
                          value="<?php echo intval($worker['ID_USER']); ?>"
                          checked
                          style="margin-right: 6px;"
                        />
                        <?php echo htmlspecialchars($worker['USER_NAME']); ?>
                      </label>
                    <?php else: ?>
                      <div class="worker-tag"><?php echo htmlspecialchars($worker['USER_NAME']); ?></div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <div class="task-detail__actions">
              <a href="total_task_list_bt_columns.php" class="btn btn-secondary">← Back</a>

              <?php if ($is_creator): ?>
                <?php if ($normalized_status === 'pending'): ?>
                  <form method="POST" action="task_list_detail.php?task_id=<?php echo intval($task_id); ?>" style="display: flex; gap: 8px;">
                    <button type="submit" name="approve_task" class="btn btn-primary">Approve</button>
                    <button type="submit" name="reject_task" class="btn btn-secondary">Reject</button>
                  </form>
                <?php else: ?>
                  <!-- Task creator view: Show delete button for non-pending tasks -->
                  <form method="POST" action="api/task/delete.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this task?');">
                    <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                    <button type="submit" class="btn btn-danger">Delete Task</button>
                  </form>
                <?php endif; ?>
              <?php else: ?>
                <!-- Other users view: Show "Do this task" button if in todo status and not already joined -->
                <?php if ($normalized_status === 'todo' && !$already_joined): ?>
                  <form method="POST" action="api/task/join.php" style="display: inline;">
                    <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                    <button type="submit" class="btn btn-primary">Do this task</button>
                  </form>
                <?php elseif ($normalized_status === 'todo' && $already_joined): ?>
                  <button class="btn btn-secondary" disabled>Already in progress</button>
                <?php elseif ($normalized_status === 'in_progress' && !$already_joined): ?>
                  <form method="POST" action="api/task/join.php" style="display: inline;">
                    <input type="hidden" name="task_id" value="<?php echo intval($task_id); ?>" />
                    <button type="submit" class="btn btn-primary">Join this task</button>
                  </form>
                <?php elseif ($normalized_status === 'in_progress' && $already_joined): ?>
                  <form method="POST" enctype="multipart/form-data" action="task_list_detail.php?task_id=<?php echo intval($task_id); ?>" style="display: inline;">
                    <label class="btn btn-secondary" for="completion-image">Submit proof</label>
                    <input type="file" name="completion_image" id="completion-image" accept="image/*" style="display:none;" />
                    <button type="submit" name="submit_completion" class="btn btn-primary">Submit task</button>
                  </form>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </main>
      </div>
    </div>
  </body>
</html>
