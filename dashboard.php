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

// Fetch user info
$user_stmt = $conn->prepare("SELECT USER_NAME FROM USER WHERE ID_USER = ?");
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user_data = $user_result->fetch_assoc();
$user_name = $user_data['USER_NAME'] ?? 'User';
$user_stmt->close();

// Fetch household name
$household_stmt = $conn->prepare("SELECT HOUSEHOLD_NAME FROM HOUSEHOLD WHERE ID_HOUSEHOLD = ?");
$household_stmt->bind_param('i', $household_id);
$household_stmt->execute();
$household_result = $household_stmt->get_result();
$household_data = $household_result->fetch_assoc();
$household_name = $household_data['HOUSEHOLD_NAME'] ?? 'Household';
$household_stmt->close();

// Fetch user's credits for this household
$credits_stmt = $conn->prepare("SELECT TOTAL_POINTS FROM POINTS WHERE ID_USER = ? AND ID_HOUSEHOLD = ?");
$credits_stmt->bind_param('ii', $user_id, $household_id);
$credits_stmt->execute();
$credits_result = $credits_stmt->get_result();
$credits_data = $credits_result->fetch_assoc();
$user_credits = $credits_data['TOTAL_POINTS'] ?? 0;
$credits_stmt->close();

// Fetch active tasks (status = 'todo' or 'in_progress') for this household
$tasks_stmt = $conn->prepare("
  SELECT ID_TASK, TASK_NAME, TASK_POINT, ID_USER 
  FROM TASK 
  WHERE ID_HOUSEHOLD = ? AND TASK_STATUS IN ('todo', 'in_progress')
  ORDER BY ID_TASK DESC
  LIMIT 4
");
$tasks_stmt->bind_param('i', $household_id);
$tasks_stmt->execute();
$tasks_result = $tasks_stmt->get_result();
$active_tasks = [];
while ($task = $tasks_result->fetch_assoc()) {
  $active_tasks[] = $task;
}
$tasks_stmt->close();

// Fetch household members for activity
$members_stmt = $conn->prepare("
  SELECT DISTINCT u.ID_USER, u.USER_NAME 
  FROM USER u
  JOIN HOUSEHOLD_MEMBER hm ON u.ID_USER = hm.ID_USER
  WHERE hm.ID_HOUSEHOLD = ?
  LIMIT 10
");
$members_stmt->bind_param('i', $household_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();
$household_members = [];
while ($member = $members_result->fetch_assoc()) {
  $household_members[] = $member;
}
$members_stmt->close();

// Colors for activity cards
$activity_colors = ['#6b63ff', '#5fb3ff', '#ff7676', '#ffb563', '#6bff9c'];
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Dashboard - Task-o-Mania</title>
    <meta name="description" content="Task-o-Mania household dashboard overview." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_dashboard.css" />
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
          <div class="topbar__greeting">
            <p class="subtitle">Hello!</p>
            <h1><?php echo htmlspecialchars($user_name); ?></h1>
          </div>

          <?php include 'header.php'; ?>
        </header>

        <div class="content-fade" aria-hidden="true"></div>
        <main class="page" role="main">
          <section class="panel" id="tasks">
            <div class="panel__header">
              <h2>Active Tasks <span class="panel__count"><?php echo count($active_tasks); ?></span></h2>
              <a class="panel__link" href="total_task_list_bt_columns.php">See all ➜</a>
            </div>

            <div class="task-grid">
              <?php if (!empty($active_tasks)): ?>
                <?php foreach ($active_tasks as $task): ?>
                  <article class="task-card">
                    <header>
                      <p class="task-card__meta">Task</p>
                      <span class="task-card__badge" aria-label="Active task"></span>
                    </header>
                    <h3 class="task-card__title"><?php echo htmlspecialchars($task['TASK_NAME']); ?></h3>
                    <a class="task-card__credits" href="#"><?php echo intval($task['TASK_POINT']); ?> credits </a>
                    <div class="task-card__progress" role="progressbar" aria-valuenow="50" aria-valuemin="0" aria-valuemax="100">
                      <span style="width: 50%"></span>
                    </div>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="grid-column: 1 / -1; text-align: center; color: #999; padding: 40px 20px;">No active tasks in this household yet.</p>
              <?php endif; ?>
            </div>
          </section>

          <section class="panel" id="activity">
            <div class="panel__header">
              <h2>Recent Activity</h2>
              <a class="panel__link" href="activity.html">See all ➜</a>
            </div>

            <div class="activity-scroll" role="list">
              <?php if (!empty($household_members)): ?>
                <?php foreach ($household_members as $index => $member): ?>
                  <article class="activity-card" role="listitem">
                    <header>
                      <span class="activity-card__label"><?php echo htmlspecialchars($member['USER_NAME']); ?>'s activity</span>
                      <span class="activity-card__value">0<?php echo ($index + 1); ?></span>
                    </header>
                    <figure aria-label="Activity trend">
                      <svg viewBox="0 0 120 40" role="presentation" focusable="false">
                        <path d="M0 28 L20 24 L40 30 L60 18 L80 22 L100 8 L120 14" stroke="<?php echo htmlspecialchars($activity_colors[$index % count($activity_colors)]); ?>" stroke-width="3" fill="none" stroke-linecap="round" />
                      </svg>
                    </figure>
                    <p class="activity-card__delta"><span>0+</span> from this period</p>
                  </article>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="text-align: center; color: #999; padding: 40px 20px;">No household members found.</p>
              <?php endif; ?>
            </div>
          </section>
        </main></div>
    </div>
  </body>
</html>













