<?php
session_start();
require 'src/config/db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: sign-in.php');
  exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_SESSION['household_id'])) {
  header('Location: households.php');
  exit;
}
$household_id = $_SESSION['household_id'];

$stmt = $conn->prepare("
    SELECT ID_REWARD, REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT AS REWARD_POINTS, ID_USER
    FROM REWARDS_CATALOGUE
    WHERE ID_HOUSEHOLD = ?
    ORDER BY ID_REWARD DESC
");
$stmt->bind_param("i", $household_id);
$stmt->execute();
$result = $stmt->get_result();

$stmt_total = $conn->prepare("SELECT COUNT(*) AS total FROM REWARDS_CATALOGUE WHERE ID_HOUSEHOLD = ?");
$stmt_total->bind_param("i", $household_id);
$stmt_total->execute();
$total = $stmt_total->get_result()->fetch_assoc()['total'];

$stmt_available = $conn->prepare("SELECT COUNT(*) AS available FROM REWARDS_CATALOGUE WHERE ID_HOUSEHOLD = ? AND IS_ACTIVE = 1");
$stmt_available->bind_param("i", $household_id);
$stmt_available->execute();
$available = $stmt_available->get_result()->fetch_assoc()['available'];
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Reward Store - Task-o-Mania</title>
  <meta name="description" content="Redeem Task-o-Mania credits for fun rewards." />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style_reward_store.css" />
  <link rel="stylesheet" href="style_user_chrome.css" />
  <style>
    .creator-actions {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-edit {
        background: #4f46e5;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.2s;
        flex: 1;
    }

    .btn-edit:hover {
        background: #4338ca;
    }

    .btn-delete {
        background: #dc2626;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        transition: background 0.2s;
        flex: 1;
    }

    .btn-delete:hover {
        background: #b91c1c;
    }

    .btn-redeem {
        background: #10b981;
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        transition: background 0.2s;
        width: 100%;
        margin-top: 10px;
    }

    .btn-redeem:hover {
        background: #059669;
    }
  </style>
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
          <p class="subtitle">Rewards hub</p>
          <h1>Reward Store</h1>
        </div>
        <?php include 'header.php'; ?>
      </header>

      <main class="page" role="main">
        <div class="credits-chip">
          <span><a href="create-reward.html" style="text-decoration: none; color:white;">Add New Reward</a></span>
          <small></small>
        </div>

        <section class="stats">
          <article class="stat-card">
            <div class="stat-card__value"><?= $total ?></div>
            <p>Total Reward</p>
          </article>
          <article class="stat-card">
            <div class="stat-card__value"><?= $available ?></div>
            <p>Available</p>
          </article>
        </section>

        <section class="reward-grid" aria-label="Available rewards">
          <?php if ($result->num_rows === 0): ?>
            <p>No rewards available yet.</p>
          <?php else:
            while ($row = $result->fetch_assoc()): 
              $is_creator = ($row['ID_USER'] == $user_id); ?>
              <article class="reward-card">
                <h2><?= htmlspecialchars($row['REWARD_NAME']) ?></h2>
                <p><?= htmlspecialchars($row['REWARD_DESCRIPTION']) ?></p>
                <p class="points"><?= htmlspecialchars($row['REWARD_POINTS']) ?> Points</p>
                
                <?php if ($is_creator): ?>
                  <div class="creator-actions">

                    <form action="api/reward/edit_reward.php" method="POST">
                      <input type="hidden" name="reward_id" value="<?= $row['ID_REWARD'] ?>">
                    <button type="submit" class="btn-edit">Edit</button>
                    </form>

                    <form action="api/reward/edit_reward.php" method="POST">
                      <input type="hidden" name="reward_id" value="<?= $row['ID_REWARD'] ?>">
                      <button type="submit" class="btn-delete">Delete</button>
                    </form>
                  </div>
                <?php else: ?>
                  <form action="redeem_reward.php" method="POST">
                    <input type="hidden" name="reward_id" value="<?= $row['ID_REWARD'] ?>">
                    <button type="submit" class="btn-redeem">Redeem Now</button>
                  </form>
                <?php endif; ?>
              </article>
          <?php endwhile;
          endif;
          $stmt->close();
          ?>
        </section>
      </main>

      <div class="reward-modal" role="alertdialog" aria-modal="true" aria-hidden="true">
        <div class="reward-modal__card">
          <h2>Reward redeemed</h2>
          <p>Your points were redeemed successfully.</p>
          <button type="button" class="reward-modal__close">OK</button>
        </div>
      </div>
    </div>
  </div>

  <script>
    (function() {
      const buttons = document.querySelectorAll('.btn-redeem');
      const modal = document.querySelector('.reward-modal');
      const closeBtn = document.querySelector('.reward-modal__close');

      if (!buttons.length || !modal || !closeBtn) return;

      const showModal = () => {
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('reward-modal--visible');
      };

      buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
          event.preventDefault();
          showModal();
        });
      });

      closeBtn.addEventListener('click', () => {
        modal.classList.remove('reward-modal--visible');
        modal.setAttribute('aria-hidden', 'true');
      });

    })();
  </script>
</body>

</html>

