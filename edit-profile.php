<?php
session_start();
require_once 'src/config/db.php';
$user_id = $_SESSION['user_id'] ?? null;
$username = '';
$email = '';
$avatar = 'images/avatar.png';
$success = false;
if ($user_id) {
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? $username;
    $email = $_POST['email'] ?? $email;
    $avatar_path = $avatar;
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = 'images/profiles/';
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
      }
      $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
      $filename = uniqid('avatar_') . '.' . $ext;
      $target = $upload_dir . $filename;
      if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target)) {
        $avatar_path = $filename;
      }
    } else {
      // If no new file uploaded, keep current avatar filename
      $avatar_path = $avatar;
    }
    $stmt = $conn->prepare("UPDATE USER SET USER_NAME = ?, USER_EMAIL = ?, AVATAR = ? WHERE ID_USER = ?");
    $stmt->bind_param('sssi', $username, $email, $avatar_path, $user_id);
    $stmt->execute();
    $stmt->close();
    $success = true;
  }
  $stmt = $conn->prepare("SELECT USER_NAME, USER_EMAIL, AVATAR FROM USER WHERE ID_USER = ?");
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  if ($row = $result->fetch_assoc()) {
    $username = $row['USER_NAME'];
    $email = $row['USER_EMAIL'];
    if (!empty($row['AVATAR'])) {
      $avatar = $row['AVATAR'];
    }
  }
  $stmt->close();
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Edit Profile - Task-o-Mania</title>
    <meta name="description" content="Update your Task-o-Mania profile information." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_profile.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
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
          <button class="back-btn" type="button" aria-label="Go back" onclick="history.back()">
            <span aria-hidden="true">&#x2039;</span>
          </button>

          <h1 class="page-title">Edit Profile</h1>


        </header>

        <main class="page" role="main">
          <section class="card edit-card">
            <div class="card__avatar">
              <img id="avatar-preview" src="images/profiles/<?php echo htmlspecialchars($avatar); ?>" alt="Profile avatar" />
            </div>
            <h2>Update your info</h2>
            <p class="card__subtitle">Make changes and save to keep your household in sync.</p>

            <form class="edit-form" action="" method="post" enctype="multipart/form-data">
              <label>
                <span>Profile Picture</span>
                <div class="input-wrapper">
                  <input type="file" name="avatar" id="avatar" accept="image/*" />
                </div>
              </label>
              <label>
                <span>Username</span>
                <div class="input-wrapper">
                  <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required />
                </div>
              </label>
              <label>
                <span>Email</span>
                <div class="input-wrapper">
                  <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required />
                </div>
              </label>

              <div class="form-actions">
                <a class="btn-secondary" href="profile.php">Cancel</a>
                <button type="submit" class="btn-primary">Save changes</button>
              </div>
            </form>
            <p class="forgot-hint">Forgot your password? <a href="recover-password.html">Recover it</a></p>
          </section>
        </main>

        <div class="profile-modal" role="alertdialog" aria-live="polite" aria-modal="true" aria-hidden="true">
          <div class="profile-modal__card">
            <h2>Success</h2>
            <p>Changes saved successfully.</p>
            <button type="button" class="profile-modal__close">OK</button>
          </div>
        </div>
      </div>
    </div>

    <script>
      (function () {
        const modal = document.querySelector('.profile-modal');
        const closeBtn = document.querySelector('.profile-modal__close');
        if (!modal || !closeBtn) return;
        // Show modal only if PHP update was successful
        <?php if ($success): ?>
          modal.setAttribute('aria-hidden', 'false');
          modal.classList.add('profile-modal--visible');
        <?php endif; ?>
        closeBtn.addEventListener('click', () => {
          modal.classList.remove('profile-modal--visible');
          modal.setAttribute('aria-hidden', 'true');
          window.location.href = 'profile.php';
        });
      })();
    </script>
  </body>
</html>

