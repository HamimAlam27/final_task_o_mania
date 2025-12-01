<?php
session_start();
require '../../src/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../sign-in.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$reward_id = $_POST['reward_id'] ?? ($_GET['reward_id'] ?? '');

if (!$reward_id) {
    header('Location: ../../reward-store.php');
    exit;
}

$action_flash = '';
$deleted = false;

$stmt = $conn->prepare("SELECT REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT, IS_ACTIVE FROM REWARDS_CATALOGUE WHERE ID_REWARD = ? AND ID_USER = ?");
$stmt->bind_param("ii", $reward_id, $user_id);
$stmt->execute();
$reward = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$reward) {
    header('Location: ../../reward-store.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        $stmt = $conn->prepare("DELETE FROM REWARDS_CATALOGUE WHERE ID_REWARD = ? AND ID_USER = ?");
        $stmt->bind_param("ii", $reward_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $deleted = true;
        $action_flash = 'deleted';
    } elseif (isset($_POST['toggle'])) {
        $new_status = $reward['IS_ACTIVE'] ? 0 : 1;
        $stmt = $conn->prepare("UPDATE REWARDS_CATALOGUE SET IS_ACTIVE = ? WHERE ID_REWARD = ? AND ID_USER = ?");
        $stmt->bind_param("iii", $new_status, $reward_id, $user_id);
        $stmt->execute();
        $stmt->close();
        $action_flash = $new_status ? 'activated' : 'deactivated';
        $reward['IS_ACTIVE'] = $new_status;
    } elseif (isset($_POST['update'])) {
        $reward_name = $_POST['reward_name'] ?? '';
        $reward_description = $_POST['reward_description'] ?? '';
        $reward_points = $_POST['reward_points'] ?? '';

        if ($reward_name && $reward_points !== '') {
            $stmt = $conn->prepare("UPDATE REWARDS_CATALOGUE SET REWARD_NAME = ?, REWARD_DESCRIPTION = ?, POINTS_TO_DISCOUNT = ? WHERE ID_REWARD = ? AND ID_USER = ?");
            $stmt->bind_param("ssiii", $reward_name, $reward_description, $reward_points, $reward_id, $user_id);
            $stmt->execute();
            $stmt->close();
            $action_flash = 'updated';
        }
    }

    if (!$deleted) {
        $stmt = $conn->prepare("SELECT REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT, IS_ACTIVE FROM REWARDS_CATALOGUE WHERE ID_REWARD = ? AND ID_USER = ?");
        $stmt->bind_param("ii", $reward_id, $user_id);
        $stmt->execute();
        $reward = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$reward) {
            header('Location: ../../reward-store.php');
            exit;
        }
    }
}

