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
        <?php include 'sidebar.php'; ?>

        <div class="content">
            <header class="topbar">
                <div class="topbar__greeting">
                    <p class="subtitle">Manage</p>
                    <h1>Invitations</h1>
                </div>

                <?php include 'header.php'; ?>
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
