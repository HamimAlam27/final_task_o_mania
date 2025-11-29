<?php
require_once 'src/config/base_path.php';
$folder_name = BASE_PATH;
// Fetch user credits if not already set
if (!isset($user_credits) && isset($_SESSION['user_id'])) {
  $cred_user_id = $_SESSION['user_id'];
  $cred_household_id = $_SESSION['household_id'] ?? null;

  if ($cred_household_id) {
    $cred_stmt = $conn->prepare("SELECT TOTAL_POINTS FROM POINTS WHERE ID_USER = ? AND ID_HOUSEHOLD = ?");
    $cred_stmt->bind_param('ii', $cred_user_id, $cred_household_id);
    $cred_stmt->execute();
    $cred_result = $cred_stmt->get_result();
    $cred_row = $cred_result->fetch_assoc();
    $user_credits = $cred_row['TOTAL_POINTS'] ?? 0;
    $cred_stmt->close();
  } else {
    $user_credits = 0;
  }
}

// Fetch user avatar path
$avatar_path = 'images/avatar.png';
if (isset($_SESSION['user_id'])) {
  $avatar_stmt = $conn->prepare("SELECT AVATAR FROM USER WHERE ID_USER = ?");
  $avatar_stmt->bind_param('i', $_SESSION['user_id']);
  $avatar_stmt->execute();
  $avatar_result = $avatar_stmt->get_result();
  $avatar_row = $avatar_result->fetch_assoc();
  if (!empty($avatar_row['AVATAR'])) {
    $avatar_path = $avatar_row['AVATAR'];
  }
  $avatar_stmt->close();
}
?>
<style>
  :root {
    --bell-size: 46px;
    --bell-color: #5f4cff;
    --bell-shadow: 0 12px 28px rgba(92, 66, 189, 0.18);
  }

  .topbar__actions {
    display: flex;
    align-items: center;
    gap: 18px;
  }

  .credits-card {
    display: grid;
    gap: 4px;
    padding: 16px 24px;
    border-radius: 18px;
    background: linear-gradient(120deg, rgba(123, 108, 255, 0.9), rgba(140, 120, 255, 0.8));
    color: #fff;
    text-decoration: none;
    min-width: 180px;
    box-shadow: 0 18px 34px rgba(92, 66, 189, 0.25);
  }

  .credits-card__amount {
    font-weight: 700;
    font-size: 20px;
  }

  .credits-card__cta {
    font-size: 14px;
    opacity: 0.85;
  }

  .user-actions {
    display: inline-flex;
    align-items: center;
    gap: 14px;
  }

  .notification-button {
    width: var(--bell-size);
    height: var(--bell-size);
    border-radius: 16px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.88);
    border: 1px solid rgba(95, 76, 255, 0.18);
    box-shadow: var(--bell-shadow);
    text-decoration: none;
    transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease;
    backdrop-filter: blur(6px);
  }

  .notification-button:hover {
    transform: translateY(-1px);
    background: rgba(255, 255, 255, 0.95);
    box-shadow: 0 14px 30px rgba(76, 54, 140, 0.2);
  }

  .notification-button:focus-visible {
    outline: 2px solid rgba(95, 76, 255, 0.65);
    outline-offset: 3px;
  }

  .notification-button svg {
    width: 22px;
    height: 24px;
    display: block;
  }

  .notification-button svg path {
    stroke: var(--bell-color);
    stroke-width: 1.8;
    fill: none;
  }

  [data-tooltip] {
    position: relative;
  }

  [data-tooltip]::after {
    content: attr(data-tooltip);
    position: absolute;
    bottom: calc(100% + 10px);
    left: 50%;
    transform: translate(-50%, 6px);
    white-space: nowrap;
    background: rgba(255, 255, 255, 0.97);
    color: #1c1340;
    font-size: 12px;
    font-weight: 600;
    padding: 6px 10px;
    border-radius: 10px;
    box-shadow: 0 10px 25px rgba(24, 14, 68, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.6);
    opacity: 0;
    pointer-events: none;
    transition: opacity .12s ease, transform .12s ease;
    z-index: 2147483647;
  }

  [data-tooltip]::before {
    content: '';
    position: absolute;
    bottom: calc(100% + 2px);
    left: 50%;
    width: 8px;
    height: 8px;
    background: rgba(255, 255, 255, 0.97);
    transform: translate(-50%, 4px) rotate(45deg);
    border-radius: 2px;
    opacity: 0;
    pointer-events: none;
    transition: opacity .12s ease, transform .12s ease;
  }

  [data-tooltip]:hover::after,
  [data-tooltip]:hover::before {
    opacity: 1;
    transform: translate(-50%, 0);
  }

  .avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    overflow: hidden;
    border: 3px solid rgba(127, 115, 255, 0.4);
    display: block;
  }

  .avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
  }
</style>
<div class="topbar__actions">
  <a class="credits-card" href="reward-store.php">
    <span class="credits-card__amount"><?php echo intval($user_credits ?? 0); ?></span>
    <span class="credits-card__cta">Redeem rewards ➜</span>
  </a>
  <div class="user-actions">

    <a class="notification-button" data-tooltip="Notifications" href="notifications.php" aria-label="Go to notifications">
      <svg aria-hidden="true" width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path d="M11 2C7.68629 2 5 4.68629 5 8V9.38268C5 10.1481 4.70711 10.8826 4.17157 11.4182L3.41421 12.1756C2.15602 13.4338 3.03714 15.5 4.82843 15.5H17.1716C18.9629 15.5 19.844 13.4338 18.5858 12.1756L17.8284 11.4182C17.2929 10.8826 17 10.1481 17 9.38268V8C17 4.68629 14.3137 2 11 2Z" stroke-linecap="round" />
        <path d="M8.5 18.5C8.89782 19.5619 9.86827 20.3333 11 20.3333C12.1317 20.3333 13.1022 19.5619 13.5 18.5" stroke-linecap="round" />
      </svg>
    </a>
    <a class="avatar" data-tooltip="Profile" href="profile.php" aria-label="Your profile">
      <!-- <img src="<?php echo htmlspecialchars($avatar_path); ?>" alt="User avatar" /> -->
      <img src="<?php echo htmlspecialchars($folder_name); ?>/images/profiles/<?php echo htmlspecialchars($avatar_path); ?>" alt="User avatar" />
    </a>
  </div>
</div>