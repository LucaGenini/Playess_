<?php
// cover-mode.php – Playess Album Cover Guess Mode
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Playess – Album Cover Guess</title>

    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main4.css">
</head>
<body>

    <div class="app-screen bg-tutorial">
        <div class="container-narrow">

            <header class="dashboard-header">
                <h1 class="dashboard-title h1">Album Cover Guess</h1>
            </header>

            <main class="dashboard-main">

                <section class="dashboard-panel">

                    <!-- ELO-Pill (für game_mode=cover) -->
                    <div class="openmode-elo">
                        <div class="mode-tab" id="eloBadgeCover">
                            ELO: <span id="eloValueCover">1200</span>
                        </div>
                    </div>

                    <!-- Cover-Bereich -->
                    <div class="open-cover-wrapper">
                        <div class="open-cover" id="coverPlaceholderCover">
                            <img
                                src="img/game/openmode_cover_placeholder.svg"
                                alt="Cover placeholder"
                                class="open-cover-img"
                            >
                        </div>

                        <div class="open-cover-reveal hidden" id="coverRevealCover">
                            <img id="coverImageCover" src="" alt="Album cover" class="open-cover-img">
                            <p id="coverCaptionCover" class="open-cover-caption"></p>
                        </div>
                    </div>

                    <!-- Countdown unterhalb des Covers -->
                    <div class="cover-countdown-wrapper">
                        <span class="cover-countdown-label">Time left</span>
                        <span class="cover-countdown-value" id="coverCountdown">30s</span>
                    </div>

                    <!-- Artist / Album Inputs -->
                    <div class="game-card-list openmode-form">

                        <div class="open-field-group">
                            <label for="artistInputCover">Artist</label>
                            <input type="text" id="artistInputCover" name="artist" placeholder="Type artist">
                            <span class="open-field-delta" id="artistDeltaCover"></span>
                        </div>

                        <div class="open-field-group">
                            <label for="albumInputCover">Album title</label>
                            <input type="text" id="albumInputCover" name="title" placeholder="Type album title">
                            <span class="open-field-delta" id="titleDeltaCover"></span>
                        </div>

                    </div>

                    <!-- Actions -->
                    <div class="mode-tabs mt-5">
                        <button class="mode-tab" id="btnEndSessionCover" type="button">
                            End Session
                        </button>
                        <button class="mode-tab mode-tab--active" id="btnGuessCover" type="button">
                            Guess
                        </button>
                    </div>

                    <!-- History -->
                    <div class="openmode-history mt-5">
                        <h2 class="history-title">Your album guesses</h2>
                        <section class="leaderboard-list" id="historyListCover"></section>
                    </div>

                    <!-- Fehler / Status -->
                    <p id="coverError" class="text-muted mt-2" style="font-size:11px;"></p>

                    <!-- Rechte-Hinweis -->
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
        <a href="leaderboard.php?mode=cover" class="bottom-nav-item bottom-nav-item--active" aria-label="leaderboard">
            <img src="img/nav-bar/playmode-rangliste.inactive.svg" alt="leaderboard icon" class="icon">
        </a>
        <a href="dashboard.php" class="bottom-nav-item" aria-label="Play">
            <img src="img/nav-bar/playmode-play-active.svg" alt="play icon" class="icon">
        </a>
        <button class="bottom-nav-item" aria-label="Profile">
            <img src="img/nav-bar/profile.svg" alt="profile icon" class="icon">
        </button>
    </nav>

    <script>
        // Konfiguration für gemeinsames JS
        window.PLAYESS_MODE = {
            mode: 'cover',
            roundSeconds: 30,

            // Wichtig: absolute Pfade vermeiden Ordner-Probleme 
            newRoundUrl: '/api/game_new_round.php?mode=cover',
            guessUrl: 'game_guess.php',

            // DOM IDs
            eloBadgeId: 'eloBadgeCover',
            eloValueId: 'eloValueCover',

            placeholderId: 'coverPlaceholderCover',
            revealId: 'coverRevealCover',
            imageId: 'coverImageCover',
            captionId: 'coverCaptionCover',

            artistInputId: 'artistInputCover',
            titleInputId: 'albumInputCover',
            artistDeltaId: 'artistDeltaCover',
            titleDeltaId: 'titleDeltaCover',

            guessBtnId: 'btnGuessCover',
            endBtnId: 'btnEndSessionCover',

            historyListId: 'historyListCover',
            errorId: 'coverError',

            // Countdown
            countdownId: 'coverCountdown',

            // Optional: Blur-Animation Klasse (nur cover)
            blurClass: 'cover-blur-animate',

            // Audio gibts in cover nicht
            hasAudio: false,

            // ✅ Neu: Apple Music Button auch im Cover-Mode aktivieren
            // playess-mode.js nutzt dieses Feld, um den Link rechts in der History zu rendern.
            historyLinkKey: 'appleMusicUrl',
            historyLinkLabel: ' Music'
        };
    </script>
    <script src="/js/playess-mode.js"></script>

</body>
</html>
