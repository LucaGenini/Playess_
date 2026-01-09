<?php
// profile.php – Profile Overview + Settings (Customisation wie signup.php) + Delete

session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$success = '';
$error   = '';

// User laden
$stmt = $pdo->prepare("
    SELECT 
        id, email, name, password_hash,
        character_choice,
        favorite_track_id,
        favorite_track_title,
        favorite_track_artist,
        favorite_track_artwork,
        favorite_song_cover
    FROM users
    WHERE id = :id
    LIMIT 1
");
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$currentName   = $user['name'] ?: 'Player';
$currentEmail  = $user['email'] ?: '';
$currentGender = $user['character_choice'] ?: 'male';

$currentTrackId = $user['favorite_track_id'] ?: '';
$currentTitle   = $user['favorite_track_title'] ?: '';
$currentArtist  = $user['favorite_track_artist'] ?: '';
$currentArtwork = $user['favorite_track_artwork'] ?: '';
$currentCover   = $user['favorite_song_cover'] ?: ($currentArtwork ?: '');

$allowedTabs = ['custom', 'delete'];
$tab = isset($_GET['tab']) && in_array($_GET['tab'], $allowedTabs, true) ? $_GET['tab'] : 'custom';

// POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_custom') {
        $gender = $_POST['character_choice'] ?? 'male';
        if (!in_array($gender, ['male', 'female'], true)) {
            $gender = 'male';
        }

        // Feldnamen identisch wie Signup Flow
        $favId      = trim($_POST['favorite_track_id'] ?? '');
        $favTitle   = trim($_POST['favorite_track_title'] ?? '');
        $favArtist  = trim($_POST['favorite_track_artist'] ?? '');
        $favArtwork = trim($_POST['favorite_track_artwork'] ?? '');

        // Auswahl muss komplett sein
        if ($favId === '' || $favTitle === '' || $favArtist === '' || $favArtwork === '') {
            $error = 'Please select a favourite song from the search results.';
            $tab = 'custom';
        } else {
            try {
                $upd = $pdo->prepare("
                    UPDATE users
                    SET
                        character_choice = :gender,
                        favorite_track_id = :fid,
                        favorite_track_title = :ftitle,
                        favorite_track_artist = :fartist,
                        favorite_track_artwork = :fartwork,
                        favorite_song_cover = :fscover,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE id = :id
                    LIMIT 1
                ");

                $upd->execute([
                    ':gender'   => $gender,
                    ':fid'      => $favId,
                    ':ftitle'   => $favTitle,
                    ':fartist'  => $favArtist,
                    ':fartwork' => $favArtwork,
                    ':fscover'  => $favArtwork,
                    ':id'       => $userId,
                ]);

                $success = 'Profile updated.';
                $error = '';
                $tab = 'custom';

                $currentGender  = $gender;
                $currentTrackId = $favId;
                $currentTitle   = $favTitle;
                $currentArtist  = $favArtist;
                $currentArtwork = $favArtwork;
                $currentCover   = $favArtwork;

            } catch (PDOException $e) {
                $error = 'Could not save changes.';
                $tab = 'custom';
            }
        }
    }

    if ($action === 'delete_account') {
        $password = $_POST['delete_password'] ?? '';
        $confirm  = isset($_POST['delete_confirm']);

        if (!$confirm) {
            $error = 'Please confirm account deletion.';
            $tab = 'delete';
        } elseif ($password === '') {
            $error = 'Please enter your password.';
            $tab = 'delete';
        } elseif (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
            $error = 'Wrong password.';
            $tab = 'delete';
        } else {
            try {
                $pdo->beginTransaction();

                $delLb = $pdo->prepare("DELETE FROM leaderboard_stats WHERE user_id = :id");
                $delLb->execute([':id' => $userId]);

                $delUser = $pdo->prepare("DELETE FROM users WHERE id = :id LIMIT 1");
                $delUser->execute([':id' => $userId]);

                $pdo->commit();

                session_destroy();
                header('Location: index.php');
                exit;
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Could not delete the account.';
                $tab = 'delete';
            }
        }
    }
}

$charImg = ($currentGender === 'female')
    ? 'img/characters/female.png'
    : 'img/characters/male.png';

