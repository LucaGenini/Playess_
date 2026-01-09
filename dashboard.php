<?php
// dashboard.php – Playess Dashboard
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Playess – Dashboard</title>

    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main4.css">
</head>
<body>

    <!-- Hintergrund via bg-tutorial wie bei tutorial.php -->
    <div class="app-screen bg-tutorial">
        <div class="container-narrow">

            <header class="dashboard-header">
                <h1 class="dashboard-title h1">Choose your game mode</h1>
            </header>

            <main class="dashboard-main">

                <section class="dashboard-panel">
                    <!-- Game Cards -->
                    <div class="game-card-list">

                        <!-- Open Mode -->
                        <a href="song-mode.php" class="game-card">
                            <div class="game-card-icon">
                                <img src="img/dashboard-icons/open.svg" alt="Open mode icon" class="icon">
                            </div>
                            <div class="game-card-text">
                                <h2 class="game-card-title">Song Guessing</h2>
                                <p class="game-card-subtitle">
                                    Guess the name of the Song.
                                </p>
                            </div>
                        </a>


                        <!-- Hardcore Mode -->
                        <a href="cover-mode.php" class="game-card">
                            <div class="game-card-icon">
                                <img src="img/dashboard-icons/competitive.svg" alt="Hardcore mode icon" class="icon">
                            </div>
                            <div class="game-card-text">
                                <h2 class="game-card-title">Cover Guessing</h2>
                                <p class="game-card-subtitle">
                                    Guess the name of the Album Cover.
                                </p>
                            </div>
                        </a>

                        <!-- Coming Soon -->
                        <a href="-" class="game-card">
                            <div class="game-card-icon">
                                <img src="-" alt="coming soon" class="icon">
                            </div>
                            <div class="game-card-text">
                                <h2 class="game-card-title">Coming Soon...</h2>
                                <p class="game-card-subtitle">
                                    More game modes are on the way!
                                </p>
                            </div>
                        </a>

                    </div>

                </section>

            </main>

        </div>
    </div>

    <!-- Navigation fixed am unteren Rand (CSS: .bottom-nav { position: fixed; bottom: 0; ... }) -->
<nav class="bottom-nav">
    <a href="leaderboard.php?mode=song" class="bottom-nav-item" aria-label="leaderboard">
        <img src="img/nav-bar/leaderboard.svg" alt="leaderboard icon" class="icon">
    </a>
    <a href="dashboard.php" class="bottom-nav-item bottom-nav-item--active" aria-label="play">
        <img src="img/nav-bar/play.svg" alt="play icon" class="icon">
    </a>
    <a href="profile.php" class="bottom-nav-item" aria-label="profile">
        <img src="img/nav-bar/profile.svg" alt="profile icon" class="icon">
    </a>
</nav>

</body>
</html>
