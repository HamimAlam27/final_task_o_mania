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
if (!$household_id) {
  header('Location: households.php');
  exit;
}

// Get completed tasks and points for this week/month
$week_start = date('Y-m-d', strtotime('monday this week'));
$month_start = date('Y-m-01');

// Weekly completions
$stmt = $conn->prepare("SELECT COUNT(*) AS completed, COALESCE(SUM(POINTS),0) AS points FROM COMPLETION c JOIN TASK t ON t.ID_TASK = c.ID_TASK WHERE c.STATUS='approved' AND c.SUBMITTED_BY=? AND t.ID_HOUSEHOLD=? AND c.SUBMITTED_AT >= ?");
$stmt->bind_param('iis', $user_id, $household_id, $week_start);
$stmt->execute();
$stmt->bind_result($tasks_week, $points_week);
$stmt->fetch();
$stmt->close();

// Monthly completions (for chart)
$stmt = $conn->prepare("SELECT DAY(c.SUBMITTED_AT) AS day, COUNT(*) AS completed FROM COMPLETION c JOIN TASK t ON t.ID_TASK = c.ID_TASK WHERE c.STATUS='approved' AND c.SUBMITTED_BY=? AND t.ID_HOUSEHOLD=? AND c.SUBMITTED_AT >= ? GROUP BY day ORDER BY day ASC");
$stmt->bind_param('iis', $user_id, $household_id, $month_start);
$stmt->execute();
$chart_days = [];
$chart_counts = [];
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $chart_days[] = $row['day'];
  $chart_counts[] = $row['completed'];
}
$stmt->close();

