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
      <?php include 'sidebar.php'; ?>

      <div class="content">
        <header class="topbar">
          <div class="topbar__greeting">
            <p class="subtitle">Updates</p>
            <h1>Notifications</h1>
          </div>

          <?php include 'header.php'; ?>
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

