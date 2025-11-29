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
                <section class="podium" aria-label="All-time podium">
                    <?php
                    $top = array_slice($members, 0, 3);
                    // Uniform cards, all same style and level
                    for ($i = 0; $i < 3; $i++) {
                        $slot = $top[$i] ?? null;
                        $rank = $i + 1;
                        if ($slot) {
                            $name = htmlspecialchars($slot['USER_NAME']);
                            $points = htmlspecialchars($slot['TOTAL_POINTS']);
                            $avatar = $slot['AVATAR'];
                        } else {
                            $name = '—';
                            $points = '0';
                            $avatar = null;
                        }
                        echo "<article class=\"podium-card\">";
                        if (!empty($avatar)) {
                            $b64 = base64_encode($avatar);
                            echo "<img src=\"data:image/png;base64,$b64\" alt=\"$name\" />";
                        } else {
                            echo "<img src=\"IMAGES/avatar.png\" alt=\"$name\" />";
                        }
                        echo "<h2>$name</h2>";
                        echo "<p>{$points} pts</p>";
                        echo "<span class=\"rank\">#$rank</span>";
                        echo "</article>";
                    }
                    ?>
                </section>

                <section class="board" aria-label="This week standings">
                    <ol>
                        <?php
                        $pos = 1;
                        foreach ($members as $m) {
                            $name = htmlspecialchars($m['USER_NAME']);
                            $points = htmlspecialchars($m['TOTAL_POINTS']);
                            $avatar = $m['AVATAR'];
                            echo '<li>';
                            echo '<span class="position">' . $pos . '</span>';
                            echo '<span class="member">';
                            if (!empty($avatar)) {
                                $b64 = base64_encode($avatar);
                                echo '<img src="data:image/png;base64,' . $b64 . '" alt="' . $name . '" /> ' . $name;
                            } else {
                                echo '<img src="IMAGES/avatar.png" alt="' . $name . '" /> ' . $name;
                            }
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