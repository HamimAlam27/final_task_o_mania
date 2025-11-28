<?php
session_start();
require_once '../src/config/db.php';

$household_id = $_SESSION['household_id'] ?? null;
$ai_confidence = 80;
if ($household_id) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ai_confidence = intval($_POST['ai_confidence'] ?? 80);
    $stmt = $conn->prepare("UPDATE HOUSEHOLD SET AI_CONFIDENCE = ? WHERE ID_HOUSEHOLD = ?");
    $stmt->bind_param('ii', $ai_confidence, $household_id);
    $stmt->execute();
    $stmt->close();
  }
  $stmt = $conn->prepare("SELECT AI_CONFIDENCE FROM HOUSEHOLD WHERE ID_HOUSEHOLD = ?");
  $stmt->bind_param('i', $household_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $ai_confidence = intval($row['AI_CONFIDENCE']);
  }
  $stmt->close();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>AI Validation - Task-o-Mania</title>
    <meta name="description" content="Configure AI validation behaviour for completed tasks." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style_deactivate_account.css" />
    <link rel="stylesheet" href="../style_user_chrome.css" />
    <link rel="stylesheet" href="../style_ai_validation.css" />
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
            <h1 class="page-title">AI Validation</h1>
            <p>Configure how the AI decides when tasks can be approved automatically.</p>
          </div>


        </header>

        <main class="page" role="main">
          <section class="panel intro-panel">
            <p>
              AI Validation lets you choose between automatic approvals or manual reviews. Set the minimum AI confidence needed: results above the threshold are auto-approved, while lower scores require human review. Turn the feature off to review every task manually.
            </p>
          </section>



          <section class="panel ai-settings-panel">
            <header class="panel__header">
              <div>
                <h2>Automatic approval threshold</h2>
                <p>Minimum AI confidence required for automatic approval.</p>
              </div>
            </header>
            <form class="form ai-settings-form" action="" method="post">
              <div class="range-field">
                <input type="range" id="ai-confidence" name="ai_confidence" min="50" max="100" value="<?php echo htmlspecialchars($ai_confidence); ?>" />
                <span id="confidence-value"><?php echo htmlspecialchars($ai_confidence); ?>%</span>
              </div>
              <div class="actions">
                <button type="submit" class="btn-primary">Save settings</button>
              </div>
            </form>
          </section>


          <section class="panel retention-panel">
            <header class="panel__header">
              <div>
                <h2>Image Retention Duration</h2>
                <p>Decide how long the submitted photos stay available for review.</p>
              </div>
            </header>
            <form class="form retention-form" action="#" method="post">
              <label class="radio-row">
                <input type="radio" name="retention" value="1week" checked />
                <span>1 week</span>
              </label>
              <label class="radio-row">
                <input type="radio" name="retention" value="1month" />
                <span>1 month</span>
              </label>
              <label class="radio-row">
                <input type="radio" name="retention" value="forever" />
                <span>Forever</span>
              </label>
              <div class="actions">
                <button type="submit" class="btn-primary">Save changes</button>
              </div>
            </form>
          </section>
        </main>
      </div>
    </div>

    <div class="ai-modal" role="alertdialog" aria-modal="true" aria-labelledby="ai-modal-title" aria-hidden="true">
      <div class="ai-modal__card">
        <h2 id="ai-modal-title">Success</h2>
        <p class="ai-modal__message">Settings updated.</p>
        <button type="button" class="ai-modal__close">Close</button>
      </div>
    </div>

    <script>
      (function () {
        const rangeInput = document.getElementById('ai-confidence');
        const rangeValue = document.getElementById('confidence-value');
        rangeInput?.addEventListener('input', () => {
          rangeValue.textContent = `${rangeInput.value}%`;
        });
      })();
    </script>
  </body>
</html>