$is_active = (!$deleted && isset($reward['IS_ACTIVE'])) ? ((int)$reward['IS_ACTIVE'] === 1) : false;
$toggle_label = $is_active ? 'Deactivate' : 'Activate';
$toggle_class = $is_active ? 'btn-warning' : 'btn-activate';
$status_label = $is_active ? 'Available' : 'Unavailable';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Reward - Task-o-Mania</title>
    <meta name="description" content="Edit your Task-o-Mania reward." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../style_create_reward.css" />
    <link rel="stylesheet" href="../../style_user_chrome.css" />
    <style>
        .edit-reward-wrapper {
            width: min(720px, 100%);
            margin: clamp(16px, 4vw, 32px) auto 0;
        }

        .edit-reward-card {
            background: rgba(255, 255, 255, 0.72);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 26px;
            box-shadow: 0 22px 48px rgba(76, 54, 140, 0.18);
            padding: clamp(22px, 3vw, 28px);
            backdrop-filter: blur(14px);
        }

        .edit-reward-card--inactive {
            background: rgba(244, 244, 244, 0.85);
            border-color: rgba(235, 235, 235, 0.9);
            box-shadow: 0 12px 32px rgba(90, 90, 110, 0.12);
        }

        .status-banner {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 13px;
            margin-bottom: 16px;
        }

        .status-banner--inactive {
            background: rgba(200, 200, 210, 0.4);
            color: #606066;
        }

        .status-banner--active {
            background: rgba(91, 45, 246, 0.12);
            color: #5b2df6;
        }

        .actions {
            display: flex;
            gap: 12px;
            margin-top: 20px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .actions button,
        .actions a {
            flex: 0 0 auto;
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
        }

        .btn:hover {
            transform: translateY(-2px);
        }

        .btn-primary {
            background: linear-gradient(125deg, #5b2df6, #724bff);
            color: #fff;
            box-shadow: 0 14px 30px rgba(91, 45, 246, 0.25);
        }

        .btn-warning {
            background: #fff4d7;
            color: #b07900;
            border: 1px solid rgba(176, 121, 0, 0.25);
        }

        .btn-warning:hover {
            background: #ffecc0;
        }

        .btn-activate {
            background: linear-gradient(120deg, #3fb67c, #43d69a);
            color: #fff;
            box-shadow: 0 14px 30px rgba(63, 182, 124, 0.3);
        }

        .btn-activate:hover {
            background: linear-gradient(120deg, #38a76f, #3cc688);
        }

        .btn-danger {
            background: #ffe3e3;
            color: #c44343;
            border: 1px solid rgba(196, 67, 67, 0.3);
        }

        .btn-danger:hover {
            background: #ffd6d6;
        }

        .success-modal {
            position: fixed;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(13, 7, 34, 0.55);
            backdrop-filter: blur(12px);
            padding: 24px;
            opacity: 0;
            pointer-events: none;
            transition: opacity .25s ease;
            z-index: 1000;
        }

        .success-modal--visible {
            opacity: 1;
            pointer-events: auto;
        }

        .success-modal__card {
            width: min(420px, 100%);
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.8);
            border-radius: 26px;
            padding: clamp(24px, 4vw, 36px);
            text-align: center;
            box-shadow: 0 32px 70px rgba(44, 25, 94, 0.28);
        }

        .success-modal__card h2 {
            margin: 0 0 8px;
            font-size: 24px;
        }

        .success-modal__actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 16px;
        }

        .success-modal__primary {
            border: none;
            border-radius: 999px;
            padding: 10px 22px;
            font-weight: 700;
            background: linear-gradient(125deg, #5b2df6, #724bff);
            color: #fff;
            cursor: pointer;
            box-shadow: 0 14px 30px rgba(91, 45, 246, 0.25);
            transition: transform .2s ease, box-shadow .2s ease;
        }

        .success-modal__primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 32px rgba(91, 45, 246, 0.3);
        }
    </style>
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
        <?php include '../../sidebar.php'; ?>

        <div class="content">
            <header class="topbar">
                <button class="back-btn" type="button" aria-label="Go back" onclick="history.back()">
                    <span aria-hidden="true">&#x2039;</span>
                </button>

                <h1 class="page-title">Edit Reward</h1>

                <?php include '../../header.php'; ?>
            </header>

            <main class="page" role="main">
                                <div class="edit-reward-wrapper">
                                    <div class="edit-reward-card<?= $is_active ? '' : ' edit-reward-card--inactive' ?>">
                    <?php if (!$deleted): ?>
                    <form class="form form--create" method="post">
                        <input type="hidden" name="reward_id" value="<?= $reward_id ?>">

                        <div class="status-banner <?= $is_active ? 'status-banner--active' : 'status-banner--inactive' ?>">
                            <?= htmlspecialchars($status_label) ?>
                        </div>
                        
                        <div class="form-field">
                            <label for="reward-name">Reward Name <span aria-hidden="true">*</span></label>
                            <div class="input-wrapper">
                                <input id="reward-name" name="reward_name" type="text" value="<?= htmlspecialchars($reward['REWARD_NAME']) ?>" required />
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="reward-description">Description</label>
                            <div class="input-wrapper">
                                <textarea id="reward-description" name="reward_description" rows="3"><?= htmlspecialchars($reward['REWARD_DESCRIPTION']) ?></textarea>
                            </div>
                        </div>

                        <div class="form-field form-field--small">
                            <label for="reward-points">Points <span aria-hidden="true">*</span></label>
                            <div class="input-wrapper">
                                <input id="reward-points" name="reward_points" type="number" min="0" value="<?= $reward['POINTS_TO_DISCOUNT'] ?>" required />
                            </div>
                        </div>

                        <div class="actions">
                            <button type="submit" name="update" class="btn btn-primary">Update Reward</button>
                            <button type="submit" name="delete" value="1" formnovalidate class="btn btn-danger" onclick="return confirm('Are you sure you want to permanently delete this reward? This action cannot be undone.');">
                                Delete Reward
                            </button>
                            <button type="submit" name="toggle" value="1" formnovalidate class="btn <?= $toggle_class ?>">
                                <?= $toggle_label ?>
                            </button>
                        </div>
                    </form>
                    <?php else: ?>
                      <p style="font-weight:700; text-align:center;">Reward removed.</p>
                    <?php endif; ?>
                  </div>
                </div>
                
                </main>
        </div>
    </div>
    <div class="success-modal" role="alertdialog" aria-modal="true" aria-hidden="true" aria-labelledby="edit-reward-success-title">
      <div class="success-modal__card">
                <h2 id="edit-reward-success-title"><?php
                    if ($action_flash === 'updated') {
                        echo 'Reward updated';
                    } elseif ($action_flash === 'deleted') {
                        echo 'Reward deleted';
                    } elseif ($action_flash === 'activated') {
                        echo 'Reward activated';
                    } elseif ($action_flash === 'deactivated') {
                        echo 'Reward deactivated';
                    }
                ?></h2>
        <div class="success-modal__actions">
          <button type="button" class="success-modal__primary" id="edit-reward-success-ok">OK</button>
        </div>
      </div>
    </div>
    <script>
      (function() {
        const modal = document.querySelector('.success-modal');
        const ok = document.getElementById('edit-reward-success-ok');
        const hasMessage = <?= $action_flash ? 'true' : 'false' ?>;

        if (!modal || !ok || !hasMessage) return;

        const showModal = () => {
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('success-modal--visible');
        };

        const hideModal = () => {
          modal.setAttribute('aria-hidden', 'true');
          modal.classList.remove('success-modal--visible');
        };

        ok.addEventListener('click', () => {
          hideModal();
          window.location.href = '../../reward-store.php';
        });

        modal.addEventListener('click', (event) => {
          if (event.target === modal) {
            hideModal();
          }
        });

        window.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') {
            hideModal();
          }
        });

        showModal();
      })();
    </script>
</body>
</html>
