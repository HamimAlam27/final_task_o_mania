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

// If no household selected, redirect to households page
if (!$household_id) {
  header('Location: households.php');
  exit;
}

// Initialize variables for form display
$task_name = '';
$task_points = '150';
$task_description = '';
$ai_validation = 0;
$image_needed = 0;
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $task_name = $_POST['task_name'] ?? '';
  $task_points = $_POST['task_points'] ?? '150';
  $task_description = $_POST['task_description'] ?? '';
  $ai_validation = isset($_POST['ai_validation']) ? 1 : 0;
  $image_needed = isset($_POST['image_needed']) ? 1 : 0;
  $image_before = null;
  $task_status = 'todo';

  // Handle image upload
  if (isset($_FILES['task_photo']) && $_FILES['task_photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'images/tasks/';
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }
    $ext = pathinfo($_FILES['task_photo']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('task_') . '.' . $ext;
    $target = $upload_dir . $filename;
    if (move_uploaded_file($_FILES['task_photo']['tmp_name'], $target)) {
      $image_before = $filename;
    }
  }

  // Insert into TASK table
  $stmt = $conn->prepare("INSERT INTO TASK (ID_HOUSEHOLD, ID_USER, TASK_NAME, TASK_DESCRIPTION, TASK_POINT, IMAGE_NEEDED, AI_VALIDATION, IMAGE_BEFORE, TASK_STATUS) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
  $stmt->bind_param('iissiiiss', $household_id, $user_id, $task_name, $task_description, $task_points, $image_needed, $ai_validation, $image_before, $task_status);
  if ($stmt->execute()) {
    $success_message = 'Task created successfully!';
  } else {
    $error_message = 'Failed to create task.';
  }
  $stmt->close();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Task - Task-o-Mania</title>
    <meta name="description" content="Add a new task for your household members." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_create_task.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    lucide.createIcons();
  });
</script>

  </head>
  <body>
    <div class="background" aria-hidden="true"></div>

    <div class="dashboard-shell">
      <?php include 'sidebar.php'; ?>

      <div class="content">
        <header class="topbar">
          <button class="back-btn" type="button" aria-label="Go back" onclick="history.back()">
            <span aria-hidden="true">&#x2039;</span>
          </button>

          <h1 class="page-title">Create Task</h1>

          <?php include 'header.php'; ?>
        </header>

        <main class="page" role="main">
          <?php if (!empty($error_message)): ?>
            <div id="error-message" style="margin-bottom: 20px; padding: 15px; background-color: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00;">
              <?php echo htmlspecialchars($error_message); ?>
            </div>
          <?php endif; ?>
          <?php if (!empty($success_message)): ?>
            <div id="success-message" style="margin-top: 20px; padding: 15px; background-color: #efe; border: 1px solid #cfc; border-radius: 8px; color: #060; text-align: center;">
              <?php echo htmlspecialchars($success_message); ?>
              <br><a href="total_task_list_bt_columns.php" style="color: #060; text-decoration: underline;">View all tasks ➜</a>
            </div>
          <?php endif; ?>

          <form class="form" id="task-form" enctype="multipart/form-data" method="POST">
            <section class="form-section">
              <div class="form-section__header">
                <h2>Task basics</h2>
                <p>Give this task a name and decide how the points are handled.</p>
              </div>
              <div class="form-field">
                <label for="task-name">Task Name <span aria-hidden="true">*</span></label>
                <div class="input-wrapper">
                  <input id="task-name" name="task_name" type="text" value="" required />
                </div>
              </div>

              <div class="form-field form-field--points">
                <label for="task-points">Points <span aria-hidden="true">*</span></label>
                <div class="input-wrapper input-wrapper--points">
                  <input id="task-points" name="task_points" type="number" min="0" value="150" required />
                </div>
              </div>
            <section class="form-section">
              <div class="form-section__header">
                <h2>Attachments</h2>
                <p>Add the task before it is completed.</p>
              </div>
              <div class="form-field form-field--upload">
                <label for="task-photo">+ Add Photo <span aria-hidden="true">*</span></label>
                <label class="upload" for="task-photo">
                  <input id="task-photo" name="task_photo" type="file" accept="image/*" />
                  <span>Upload your photo</span>
                </label>
              </div>
            </section>

            <section class="form-section">
              <div class="form-section__header">
                <h2>Description</h2>
              </div>
              <div class="form-field">
                <label for="task-description">Task Description</label>
                <div class="input-wrapper">
                  <textarea id="task-description" name="task_description" rows="4" placeholder="Describe what needs to be done"></textarea>
                </div>
              </div>
            </section>

            <section class="form-section form-section--options">

              <label class="toggle">
                <input type="checkbox" name="ai_validation" />
                <span class="slider" aria-hidden="true"></span>
                <span class="toggle__label">Apply the AI Validation</span>
</label>
<label class="toggle">
                <input type="checkbox" name="image_needed" />
                <span class="slider" aria-hidden="true"></span>
                <span class="toggle__label">Image Needed</span>
              </label>

              <div class="actions">
                <button type="submit" class="btn-primary">Add Task</button>
              </div>
            </section>
          </form>
        </main></div>
      </div>
    </div>

    <script>
      (function () {
        const splitToggle = document.getElementById('split-points-toggle');
        const splitChoices = document.querySelectorAll('.split-list input[type="checkbox"]');
        const splitContainer = document.querySelector('.split-points');

        // Split points toggle functionality (kept for UI consistency)
        const syncSplitState = () => {
          if (!splitToggle || !splitContainer) return;
          const enabled = splitToggle.checked;
          splitContainer.classList.toggle('split-points--disabled', !enabled);
          splitChoices.forEach((choice) => {
            choice.disabled = !enabled;
            if (!enabled) choice.checked = false;
          });
        };

        if (splitToggle) {
          syncSplitState();
          splitToggle.addEventListener('change', syncSplitState);
        }
      })();
    </script>
  </body>
</html>









