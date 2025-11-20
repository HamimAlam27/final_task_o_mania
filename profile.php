<?php
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: sign-in.html");
    exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Profile - Task-o-Mania</title>
    <meta name="description" content="View and edit your Task-o-Mania profile information." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_profile.css" />
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
      <aside class="sidebar" aria-label="Primary">
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
          <button class="back-btn" type="button" aria-label="Go back" onclick="history.back()">
            <span aria-hidden="true">&#x2039;</span>
          </button>

          <h1 class="page-title">Profile</h1>

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
        </header>

        <main class="page" role="main">
          <section class="card">
            <div class="card__avatar">
              <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
            </div>
            <h2 class="card__name">
              Livia Vaccaro
              <a href="edit-profile.html" class="name-edit" aria-label="Go to edit profile page">
                <i data-lucide="pencil"></i>
              </a>
            </h2>

            <dl>
              <div>
                <dt>Profile type:</dt>
                <dd>household owner</dd>
              </div>
              <div>
                <dt>Total accumulated points:</dt>
                <dd>5600 <small>(for this household)</small></dd>
              </div>
              <div>
                <dt>email:</dt>
                <dd><a href="mailto:liviavaccaro7@email.com">liviavaccaro7@email.com</a> <small>(required for password reset)</small></dd>
              </div>
            </dl>
  <a href="profile.php?logout=1">Log Out</a>
          </section>
        </main>
    </div>
    </div>
  </body>
</html>







