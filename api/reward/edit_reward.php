<?php
session_start();
require '../../src/config/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../../sign-in.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$reward_id = $_POST['reward_id'] ?? '';

if (!$reward_id) {
    header('Location: ../../reward-store.php');
    exit;
}

if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM REWARDS_CATALOGUE WHERE ID_REWARD = ? AND ID_USER = ?");
    $stmt->bind_param("ii", $reward_id, $user_id);
    $stmt->execute();
    header('Location: ../../reward-store.php');
    exit;
}

if (isset($_POST['toggle'])) {
    $stmt = $conn->prepare("UPDATE REWARDS_CATALOGUE SET IS_ACTIVE = NOT IS_ACTIVE WHERE ID_REWARD = ? AND ID_USER = ?");
    $stmt->bind_param("ii", $reward_id, $user_id);
    $stmt->execute();
    header('Location: ../../reward-store.php');
    exit;
}

if (isset($_POST['update'])) {
    $reward_name = $_POST['reward_name'] ?? '';
    $reward_description = $_POST['reward_description'] ?? '';
    $reward_points = $_POST['reward_points'] ?? '';
    
    if ($reward_name && $reward_points) {
        $stmt = $conn->prepare("UPDATE REWARDS_CATALOGUE SET REWARD_NAME = ?, REWARD_DESCRIPTION = ?, POINTS_TO_DISCOUNT = ? WHERE ID_REWARD = ? AND ID_USER = ?");
        $stmt->bind_param("ssiii", $reward_name, $reward_description, $reward_points, $reward_id, $user_id);
        $stmt->execute();
        header('Location: ../../reward-store.php');
        exit;
    }
}

$stmt = $conn->prepare("SELECT REWARD_NAME, REWARD_DESCRIPTION, POINTS_TO_DISCOUNT, IS_ACTIVE FROM REWARDS_CATALOGUE WHERE ID_REWARD = ? AND ID_USER = ?");
$stmt->bind_param("ii", $reward_id, $user_id);
$stmt->execute();
$reward = $stmt->get_result()->fetch_assoc();

if (!$reward) {
    header('Location: ../../reward-store.php');
    exit;
}
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
        /* New/Modified Styles */
        /* Make the actions container use flex to align all buttons */
        .actions { 
            display: flex;
            gap: 10px;
            margin-top: 20px; /* Keep margin for spacing from form fields */
            padding-top: 0; /* Override any padding if needed */
        }
        
        /* Ensure all buttons and forms inside .actions stretch correctly */
        .actions button,
        .actions form {
            flex: 1; /* Allow all items to share space equally */
            width: auto; /* Reset width */
        }

        /* Update button (Assuming btn-primary is the update button) */
        .btn-primary {
            background: #4f46e5; /* Example primary color */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
            text-decoration: none; /* In case it's a link styled as a button */
            display: block; /* Ensure button takes up flex space */
            text-align: center;
        }

        .btn-primary:hover {
            background: #4338ca; /* Darker hover state */
        }

        /* Toggle Button Styles */
        .btn-toggle {
            background: #f59e0b; /* Orange for Toggle/Active status */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
            width: 100%; /* Ensure button fills its flex container */
        }
        .btn-toggle:hover {
            background: #d97706;
        }

        /* Delete Button Styles (Make it clear this is a destructive action) */
        .btn-delete {
            background: #dc2626; /* Red for Delete */
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.2s;
            width: 100%; /* Ensure button fills its flex container */
        }
        .btn-delete:hover {
            background: #b91c1c;
        }

        /* Remove old action-buttons div styling as we are integrating into .actions */
        /* .action-buttons is no longer used and can be removed */

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
                <form class="form form--create" method="post">
                    <input type="hidden" name="reward_id" value="<?= $reward_id ?>">
                    
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
                        <form method="post">
                            <input type="hidden" name="reward_id" value="<?= $reward_id ?>">
                            <button type="submit" name="toggle" class="btn-toggle">
                                <?= $reward['IS_ACTIVE'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                        <form method="post" onsubmit="return confirm('Are you sure you want to permanently delete this reward? This action cannot be undone.');">
                            <input type="hidden" name="reward_id" value="<?= $reward_id ?>">
                            <button type="submit" name="delete" class="btn-delete">Delete Reward</button>
                        </form>
                        
                        <button type="submit" name="update" class="btn-primary">Update Reward</button>
                    </div>
                </form>
                
                </main>
        </div>
    </div>
</body>
</html>