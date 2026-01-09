<?php
// signup.php – Schritt 2: Character + Lieblingssong wählen

session_start();

// Sicherstellen, dass Step 1 ausgefüllt wurde
if (
    empty($_SESSION['signup_name']) ||
    empty($_SESSION['signup_email']) ||
    empty($_SESSION['signup_pass'])
) {
    header('Location: register.php');
    exit;
}

// Standardwerte für die Anzeige
$selectedCharacter = 'male';

// Optional: Fehlermeldung aus vorherigem Versuch
$error = $_SESSION['signup_step2_error'] ?? '';
unset($_SESSION['signup_step2_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Playess – Choose your style</title>

    <link rel="stylesheet" href="/css/resets.css">
    <link rel="stylesheet" href="/css/fonts.css">
    <link rel="stylesheet" href="/css/main4.css">
</head>

<!-- Hintergrund wie bei register.php -->
<body style="background-image: var(--gradient-1);">

<div class="app-screen container-narrow">

    <header class="start-top">

        <h1 class="start-title h1">
            Choose your<br>
            character & favourite song.
        </h1>
    </header>

    <main>

        <?php if (!empty($error)): ?>
            <div class="form-alert form-alert--error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <!-- Character Preview mit Cover auf dem Shirt -->
        <div class="character-preview-wrapper mt-3">
            <div
                id="characterWrapper"
                class="character-wrapper <?= $selectedCharacter === 'female' ? 'female' : 'male' ?>"
            >
                <img
                    id="characterBase"
                    src="<?= $selectedCharacter === 'female'
                        ? '/img/characters/female.png'
                        : '/img/characters/male.png' ?>"
                    alt="Character preview"
                    class="character-base"
                >

                <!-- initial versteckt via CSS (.is-hidden) -->
                <div class="character-shirt-cover is-hidden" id="shirtCoverWrapper">
                    <img
                        id="shirtCoverImage"
                        src=""
                        alt="Favourite track cover"
                    >
                </div>
            </div>
        </div>

        <!-- action zeigt auf signup-finish.php -->
        <form action="signup-finish.php" method="post" class="login-form" id="signupForm">

            <!-- Character Auswahl -->
            <div class="signup-block">
                <p class="text-center text-muted h4">Choose your character:</p>

                <div class="character-options">
                    <label class="character-option">
                        <input
                            type="radio"
                            name="character_choice"
                            value="male"
                            checked
                            data-img="/img/characters/male.png"
                            data-class="male"
                        >
                        <img src="/img/characters/male.png" alt="Male character">
                        <span>Male</span>
                    </label>

                    <label class="character-option">
                        <input
                            type="radio"
                            name="character_choice"
                            value="female"
                            data-img="/img/characters/female.png"
                            data-class="female"
                        >
                        <img src="/img/characters/female.png" alt="Female character">
                        <span>Female</span>
                    </label>
                </div>
            </div>

            <!-- Lieblingssong Suche -->
            <div class="favorite-song-search">
                <p class="text-muted mt-3 h4">
                    Search for your favourite song. We will show its cover on your character&apos;s shirt.
                </p>
                <div class="mt-5">
                    <div class="favorite-song-search-row">
                        <input
                            type="text"
                            id="trackSearchInput"
                            placeholder="Type song or artist"
                        >
                        <button
                            type="button"
                            class="primary-btn btn-compact"
                            id="trackSearchButton"
                        >
                            Search
                        </button>
                    </div>
                </div>

                <div class="track-results" id="trackResults"></div>
            </div>

            <!-- Hidden Inputs für ausgewählten Track -->
            <input type="hidden" name="favorite_track_id" id="favoriteTrackId" value="">
            <input type="hidden" name="favorite_track_title" id="favoriteTrackTitle" value="">
            <input type="hidden" name="favorite_track_artist" id="favoriteTrackArtist" value="">
            <input type="hidden" name="favorite_track_artwork" id="favoriteTrackArtwork" value="">

            <!-- Button -->
            <div class="login-btn-wrapper login-btn-wrapper--center">
                <button type="submit" class="primary-btn">
                    Finish
                </button>
            </div>

        </form>

    </main>

    <footer class="text-center text-muted h4 mt-3">
        © Playess 2025
    </footer>

</div>

<script>
    // Character-Preview beim Wechseln des Characters aktualisieren
    const characterBase    = document.getElementById('characterBase');
    const characterWrapper = document.getElementById('characterWrapper');

    document.querySelectorAll('input[name="character_choice"]').forEach(radio => {
        radio.addEventListener('change', () => {
            const imgSrc = radio.getAttribute('data-img');
            const cls    = radio.getAttribute('data-class'); // male/female

            if (imgSrc) characterBase.src = imgSrc;
            if (cls) characterWrapper.className = 'character-wrapper ' + cls;
        });
    });

    // Track-Suche via API-Endpunkt
    const searchInput   = document.getElementById('trackSearchInput');
    const searchButton  = document.getElementById('trackSearchButton');
    const resultsBox    = document.getElementById('trackResults');
    const shirtWrapper  = document.getElementById('shirtCoverWrapper');
    const shirtImage    = document.getElementById('shirtCoverImage');

    const hiddenId      = document.getElementById('favoriteTrackId');
    const hiddenTitle   = document.getElementById('favoriteTrackTitle');
    const hiddenArtist  = document.getElementById('favoriteTrackArtist');
    const hiddenArtwork = document.getElementById('favoriteTrackArtwork');

    function renderResults(tracks) {
        resultsBox.innerHTML = '';
        if (!tracks || tracks.length === 0) {
            resultsBox.innerHTML = '<div class="text-muted">No results found.</div>';
            return;
        }

        tracks.forEach(track => {
            const item = document.createElement('div');
            item.className = 'track-result-item';
            item.innerHTML = `
                <img src="${track.artwork}" alt="">
                <div class="track-result-meta">
                    <div class="track-result-title">${track.title}</div>
                    <div class="track-result-artist">${track.artist}</div>
                </div>
            `;

            item.addEventListener('click', () => {
                hiddenId.value      = track.id;
                hiddenTitle.value   = track.title;
                hiddenArtist.value  = track.artist;
                hiddenArtwork.value = track.artwork;

                shirtImage.src = track.artwork;
                shirtWrapper.classList.remove('is-hidden');
            });

            resultsBox.appendChild(item);
        });
    }

    function searchTracks() {
        const q = searchInput.value.trim();
        if (!q) return;

        resultsBox.innerHTML = '<div class="text-muted">Searching…</div>';

        fetch('/api/t-shirt_picture_search.php?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => renderResults(data.tracks || []))
            .catch(err => {
                console.error(err);
                resultsBox.innerHTML = '<div class="text-muted">Error while searching.</div>';
            });
    }

    searchButton.addEventListener('click', (e) => {
        e.preventDefault();
        searchTracks();
    });

    searchInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchTracks();
        }
    });
</script>

</body>
</html>
