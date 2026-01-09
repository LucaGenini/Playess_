<?php
// song-mode.php – Playess Open Mode (Song Guess)
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Playess – Open Mode</title>

    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main4.css">
</head>
<body>

    <div class="app-screen bg-tutorial">
        <div class="container-narrow">

            <header class="dashboard-header">
                <h1 class="dashboard-title">Open Mode</h1>
            </header>

            <main class="dashboard-main">

                <section class="dashboard-panel">

                    <!-- ELO -->
                    <div class="openmode-elo">
                        <div class="mode-tab" id="eloBadge">
                            ELO: <span id="eloValue">1200</span>
                        </div>
                    </div>

                    <!-- Cover -->
                    <div class="open-cover-wrapper">
                        <div class="open-cover" id="coverPlaceholder">
                            <img
                                src="img/game/openmode_cover_placeholder.svg"
                                alt="Cover placeholder"
                                class="open-cover-img"
                            >
                        </div>

                        <div class="open-cover-reveal hidden" id="coverReveal">
                            <img id="coverImage" src="" alt="Song cover" class="open-cover-img">
                            <p id="coverCaption" class="open-cover-caption"></p>
                        </div>
                    </div>

                    <!-- Countdown (neu, wie im Cover Mode) -->
                    <div class="cover-countdown-wrapper">
                        <span class="cover-countdown-label">Time left</span>
                        <span class="cover-countdown-value" id="songCountdown">20s</span>
                    </div>

                    <!-- Player -->
                    <div class="openmode-player">
                        <button class="player-btn" id="btnPlay" type="button">
                            <img src="img/nav-bar/play.svg" alt="Play" class="icon">
                        </button>
                        <audio id="audioPreview"></audio>
                    </div>

                    <!-- Inputs -->
                    <div class="game-card-list openmode-form">

                        <div class="open-field-group">
                            <label for="artistInput">Artist</label>
                            <input type="text" id="artistInput" name="artist" placeholder="Type artist">
                            <span class="open-field-delta" id="artistDelta"></span>
                        </div>

                        <div class="open-field-group">
                            <label for="titleInput">Title</label>
                            <input type="text" id="titleInput" name="title" placeholder="Type song title">
                            <span class="open-field-delta" id="titleDelta"></span>
                        </div>

                    </div>

                    <!-- Actions -->
                    <div class="mode-tabs mt-5">
                        <button class="mode-tab" id="btnEndSession" type="button">
                            End Session
                        </button>
                        <button class="mode-tab mode-tab--active" id="btnGuess" type="button">
                            Guess
                        </button>
                    </div>

                    <!-- History -->
                    <div class="openmode-history mt-5">
                        <h2 class="history-title">Your guessed tracks</h2>
                        <section class="leaderboard-list" id="historyList"></section>
                    </div>

                    <!-- Optional Error -->
                    <p id="songError" class="text-muted mt-2" style="font-size:11px;"></p>

                    <!-- Disclaimer -->
                    <div class="openmode-disclaimer">
                        <p>
                            Album artwork and audio previews are provided by the Apple iTunes Search API.
                            All rights to the music, artwork and trademarks belong to Apple and the respective
                            rights holders. This game mode is for informational and promotional purposes only.
                        </p>
                    </div>

                </section>

            </main>

        </div>
    </div>

    <nav class="bottom-nav">
        <a href="leaderboard.php?cat=all" class="bottom-nav-item" aria-label="leaderboard">
            <img src="img/nav-bar/leaderboard.svg" alt="leaderboard icon" class="icon">
        </a>
        <button class="bottom-nav-item bottom-nav-item--active" aria-label="Play">
            <img src="img/nav-bar/play.svg" alt="play icon" class="icon">
        </button>
        <button class="bottom-nav-item" aria-label="Profile">
            <img src="img/nav-bar/profile.svg" alt="profile icon" class="icon">
        </button>
    </nav>

    <script>
        window.PLAYESS_MODE = {
            mode: 'open',
            roundSeconds: 20,

            newRoundUrl: '/api/game_new_round.php?mode=open',
            guessUrl: 'game_guess.php',

            eloBadgeId: 'eloBadge',
            eloValueId: 'eloValue',

            placeholderId: 'coverPlaceholder',
            revealId: 'coverReveal',
            imageId: 'coverImage',
            captionId: 'coverCaption',

            artistInputId: 'artistInput',
            titleInputId: 'titleInput',
            artistDeltaId: 'artistDelta',
            titleDeltaId: 'titleDelta',

            guessBtnId: 'btnGuess',
            endBtnId: 'btnEndSession',

            historyListId: 'historyList',
            errorId: 'songError',

            countdownId: 'songCountdown',

            // Audio nur in open
            hasAudio: true,
            audioId: 'audioPreview',
            playBtnId: 'btnPlay'
        };
    </script>
    <script src="/js/playess-mode.js"></script>

</body>
</html>
