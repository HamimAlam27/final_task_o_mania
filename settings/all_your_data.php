<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>All Your Data - Task-o-Mania</title>
    <meta name="description" content="View, export, or delete the data stored in your Task-o-Mania account." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style_deactivate_account.css" />
    <link rel="stylesheet" href="../style_user_chrome.css" />
    <link rel="stylesheet" href="../style_all_your_data.css" />
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
            <h1 class="page-title">All Your Data</h1>
            <p>View, export, or delete the information stored in your household account.</p>
          </div>

          <?php include '../header.php'; ?>
        </header>

        <main class="page" role="main">
          <section class="panel data-panel">
            <header class="panel__header">
              <div>
                <h2>View Full Household Data</h2>
                <p>Download a full report of everything stored in your household.</p>
              </div>
            </header>
            <button type="button" class="btn-primary view-data">View Full Household Data</button>
          </section>

          <section class="panel user-delete-panel">
            <header class="panel__header">
              <div>
                <h2>Delete data for a specific member</h2>
                <p>Select a member to remove their data only.</p>
              </div>
            </header>
            <form class="form user-delete-form" action="#" method="post">
              <div class="form-field">
                <label for="user-select">Member</label>
                <div class="input-wrapper select-wrapper">
                  <select id="user-select" required>
                    <option value="" disabled selected>Select member</option>
                    <option value="livia">Livia</option>
                    <option value="sophia">Sophia</option>
                    <option value="victor">Victor</option>
                  </select>
                </div>
              </div>
              <div class="actions">
                <button type="submit" class="btn-primary">Delete data for this user</button>
              </div>
            </form>
          </section>

          <section class="panel danger-panel">
            <header class="panel__header">
              <div>
                <h2>Delete All My Data</h2>
                <p>Remove every bit of information stored in this account.</p>
              </div>
            </header>
            <button type="button" class="btn-primary btn-danger delete-data">Delete All My Data</button>
          </section>
        </main>
      </div>
    </div>

    <div class="data-modal" role="alertdialog" aria-modal="true" aria-labelledby="data-modal-title" aria-hidden="true">
      <div class="data-modal__card">
        <h2 id="data-modal-title">Success</h2>
        <p class="data-modal__message">Action completed.</p>
        <div class="data-modal__actions">
          <button type="button" class="data-modal__close">Close</button>
          <button type="button" class="data-modal__confirm" hidden>Confirm</button>
        </div>
      </div>
    </div>

    <script>
      (function () {
        const viewBtn = document.querySelector('.view-data');
        const deleteUserForm = document.querySelector('.user-delete-form');
        const deleteAllBtn = document.querySelector('.delete-data');
        const modal = document.querySelector('.data-modal');
        const modalMessage = document.querySelector('.data-modal__message');
        const modalClose = document.querySelector('.data-modal__close');
        const modalConfirm = document.querySelector('.data-modal__confirm');
        let confirmHandler = null;

        if (!modal || !modalClose || !modalConfirm) return;

        const showInfo = (message) => {
          modalMessage.textContent = message;
          modalConfirm.hidden = true;
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('data-modal--visible');
        };

        const showConfirm = (message, onConfirm) => {
          modalMessage.textContent = message;
          modalConfirm.hidden = false;
          confirmHandler = onConfirm;
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('data-modal--visible');
        };

        const hideModal = () => {
          modal.setAttribute('aria-hidden', 'true');
          modal.classList.remove('data-modal--visible');
          confirmHandler = null;
        };

        modalClose.addEventListener('click', hideModal);
        modal.addEventListener('click', (event) => {
          if (event.target === modal) hideModal();
        });
        window.addEventListener('keydown', (event) => {
          if (event.key === 'Escape') hideModal();
        });

        modalConfirm.addEventListener('click', () => {
          if (typeof confirmHandler === 'function') confirmHandler();
          hideModal();
        });

        viewBtn?.addEventListener('click', () => {
          showInfo('Opening household data report...');
          window.location.href = '../households.html';
        });

        deleteUserForm?.addEventListener('submit', (event) => {
          event.preventDefault();
          showInfo('Selected user data deleted.');
          event.currentTarget.reset();
        });

        deleteAllBtn?.addEventListener('click', () => {
          showConfirm('Are you sure you want to permanently delete all data from this account?', () => {
            showInfo('All data deleted successfully.');
          });
        });
      })();
    </script>
  </body>
</html>



