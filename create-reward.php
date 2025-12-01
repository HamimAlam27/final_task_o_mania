<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Create a Reward - Task-o-Mania</title>
  <meta name="description" content="Add a new reward to your Task-o-Mania store." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style_create_reward.css" />
  <link rel="stylesheet" href="style_user_chrome.css" />
  <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
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

        <h1 class="page-title">Create a Reward</h1>

        <?php include 'header.php'; ?>
      </header>

      <main class="page" role="main">
        <div class="tabs" role="tablist">
          <button class="tab tab--active" type="button" role="tab" aria-selected="true" data-tab="create">Create new
            Reward</button>
          <button class="tab" type="button" role="tab" data-tab="select">Select from list</button>
        </div>

        <form class="form form--create" action="api/reward/create_reward.php" method="post">
          <div class="form-field">
            <label for="reward-name">Reward Name <span aria-hidden="true">*</span></label>
            <div class="input-wrapper">
              <input id="reward-name" name="reward_name" type="text" placeholder="2 tickets to the Cinema" required />
            </div>
          </div>

          <div class="form-field">
            <label for="reward-description">Description</label>
            <div class="input-wrapper">
              <textarea id="reward-description" name="reward_description" rows="3" required></textarea>
            </div>
          </div>

          <div class="form-field form-field--small">
            <label for="reward-points">Points <span aria-hidden="true">*</span></label>
            <div class="input-wrapper">
              <input id="reward-points" name="reward_points" type="number" min="0" placeholder="150" required />
            </div>
          </div>

          <div class="actions">
            <button type="submit" class="btn-primary">Add Reward</button>
          </div>
        </form>

        


      </main>
    </div>
  </div>
</body>

</html>