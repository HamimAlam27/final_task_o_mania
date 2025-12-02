<?php
include 'src/config/base_path.php';
$folder_name = BASE_PATH;
$is_kid = $_SESSION['is_kid'] ?? 0;
?>
<aside class="sidebar" aria-label="Primary">
        <a class="sidebar__logo" href="index.html" aria-label="Task-o-Mania home">
          <span class="sidebar__logo-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
        </a>

            <nav class="sidebar__nav" aria-label="Main navigation">
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/dashboard.php" data-tooltip="Home">
            <span aria-hidden="true"><i data-lucide="home"></i></span>
          </a>
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/households.php" data-tooltip="Households">
            <span aria-hidden="true"><i data-lucide="users"></i></span>
          </a>
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/reward-store.php" data-tooltip="Reward Store">
            <span aria-hidden="true"><i data-lucide="gift"></i></span>
          </a>
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/total_task_list_bt_columns.php" data-tooltip="Task List">
            <span aria-hidden="true"><i data-lucide="check-square"></i></span>
          </a>
          <?php if (!$is_kid): ?>
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/choose_between_buttons.php" data-tooltip="New">
            <span aria-hidden="true"><i data-lucide="plus-square"></i></span>
          </a>
          <?php endif; ?>
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/activity.php" data-tooltip="Activity">
            <span aria-hidden="true"><i data-lucide="activity"></i></span>
          </a>
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/leaderboard.php" data-tooltip="Leaderboard">
            <span aria-hidden="true"><i data-lucide="trophy"></i></span>
          </a>
          <?php if (!$is_kid): ?> 
          <a class="sidebar__link" href="<?php echo htmlspecialchars($folder_name);?>/settings.php" data-tooltip="Definitions">
            <span aria-hidden="true"><i data-lucide="settings"></i></span>
          </a>
          <?php endif; ?>
        </nav>

      </aside>