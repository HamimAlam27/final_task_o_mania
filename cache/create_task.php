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
$success_message = '';
$error_message = '';
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

          <div class="user-actions">
            <a class="notification-button" data-tooltip="Notifications" href="notifications.php" aria-label="Go to notifications">
              <svg aria-hidden="true" width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M11 2C7.686 2 5 4.686 5 8v1.383c0 .765-.293 1.5-.829 2.036l-.757.757C2.156 13.434 3.037 15.5 4.828 15.5h12.344c1.791 0 2.672-2.066 1.414-3.324l-.757-.757A2.882 2.882 0 0 1 17 9.383V8c0-3.314-2.686-6-6-6Z" stroke-linecap="round" />
                <path d="M8.5 18.5c.398 1.062 1.368 1.833 2.5 1.833 1.132 0 2.102-.771 2.5-1.833" stroke-linecap="round" />
              </svg>
            </a>
            <a class="avatar" data-tooltip="Profile" href="profile.php" aria-label="Your profile">
              <img src="IMAGES/avatar.png" alt="User avatar" />
            </a>
          </div>
        </header>

        <main class="page" role="main">
          <div id="error-message" style="display:none; margin-bottom: 20px; padding: 15px; background-color: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00;"></div>
          <div id="success-message" style="display:none; margin-top: 20px; padding: 15px; background-color: #efe; border: 1px solid #cfc; border-radius: 8px; color: #060; text-align: center;"></div>

          <form class="form" id="task-form" enctype="multipart/form-data">
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
              <div class="form-section__header">
                <h2>Validation</h2>
                <p>Review how this task will be approved.</p>
              </div>
              <label class="toggle">
                <input type="checkbox" name="ai_validation" />
                <span class="slider" aria-hidden="true"></span>
                <span class="toggle__label">Apply the AI Validation</span>
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
        const form = document.getElementById('task-form');
        const errorMsg = document.getElementById('error-message');
        const successMsg = document.getElementById('success-message');
        const splitToggle = document.getElementById('split-points-toggle');
        const splitChoices = document.querySelectorAll('.split-list input[type="checkbox"]');
        const splitContainer = document.querySelector('.split-points');

        if (!form) return;

        // Handle form submission via API
        form.addEventListener('submit', async function (event) {
          event.preventDefault();
          
          // Reset messages
          errorMsg.style.display = 'none';
          successMsg.style.display = 'none';

          try {
            const formData = new FormData(form);
            const response = await fetch('api/task/create.php', {
              method: 'POST',
              body: formData
            });

            const data = await response.json();

            if (response.ok) {
              successMsg.innerHTML = `
                <p>${data.message}</p>
                <p><a href="total_task_list_bt_columns.php" style="color: #060; text-decoration: underline;">View all tasks ➜</a></p>
              `;
              successMsg.style.display = 'block';
              form.reset();
              syncSplitState();
            } else {
              errorMsg.textContent = data.message || 'Failed to create task';
              errorMsg.style.display = 'block';
            }
          } catch (error) {
            console.error('Error:', error);
            errorMsg.textContent = 'An error occurred. Please try again.';
            errorMsg.style.display = 'block';
          }
        });

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