$profileSubtitle = trim($currentTitle . ($currentArtist ? ' · ' . $currentArtist : ''));

// Für initiales Preview (wie signup.php): Shirt-Cover nur zeigen, wenn vorhanden
$shirtHiddenClass = !empty($currentCover) ? '' : 'is-hidden';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Playess – Profile</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />

    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main4.css">
</head>

<body>

<div class="app-screen bg-tutorial">
    <div class="container-narrow">

        <header class="dashboard-header">
            <h1 class="dashboard-title h1">Profile</h1>
        </header>

        <main class="dashboard-main">

            <!-- On Top: aktuelles Profil -->
            <section class="dashboard-panel">

                <div class="leaderboard-row self" style="margin: 0 auto;">
                    <div class="leaderboard-left">
                        <div class="leaderboard-avatar">
                            <div class="lb-character-wrapper">
                                <img
                                    src="<?= htmlspecialchars($charImg, ENT_QUOTES, 'UTF-8') ?>"
                                    alt=""
                                    class="lb-character-base"
                                >
                                <?php if (!empty($currentCover)): ?>
                                    <div class="lb-character-shirt-cover">
                                        <img
                                            src="<?= htmlspecialchars($currentCover, ENT_QUOTES, 'UTF-8') ?>"
                                            alt=""
                                            onerror="this.style.display='none';"
                                        >
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="leaderboard-userinfo">
                            <div class="leaderboard-username">
                                <?= htmlspecialchars($currentName, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="leaderboard-meta">
                                <?= htmlspecialchars($currentEmail, ENT_QUOTES, 'UTF-8') ?> <br>
                                <?php if ($profileSubtitle !== ''): ?>
                                    · <?= htmlspecialchars($profileSubtitle, ENT_QUOTES, 'UTF-8') ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="leaderboard-score">
                        <img src="img/nav-bar/playmode-profil-inactive.svg" alt="profile icon" class="icon">
                    </div>
                </div>

                <?php if ($success !== ''): ?>
                    <p class="text-muted mt-3" style="color:#bff0c5;">
                        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

                <?php if ($error !== ''): ?>
                    <p class="text-muted mt-3" style="color:#ffb4b4;">
                        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>

            </section>

            <div class="mt-4"></div>

            <!-- Settings -->
            <section class="dashboard-panel">

                <p class="text-muted mt-1">Settings</p>

                <div class="mode-tabs mt-3">
                    <a href="profile.php?tab=custom" style="text-decoration:none; flex:1;">
                        <button
                            type="button"
                            class="mode-tab <?= $tab === 'custom' ? 'mode-tab--active' : '' ?>"
                            style="width:100%;"
                        >
                            Customisation
                        </button>
                    </a>

                    <a href="profile.php?tab=delete" style="text-decoration:none; flex:1;">
                        <button
                            type="button"
                            class="mode-tab <?= $tab === 'delete' ? 'mode-tab--active' : '' ?>"
                            style="width:100%;"
                        >
                            Account delete
                        </button>
                    </a>
                </div>

                <!-- Customisation: aufgebaut wie signup.php -->
                <div class="mt-3" style="<?= $tab === 'custom' ? '' : 'display:none;' ?>">

                    <!-- Character Preview mit Shirt-Cover -->
                    <div class="character-preview-wrapper mt-3">
                        <div
                            id="characterWrapper"
                            class="character-wrapper <?= $currentGender === 'female' ? 'female' : 'male' ?>"
                        >
                            <img
                                id="characterBase"
                                src="<?= $currentGender === 'female'
                                    ? 'img/characters/female.png'
                                    : 'img/characters/male.png' ?>"
                                alt="Character preview"
                                class="character-base"
                            >

                            <div class="character-shirt-cover <?= $shirtHiddenClass ?>" id="shirtCoverWrapper">
                                <img
                                    id="shirtCoverImage"
                                    src="<?= htmlspecialchars($currentCover, ENT_QUOTES, 'UTF-8') ?>"
                                    alt="Favourite track cover"
                                >
                            </div>
                        </div>
                    </div>

                    <form action="profile.php?tab=custom" method="post" class="login-form" id="profileCustomForm">
                        <input type="hidden" name="action" value="save_custom">

                        <!-- Character Auswahl -->
                        <div class="signup-block">
                            <p class="text-center text-muted h4">Choose your character:</p>

                            <div class="character-options">
                                <label class="character-option">
                                    <input
                                        type="radio"
                                        name="character_choice"
                                        value="male"
                                        <?= $currentGender === 'male' ? 'checked' : '' ?>
                                        data-img="img/characters/male.png"
                                        data-class="male"
                                    >
                                    <img src="img/characters/male.png" alt="Male character">
                                    <span>Male</span>
                                </label>

                                <label class="character-option">
                                    <input
                                        type="radio"
                                        name="character_choice"
                                        value="female"
                                        <?= $currentGender === 'female' ? 'checked' : '' ?>
                                        data-img="img/characters/female.png"
                                        data-class="female"
                                    >
                                    <img src="img/characters/female.png" alt="Female character">
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

                        <!-- Hidden Inputs -->
                        <input type="hidden" name="favorite_track_id" id="favoriteTrackId" value="<?= htmlspecialchars($currentTrackId, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="favorite_track_title" id="favoriteTrackTitle" value="<?= htmlspecialchars($currentTitle, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="favorite_track_artist" id="favoriteTrackArtist" value="<?= htmlspecialchars($currentArtist, ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="favorite_track_artwork" id="favoriteTrackArtwork" value="<?= htmlspecialchars($currentArtwork ?: $currentCover, ENT_QUOTES, 'UTF-8') ?>">

                        <div class="login-btn-wrapper login-btn-wrapper--center">
                            <button type="submit" class="primary-btn">
                                Save
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Delete -->
                <div class="mt-3" style="<?= $tab === 'delete' ? '' : 'display:none;' ?>">

                    <form action="profile.php?tab=delete" method="post">
                        <input type="hidden" name="action" value="delete_account">

                        <p class="text-muted mt-2">Delete account</p>
                        <p class="text-muted mt-1" style="font-size:12px; opacity:0.8;">
                            This will permanently delete your account and leaderboard stats.
                        </p>

                        <p class="text-muted mt-2">Password</p>
                        <input
                            type="password"
                            name="delete_password"
                            placeholder="Enter your password"
                            required
                        >

                        <div class="mt-3" style="font-size:12px; display:flex; align-items:flex-start; gap:8px;">
                            <input
                                type="checkbox"
                                id="delete_confirm"
                                name="delete_confirm"
                                style="width:16px; height:16px; margin-top:2px;"
                            >
                            <label for="delete_confirm" class="text-muted" style="margin:0;">
                                I understand, delete my account.
                            </label>
                        </div>

                        <div class="text-center mt-4">
                            <button type="submit" class="primary-btn">Delete</button>
                        </div>
                    </form>

                </div>

            </section>

        </main>

    </div>
</div>

<nav class="bottom-nav">
    <a href="leaderboard.php?mode=song" class="bottom-nav-item" aria-label="leaderboard">
        <img src="img/nav-bar/leaderboard.svg" alt="leaderboard icon" class="icon">
    </a>
    <a href="dashboard.php" class="bottom-nav-item" aria-label="play">
        <img src="img/nav-bar/play.svg" alt="play icon" class="icon">
    </a>
    <a href="profile.php" class="bottom-nav-item bottom-nav-item--active" aria-label="profile">
        <img src="img/nav-bar/profile.svg" alt="profile icon" class="icon">
    </a>
</nav>

<script>
    // Falls Custom-Tab nicht aktiv ist, JS nicht initialisieren
    if (document.getElementById('profileCustomForm')) {

        // Character-Preview beim Wechseln aktualisieren
        const characterBase    = document.getElementById('characterBase');
        const characterWrapper = document.getElementById('characterWrapper');

        document.querySelectorAll('input[name="character_choice"]').forEach(radio => {
            radio.addEventListener('change', () => {
                const imgSrc = radio.getAttribute('data-img');
                const cls    = radio.getAttribute('data-class');

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
            const q = (searchInput.value || '').trim();
            if (!q) return;

            resultsBox.innerHTML = '<div class="text-muted">Searching…</div>';

            fetch('api/t-shirt_picture_search.php?q=' + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => renderResults((data && data.tracks) ? data.tracks : []))
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
    }
</script>

</body>
</html>
