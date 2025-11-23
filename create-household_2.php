<?php
session_start();

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../sign-in.php");
    exit;
} elseif (!isset($_SESSION['household_id'])) {
    header("Location: households.php");
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'];
?>

<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Household Created - Task-o-Mania</title>
    <meta name="description" content="Invite members to your newly created Task-o-Mania household." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_inside_app_2.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
<script>
  document.addEventListener("DOMContentLoaded", function() {
    lucide.createIcons();
  });
</script>
  </head>
  <body>
    <div class="background" aria-hidden="true"></div>

<?php
// Read invite token from query string (if provided)
$invite_token = isset($_GET['invite']) ? trim($_GET['invite']) : '';

?>

    <main class="page" role="main">
      <section class="card">
        <header class="card-header">
          <h1>Household Successfully Created</h1>
        </header>

        <div class="card-body">
          <section class="block">
            <h2>Invite users by invite link</h2>
            <div class="input-wrapper readonly">
              <input id="invite-link" type="text" value="<?php echo htmlspecialchars($invite_token ?: ''); ?>" readonly aria-label="Shareable invite link" />
              <button id="copy-invite" type="button" class="btn-ghost" aria-label="Copy invite link"><?php echo $invite_token ? 'Copy' : 'No link'; ?></button>
            </div>
            <?php if ($invite_token): ?>
              <p style="margin-top:8px;font-size:13px;color:#666;">Share this link to invite members to your household.</p>
            <?php else: ?>
              <p style="margin-top:8px;font-size:13px;color:#c33;">Invite link not available. If you were redirected here after creating a household, contact support.</p>
            <?php endif; ?>
          </section>

          <section class="block">
            <h2>Invite users by email</h2>
            <form id="invite-form" method="post" action="api/invitation/invite_members.php">
              <div class="emails-list">
                <div class="input-wrapper">
                  <label for="invite-1">Email</label>
                  <input id="invite-1" name="invited_emails[]" type="email" placeholder="name@example.com" autocomplete="email" />
                </div>
            </div>
            <div class="actions">
                      <button id="enter-household" class="btn-primary" type="submit">Enter the household</button>
                  </div>
        </form>
    </section>
    
    <p class="helper">
        Want to add kids to your household but don’t have their accounts set up yet?
        <a href="new-kids-account.php">Click here!</a>
    </p>
    
        </div>
      </section>
    </main>
    <script>
      // Copy invite link button
      document.getElementById('copy-invite')?.addEventListener('click', function () {
        const input = document.getElementById('invite-link');
        if (!input || !input.value) return;
        navigator.clipboard?.writeText(input.value).then(() => {
          const prev = this.innerText;
          this.innerText = 'Copied';
          setTimeout(() => this.innerText = prev, 1400);
        }).catch(() => {
          // fallback: select the text
          input.select();
          try { document.execCommand('copy'); } catch (e) {}
        });
      });

      // Dynamic email inputs: keep exactly one empty input at the end
      (function () {
        const container = document.querySelector('.emails-list');

        function createEmailInput(index) {
          const wrapper = document.createElement('div');
          wrapper.className = 'input-wrapper';
          const id = 'invite-' + index;
          wrapper.innerHTML = `
            <label for="${id}">Email</label>
            <input id="${id}" name="invited_emails[]" type="email" placeholder="name@example.com" autocomplete="email" />
          `;
          return wrapper;
        }

        function ensureOneEmptyAtEnd() {
          const inputs = Array.from(container.querySelectorAll('input[name="invited_emails[]"]'));
          if (inputs.length === 0) {
            container.appendChild(createEmailInput(1));
            return;
          }

          // If last input isn't empty, append a new one
          const last = inputs[inputs.length - 1];
          if (last.value.trim() !== '') {
            container.appendChild(createEmailInput(inputs.length + 1));
          }

          // Remove extra empty inputs so only one empty input remains at end
          // Walk from the end and remove consecutive empty inputs beyond the last one
          let emptyCount = 0;
          for (let i = inputs.length - 1; i >= 0; i--) {
            if (inputs[i].value.trim() === '') emptyCount++; else break;
          }
          while (emptyCount > 1) {
            const toRemove = container.querySelectorAll('.input-wrapper')[container.querySelectorAll('.input-wrapper').length - 1];
            if (toRemove) toRemove.remove();
            emptyCount--;
          }
        }

        // Delegate input events
        container.addEventListener('input', function (e) {
          if (e.target && e.target.matches('input[name="invited_emails[]"]')) {
            ensureOneEmptyAtEnd();
          }
        });

        // Initialize: ensure at least one empty input
        ensureOneEmptyAtEnd();
      })();


    </script>
  </body>
</html>




