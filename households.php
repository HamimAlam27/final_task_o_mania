<?php
/**
 * Task-o-Mania Households Management Page
 * 
 * Features:
 * - Session-based authentication check
 * - Display user's households from database
 * - Handle users with no household (redirect to create)
 * - Display user info (username, credits)
 * - Set selected household in session when clicked
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

// Handle household selection via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['household_id'])) {
    $household_id = intval($_POST['household_id']);
    $_SESSION['household_id'] = $household_id;
    header('Location: dashboard.php');
    exit;
}

// Database configuration
require 'src/config/db.php';

// Initialize variables
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
$user_id = $_SESSION['user_id'];
$households = [];
$user_credits = 0;
$has_household = false;

try {
    // Get user's households
    $stmt = $conn->prepare("
        SELECT 
    h.ID_HOUSEHOLD, 
    h.HOUSEHOLD_NAME, 
    (
        SELECT COUNT(ID_USER) 
        FROM HOUSEHOLD_MEMBER 
        WHERE ID_HOUSEHOLD = h.ID_HOUSEHOLD
    ) as member_count
FROM 
    HOUSEHOLD h
JOIN 
    HOUSEHOLD_MEMBER hm ON h.ID_HOUSEHOLD = hm.ID_HOUSEHOLD
WHERE 
    hm.ID_USER = ?
GROUP BY 
    h.ID_HOUSEHOLD, h.HOUSEHOLD_NAME
ORDER BY 
    h.ID_HOUSEHOLD DESC
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
    
    // Get user's total points (for credits display)
    $points_stmt = $conn->prepare("
        SELECT SUM(p.TOTAL_POINTS) as total_credits
        FROM POINTS p
        WHERE p.ID_USER = ?
    ");
    if ($points_stmt) {
        $points_stmt->bind_param("i", $user_id);
        $points_stmt->execute();
        $points_result = $points_stmt->get_result();
        if ($points_row = $points_result->fetch_assoc()) {
            $user_credits = $points_row['total_credits'] ?? 0;
        }
        $points_stmt->close();
    }
    
    $stmt->close();
    
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

          <?php include 'header.php'; ?>
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
                  <form method="POST" style="all: unset; display: contents;">
                    <button type="submit" name="household_id" value="<?php echo htmlspecialchars($household['ID_HOUSEHOLD']); ?>" class="household-card" style="cursor: pointer; border: none; background: none; padding: 0;">
                      <div class="household-card__avatar">
                        <svg aria-hidden="true" width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <circle cx="24" cy="26" r="12" fill="<?php echo htmlspecialchars($colors['vlight']); ?>" />
                          <circle cx="40" cy="26" r="10" fill="<?php echo htmlspecialchars($colors['light']); ?>" />
                          <ellipse cx="27" cy="46" rx="15" ry="10" fill="<?php echo htmlspecialchars($colors['light']); ?>" />
                          <ellipse cx="42" cy="44" rx="11" ry="8" fill="<?php echo htmlspecialchars($colors['dark']); ?>" />
                        </svg>
                      </div>
                      <div>
                        <p class="household-card__name"><?php echo htmlspecialchars($household['HOUSEHOLD_NAME']); ?></p>
                        <p style="font-size: 12px; color: #999; margin: 4px 0 0 0;">
                          <?php echo htmlspecialchars($household['member_count']); ?> member<?php echo $household['member_count'] > 1 ? 's' : ''; ?>
                        </p>
                      </div>
                    </button>
                  </form>
                <?php endforeach; ?>

                <a class="household-card household-card--add" href="create-household.php">
                  <div class="household-card__avatar">
                    <span aria-hidden="true">+</span>
                  </div>
                  <p class="household-card__name">Add household</p>
                </a>
              </div>
              <div style="margin-top: 40px; padding: 24px; background: rgba(124, 110, 255, 0.08); border-radius: 18px; text-align: center;">
                <a href="invitations.php" class="btn-primary">Join household</a>
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
                <a href="invitations.php" class="btn-primary">Join household</a>
              </div>
            </section>
          <?php endif; ?>
        </main>
      </div>
    </div>
  </body>
</html>




