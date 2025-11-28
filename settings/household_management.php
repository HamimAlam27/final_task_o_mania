<?php
session_start();
require '../src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
  header('Location: ../sign-in.php');
  exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;

// If no household selected, redirect to households page
if (!$household_id) {
  header('Location: ../households.php');
  exit;
}

// Fetch household info
$household_stmt = $conn->prepare("SELECT HOUSEHOLD_NAME, INVITE_LINK, AI_CONFIDENCE FROM HOUSEHOLD WHERE ID_HOUSEHOLD = ?");
$household_stmt->bind_param('i', $household_id);
$household_stmt->execute();
$household_result = $household_stmt->get_result();
$household = $household_result->fetch_assoc();
$household_stmt->close();

// Fetch household members
$members_stmt = $conn->prepare("
  SELECT u.ID_USER, u.USER_NAME, u.AVATAR, hm.ROLE
  FROM USER u
  JOIN HOUSEHOLD_MEMBER hm ON u.ID_USER = hm.ID_USER
  WHERE hm.ID_HOUSEHOLD = ?
  ORDER BY hm.ROLE DESC, u.USER_NAME ASC
");
$members_stmt->bind_param('i', $household_id);
$members_stmt->execute();
$members_result = $members_stmt->get_result();
$members = [];
while ($member = $members_result->fetch_assoc()) {
  $members[] = $member;
}
$members_stmt->close();

// Check if current user is admin/owner
$user_role = null;
foreach ($members as $member) {
  if ($member['ID_USER'] === $user_id) {
    $user_role = $member['ROLE'];
    break;
  }
}

// Restrict access to admins only
if ($user_role !== 'admin' && $user_role !== 'admin') {
  $_SESSION['error'] = 'You do not have permission to manage this household';
  header('Location: ../dashboard.php');
  exit;
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Household Management - Task-o-Mania</title>
    <meta name="description" content="Manage every detail of your Task-o-Mania household from a single place." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style_deactivate_account.css" />
    <link rel="stylesheet" href="../style_user_chrome.css" />
    <link rel="stylesheet" href="../style_household_management.css" />
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
      <?php include '../sidebar.php'; ?>

      <div class="content">
        <header class="topbar">
          <button class="back-btn" type="button" aria-label="Go back" onclick="history.back()">
            <span aria-hidden="true">&#x2039;</span>
          </button>

          <div class="topbar__intro">
            <h1 class="page-title">Household Management</h1>
            <p>Control every aspect of your household: members, permissions, invites, and ownership.</p>
          </div>

          <?php include '../header.php'; ?>
        </header>

        <main class="page" role="main">
          <section class="panel">
            <header class="panel__header">
              <div>
                <h2>Household Name</h2>
                <p>Rename your household. Everyone inside will see this change immediately.</p>
              </div>
            </header>
            <form class="form household-name-form" action="../api/household/update_name.php" method="post">
              <div class="form-field">
                <label for="household-name">Household name</label>
                <div class="input-wrapper">
                  <input id="household-name" name="household_name" type="text" value="<?php echo htmlspecialchars($household['HOUSEHOLD_NAME'] ?? ''); ?>" required />
                </div>
              </div>
              <div class="actions">
                <button type="submit" class="btn-primary">Save changes</button>
              </div>
            </form>
          </section>

          <section class="panel members-panel">
            <header class="panel__header">
              <div>
                <h2>Household Members</h2>
                <p>Promote, demote, or remove members from the family.</p>
              </div>
            </header>

            <div class="members-grid" id="members-grid">
              <?php foreach ($members as $member): ?>
              <article class="member-card" data-member="<?php echo htmlspecialchars($member['USER_NAME']); ?>" data-user-id="<?php echo $member['ID_USER']; ?>">
                <div class="member-card__info">
                  <img src="/final_task_o_mania-1/images/profiles/<?php echo htmlspecialchars($member['AVATAR'] ?? 'avatar.png'); ?>" alt="" />
                  <div>
                    <p><?php echo htmlspecialchars($member['USER_NAME']); ?></p>
                    <span><?php echo ucfirst(htmlspecialchars($member['ROLE'])); ?></span>
                  </div>
                </div>
                <div class="member-card__controls">
                  <select class="role-select" data-user-id="<?php echo $member['ID_USER']; ?>">
                    <option value="admin" <?php echo $member['ROLE'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="member" <?php echo $member['ROLE'] === 'member' ? 'selected' : ''; ?>>Member</option>
                  </select>
                  <?php if ($member['ID_USER'] !== $user_id): ?>
                  <button type="button" class="pill pill--ghost member-remove" data-user-id="<?php echo $member['ID_USER']; ?>">Remove</button>
                  <?php endif; ?>
                </div>
              </article>
              <?php endforeach; ?>
            </div>
          </section>

          <section class="panel invite-panel">
            <header class="panel__header">
              <div>
                <h2>Add Member</h2>
                <p>Send an invite to a new family member.</p>
              </div>
            </header>

            <form class="form add-member-form" id="add-member-form">
              <div class="form-field">
                <label for="invite-email">Email address</label>
                <div class="input-wrapper">
                  <input id="invite-email" name="email" type="email" placeholder="user@example.com" required />
                </div>
              </div>
              <div class="actions">
                <button type="submit" class="btn-primary">Add Member</button>
              </div>
            </form>
          </section>

          <section class="panel invite-link-panel">
            <header class="panel__header">
              <div>
                <h2>Invite Code</h2>
              </div>
            </header>
            <div class="invite-link-controls">
              <div class="input-wrapper">
                <input type="text" id="invite-link" readonly value="<?php echo $household['INVITE_LINK'] ? htmlspecialchars($household['INVITE_LINK']) : 'No link generated'; ?>" />
              </div>
              <button type="button" class="pill copy-link">Copy link</button>
            </div>
          </section>

          <section class="panel danger-panel">
            <header class="panel__header">
              <div>
                <h2>Delete Household</h2>
                <p>This action cannot be undone. All data will be lost.</p>
              </div>
            </header>
            <form action="../api/household/delete.php" method="post" onsubmit="return confirm('Are you sure? This cannot be undone.');">
              <input type="hidden" name="household_id" value="<?php echo $household_id; ?>" />
              <button type="submit" class="btn-primary btn-danger">Delete household</button>
            </form>
          </section>
        </main>

      </div>
    </div>

    <div class="household-modal" role="alertdialog" aria-modal="true" aria-labelledby="household-modal-title" aria-hidden="true">
      <div class="household-modal__card">
        <h2 id="household-modal-title">Success</h2>
        <p class="household-modal__message">Action completed.</p>
        <div class="household-modal__actions">
          <button type="button" class="household-modal__close">Close</button>
          <button type="button" class="household-modal__confirm" hidden>Confirm</button>
        </div>
      </div>
    </div>

    <script>
      (function () {
        const modal = document.querySelector('.household-modal');
        const modalMessage = document.querySelector('.household-modal__message');
        const modalClose = document.querySelector('.household-modal__close');
        const copyBtn = document.querySelector('.copy-link');
        const inviteInput = document.getElementById('invite-link');
        const membersGrid = document.getElementById('members-grid');
        const addMemberForm = document.getElementById('add-member-form');

        if (!modal) return;

        const showInfo = (message) => {
          modalMessage.textContent = message;
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('household-modal--visible');
        };

        const hideModal = () => {
          modal.setAttribute('aria-hidden', 'true');
          modal.classList.remove('household-modal--visible');
        };

        modalClose.addEventListener('click', hideModal);
        modal.addEventListener('click', (event) => {
          if (event.target === modal) hideModal();
        });

        // Copy link
        copyBtn?.addEventListener('click', () => {
          inviteInput.select();
          document.execCommand('copy');
          showInfo('Invite link copied.');
        });

        // Add member form - send to API
        addMemberForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          const email = document.getElementById('invite-email').value;
          
          fetch('../api/invitation/send_invite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
              email: email,
              household_id: <?php echo $household_id; ?>,
              invited_by: <?php echo $user_id; ?>
            })
          })
          .then(r => r.text())
          .then(data => {
            addMemberForm.reset();
            showInfo('Invite sent successfully!');
          })
          .catch(e => showInfo('Error sending invite.'));
        });

        // Role change
        membersGrid?.addEventListener('change', (event) => {
          if (event.target.classList.contains('role-select')) {
            const userId = event.target.dataset.userId;
            const newRole = event.target.value;
            
            fetch('../api/household/update_member_role.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({ user_id: userId, role: newRole, household_id: <?php echo $household_id; ?> })
            })
            .then(r => r.json())
            .then(data => showInfo(data.message || 'Role updated.'))
            .catch(e => showInfo('Error updating role.'));
          }
        });

        // Remove member
        membersGrid?.addEventListener('click', (event) => {
          if (event.target.classList.contains('member-remove')) {
            const userId = event.target.dataset.userId;
            if (confirm('Remove this member?')) {
              fetch('../api/household/remove_member.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, household_id: <?php echo $household_id; ?> })
              })
              .then(r => r.json())
              .then(data => {
                if (data.success) {
                  event.target.closest('.member-card').remove();
                  showInfo('Member removed.');
                } else {
                  showInfo(data.message || 'Error removing member.');
                }
              })
              .catch(e => showInfo('Error removing member.'));
            }
          }
        });
      })();
    </script>
  </body>
