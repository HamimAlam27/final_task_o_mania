<?php
session_start();
require 'src/config/db.php';

// Validate user session and household
if (!isset($_SESSION['user_id'])) {
    header('Location: sign-in.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$household_id = $_SESSION['household_id'] ?? null;

if (!$household_id) {
    header('Location: households.php');
    exit;
}

// 1. DETERMINE TIMEFRAME AND DATE RANGE
$timeframe = $_GET['timeframe'] ?? 'week'; // Default to 'week'
$date_start = '';
$leaderboard_title = '';

if ($timeframe === 'month') {
    // Start of the current month
    $date_start = date('Y-m-01');
    $leaderboard_title = 'Monthly Standings';
} else {
    // Start of the current week (Monday)
    $timeframe = 'week';
    $date_start = date('Y-m-d', strtotime('monday this week'));
    $leaderboard_title = 'Weekly Standings';
}


// 2. LEADERBOARD QUERY (USING COMPLETION TABLE FOR TIMEFRAME)
// We join USER and HOUSEHOLD_MEMBER, then LEFT JOIN a subquery on COMPLETION
// to SUM points only for the selected timeframe.
$stmt = $conn->prepare("
    SELECT 
        u.ID_USER, 
        u.USER_NAME, 
        u.AVATAR, 
        COALESCE(SUM(c.POINTS), 0) AS TOTAL_POINTS
    FROM USER u
    JOIN HOUSEHOLD_MEMBER hm ON u.ID_USER = hm.ID_USER AND hm.ID_HOUSEHOLD = ?
    LEFT JOIN COMPLETION c ON c.SUBMITTED_BY = u.ID_USER 
        AND c.ID_HOUSEHOLD = hm.ID_HOUSEHOLD 
        AND c.APPROVED_BY IS NOT NULL
        AND c.COMPLETED_AT >= ?
    GROUP BY u.ID_USER, u.USER_NAME, u.AVATAR
    ORDER BY TOTAL_POINTS DESC, u.USER_NAME ASC
");

$stmt->bind_param('is', $household_id, $date_start); // 'i' for household_id, 's' for date_start
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
    <link rel="stylesheet" href="style_leaderboard.css" />
    <link rel="stylesheet" href="style_user_chrome.css" />
    <style>
        .timeframe-selector {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        .timeframe-selector .btn {
            padding: 8px 18px;
            border-radius: 8px;
            border: 1px solid #ddd;
            background: #fff;
            color: #444;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .timeframe-selector .btn.active {
            background: #5b2df6;
            color: #fff;
            border-color: #5b2df6;
            font-weight: 600;
        }
    </style>
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
                


                <section class="board" aria-label="<?php echo htmlspecialchars($leaderboard_title); ?>">
                    
                <header>
                                    <div class="timeframe-selector">
                    <a href="?timeframe=week" class="btn <?php echo $timeframe === 'week' ? 'active' : ''; ?>">
                        This Week
                    </a>
                    <a href="?timeframe=month" class="btn <?php echo $timeframe === 'month' ? 'active' : ''; ?>">
                        This Month
                    </a>
                </div>
                       
                    </header>
                    <ol>
                        <?php
                        $pos = 1;
                        $image_base_path = 'images/profiles/'; 

                        foreach ($members as $m) {
                            $name = htmlspecialchars($m['USER_NAME']);
                            $points = htmlspecialchars($m['TOTAL_POINTS']);
                            $avatar = $m['AVATAR']; 
                            
                            if (!empty($avatar)) {
                                $img_src = htmlspecialchars($image_base_path . $avatar);
                                $img_alt = 'Profile picture for ' . $name;
                            } else {
                                $img_src = 'IMAGES/avatar.png'; 
                                $img_alt = 'Default profile picture for ' . $name;
                            }

                            echo '<li>';
                            echo '<span class="position">' . $pos . '</span>';
                            echo '<span class="member">';
                            echo '<img src="' . $img_src . '" alt="' . htmlspecialchars($img_alt) . '" />';
                            echo ' ' . $name; 
                            echo '</span>';
                            echo '<span class="points">' . $points . ' pts</span>';
                            echo '</li>';
                            $pos++;
                        }
                        if (count($members) === 0) {
                            echo '<li>No members found, or no tasks completed this ' . htmlspecialchars($timeframe) . '.</li>';
                        }
                        ?>
                    </ol>
                </section>
            </main>
        </div>
    </div>
</body>

</html>