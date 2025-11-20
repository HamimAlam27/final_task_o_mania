<?php
/**
 * Task-o-Mania Households Management Page
 * 
 * Features:
 * - Session-based authentication check
 * - Display user's households from database
 * - Handle users with no household (redirect to create)
 * - Display user info (username, credits)
 */

// Set headers
header('Content-Type: text/html; charset=utf-8');

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'task_o_mania';

// Initialize variables
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$user_id = $_SESSION['user_id'];
$households = [];
$user_credits = 0;
$has_household = false;

try {
    // Create database connection
    $conn = new mysqli($db_host, $db_user, $db_password, $db_name);
    
    if ($conn->connect_error) {
        throw new Exception('Database connection failed');
    }
    
    $conn->set_charset("utf8");
    
    // Get user's households
    $stmt = $conn->prepare("
        SELECT h.household_id, h.household_name, h.household_type, h.created_at, COUNT(hm.user_id) as member_count
        FROM households h
        JOIN household_members hm ON h.household_id = hm.household_id
        WHERE hm.user_id = ? AND hm.is_active = 1
        GROUP BY h.household_id
        ORDER BY h.created_at DESC
    ");
    
    if (!$stmt) {
        throw new Exception('Database query failed');
    }
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Fetch households
    while ($row = $result->fetch_assoc()) {
        $households[] = $row;
    }
    
    $has_household = count($households) > 0;
    
    // Get user's total credits
    $credits_stmt = $conn->prepare("SELECT total_credits FROM users WHERE user_id = ?");
    if ($credits_stmt) {
        $credits_stmt->bind_param("i", $user_id);
        $credits_stmt->execute();
        $credits_result = $credits_stmt->get_result();
        if ($credits_row = $credits_result->fetch_assoc()) {
            $user_credits = $credits_row['total_credits'] ?? 0;
        }
        $credits_stmt->close();
    }
    
    $stmt->close();
    $conn->close();
    
} catch (Exception $e) {
    $households = [];
    $has_household = false;
}

// Generate random avatar color for visual variety
function getHouseholdColor($index) {
    $colors = [
        ['dark' => '#5b2df6', 'light' => '#7c65ff', 'vlight' => '#e0d2ff'],
        ['dark' => '#6d4bff', 'light' => '#8d6bff', 'vlight' => '#eed4ff'],
        ['dark' => '#5540e8', 'light' => '#7759f5', 'vlight' => '#e8deff'],
        ['dark' => '#6b5eff', 'light' => '#9087ff', 'vlight' => '#f0ebff'],
    ];
    return $colors[$index % count($colors)];
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Households Overview - Task-o-Mania</title>
    <meta name="description" content="See all households you belong to in Task-o-Mania." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_dashboard.css" />
    <link rel="stylesheet" href="style_households.css" />
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
            <p class="subtitle">Manage households</p>
            <h1>Households</h1>
          </div>

          <div class="topbar__actions">
            <a class="credits-card" href="reward-store.php">
              <span class="credits-card__amount"><?php echo htmlspecialchars($user_credits); ?> Credits</span>
              <span class="credits-card__cta">Redeem rewards ➜ </span>
            </a>
            <div class="user-actions">
              <a class="notification-button" data-tooltip="Notifications" href="notifications.php" aria-label="Go to notifications">
                <svg aria-hidden="true" width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path d="M11 2C7.68629 2 5 4.68629 5 8V9.38268C5 10.1481 4.70711 10.8826 4.17157 11.4182L3.41421 12.1756C2.15602 13.4338 3.03714 15.5 4.82843 15.5H17.1716C18.9629 15.5 19.844 13.4338 18.5858 12.1756L17.8284 11.4182C17.2929 10.8826 17 10.1481 17 9.38268V8C17 4.68629 14.3137 2 11 2Z" stroke-linecap="round" />
                  <path d="M8.5 18.5C8.89782 19.5619 9.86827 20.3333 11 20.3333C12.1317 20.3333 13.1022 19.5619 13.5 18.5" stroke-linecap="round" />
                </svg>
              </a>
              <a class="avatar" data-tooltip="Profile" href="profile.php" aria-label="Your profile">
                <img src="IMAGES/avatar.png" alt="User avatar" />
              </a>
            </div>
          </div>
        </header>

        <main class="page" role="main">
          <?php if ($has_household): ?>
            <!-- User has household(s) -->
            <section class="panel households-panel" aria-label="Your households">
              <div class="panel__header">
                <div>
                  <h2>Your households</h2>
                  <p class="households-panel__subtitle">Switch households, invite members, or create a brand new space for your family.</p>
                </div>
              </div>

              <div class="household-grid">
                <?php foreach ($households as $index => $household): 
                  $colors = getHouseholdColor($index);
                ?>
                  <article class="household-card" onclick="window.location.href='dashboard.php?household_id=<?php echo htmlspecialchars($household['household_id']); ?>';" style="cursor: pointer;">
                    <div class="household-card__avatar">
                      <svg aria-hidden="true" width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="24" cy="26" r="12" fill="<?php echo htmlspecialchars($colors['vlight']); ?>" />
                        <circle cx="40" cy="26" r="10" fill="<?php echo htmlspecialchars($colors['light']); ?>" />
                        <ellipse cx="27" cy="46" rx="15" ry="10" fill="<?php echo htmlspecialchars($colors['light']); ?>" />
                        <ellipse cx="42" cy="44" rx="11" ry="8" fill="<?php echo htmlspecialchars($colors['dark']); ?>" />
                      </svg>
                    </div>
                    <div>
                      <p class="household-card__name"><?php echo htmlspecialchars($household['household_name']); ?></p>
                      <p style="font-size: 12px; color: #999; margin: 4px 0 0 0;">
                        <?php echo htmlspecialchars($household['member_count']); ?> member<?php echo $household['member_count'] > 1 ? 's' : ''; ?>
                      </p>
                    </div>
                  </article>
                <?php endforeach; ?>

                <a class="household-card household-card--add" href="create-household.php">
                  <div class="household-card__avatar">
                    <span aria-hidden="true">+</span>
                  </div>
                  <p class="household-card__name">Add household</p>
                </a>
              </div>
            </section>
          <?php else: ?>
            <!-- User has NO household - show empty state and create button -->
            <section class="panel households-panel" aria-label="Your households">
              <div class="panel__header">
                <div>
                  <h2>Welcome, <?php echo htmlspecialchars($username); ?>!</h2>
                  <p class="households-panel__subtitle">You haven't joined or created a household yet. Let's get started!</p>
                </div>
              </div>

              <div class="household-grid">
                <a class="household-card household-card--add" href="create-household.php">
                  <div class="household-card__avatar">
                    <span aria-hidden="true">+</span>
                  </div>
                  <p class="household-card__name">Create household</p>
                </a>
              </div>

              <div style="margin-top: 40px; padding: 24px; background: rgba(124, 110, 255, 0.08); border-radius: 18px; text-align: center;">
                <h3 style="margin: 0 0 12px 0; color: #4b2dbd; font-size: 18px;">Join an existing household?</h3>
                <p style="margin: 0 0 16px 0; color: #666; font-size: 14px;">If someone has invited you to their household, ask them for an invite link or check your email for an invitation.</p>
              </div>
            </section>
          <?php endif; ?>
        </main>
      </div>
    </div>
  </body>
</html>