</html>



    <script>
      (function () {
        const nameForm = document.querySelector('.household-name-form');
        const addMemberForm = document.querySelector('.add-member-form');
        const permissionsForm = document.querySelector('.permissions-form');
        const transferForm = document.querySelector('.transfer-form');
        const membersGrid = document.getElementById('members-grid');
        const generateBtn = document.querySelector('.generate-link');
        const copyBtn = document.querySelector('.copy-link');
        const inviteInput = document.getElementById('invite-link');
        
        const deleteBtn = document.querySelector('.delete-household');
        const modal = document.querySelector('.household-modal');
        const modalMessage = document.querySelector('.household-modal__message');
        const modalClose = document.querySelector('.household-modal__close');
        const modalConfirm = document.querySelector('.household-modal__confirm');
        let pendingConfirm = null;

        if (!modal) return;

        const showInfo = (message) => {
          modalMessage.textContent = message;
          modalConfirm.hidden = true;
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('household-modal--visible');
        };

        const showConfirm = (message, onConfirm) => {
          modalMessage.textContent = message;
          modalConfirm.hidden = false;
          pendingConfirm = onConfirm;
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('household-modal--visible');
        };

        const hideModal = () => {
          modal.setAttribute('aria-hidden', 'true');
          modal.classList.remove('household-modal--visible');
          modalConfirm.hidden = true;
          pendingConfirm = null;
        };

        modalClose.addEventListener('click', hideModal);
        modal.addEventListener('click', (event) => {
          if (event.target === modal) hideModal();
        });
        window.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') hideModal();
        });

        modalConfirm.addEventListener('click', () => {
          if (typeof pendingConfirm === 'function') {
            pendingConfirm();
          }
          hideModal();
        });

        nameForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          showInfo('Household name updated.');
        });

        addMemberForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          event.currentTarget.reset();
          showInfo('Member added successfully.');
        });

        permissionsForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          showInfo('Permissions updated.');
        });

        transferForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          showInfo('Ownership transferred successfully.');
        });

        membersGrid?.addEventListener('change', (event) => {
          if (event.target.classList.contains('role-select')) {
            showInfo('Role updated.');
          }
        });

        membersGrid?.addEventListener('click', (event) => {
          if (event.target.classList.contains('member-remove')) {
            const card = event.target.closest('.member-card');
            card?.remove();
            showInfo('Member removed.');
          }
        });


        copyBtn?.addEventListener('click', () => {
          inviteInput.select();
          document.execCommand('copy');
          showInfo('Invite link copied.');
        });

        
            const del = document.querySelector('[data-perm=\"delete\"]');
            const approve = document.querySelector('[data-perm=\"approve\"]');

            if (template === 'strict') {
              create.value = 'owners';
              del.value = 'owners';
              approve.value = 'owners';
            }
            if (template === 'family') {
              create.value = 'admins';
              del.value = 'admins';
              approve.value = 'admins';
            }
            if (template === 'shared') {
              create.value = 'everyone';
              del.value = 'admins';
              approve.value = 'admins';
            }
            showInfo('Permissions updated.');
          });


        deleteBtn?.addEventListener('click', () => {
          showConfirm('Are you sure you want to delete the household?', () => {
            showInfo('Household deleted.');
          });
        })();
    </script>
  </body>
</html>





