
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Privacy & Sharing - Task-o-Mania</title>
    <meta name="description" content="Control how your household data, tasks, and media are shared." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style_deactivate_account.css" />
    <link rel="stylesheet" href="../style_user_chrome.css" />
    <link rel="stylesheet" href="../style_privacy_and_sharing.css" />
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
            <h1 class="page-title">Privacy & Sharing</h1>
            <p>Decide who can see tasks, media, and personal activity inside your household.</p>
          </div>

          <?php include '../header.php'; ?>
        </header>

        <main class="page" role="main">
          <section class="panel">
            <header class="panel__header">
              <div>
                <h2>Task Visibility</h2>
                <p>Who can see tasks assigned to household members.</p>
              </div>
            </header>
            <form class="form visibility-form" action="#" method="post">
              <label class="radio-row">
                <input type="radio" name="visibility" value="everyone" checked />
                <span>Everyone in the household</span>
              </label>
              <label class="radio-row">
                <input type="radio" name="visibility" value="admins" />
                <span>Only admins and owners</span>
              </label>
              <label class="radio-row">
                <input type="radio" name="visibility" value="assigned" />
                <span>Only the assigned person</span>
              </label>
              <div class="actions">
                <button type="submit" class="btn-primary">Save visibility</button>
              </div>
            </form>
          </section>

          <section class="panel">
            <header class="panel__header">
              <div>
                <h2>Media sharing</h2>
                <p>Decide who can view photos or videos submitted for task validation.</p>
              </div>
            </header>
            <form class="form media-form" action="#" method="post">
              <label class="radio-row">
                <input type="radio" name="media" value="admins" checked />
                <span>Admins & owners only</span>
              </label>
              <label class="radio-row">
                <input type="radio" name="media" value="household" />
                <span>Everyone in the household</span>
              </label>
              <label class="radio-row">
                <input type="radio" name="media" value="uploader" />
                <span>Uploader + admin/owner</span>
              </label>
              <div class="actions">
                <button type="submit" class="btn-primary">Save media settings</button>
              </div>
            </form>
          </section>

          <section class="panel info-panel">
            <header class="panel__header">
              <div>
                <h2>Data collection summary</h2>
              </div>
            </header>
            <ul>
              <li>We collect task names, completion logs, and optional photos to keep the household organized.</li>
              <li>We do <strong>not</strong> collect personal messages, location data, or pictures from outside Task-o-Mania.</li>
              <li>You control what is visible, who can view it, and you can delete your data at any time.</li>
            </ul>
          </section>
        </main>
      </div>
    </div>

    <div class="privacy-modal" role="alertdialog" aria-modal="true" aria-labelledby="privacy-modal-title" aria-hidden="true">
      <div class="privacy-modal__card">
        <h2 id="privacy-modal-title">Success</h2>
        <p class="privacy-modal__message">Updated.</p>
        <div class="privacy-modal__actions">
          <button type="button" class="privacy-modal__close">Close</button>
        </div>
      </div>
    </div>

    <script>
      (function () {
        const visibilityForm = document.querySelector('.visibility-form');
        const mediaForm = document.querySelector('.media-form');
        const modal = document.querySelector('.privacy-modal');
        const modalMessage = document.querySelector('.privacy-modal__message');
        const modalClose = document.querySelector('.privacy-modal__close');

        if (!modal || !modalClose) return;

        const showModal = (message) => {
          modalMessage.textContent = message;
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('privacy-modal--visible');
        };

        const hideModal = () => {
          modal.setAttribute('aria-hidden', 'true');
          modal.classList.remove('privacy-modal--visible');
        };

        modalClose.addEventListener('click', hideModal);
        modal.addEventListener('click', (event) => {
          if (event.target === modal) hideModal();
        });
        window.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') hideModal();
        });

        visibilityForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          showModal('Task visibility updated.');
        });

        mediaForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          showModal('Media sharing preferences updated.');
        });
      })();
    </script>
  </body>
</html>


