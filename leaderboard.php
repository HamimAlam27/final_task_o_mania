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
                    <article class="podium-card podium-card--second">
                        <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
                        <h2>Livia Vaccaro</h2>
                        <p>1.8k</p>
                        <span class="rank">#2</span>
                    </article>

                    <article class="podium-card podium-card--first">
                        <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
                        <h2>Livia Vaccaro</h2>
                        <p>2.5k points!</p>
                        <span class="rank">#1</span>
                    </article>

                    <article class="podium-card podium-card--third">
                        <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
                        <h2>Livia Vaccaro</h2>
                        <p>1.4k</p>
                        <span class="rank">#3</span>
                    </article>
                </section>

                <section class="board" aria-label="This week standings">

                    <ol>
                        <li>
                            <span class="position">1</span>
                            <span class="member">
                                <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
                                Livia Vaccaro
                            </span>
                            <span class="points">50 pts</span>
                        </li>
                        <li>
                            <span class="position">2</span>
                            <span class="member">
                                <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
                                Livia Vaccaro
                            </span>
                            <span class="points">45 pts</span>
                        </li>
                        <li>
                            <span class="position">3</span>
                            <span class="member">
                                <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
                                Livia Vaccaro
                            </span>
                            <span class="points">30 pts</span>
                        </li>
                        <li>
                            <span class="position">4</span>
                            <span class="member">
                                <img src="IMAGES/avatar.png" alt="Livia Vaccaro" />
                                Livia Vaccaro
                            </span>
                            <span class="points">10 pts</span>
                        </li>
                    </ol>
                </section>
            </main>
        </div>
    </div>
</body>

</html>