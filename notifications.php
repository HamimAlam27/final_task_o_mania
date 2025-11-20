<?php
 
        session_start();
        require_once __DIR__ . '/src/config/db.php';

        // Redirect if not logged in
        if (!isset($_SESSION['user_id'])) {
            header('Location: sign-in.php');
            exit;
        }

        $userId = intval($_SESSION['user_id']);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Notifications - Task-o-Mania</title>
    <meta name="description" content="Review your Task-o-Mania notifications and updates." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="style_notifications.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        lucide.createIcons();
      });
    </script>
  </head>
  <body>
    <div class="background" aria-hidden="true"></div>
    <div class="dashboard-shell">
      <aside class="sidebar" aria-label="Primary navigation">
        <a class="sidebar__logo" href="index.html" aria-label="Task-o-Mania home">
          <span class="sidebar__logo-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
        </a>
        <nav class="sidebar__nav" aria-label="Main navigation">
          <a class="sidebar__link" href="dashboard.html" data-tooltip="Home">
            <span aria-hidden="true"><i data-lucide="home"></i></span>
          </a>
          <a class="sidebar__link" href="households.html" data-tooltip="Households">
            <span aria-hidden="true"><i data-lucide="users"></i></span>
          </a>
          <a class="sidebar__link" href="reward-store.html" data-tooltip="Reward Store">
            <span aria-hidden="true"><i data-lucide="gift"></i></span>
          </a>
          <a class="sidebar__link" href="task-list-total-tasks.html" data-tooltip="Task List">
            <span aria-hidden="true"><i data-lucide="check-square"></i></span>
          </a>
          <a class="sidebar__link" href="choose_between_buttons.html" data-tooltip="New">
            <span aria-hidden="true"><i data-lucide="plus-square"></i></span>
          </a>
          <a class="sidebar__link" href="activity.html" data-tooltip="Activity">
            <span aria-hidden="true"><i data-lucide="activity"></i></span>
          </a>
          <a class="sidebar__link" href="leaderboard.html" data-tooltip="Leaderboard">
            <span aria-hidden="true"><i data-lucide="trophy"></i></span>
          </a>
          <a class="sidebar__link" href="settings.html" data-tooltip="Definitions">
            <span aria-hidden="true"><i data-lucide="settings"></i></span>
          </a>
        </nav>
      </aside>

      <div class="content">
        <header class="topbar">
          <div class="topbar__greeting">
            <p class="subtitle">Updates</p>
            <h1>Notifications</h1>
          </div>

          <div class="topbar__actions">
            <div class="user-actions">
              <a class="notification-button" data-tooltip="Notifications" href="notifications.html" aria-label="Current notifications">
                <svg aria-hidden="true" width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M11 2C7.686 2 5 4.686 5 8v1.383c0 .765-.293 1.5-.829 2.036l-.757.757C2.156 13.434 3.037 15.5 4.828 15.5h12.344c1.791 0 2.672-2.066 1.414-3.324l-.757-.757A2.882 2.882 0 0 1 17 9.383V8c0-3.314-2.686-6-6-6Z"
                    stroke-linecap="round"
                  />
                  <path d="M8.5 18.5c.398 1.062 1.368 1.833 2.5 1.833 1.132 0 2.102-.771 2.5-1.833" stroke-linecap="round" />
                </svg>
              </a>
              <a class="avatar" data-tooltip="Profile" href="profile.html" aria-label="Your profile">
                <img src="IMAGES/avatar.png" alt="User avatar" />
              </a>
            </div>
          </div>
        </header>

        <?php


        // Fetch notifications for the user
        $notifications = [];
        $stmt = $conn->prepare("SELECT ID_NOTIFICATION, NOTIFICATION_TITLE, NOTIFICATION_MESSAGE, IS_READ, NOTIFICATION_CREATED, NOTIFICATION_TYPE, REFERENCE_ID FROM notification WHERE ID_USER = ? ORDER BY NOTIFICATION_CREATED DESC LIMIT 200");
        if ($stmt) {
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()) {
                $notifications[] = $row;
            }
            $stmt->close();
        }

        // Group notifications into Today and Earlier this week (<=7 days)
        $today = [];
        $earlier = [];
        $now = new DateTime();
        foreach ($notifications as $n) {
            $created = new DateTime($n['NOTIFICATION_CREATED']);
            $diff = (int)$now->diff($created)->days;
            if ($created->format('Y-m-d') === $now->format('Y-m-d')) {
                $today[] = $n;
            } elseif ($diff <= 7) {
                $earlier[] = $n;
            } else {
                $earlier[] = $n; // put older items also in earlier section
            }
        }
        ?>

        <main class="page" role="main">
          <section class="group" aria-labelledby="today-heading">
            <header>
              <h2 id="today-heading">Today</h2>
            </header>
            <?php if (empty($today)): ?>
              <p style="color:#777; padding:12px 0;">No notifications for today.</p>
            <?php else: ?>
              <?php foreach ($today as $n):
                $icon = 'bell';
                switch ($n['NOTIFICATION_TYPE']) {
                  case 'reward': $icon = 'star'; break;
                  case 'task': $icon = 'check-square'; break;
                  case 'leaderboard': $icon = 'trophy'; break;
                  case 'reminder': $icon = 'clock-4'; break;
                  case 'invitation': $icon = 'mail'; break;
                }
                $timeText = (new DateTime($n['NOTIFICATION_CREATED']))->format('g:i A');
                $isRead = intval($n['IS_READ']) === 1;
                $readClass = $isRead ? 'notification-card--read' : '';
                $reminderClass = $n['NOTIFICATION_TYPE'] === 'reminder' ? 'notification-card--reminder' : '';
              ?>
              <article class="notification-card <?php echo $readClass; ?> <?php echo $reminderClass; ?>" style="<?php echo $isRead ? 'opacity: 0.65;' : ''; ?>" data-notification-id="<?php echo intval($n['ID_NOTIFICATION']); ?>">
                <label class="notification-card__check">
                  <input type="checkbox" aria-label="Mark as read" value="<?php echo intval($n['ID_NOTIFICATION']); ?>" <?php echo $isRead ? 'checked' : ''; ?> />
                  <span></span>
                </label>
                <div class="notification-card__icon" aria-hidden="true">
                  <i data-lucide="<?php echo htmlspecialchars($icon); ?>"></i>
                </div>
                <div class="notification-card__body">
                  <h3><?php echo htmlspecialchars($n['NOTIFICATION_TITLE']); ?></h3>
                  <p><?php echo htmlspecialchars($n['NOTIFICATION_MESSAGE']); ?></p>
                  <span class="notification-card__time"><?php echo htmlspecialchars($timeText); ?></span>
                </div>
              </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </section>

          <section class="group" aria-labelledby="earlier-heading">
            <header>
              <h2 id="earlier-heading">Earlier this week</h2>
            </header>
            <?php if (empty($earlier)): ?>
              <p style="color:#777; padding:12px 0;">No earlier notifications.</p>
            <?php else: ?>
              <?php foreach ($earlier as $n):
                $icon = 'bell';
                switch ($n['NOTIFICATION_TYPE']) {
                  case 'reward': $icon = 'star'; break;
                  case 'task': $icon = 'check-square'; break;
                  case 'leaderboard': $icon = 'trophy'; break;
                  case 'reminder': $icon = 'clock-4'; break;
                  case 'invitation': $icon = 'mail'; break;
                }
                $created = new DateTime($n['NOTIFICATION_CREATED']);
                $timeText = $created->format('l · g:i A');
                $isRead = intval($n['IS_READ']) === 1;
                $readClass = $isRead ? 'notification-card--read' : '';
                $reminderClass = $n['NOTIFICATION_TYPE'] === 'reminder' ? 'notification-card--reminder' : '';
              ?>
              <article class="notification-card <?php echo $readClass; ?> <?php echo $reminderClass; ?>" style="<?php echo $isRead ? 'opacity: 0.65;' : ''; ?>" data-notification-id="<?php echo intval($n['ID_NOTIFICATION']); ?>">
                <label class="notification-card__check">
                  <input type="checkbox" aria-label="Mark as read" value="<?php echo intval($n['ID_NOTIFICATION']); ?>" <?php echo $isRead ? 'checked' : ''; ?> />
                  <span></span>
                </label>
                <div class="notification-card__body">
                  <h3><?php echo htmlspecialchars($n['NOTIFICATION_TITLE']); ?></h3>
                  <p><?php echo htmlspecialchars($n['NOTIFICATION_MESSAGE']); ?></p>
                  <span class="notification-card__time"><?php echo htmlspecialchars($timeText); ?></span>
                </div>
              </article>
              <?php endforeach; ?>
            <?php endif; ?>
          </section>
        </main>
      </div>
    </div>
    <script>
      // Handle notification checkbox changes to mark as read
      document.addEventListener('change', function(e) {
        const checkbox = e.target;
        if (!checkbox.matches('input[type="checkbox"]')) return;

        const notificationId = checkbox.value;
        const article = checkbox.closest('article[data-notification-id]');
        if (!article) return;

        // Immediately apply visual change
        const isChecked = checkbox.checked;
        if (isChecked) {
          article.classList.add('notification-card--read');
          article.style.opacity = '0.65';
        } else {
          article.classList.remove('notification-card--read');
          article.style.opacity = '1';
        }

        // POST to API to update IS_READ in database
        fetch('api/notification/mark_read.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            notification_id: notificationId,
            is_read: isChecked ? 1 : 0
          }),
          credentials: 'same-origin'
        })
        .catch(err => console.error('Failed to update notification read status:', err));
      });
    </script>
  </body>
</html>

