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
$show_success_modal = false;

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
    $show_success_modal = true;
    $task_name = '';
    $task_points = '150';
    $task_description = '';
    $ai_validation = 0;
    $image_needed = 0;
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
    <link rel="stylesheet" href="style_create_reward.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    lucide.createIcons();
  });
</script>

    <style>
      .form-field--upload label {
        font-size: 14px;
        font-weight: 600;
        color: rgba(22, 22, 29, 0.75);
      }

      .upload {
        display: grid;
        place-items: center;
        padding: 20px;
        border: 2px dashed rgba(132, 124, 255, 0.35);
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.65);
        cursor: pointer;
        font-weight: 600;
        color: rgba(22, 22, 29, 0.6);
      }

      .upload input {
        display: none;
      }

      .form-toggle-group {
        display: flex;
        flex-wrap: wrap;
        gap: 16px;
        justify-content: center;
      }

      .toggle {
        display: inline-flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        color: rgba(22, 22, 29, 0.65);
      }

      .toggle input {
        width: 0;
        height: 0;
        opacity: 0;
      }

      .slider {
        position: relative;
        width: 54px;
        height: 28px;
        border-radius: 999px;
        background: rgba(147, 142, 255, 0.3);
        transition: background 0.2s ease;
      }

      .slider::after {
        content: "";
        position: absolute;
        width: 24px;
        height: 24px;
        border-radius: 50%;
        background: #fff;
        top: 2px;
        left: 2px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        transition: transform 0.2s ease;
      }

      .toggle input:checked + .slider {
        background: linear-gradient(120deg, var(--accent-1), var(--accent-2));
      }

      .toggle input:checked + .slider::after {
        transform: translateX(26px);
      }

      .toggle__label {
        font-size: 15px;
      }

      .success-modal {
        position: fixed;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(13, 7, 34, 0.55);
        backdrop-filter: blur(12px);
        padding: 24px;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.25s ease;
        z-index: 1000;
      }

      .success-modal--visible {
        opacity: 1;
        pointer-events: auto;
      }

      .success-modal__card {
        width: min(420px, 100%);
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 26px;
        padding: clamp(24px, 4vw, 36px);
        text-align: center;
        box-shadow: 0 32px 70px rgba(44, 25, 94, 0.28);
      }

      .success-modal__card h2 {
        margin: 0 0 8px;
        font-size: 26px;
      }

      .success-modal__actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        flex-wrap: wrap;
      }

      .success-modal__primary {
        border-radius: 999px;
        border: none;
        padding: 10px 20px;
        font-weight: 600;
        cursor: pointer;
        background: linear-gradient(135deg, #7c65ff, #9087ff);
        color: #fff;
        box-shadow: 0 16px 32px rgba(124, 101, 255, 0.35);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .success-modal__primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 22px 40px rgba(124, 101, 255, 0.4);
      }

      .form-alert {
        width: min(560px, 100%);
        margin: 0 auto;
        padding: 14px 18px;
        border-radius: 16px;
        font-size: 14px;
        font-weight: 600;
        text-align: center;
      }

      .form-alert--error {
        background: rgba(255, 107, 107, 0.12);
        border: 1px solid rgba(255, 107, 107, 0.3);
        color: #b32424;
      }

      .form-alert--success {
        background: rgba(124, 101, 255, 0.1);
        border: 1px solid rgba(124, 101, 255, 0.25);
        color: #4b2dbd;
      }
    </style>

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
            <div class="form-alert form-alert--error">
              <?php echo htmlspecialchars($error_message); ?>
            </div>
          <?php endif; ?>

          <form class="form form--create" id="task-form" enctype="multipart/form-data" method="POST">
            <div class="form-field">
              <label for="task-name">Task Name <span aria-hidden="true">*</span></label>
              <div class="input-wrapper">
                <input
                  id="task-name"
                  name="task_name"
                  type="text"
                  value="<?php echo htmlspecialchars($task_name); ?>"
                  placeholder="Saturday chores"
                  required />
              </div>
            </div>

            <div class="form-field form-field--small">
              <label for="task-points">Points <span aria-hidden="true">*</span></label>
              <div class="input-wrapper">
                <input
                  id="task-points"
                  name="task_points"
                  type="number"
                  min="0"
                  value="<?php echo htmlspecialchars($task_points); ?>"
                  required />
              </div>
            </div>

            <div class="form-field">
              <label for="task-description">Task Description</label>
              <div class="input-wrapper">
                <textarea
                  id="task-description"
                  name="task_description"
                  rows="4"
                  placeholder="Describe what needs to be done in detail."><?php echo htmlspecialchars($task_description); ?></textarea>
              </div>
            </div>

            <div class="form-field form-field--upload">
              <label for="task-photo">Add Photo</label>
              <label class="upload" for="task-photo">
                <input id="task-photo" name="task_photo" type="file" accept="image/*" />
                <span>Upload your photo</span>
              </label>
            </div>

            <div class="form-toggle-group">
              <label class="toggle">
                <input type="checkbox" name="ai_validation" <?php echo $ai_validation ? 'checked' : ''; ?> />
                <span class="slider" aria-hidden="true"></span>
                <span class="toggle__label">Apply the AI Validation</span>
              </label>
              <label class="toggle">
                <input type="checkbox" name="image_needed" <?php echo $image_needed ? 'checked' : ''; ?> />
                <span class="slider" aria-hidden="true"></span>
                <span class="toggle__label">Image Needed</span>
              </label>
            </div>

            <div class="actions">
              <button type="submit" class="btn-primary">Add Task</button>
            </div>
          </form>
        </main>
      </div>
    </div>

    <div class="success-modal" role="alertdialog" aria-modal="true" aria-labelledby="success-title" aria-hidden="true">
      <div class="success-modal__card">
        <h2 id="success-title">Task successfully created</h2>
        <div class="success-modal__actions">
          <button type="button" class="success-modal__primary" id="success-modal-ok">OK</button>
        </div>
      </div>
    </div>

    <script>
      (function () {
        const modal = document.querySelector('.success-modal');
        const okButton = document.getElementById('success-modal-ok');
        const shouldShowSuccessModal = <?php echo $show_success_modal ? 'true' : 'false'; ?>;

        const toggleModal = (visible) => {
          if (!modal) return;
          modal.setAttribute('aria-hidden', visible ? 'false' : 'true');
          modal.classList.toggle('success-modal--visible', visible);
        };

        if (modal && okButton) {
          okButton.addEventListener('click', () => {
            toggleModal(false);
            window.location.href = 'total_task_list_bt_columns.php';
          });

          modal.addEventListener('click', (event) => {
            if (event.target === modal) {
              toggleModal(false);
              window.location.href = 'total_task_list_bt_columns.php';
            }
          });

          window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && modal.classList.contains('success-modal--visible')) {
              toggleModal(false);
            }
          });

          if (shouldShowSuccessModal) {
            toggleModal(true);
          }
        }
      })();
    </script>
  </body>
</html>