// Inactivity warnings (tasks not completed in last 7 days)
$stmt = $conn->prepare("SELECT COUNT(*) FROM TASK t LEFT JOIN COMPLETION c ON c.ID_TASK = t.ID_TASK AND c.SUBMITTED_BY=? AND c.STATUS='approved' WHERE t.ID_HOUSEHOLD=? AND (c.SUBMITTED_AT IS NULL OR c.SUBMITTED_AT < ?)");
$seven_days_ago = date('Y-m-d', strtotime('-7 days'));
$stmt->bind_param('iis', $user_id, $household_id, $seven_days_ago);
$stmt->execute();
$stmt->bind_result($inactivity_warnings);
$stmt->fetch();
$stmt->close();
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Activity - Task-o-Mania</title>
    <meta name="description" content="Task-o-Mania activity analytics view." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_activity.css" />
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
            <p class="subtitle">Activity insights</p>
            <h1>Activity</h1>
          </div>

          <div class="topbar__actions">
            <div class="user-actions">
              <a class="notification-button" data-tooltip="Notifications" href="notifications.html" aria-label="Go to notifications">
                <svg aria-hidden="true" width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 2C7.686 2 5 4.686 5 8v1.383c0 .765-.293 1.5-.829 2.036l-.757.757C2.156 13.434 3.037 15.5 4.828 15.5h12.344c1.791 0 2.672-2.066 1.414-3.324l-.757-.757A2.882 2.882 0 0 1 17 9.383V8c0-3.314-2.686-6-6-6Z" stroke-linecap="round" />
                  <path d="M8.5 18.5c.398 1.062 1.368 1.833 2.5 1.833 1.132 0 2.102-.771 2.5-1.833" stroke-linecap="round" />
                </svg>
              </a>
              <a class="avatar" data-tooltip="Profile" href="profile.html" aria-label="Your profile">
                <img src="IMAGES/avatar.png" alt="User avatar" />
              </a>
            </div>
          </div>
        </header>

        <main class="page" role="main">
          <section class="metrics">
            <article class="metric-card">
              <p class="metric-card__label">Tasks completed this week</p>
              <div class="metric-card__value"><?php echo $tasks_week; ?></div>
            </article>

            <article class="metric-card metric-card--accent">
              <p class="metric-card__label">Accumulated points this week!</p>
              <div class="metric-card__value"><?php echo $points_week; ?></div>
            </article>
          </section>

          <p class="warnings">Inactivity warnings this week: <strong><?php echo $inactivity_warnings; ?></strong></p>

          <section class="chart-panel" aria-labelledby="chart-title">
            <div class="chart-panel__header">
              <h2 id="chart-title">Tasks Completed (Monthly)</h2>
              <nav aria-label="Chart intervals">
                <ul>
                  <li><a href="#" onclick="setChart('daily')">Daily</a></li>
                  <li><a href="#" onclick="setChart('weekly')">Weekly</a></li>
                  <li><a class="active" href="#" onclick="setChart('monthly')">Monthly</a></li>
                </ul>
              </nav>
            </div>
            <figure class="chart">
              <canvas id="activityChart" width="600" height="220"></canvas>
            </figure>
          </section>
          <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
          <script>
            const chartDays = <?php echo json_encode($chart_days); ?>;
            const chartCounts = <?php echo json_encode($chart_counts); ?>;
            const ctx = document.getElementById('activityChart').getContext('2d');
            const activityChart = new Chart(ctx, {
              type: 'line',
              data: {
                labels: chartDays,
                datasets: [{
                  label: 'Tasks Completed',
                  data: chartCounts,
                  backgroundColor: 'rgba(125, 117, 255, 0.28)',
                  borderColor: '#6b63ff',
                  borderWidth: 4,
                  pointBackgroundColor: '#fff',
                  pointBorderColor: '#6b63ff',
                  pointRadius: 6,
                  fill: true,
                  tension: 0.4
                }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: { display: false },
                  title: { display: false }
                },
                scales: {
                  x: { title: { display: true, text: 'Day of Month' } },
                  y: { title: { display: true, text: 'Tasks Completed' }, beginAtZero: true }
                }
              }
            });
            // You can add setChart() for interval switching if you add more data
          </script>

          <section class="activity-scroll" role="list">
            <article class="activity-card" role="listitem">
              <header>
                <span class="activity-card__label">My activity</span>
                <span class="activity-card__value">08</span>
              </header>
              <figure aria-label="Activity trend">
                <svg viewBox="0 0 120 40" role="presentation" focusable="false">
                  <path d="M0 28 L20 24 L40 30 L60 18 L80 22 L100 8 L120 14" stroke="#6b63ff" stroke-width="3" fill="none" stroke-linecap="round" />
                </svg>
              </figure>
              <p class="activity-card__delta"><span>10+</span> more from last week</p>
            </article>

            <article class="activity-card" role="listitem">
              <header>
                <span class="activity-card__label">Mom's activity</span>
                <span class="activity-card__value">10</span>
              </header>
              <figure aria-label="Activity trend">
                <svg viewBox="0 0 120 40" role="presentation" focusable="false">
                  <path d="M0 30 L20 22 L40 28 L60 16 L80 20 L100 12 L120 18" stroke="#5fb3ff" stroke-width="3" fill="none" stroke-linecap="round" />
                </svg>
              </figure>
              <p class="activity-card__delta"><span>10+</span> more from last week</p>
            </article>

            <article class="activity-card" role="listitem">
              <header>
                <span class="activity-card__label">Joe's activity</span>
                <span class="activity-card__value">10</span>
              </header>
              <figure aria-label="Activity trend">
                <svg viewBox="0 0 120 40" role="presentation" focusable="false">
                  <path d="M0 24 L20 18 L40 22 L60 30 L80 20 L100 24 L120 18" stroke="#ff7676" stroke-width="3" fill="none" stroke-linecap="round" />
                </svg>
              </figure>
              <p class="activity-card__delta"><span>08+</span> more from last week</p>
            </article>

            <article class="activity-card" role="listitem">
              <header>
                <span class="activity-card__label">My activity</span>
                <span class="activity-card__value">08</span>
              </header>
              <figure aria-label="Activity trend">
                <svg viewBox="0 0 120 40" role="presentation" focusable="false">
                  <path d="M0 28 L20 24 L40 30 L60 18 L80 22 L100 8 L120 14" stroke="#6b63ff" stroke-width="3" fill="none" stroke-linecap="round" />
                </svg>
              </figure>
              <p class="activity-card__delta"><span>10+</span> more from last week</p>
            </article>
          </section>
        </main></div>
    </div>
  </body>
</html>









