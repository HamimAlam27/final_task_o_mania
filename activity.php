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
// Use COMPLETION.COMPLETED_AT and APPROVED_BY (no STATUS column in schema)
$stmt = $conn->prepare("SELECT COUNT(*) AS completed, COALESCE(SUM(POINTS),0) AS points FROM COMPLETION c JOIN TASK t ON t.ID_TASK = c.ID_TASK WHERE c.APPROVED_BY IS NOT NULL AND c.SUBMITTED_BY=? AND t.ID_HOUSEHOLD=? AND c.COMPLETED_AT >= ?");
$stmt->bind_param('iis', $user_id, $household_id, $week_start);
$stmt->execute();
$stmt->bind_result($tasks_week, $points_week);
$stmt->fetch();
$stmt->close();

// Monthly completions (for chart)
// Use COMPLETION.COMPLETED_AT and APPROVED_BY
// Fetch household members (to show everyone's activity)
$members_stmt = $conn->prepare("SELECT DISTINCT u.ID_USER, u.USER_NAME FROM USER u JOIN HOUSEHOLD_MEMBER hm ON u.ID_USER = hm.ID_USER WHERE hm.ID_HOUSEHOLD = ? ORDER BY u.USER_NAME ASC");
$members_stmt->bind_param('i', $household_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();
$household_members = [];
while ($m = $members_result->fetch_assoc()) {
  $household_members[] = $m;
}
$members_stmt->close();

// Prepare chart labels: days of the current month
$days_in_month = intval(date('t'));
$chart_labels = [];
for ($d = 1; $d <= $days_in_month; $d++) $chart_labels[] = $d;

// Initialize per-member daily buckets for tasks and points
$tasks_by_member = []; // [user_id][day] = count
$points_by_member = []; // [user_id][day] = sum points
foreach ($household_members as $m) {
  $uid = intval($m['ID_USER']);
  $tasks_by_member[$uid] = array_fill(1, $days_in_month, 0);
  $points_by_member[$uid] = array_fill(1, $days_in_month, 0);
}

// Fetch task completions grouped by submitter and day for this household/month
$stmt = $conn->prepare(
  "SELECT DAY(c.COMPLETED_AT) AS day, c.SUBMITTED_BY AS user_id, COUNT(*) AS completed, COALESCE(SUM(c.POINTS),0) AS points " .
  "FROM COMPLETION c JOIN TASK t ON t.ID_TASK = c.ID_TASK " .
  "WHERE c.APPROVED_BY IS NOT NULL AND t.ID_HOUSEHOLD = ? AND c.COMPLETED_AT >= ? GROUP BY user_id, day ORDER BY day ASC"
);
$stmt->bind_param('is', $household_id, $month_start);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
  $day = intval($row['day']);
  $uid = intval($row['user_id']);
  if ($day >= 1 && $day <= $days_in_month) {
    if (!isset($tasks_by_member[$uid])) {
      // ensure unknown members don't break things
      $tasks_by_member[$uid] = array_fill(1, $days_in_month, 0);
      $points_by_member[$uid] = array_fill(1, $days_in_month, 0);
    }
    $tasks_by_member[$uid][$day] = intval($row['completed']);
    $points_by_member[$uid][$day] = floatval($row['points']);
  }
}
$stmt->close();

// Expose chart data to JS
$js_chart_labels = json_encode($chart_labels);
$js_household_members = json_encode($household_members);
$js_tasks_by_member = json_encode($tasks_by_member);
$js_points_by_member = json_encode($points_by_member);

// Inactivity warnings (tasks not completed in last 7 days)
// Left join COMPLETION on SUBMITTED_BY and check COMPLETED_AT/APPROVED_BY
$stmt = $conn->prepare("SELECT COUNT(*) FROM TASK t LEFT JOIN COMPLETION c ON c.ID_TASK = t.ID_TASK AND c.SUBMITTED_BY=? AND c.APPROVED_BY IS NOT NULL WHERE t.ID_HOUSEHOLD=? AND (c.COMPLETED_AT IS NULL OR c.COMPLETED_AT < ?)");
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

