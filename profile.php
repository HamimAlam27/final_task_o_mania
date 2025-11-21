<?php
session_start();

if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header("Location: sign-in.html");
    exit;
}
require_once 'src/config/db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: sign-in.php');
  exit();
}

$user_id = intval($_SESSION['user_id']);

// Fetch user
$user = null;
$stmt = $conn->prepare("SELECT USER_NAME, USER_EMAIL, AVATAR FROM `user` WHERE ID_USER = ? LIMIT 1");
if ($stmt) {
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $user = $res->fetch_assoc();
  $stmt->close();
}

// Determine profile type: owner if user has any household_member role = 'admin'
$profile_type = 'member';
$stmt = $conn->prepare("SELECT ROLE FROM household_member WHERE ID_USER = ? LIMIT 1");
if ($stmt) {
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res->fetch_assoc();
  if ($row && isset($row['ROLE']) && $row['ROLE'] === 'admin') {
    $profile_type = 'household owner';
  }
  $stmt->close();
}

// Sum total points across households for this user
$total_points = 0;
$stmt = $conn->prepare("SELECT SUM(TOTAL_POINTS) AS total FROM points WHERE ID_USER = ?");
if ($stmt) {
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  $r = $res->fetch_assoc();
  if ($r && $r['total'] !== null) $total_points = intval($r['total']);
  $stmt->close();
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
      <?php include 'sidebar.php'; ?>

      <div class="content">
        <header class="topbar">
          <button class="back-btn" type="button" aria-label="Go back" onclick="history.back()">
            <span aria-hidden="true">&#x2039;</span>
          </button>

          <h1 class="page-title">Profile</h1>

          <?php include 'header.php'; ?>
        </header>

        <main class="page" role="main">
          <section class="card">
            <div class="card__avatar">
              <?php if (!empty($user['AVATAR'])): ?>
                <?php $data = base64_encode($user['AVATAR']); ?>
                <img src="data:image/png;base64,<?php echo $data; ?>" alt="<?php echo htmlspecialchars($user['USER_NAME'] ?? 'User'); ?>" />
              <?php else: ?>
                <img src="IMAGES/avatar.png" alt="<?php echo htmlspecialchars($user['USER_NAME'] ?? 'User'); ?>" />
              <?php endif; ?>
            </div>
            <h2 class="card__name">
              <?php echo htmlspecialchars($user['USER_NAME'] ?? 'Your name'); ?>
              <a href="edit-profile.php" class="name-edit" aria-label="Go to edit profile page">
                <i data-lucide="pencil"></i>
              </a>
            </h2>

            <dl>
              <div>
                <dt>Profile type:</dt>
                <dd><?php echo htmlspecialchars($profile_type); ?></dd>
              </div>
              <div>
                <dt>Total accumulated points:</dt>
                <dd><?php echo htmlspecialchars($total_points); ?> <small>(across households)</small></dd>
              </div>
              <div>
                <dt>email:</dt>
                <dd>
                  <?php if (!empty($user['USER_EMAIL'])): ?>
                    <a href="mailto:<?php echo htmlspecialchars($user['USER_EMAIL']); ?>"><?php echo htmlspecialchars($user['USER_EMAIL']); ?></a>
                  <?php else: ?>
                    <span class="muted">No email set</span>
                  <?php endif; ?>
                  <small> (required for password reset)</small>
                </dd>
              </div>
            </dl>
            <a href="profile.php?logout=1">Log Out</a>
          </section>
        </main>
    </div>
    </div>
  </body>
</html>







