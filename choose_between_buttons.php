<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Choose Action - Task-o-Mania</title>
    <meta name="description" content="Choose what you would like to do next inside Task-o-Mania." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="style_deactivate_account.css" />
    <link rel="stylesheet" href="style_dashboard.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <style>
      .button-grid {
        display: flex;
        flex-wrap: wrap;
        gap: 18px;
        justify-content: center;
        align-items: center;
        margin-top: 10px;
      }

      .button-grid .btn-primary {
        background: linear-gradient(125deg, #5b2df6, #724bff);
        color: #fff;
        box-shadow: 0 18px 34px rgba(91, 45, 246, 0.35);
      }

      .btn-primary--wide {
        min-width: 220px;
        text-align: center;
      }
    </style>
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
            <p class="subtitle">Quick actions</p>
            <h1>Choose Your Next Action</h1>
          </div>

          <?php include 'header.php'; ?>
        </header>

        <main class="page" role="main">
          <section class="panel">
            <header class="panel__header">
              <div>
                <h2>Select an option</h2>
                <p>Continue your journey inside Task-o-Mania by picking one of the quick actions below.</p>
              </div>
            </header>

            <div class="button-grid">
              <button type="button" class="btn-primary btn-primary--wide" data-link="create-task.php">
                Create New Task
              </button>
              <button type="button" class="btn-primary btn-primary--wide" data-link="create-reward.php">
                Add New Reward
              </button>
            </div>
          </section>
        </main>
      </div>
    </div>
    <script>
      document.querySelectorAll('button[data-link]').forEach((button) => {
        button.addEventListener('click', () => {
          const target = button.getAttribute('data-link');
          if (target) {
            window.location.href = target;
          }
        });
      });
    </script>
  </body>
</html>

