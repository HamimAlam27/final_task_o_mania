<?php
session_start();
require 'src/config/db.php';

// Validate user session
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;

// If no household selected, redirect to households page
if (!$household_id) {
    header('Location: households.php');
    exit;
}



// Leaderboard: get points from POINTS table (not computed from COMPLETION)
$stmt = $conn->prepare(
    "SELECT u.ID_USER, u.USER_NAME, u.AVATAR, COALESCE(p.TOTAL_POINTS,0) AS TOTAL_POINTS
     FROM USER u
     JOIN HOUSEHOLD_MEMBER hm ON u.ID_USER = hm.ID_USER AND hm.ID_HOUSEHOLD = ?
     LEFT JOIN POINTS p ON p.ID_USER = u.ID_USER AND p.ID_HOUSEHOLD = ?
     ORDER BY TOTAL_POINTS DESC, u.USER_NAME ASC"
);
$stmt->bind_param('ii', $household_id, $household_id);
$stmt->execute();
$result = $stmt->get_result();
$members = [];
while ($row = $result->fetch_assoc()) {
    $members[] = $row;
}
$stmt->close();

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Leaderboard - Task-o-Mania</title>
    <meta name="description" content="See household leaders across time frames." />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style_leaderboard.css" />
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
                    <p class="subtitle">Top performers</p>
                    <h1>Leaderboard</h1>
                </div>

                <?php include 'header.php'; ?>
            </header>

            <main class="page" role="main">
                

                <section class="board" aria-label="This week standings">
    <ol>
        <?php
        $pos = 1;
        $image_base_path = 'images/profiles/'; // Define the path once

        foreach ($members as $m) {
            $name = htmlspecialchars($m['USER_NAME']);
            $points = htmlspecialchars($m['TOTAL_POINTS']);
            $avatar = $m['AVATAR']; // This should be the filename (e.g., 'avatar_123.jpg')
            
            // Determine the image source and alt text
            if (!empty($avatar)) {
                // Correctly construct the full path for the custom avatar
                $img_src = htmlspecialchars($image_base_path . $avatar);
                $img_alt = 'Profile picture for ' . $name;
            } else {
                // Use the default avatar
                $img_src = 'IMAGES/avatar.png'; // Make sure the case (IMAGES) is correct
                $img_alt = 'Default profile picture for ' . $name;
            }

            echo '<li>';
            echo '<span class="position">' . $pos . '</span>';
            echo '<span class="member">';
            
            // Output the image tag
            echo '<img src="' . $img_src . '" alt="' . htmlspecialchars($img_alt) . '" />';
            
            // Output the member name next to the image (optional, depends on your design)
            echo ' ' . $name; 
            
            echo '</span>';
            echo '<span class="points">' . $points . ' pts</span>';
            echo '</li>';
            $pos++;
        }
        if (count($members) === 0) {
            echo '<li>No members found.</li>';
        }
        ?>
    </ol>
</section>
            </main>
        </div>
    </div>
</body>

</html>