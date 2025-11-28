<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit();
}

require_once 'src/config/db.php';

$user_id = $_SESSION['user_id'];

$success_message = '';
$error_message = '';

// Handle invite code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_code'])) {
    $invite_code = trim($_POST['invite_code']);
    // Find household by invite code
    $stmt = $conn->prepare("SELECT ID_HOUSEHOLD FROM HOUSEHOLD WHERE INVITE_LINK = ?");
    $stmt->bind_param('s', $invite_code);
    $stmt->execute();
    $result = $stmt->get_result();
    $household = $result->fetch_assoc();
    $stmt->close();

    if ($household) {
        $household_id = $household['ID_HOUSEHOLD'];
        // Check if already a member
        $check_stmt = $conn->prepare("SELECT 1 FROM HOUSEHOLD_MEMBER WHERE ID_USER = ? AND ID_HOUSEHOLD = ?");
        $check_stmt->bind_param('ii', $user_id, $household_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows === 0) {
            // Add user as member
            $add_stmt = $conn->prepare("INSERT INTO HOUSEHOLD_MEMBER (ID_USER, ID_HOUSEHOLD, ROLE) VALUES (?, ?, 'member')");

            $add_stmt->bind_param('ii', $user_id, $household_id);
            if ($add_stmt->execute()) {
                // $_SESSION['household_id'] = $household_id;
                $success_message = 'You have joined the household!';
                $add_points = $conn->prepare("INSERT INTO POINTS (ID_USER, ID_HOUSEHOLD, TOTAL_POINTS) VALUES (?, ?, 0)");
                $add_points->bind_param('ii', $user_id, $household_id);
                $add_points->execute();
                $add_points->close();
            } else {
                $error_message = 'Failed to join household.';
            }
            $add_stmt->close();
        } else {
            $error_message = 'You are already a member of this household.';
        }
        $check_stmt->close();
    } else {
        $error_message = 'Invalid invite code.';
    }
}

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
    <link rel="stylesheet" href="style_profile.css" />
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

                <form class="edit-form" action="" method="post" enctype="multipart/form-data">
                    <?php if (!empty($error_message)): ?>
                        <div style="margin-bottom: 20px; padding: 15px; background-color: #fee; border: 1px solid #fcc; border-radius: 8px; color: #c00;">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($success_message)): ?>
                        <div style="margin-bottom: 20px; padding: 15px; background-color: #efe; border: 1px solid #cfc; border-radius: 8px; color: #060;">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <label>
                        <span>Invite Code</span>
                        <div class="input-wrapper">
                            <input type="text" name="invite_code" placeholder="code" required />
                        </div>
                    </label>

                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Join household</button>
                    </div>
                </form>


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