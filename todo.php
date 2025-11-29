<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Task Details - Task-o-Mania</title>
    <meta name="description" content="View every field captured when creating a household task." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../style_task_list.css" />
    <link rel="stylesheet" href="../style_user_chrome.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', function () {
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
          <a class="back-btn" href="task-list-total-tasks.html" aria-label="Back to the task list">
            <span aria-hidden="true">&#x2039;</span>
          </a>

          <h1 class="page-title">Task Details</h1>

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
          <section class="task-details" aria-live="polite">
            <div class="task-detail-message" data-task-loading>Loading selected task...</div>
            <div class="task-detail-message" data-task-error hidden>
              We could not find the selected task. <a href="task-list-total-tasks.html">Return to the task list</a> and try again.
            </div>

            <article class="task-detail-card" data-task-card hidden>
              <header>
                <h2 data-task-title>Task details</h2>
              </header>
              <dl class="task-detail-fields" data-task-fields></dl>

              <div class="task-detail-flow" data-task-flow>
                <div class="task-detail-flow__card" data-share-card>
                  <h3>Share your progress</h3>
                  <form class="task-detail-form" data-task-form novalidate>
                    <label class="task-detail-form__field">
                      <span>Upload an image</span>
                      <input type="file" accept="image/*" data-task-proof />
                    </label>

                    <fieldset class="task-detail-form__field task-detail-form__field--inline">
                      <legend>Who confirms this task?</legend>
                      <label>
                        <input type="radio" name="confirmation-method" value="parent" checked />
                        Parent
                      </label>
                      <label>
                        <input type="radio" name="confirmation-method" value="ai" />
                        AI
                      </label>
                    </fieldset>

                    <label class="task-detail-form__field">
                      <span>Your display name</span>
                      <input type="text" placeholder="Add your name" data-participant-name />
                    </label>

                    <div class="task-detail-form__actions">
                      <button type="button" class="btn-primary" data-task-start>Do this task</button>
                      <button type="button" class="btn-secondary" data-task-join>Join</button>
                    </div>
                  </form>
                </div>

                <div class="task-detail-participants" data-participants-panel hidden>
                  <div class="task-detail-participants__header">
                    <h3>Participants</h3>
                    <p data-points-per-user></p>
                  </div>
                  <div class="chip-list" data-participants-list></div>
                </div>

                <div class="task-detail-completed" data-completed-panel hidden>
                  <h3>Completed submission</h3>
                  <p data-completed-description></p>
                  <img data-completed-image alt="Submitted image" hidden />
                </div>
              </div>

              <div class="task-detail-review" data-review-actions hidden>
                <p data-review-message>This task requires your review. Choose an action:</p>
                <div class="task-detail-review__buttons">
                  <button type="button" class="btn-primary" data-review-complete>Mark as Completed</button>
                  <button type="button" class="btn-secondary" data-review-decline>Decline Task</button>
                </div>
              </div>

              <div class="task-detail-actions" data-task-actions>
                <button type="button" class="btn-primary" data-submit-task>Submit Task</button>
              </div>
            </article>
          </section>
        </main>
      </div>
    </div>


  </body>
</html>