<style>
  /* Responsive chart container adjustments */
  .chart-panel { padding: 12px; }
  .chart-panel .chart-wrapper { width: 100%; max-width: 100%; height: 360px; box-sizing: border-box; }
  .chart-panel canvas { width: 100% !important; height: 100% !important; display: block; }
  #memberFilters { max-width: 45%; overflow-x: auto; display: flex; gap: 8px; align-items: center; }
  #memberFilters label { white-space: nowrap; }
  .btn { background: #eee; border: 1px solid #ddd; padding: 6px 10px; border-radius: 6px; cursor: pointer; }
  .btn.active { background: #6b63ff; color: #fff; border-color: #6b63ff; }
</style>

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

          <?php include 'header.php'; ?>
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
            <div class="chart-panel__header" style="display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
              <h2 id="chart-title">Activity (Monthly)</h2>
              <div style="margin-left:8px;">
                <button id="metricTasks" class="btn" style="margin-right:8px;">Tasks Completed</button>
                <button id="metricPoints" class="btn">Points Earned</button>
              </div>
              <div id="memberFilters" style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap;align-items:center;">
                <!-- Member checkboxes populated by JS -->
              </div>
            </div>
            <figure class="chart">
              <div class="chart-wrapper">
                <canvas id="activityChart"></canvas>
              </div>
            </figure>
          </section>
          <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
          <script>
            // Data from PHP
            const chartLabels = <?php echo $js_chart_labels; ?>;
            const householdMembers = <?php echo $js_household_members; ?>; // array of {ID_USER, USER_NAME}
            const tasksByMember = <?php echo $js_tasks_by_member; ?>; // { user_id: {day: count} }
            const pointsByMember = <?php echo $js_points_by_member; ?>; // { user_id: {day: points} }

            // Colors (rotate)
            const palette = ['#6b63ff','#5fb3ff','#ff7676','#ffb563','#6bff9c','#9d7eff','#7ec8ff'];

            // Build datasets function
            function buildDatasets(metric) {
              const datasets = [];
              householdMembers.forEach((m, idx) => {
                const uid = m.ID_USER;
                const name = m.USER_NAME;
                const raw = (metric === 'tasks') ? tasksByMember[uid] || {} : pointsByMember[uid] || {};
                const data = chartLabels.map(day => {
                  // raw may be keyed by day number
                  return raw[day] !== undefined ? raw[day] : 0;
                });
                datasets.push({
                  label: name,
                  data: data,
                  borderColor: palette[idx % palette.length],
                  backgroundColor: palette[idx % palette.length] + '33',
                  fill: metric === 'tasks' ? false : true,
                  tension: 0.3,
                  pointRadius: 4,
                  hidden: idx !== 0 ? true : false // show only first by default
                });
              });
              return datasets;
            }

            const ctx = document.getElementById('activityChart').getContext('2d');
            let currentMetric = 'tasks';
            let activityChart = null;
            try {
              if (typeof Chart === 'undefined') throw new Error('Chart.js not loaded');
              activityChart = new Chart(ctx, {
                type: 'line',
                data: {
                  labels: chartLabels,
                  datasets: buildDatasets(currentMetric)
                },
                options: {
                  responsive: true,
                  maintainAspectRatio: false,
                  interaction: { mode: 'nearest', intersect: false },
                  plugins: { title: { display: true, text: 'Activity by Member' }, legend: { display: false } },
                  scales: {
                    x: { title: { display: true, text: 'Day of Month' } },
                    y: { title: { display: true, text: 'Value' }, beginAtZero: true }
                  }
                }
              });
            } catch (err) {
              console.error('Chart initialization failed:', err);
              // show a lightweight fallback message inside the chart area
              const wrapper = document.querySelector('.chart-wrapper');
              if (wrapper) {
                wrapper.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;color:#666">Chart unavailable</div>';
              }
            }

            // Metric button helpers (always attach so UI responds even if chart fails)
            function setActiveMetricButton(metric) {
              document.getElementById('metricTasks').classList.toggle('active', metric === 'tasks');
              document.getElementById('metricPoints').classList.toggle('active', metric === 'points');
            }

            document.getElementById('metricTasks').addEventListener('click', () => {
              currentMetric = 'tasks';
              setActiveMetricButton(currentMetric);
              if (activityChart) {
                activityChart.data.datasets = buildDatasets(currentMetric);
                activityChart.options.plugins.title.text = 'Tasks Completed by Member';
                activityChart.options.scales.y.title.text = 'Tasks Completed';
                activityChart.update();
              }
            });

            document.getElementById('metricPoints').addEventListener('click', () => {
              currentMetric = 'points';
              setActiveMetricButton(currentMetric);
              if (activityChart) {
                activityChart.data.datasets = buildDatasets(currentMetric);
                activityChart.options.plugins.title.text = 'Points Earned by Member';
                activityChart.options.scales.y.title.text = 'Points Earned';
                activityChart.update();
              }
            });

            // initialize active state
            setActiveMetricButton(currentMetric);

            // Member filters (checkboxes)
            const filtersEl = document.getElementById('memberFilters');
            householdMembers.forEach((m, idx) => {
              const id = 'm_' + m.ID_USER;
              const wrapper = document.createElement('label');
              wrapper.style.display = 'flex';
              wrapper.style.alignItems = 'center';
              wrapper.style.gap = '6px';
              wrapper.style.cursor = 'pointer';
              const cb = document.createElement('input');
              cb.type = 'checkbox';
              cb.id = id;
              cb.checked = idx === 0; // check first only
              cb.addEventListener('change', () => {
                const dsIndex = householdMembers.findIndex(h => h.ID_USER == m.ID_USER);
                if (dsIndex >= 0) {
                  activityChart.data.datasets[dsIndex].hidden = !cb.checked;
                  activityChart.update();
                }
              });
              const span = document.createElement('span');
              span.textContent = m.USER_NAME;
              span.style.fontSize = '13px';
              wrapper.appendChild(cb);
              wrapper.appendChild(span);
              filtersEl.appendChild(wrapper);
            });
          </script>


        </main></div>
    </div>
  </body>
</html>









