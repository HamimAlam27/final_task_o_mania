<?php
session_start();
require 'src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
  header('Location: sign-in.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Validate household session
if (!isset($_SESSION['household_id'])) {
  header('Location: households.php');
  exit;
}

$current_household_id = intval($_SESSION['household_id']);

$tasks_by_status = [
  'todo' => [],
  'in_progress' => [],
  'pending' => [],
  'completed' => []
];

// Use only the current session household
$household_ids = [$current_household_id];

// Fetch tasks for ONLY the selected household
$placeholders = '?';

$task_query = $conn->prepare("
  SELECT 
    ID_TASK,
    ID_USER,
    TASK_NAME,
    TASK_POINT,
    TASK_STATUS
  FROM task 
  WHERE ID_HOUSEHOLD IN ($placeholders)
  ORDER BY TASK_CREATED DESC
");
$task_query->bind_param('i', $household_ids[0]);
$task_query->execute();
$task_result = $task_query->get_result();

// Helper statements for worker/completion checks
$progress_check = $conn->prepare("
  SELECT 1 
  FROM PROGRESS 
  WHERE ID_TASK = ? AND ID_USER = ? 
  LIMIT 1
");

$completion_check = $conn->prepare("
  SELECT 1 
  FROM COMPLETION 
  WHERE ID_TASK = ? 
    AND SUBMITTED_BY = ? 
  LIMIT 1
");

// Process tasks
while ($task = $task_result->fetch_assoc()) {
  $status = strtolower(str_replace([' ', '-'], '_', $task['TASK_STATUS']));

  if (!array_key_exists($status, $tasks_by_status)) {
    continue;
  }

  $include = true;
  $task_owner = $task['ID_USER'] ? intval($task['ID_USER']) : $user_id;

  // Pending visibility
  if ($status === 'pending') {
    $progress_check->bind_param('ii', $task['ID_TASK'], $user_id);
    $progress_check->execute();
    $progress_result = $progress_check->get_result();
    $is_worker = $progress_result->num_rows > 0;
    $progress_result->free();

    $include = ($task_owner === $user_id) || $is_worker;
  }

  // Completed visibility
  if ($status === 'completed') {
    $completion_check->bind_param('ii', $task['ID_TASK'], $user_id);
    $completion_check->execute();
    $completion_result = $completion_check->get_result();
    $has_completion = $completion_result->num_rows > 0;
    $completion_result->free();
    
    $include = $has_completion || ($task_owner === $user_id);
  }

  if (!$include) continue;

  $tasks_by_status[$status][] = [
    'id' => $task['ID_TASK'],
    'task_name' => $task['TASK_NAME'],
    'task_points' => $task['TASK_POINT']
  ];
}

// Limit completed tasks to the most recent 5
$tasks_by_status['completed'] = array_slice($tasks_by_status['completed'], 0, 20);

$progress_check->close();
$completion_check->close();
$task_query->close();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Task Matrix - Task-o-Mania</title>
    <meta name="description" content="Compare every household task across To Do, In Progress, Pending, and Completed states." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style_task_list.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        if (window.lucide) {
          window.lucide.createIcons();
        }
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
            <p class="subtitle">Every task, one glance</p>
            <h1>Household Task Matrix</h1>
          </div>

          <?php include 'header.php'; ?>
        </header>

        <main class="page" role="main">
          <section class="task-columns" aria-label="Task overview">
            <header class="task-columns__intro">
              <div>
                <h2>Track available work, join teammates, await approval, and celebrate completions.</h2>
              </div>
              <div class="task-columns__legend">
                <span>Click a card to open task details.</span>
                <span class="legend-dot legend-dot--todo">To Do</span>
                <span class="legend-dot legend-dot--progress">In Progress</span>
                <span class="legend-dot legend-dot--pending">Pending</span>
                <span class="legend-dot legend-dot--completed">Completed</span>
              </div>
            </header>

            <div class="task-columns__grid">
              <article class="task-column task-column--todo" data-column="todo">
                <header>
                  <div><h3>To Do</h3></div>
                  <span class="column-count" data-column-count="todo">0</span>
                </header>
                <p class="column-description">All available house tasks waiting for a hero.</p>
                <div class="task-column__list" data-column-list="todo"></div>
                <p class="task-column__empty" data-column-empty="todo">No available tasks yet. Create a task to populate this column.</p>
              </article>

              <article class="task-column task-column--progress" data-column="in_progress">
                <header>
                  <div><h3>In Progress</h3></div>
                  <span class="column-count" data-column-count="in_progress">0</span>
                </header>
                <p class="column-description">Join ongoing tasks and share the effort (and the points).</p>
                <div class="task-column__list" data-column-list="in_progress"></div>
                <p class="task-column__empty" data-column-empty="in_progress">No one is working on any task right now.</p>
              </article>

              <article class="task-column task-column--pending" data-column="pending">
                <header>
                  <div><h3>Pending</h3></div>
                  <span class="column-count" data-column-count="pending">0</span>
                </header>
                <p class="column-description">Awaiting final confirmation by the owner.</p>
                <div class="task-column__list" data-column-list="pending"></div>
                <p class="task-column__empty" data-column-empty="pending">No tasks are pending confirmation.</p>
              </article>

              <article class="task-column task-column--completed" data-column="completed">
                <header>
                  <div><h3>Completed</h3></div>
                  <span class="column-count" data-column-count="completed">0</span>
                </header>
                <p class="column-description">Recent 5 completed tasks.</p>
                <div class="task-column__list" data-column-list="completed"></div>
                <p class="task-column__empty" data-column-empty="completed">No completed tasks yet.</p>
              </article>
            </div>
          </section>
        </main>
      </div>
    </div>

    <script>
      (function () {
        const DETAIL_PAGE = 'task_list_detail.php';
        const OWNER_PENDING_PAGE = 'task_list_detail.php';

        const serverTasks = {
          todo: <?php echo json_encode($tasks_by_status['todo']); ?>,
          in_progress: <?php echo json_encode($tasks_by_status['in_progress']); ?>,
          pending: <?php echo json_encode($tasks_by_status['pending']); ?>,
          completed: <?php echo json_encode($tasks_by_status['completed']); ?>
        };

        const handleNavigateToDetails = (taskId) => {
          if (!taskId) return;
          window.location.href = 'task_list_detail.php?task_id=' + taskId;
        };

        const handleNavigateToPending = (taskId) => {
          if (!taskId) return;
          window.location.href = `task_list_detail.php?task_id=${taskId}`;
        };

        const columns = ['todo', 'in_progress', 'pending', 'completed'];

        const columnLists = Object.fromEntries(
          columns.map(key => [key, document.querySelector(`[data-column-list="${key}"]`)])
        );

        const columnEmpty = Object.fromEntries(
          columns.map(key => [key, document.querySelector(`[data-column-empty="${key}"]`)])
        );

        const columnCounts = Object.fromEntries(
          columns.map(key => [key, document.querySelector(`[data-column-count="${key}"]`)])
        );

        const toggleColumnEmptyState = (key, hasItems) => {
          if (columnEmpty[key]) columnEmpty[key].hidden = hasItems;
        };

        const updateColumnCount = (key, count) => {
          if (columnCounts[key]) columnCounts[key].textContent = count;
        };

        const formatPoints = (value) => {
          if (!value) return 'No points assigned';
          const numeric = Number(value);
          return Number.isNaN(numeric) ? value : `${numeric} pts`;
        };

        const createCard = (task, statusKey) => {
          const { id, task_name, task_points } = task;
          const card = document.createElement('article');
          card.className = `task-matrix-card task-matrix-card--${statusKey}`;
          card.tabIndex = 0;
          card.setAttribute('role', 'button');

          const body = document.createElement('div');
          body.className = 'task-matrix-card__body';
          card.appendChild(body);

          const title = document.createElement('h4');
          title.textContent = task_name || 'Untitled task';
          body.appendChild(title);

          const details = document.createElement('div');
          details.className = 'task-matrix-card__details';
          details.innerHTML = `<span>${formatPoints(task_points)}</span>`;
          body.appendChild(details);

          const handleClick = () => {
            if (statusKey === 'pending') {
              handleNavigateToPending(id);
              return;
            }
            handleNavigateToDetails(id);
          };

          card.addEventListener('click', handleClick);

          card.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
              event.preventDefault();
              handleClick();
            }
          });

          return card;
        };

        const renderTasks = () => {
          columns.forEach((key) => {
            if (columnLists[key]) columnLists[key].innerHTML = '';
          });

          columns.forEach((key) => {
            const list = columnLists[key];
            const items = serverTasks[key] || [];
            if (!list) return;
            toggleColumnEmptyState(key, items.length > 0);
            updateColumnCount(key, items.length);
            items.forEach((task) => {
              list.appendChild(createCard(task, key));
            });
          });
        };

        renderTasks();
      })();
    </script>
  </body>
</html>
