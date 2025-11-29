<?php
session_start();
require 'src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
  header('Location: sign-in.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;

// If no household selected, redirect to households page
if (!$household_id) {
  header('Location: households.php');
  exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Settings - Task-o-Mania</title>
    <meta name="description" content="Manage Task-o-Mania preferences and controls." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_settings.css" />
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
            <div class="topbar__greeting">
              <p class="subtitle">Hello!</p>
              <h1>Settings</h1>
            </div>
            <?php include 'header.php'; ?>
          </header>

        <main class="page" role="main">
          <section class="grid" aria-label="Settings sections">
            <a class="tile" href="settings/household_management.php"><div class="icon"><i data-lucide="users"></i></div><div><h2>Household Management</h2><p>Organize members and roles.</p></div></a>
            <a class="tile" href="settings/ai_validation.php">
              <div class="icon"><i data-lucide="cpu"></i></div>
              <div>
                <h2>AI Validation</h2>
                <p>Control how AI reviews or approves.</p>
              </div>
            </a>
            <a class="tile" href="settings/faq.php">
              <div class="icon"><i data-lucide="help-circle"></i></div>
              <div>
                <h2>FAQ</h2>
                <p>Find quick answers to common Task-O-Mania questions.</p>
              </div>
            </a>
            <a class="tile" href="settings/all_your_data.php">
              <div class="icon"><i data-lucide="database"></i></div>
              <div>
                <h2>All your data</h2>
                <p>View or delete your personal and household data securely.</p>
              </div>
            </a>
            <a class="tile" href="settings/privacy_and_sharing.php">
              <div class="icon"><i data-lucide="shield"></i></div>
              <div>
                <h2>Privacy & sharing</h2>
                <p>Manage visibility, sharing preferences, and privacy controls.</p>
              </div>
            </a>
          </section>

          <p class="cta-text">
            Need to deactivate your account? <a href="deactivate-account.php">Take care of that now</a>
          </p>
        </main>

      </div>
    </div>
  </body>
</html>















