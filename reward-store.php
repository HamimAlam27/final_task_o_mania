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
    SELECT ID_REWARD, REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT AS REWARD_POINTS, ID_USER, IS_ACTIVE
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

$popup_reward = null;
if (isset($_GET['reward_id'])) {
  $reward_id = intval($_GET['reward_id']);

  $stmt_reward = $conn->prepare("SELECT REWARD_NAME, REWARD_DESCRIPTION FROM REWARDS_CATALOGUE WHERE ID_REWARD = ? AND ID_HOUSEHOLD = ?");
  $stmt_reward->bind_param("ii", $reward_id, $household_id);
  $stmt_reward->execute();
  $popup_reward = $stmt_reward->get_result()->fetch_assoc();
  $stmt_reward->close();
}
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
        justify-content: flex-end;
    }

    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 14px;
        font-size: 15px;
        font-weight: 700;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 6px;
        transition: transform 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
        width: 100%;
        min-width: 180px;
    }

    .btn:hover {
        transform: translateY(-2px);
    }

    .btn-primary {
        background: linear-gradient(125deg, #5b2df6, #724bff);
        color: #fff;
        box-shadow: 0 14px 30px rgba(91, 45, 246, 0.25);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 16px 32px rgba(91, 45, 246, 0.3);
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

    .reward-card {
        position: relative;
    }

    .reward-card--inactive {
      background: rgba(235, 235, 235, 0.92);
      color: #7a7a7a;
      border: 1px solid rgba(210, 210, 210, 0.9);
      box-shadow: none;
      filter: grayscale(0.55);
    }

    .reward-card--inactive .points,
    .reward-card--inactive h2,
    .reward-card--inactive p {
        color: #7a7a7a;
    }

    .reward-card--inactive .btn-redeem {
      background: #d6d6d6;
      color: #9a9a9a;
      box-shadow: none;
      cursor: not-allowed;
    }

    .reward-card--inactive .btn-redeem:hover {
      transform: none;
      box-shadow: none;
    }

    .status-pill {
        position: absolute;
        top: 12px;
        right: 12px;
        padding: 6px 12px;
        border-radius: 999px;
      background: rgba(180, 180, 190, 0.5);
      color: #555;
        font-weight: 700;
        font-size: 12px;
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
              $is_creator = ($row['ID_USER'] == $user_id);
              $is_active = isset($row['IS_ACTIVE']) ? (int)$row['IS_ACTIVE'] : 1;
              $card_class = 'reward-card';
              if (!$is_active) {
                $card_class .= ' reward-card--inactive';
              }
              ?>
              <article class="<?= $card_class ?>">
                <?php if (!$is_active): ?>
                  <div class="status-pill">Unavailable</div>
                <?php endif; ?>
                <h2><?= htmlspecialchars($row['REWARD_NAME']) ?></h2>
                <p><?= htmlspecialchars($row['REWARD_DESCRIPTION']) ?></p>
                <p class="points"><?= htmlspecialchars($row['REWARD_POINTS']) ?> Points</p>
                
                <?php if ($is_creator): ?>
                  <div class="creator-actions">

                    <form action="api/reward/edit_reward.php" method="POST">
                      <input type="hidden" name="reward_id" value="<?= $row['ID_REWARD'] ?>">
                      <button type="submit" class="btn btn-primary">Edit</button>
                    </form>
                  </div>
                <?php else: ?>
                  <form action="api/reward/get_reward.php" method="POST">
                    <input type="hidden" name="reward_id" value="<?= $row['ID_REWARD'] ?>">
                    <button type="submit" class="btn-redeem" <?= $is_active ? '' : 'disabled' ?>>Redeem Now</button>
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
          <h2>Reward Redeemed successfully</h2>
          <h3>Reward redeemed</h3>
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


      buttons.forEach((button) => {
        button.addEventListener('click', (event) => {
          event.preventDefault();
          window.location.href = "api/reward/get_reward.php?reward_id=" + button.previousElementSibling.value;
          showModal();
        });
      });


    })();

    document.addEventListener("DOMContentLoaded", function() {
      const modal = document.querySelector('.reward-modal');
      const closeBtn = document.querySelector('.reward-modal__close');

      <?php if ($popup_reward): ?>
        modal.querySelector('h2').textContent = "Reward Redeemed Successfully";
        modal.querySelector('h3').textContent = "<?= htmlspecialchars($popup_reward['REWARD_NAME']) ?>";
        modal.querySelector('p').textContent = "<?= htmlspecialchars($popup_reward['REWARD_DESCRIPTION']) ?>";
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('reward-modal--visible');
      <?php endif; ?>
      <?php if (isset($_GET['mode']) && $_GET['mode'] === 'error'): ?>
        modal.querySelector('h2').textContent = "Redemption Failed";
        modal.querySelector('h3').textContent = "";
        modal.querySelector('p').textContent = "You don't have enough points to redeem this reward.";
        modal.setAttribute('aria-hidden', 'false');
        modal.classList.add('reward-modal--visible');
      <?php endif; ?>
      if (closeBtn) {
        closeBtn.addEventListener('click', () => {
          modal.classList.remove('reward-modal--visible');
          modal.setAttribute('aria-hidden', 'true');
        });
      }
    });
  </script>
</body>

</html>
