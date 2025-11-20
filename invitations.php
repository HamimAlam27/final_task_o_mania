<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit();
}

require_once 'src/config/db.php';

$user_id = $_SESSION['user_id'];

// Fetch all pending invitations for this user
$query = "SELECT i.ID_INVITATION, i.ID_HOUSEHOLD, i.INVITED_EMAIL, i.INVITED_BY, 
                 h.HOUSEHOLD_NAME, u.USER_NAME
          FROM invitation i
          LEFT JOIN household h ON i.ID_HOUSEHOLD = h.ID_HOUSEHOLD
          LEFT JOIN user u ON i.INVITED_BY = u.ID_USER
          WHERE i.INVITED_EMAIL = (SELECT USER_EMAIL FROM user WHERE ID_USER = ?)
          AND i.STATUS = 'pending'";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$invitations = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Invitations - Task-o-Mania</title>
    <meta name="description" content="Review your household invitations." />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="style_notifications.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <link rel="stylesheet" href="style_invitations.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            lucide.createIcons();
        });
    </script>
</head>
<body>
    <div class="background" aria-hidden="true"></div>
    <div class="dashboard-shell">
        <aside class="sidebar" aria-label="Primary navigation">
            <a class="sidebar__logo" href="index.html" aria-label="Task-o-Mania home">
                <span class="sidebar__logo-icon" aria-hidden="true"><i data-lucide="sparkles"></i></span>
            </a>
            <nav class="sidebar__nav" aria-label="Main navigation">
                <a class="sidebar__link" href="dashboard.php" data-tooltip="Home">
                    <span aria-hidden="true"><i data-lucide="home"></i></span>
                </a>
                <a class="sidebar__link" href="households.php" data-tooltip="Households">
                    <span aria-hidden="true"><i data-lucide="users"></i></span>
                </a>
                <a class="sidebar__link" href="reward-store.php" data-tooltip="Reward Store">
                    <span aria-hidden="true"><i data-lucide="gift"></i></span>
                </a>
                <a class="sidebar__link" href="task-list.php" data-tooltip="Task List">
                    <span aria-hidden="true"><i data-lucide="check-square"></i></span>
                </a>
                <a class="sidebar__link" href="activity.php" data-tooltip="Activity">
                    <span aria-hidden="true"><i data-lucide="activity"></i></span>
                </a>
                <a class="sidebar__link" href="leaderboard.php" data-tooltip="Leaderboard">
                    <span aria-hidden="true"><i data-lucide="trophy"></i></span>
                </a>
                <a class="sidebar__link" href="settings.php" data-tooltip="Settings">
                    <span aria-hidden="true"><i data-lucide="settings"></i></span>
                </a>
            </nav>
        </aside>

        <div class="content">
            <header class="topbar">
                <div class="topbar__greeting">
                    <p class="subtitle">Manage</p>
                    <h1>Invitations</h1>
                </div>

                <div class="topbar__actions">
                    <div class="user-actions">
                        <a class="notification-button" data-tooltip="Invitations" href="invitations.php" aria-label="Current invitations">
                            <svg aria-hidden="true" width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M3 5a2 2 0 012-2h3.28a1 1 0 00.948-1.684c-.502-.757.074-2.316.944-2.316a9 9 0 019.5 9v.5c0 4.418-3.582 8-8 8s-8-3.582-8-8V7.5a2 2 0 00-2-2z" stroke-linecap="round" />
                            </svg>
                        </a>
                        <a class="notification-button" data-tooltip="Notifications" href="notifications.php" aria-label="Notifications">
                            <svg aria-hidden="true" width="22" height="24" viewBox="0 0 22 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M11 2C7.686 2 5 4.686 5 8v1.383c0 .765-.293 1.5-.829 2.036l-.757.757C2.156 13.434 3.037 15.5 4.828 15.5h12.344c1.791 0 2.672-2.066 1.414-3.324l-.757-.757A2.882 2.882 0 0 1 17 9.383V8c0-3.314-2.686-6-6-6Z" stroke-linecap="round" />
                                <path d="M8.5 18.5c.398 1.062 1.368 1.833 2.5 1.833 1.132 0 2.102-.771 2.5-1.833" stroke-linecap="round" />
                            </svg>
                        </a>
                        <a class="avatar" data-tooltip="Profile" href="profile.php" aria-label="Your profile">
                            <img src="IMAGES/avatar.png" alt="User avatar" />
                        </a>
                    </div>
                </div>
            </header>

            <main class="page" role="main">
                <?php if (empty($invitations)): ?>
                    <section class="group">
                        <header>
                            <h2>Pending Invitations</h2>
                        </header>
                        <p style="color:#777; padding:12px 0;">No pending invitations.</p>
                    </section>
                <?php else: ?>
                    <section class="group" aria-labelledby="invitations-heading">
                        <header>
                            <h2 id="invitations-heading">Pending Invitations</h2>
                        </header>
                        <?php foreach ($invitations as $invitation): ?>
                            <article class="invitation-card" data-invitation-id="<?php echo intval($invitation['ID_INVITATION']); ?>">
                                <div class="invitation-card__icon" aria-hidden="true">
                                    <i data-lucide="mail"></i>
                                </div>
                                <div class="invitation-card__body">
                                    <h3>Join <?php echo htmlspecialchars($invitation['HOUSEHOLD_NAME'] ?? 'Household'); ?></h3>
                                    <p><?php echo htmlspecialchars($invitation['USER_NAME'] ?? 'Someone'); ?> invited you to join their household.</p>
                                </div>
                                <div class="invitation-card__actions">
                                    <button class="btn btn-primary" onclick="respondToInvitation(<?php echo intval($invitation['ID_INVITATION']); ?>, 'accept')">
                                        Accept
                                    </button>
                                    <button class="btn btn-secondary" onclick="respondToInvitation(<?php echo intval($invitation['ID_INVITATION']); ?>, 'reject')">
                                        Reject
                                    </button>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </section>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
        async function respondToInvitation(invitationId, response) {
            try {
                const res = await fetch('api/invitation/respond.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        invitation_id: invitationId,
                        response: response
                    }),
                    credentials: 'same-origin'
                });

                const data = await res.json();

                if (data.success) {
                    // Remove the invitation from the list
                    const invitationElement = document.querySelector(`[data-invitation-id="${invitationId}"]`);
                    if (invitationElement) {
                        invitationElement.remove();
                    }

                    // Check if there are any invitations left
                    const remainingInvitations = document.querySelectorAll('[data-invitation-id]');
                    if (remainingInvitations.length === 0) {
                        // Reload page to show empty state
                        window.location.reload();
                    }
                } else {
                    alert('Error: ' + (data.message || 'Failed to process invitation'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while processing your request');
            }
        }
    </script>
</body>
</html>
