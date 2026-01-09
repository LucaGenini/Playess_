<?php
session_start();
require __DIR__ . '/config.php';

if (empty($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];

$allowedModes = ['song', 'cover'];
$mode = (isset($_GET['mode']) && in_array($_GET['mode'], $allowedModes, true)) ? $_GET['mode'] : 'song';

$gameMode = ($mode === 'song') ? 'open' : 'cover';
$category = 'all';
$defaultElo = 1200;

$debug = (isset($_GET['debug']) && $_GET['debug'] === '1');

$dbError = null;
$rows = [];

$debugInfo = [
    'db' => null,
    'userId' => $userId,
    'mode' => $mode,
    'gameMode' => $gameMode,
    'category' => $category,
    'rows' => 0,
    'counts' => [],
    'recent_stats' => [],
    'recent_stats_for_user' => [],
];

function safe_fetch_all(PDOStatement $stmt): array {
    $r = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return is_array($r) ? $r : [];
}

function safe_fetch(PDOStatement $stmt): ?array {
    $r = $stmt->fetch(PDO::FETCH_ASSOC);
    return is_array($r) ? $r : null;
}

function ensure_stats_row(PDO $pdo, int $userId, string $gameMode, string $category, int $defaultElo): void
{
    $check = $pdo->prepare("
        SELECT id
        FROM leaderboard_stats
        WHERE user_id = :u AND game_mode = :m AND category = :c
        ORDER BY id DESC
        LIMIT 1
    ");
    $check->execute([':u' => $userId, ':m' => $gameMode, ':c' => $category]);

    $exists = $check->fetch(PDO::FETCH_ASSOC);
    if ($exists) return;

    $ins = $pdo->prepare("
        INSERT INTO leaderboard_stats
            (user_id, game_mode, category, elo, games_played, correct_answers, wrong_answers, best_streak)
        VALUES
            (:u, :m, :c, :e, 0, 0, 0, 0)
    ");
    $ins->execute([
        ':u' => $userId,
        ':m' => $gameMode,
        ':c' => $category,
        ':e' => $defaultElo
    ]);
}

try {
    ensure_stats_row($pdo, $userId, $gameMode, $category, $defaultElo);
} catch (Throwable $e) {
    $dbError = 'Ensure row failed: ' . $e->getMessage();
}

try {
    /**
     * WICHTIG:
     * Keine doppelten Named Params mehr, sonst HY093.
     * Darum :m1/:c1 und :m2/:c2.
     */
    $sql = "
        SELECT
            ls.user_id,
            ls.elo,
            ls.games_played,
            ls.correct_answers,
            ls.wrong_answers,
            ls.best_streak,
            ls.updated_at,

            u.name AS username,
            u.character_choice,
            u.favorite_track_artwork

        FROM leaderboard_stats ls
        INNER JOIN (
            SELECT user_id, MAX(id) AS max_id
            FROM leaderboard_stats
            WHERE game_mode = :m1 AND category = :c1
            GROUP BY user_id
        ) x ON x.user_id = ls.user_id AND x.max_id = ls.id
        LEFT JOIN users u ON u.id = ls.user_id
        WHERE ls.game_mode = :m2 AND ls.category = :c2
        ORDER BY ls.elo DESC, ls.games_played DESC
        LIMIT 50
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':m1' => $gameMode,
        ':c1' => $category,
        ':m2' => $gameMode,
        ':c2' => $category
    ]);

    $rows = safe_fetch_all($stmt);

} catch (Throwable $e) {
    $dbError = 'Leaderboard query failed: ' . $e->getMessage();
    $rows = [];
}

if ($debug) {
    $debugInfo['rows'] = (int)count($rows);

    try {
        $stmt = $pdo->query("SELECT DATABASE() AS db");
        $r = safe_fetch($stmt);
        $debugInfo['db'] = $r['db'] ?? null;
    } catch (Throwable $e) {
        $debugInfo['db'] = 'unknown';
    }

    try {
        $stmt = $pdo->query("
            SELECT game_mode, category, COUNT(*) AS cnt
            FROM leaderboard_stats
            GROUP BY game_mode, category
            ORDER BY cnt DESC
        ");
        $debugInfo['counts'] = safe_fetch_all($stmt);
    } catch (Throwable $e) {
        $debugInfo['counts'] = [['error' => $e->getMessage()]];
    }

    try {
        $stmt = $pdo->query("
            SELECT id, user_id, game_mode, category, elo, games_played, correct_answers, wrong_answers, best_streak, updated_at
            FROM leaderboard_stats
            ORDER BY id DESC
            LIMIT 15
        ");
        $debugInfo['recent_stats'] = safe_fetch_all($stmt);
    } catch (Throwable $e) {
        $debugInfo['recent_stats'] = [['error' => $e->getMessage()]];
    }

    try {
        $stmt = $pdo->prepare("
            SELECT id, user_id, game_mode, category, elo, games_played, correct_answers, wrong_answers, best_streak, updated_at
            FROM leaderboard_stats
            WHERE user_id = :u
            ORDER BY id DESC
            LIMIT 15
        ");
        $stmt->execute([':u' => $userId]);
        $debugInfo['recent_stats_for_user'] = safe_fetch_all($stmt);
    } catch (Throwable $e) {
        $debugInfo['recent_stats_for_user'] = [['error' => $e->getMessage()]];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Playess – Leaderboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main4.css">
</head>
<body>

<div class="app-screen bg-tutorial">
    <div class="container-narrow">

        <header class="dashboard-header">
            <h1 class="dashboard-title h1">Leaderboard</h1>
        </header>

        <main class="dashboard-main">
            <section class="dashboard-panel">

                <div class="mode-tabs">
                    <a href="leaderboard.php?mode=song" style="text-decoration:none; flex:1;">
                        <button type="button" class="mode-tab <?= $mode === 'song' ? 'mode-tab--active' : '' ?>" style="width:100%;">
                            Song Guess
                        </button>
                    </a>
                    <a href="leaderboard.php?mode=cover" style="text-decoration:none; flex:1;">
                        <button type="button" class="mode-tab <?= $mode === 'cover' ? 'mode-tab--active' : '' ?>" style="width:100%;">
                            Album Cover Guess
                        </button>
                    </a>
                </div>

                <?php if ($debug): ?>
                    <div class="leaderboard-empty" style="text-align:left;">
                        <b>Debug</b><br>
                        DB: <?= htmlspecialchars((string)($debugInfo['db'] ?? ''), ENT_QUOTES, 'UTF-8') ?><br>
                        userId: <?= (int)$userId ?><br>
                        mode: <?= htmlspecialchars((string)$mode, ENT_QUOTES, 'UTF-8') ?><br>
                        gameMode: <?= htmlspecialchars((string)$gameMode, ENT_QUOTES, 'UTF-8') ?><br>
                        category: <?= htmlspecialchars((string)$category, ENT_QUOTES, 'UTF-8') ?><br>
                        rows: <?= (int)count($rows) ?><br>
                        dbError: <?= htmlspecialchars((string)$dbError, ENT_QUOTES, 'UTF-8') ?><br><br>

                        <b>Counts leaderboard_stats</b>
                        <pre style="white-space:pre-wrap; font-size:11px; margin-top:8px;"><?= htmlspecialchars(json_encode($debugInfo['counts'], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>

                        <b>Recent leaderboard_stats (last 15)</b>
                        <pre style="white-space:pre-wrap; font-size:11px; margin-top:8px;"><?= htmlspecialchars(json_encode($debugInfo['recent_stats'], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>

                        <b>Recent leaderboard_stats for this user (last 15)</b>
                        <pre style="white-space:pre-wrap; font-size:11px; margin-top:8px;"><?= htmlspecialchars(json_encode($debugInfo['recent_stats_for_user'], JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                <?php endif; ?>

                <?php if (empty($rows)): ?>
                    <div class="leaderboard-empty">
                        No scores yet. Play a few rounds to enter the leaderboard.
                        <?php if ($dbError): ?>
                            <div style="margin-top:10px; font-size:11px; text-align:left;">
                                DB error: <?= htmlspecialchars((string)$dbError, ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="leaderboard-list">
                        <?php
                        $rank = 1;
                        foreach ($rows as $row):
                            $isSelf = ((int)$row['user_id'] === $userId);

                            $username = trim((string)($row['username'] ?? ''));
                            if ($username === '') $username = 'User #' . (int)$row['user_id'];

                            $choice = (string)($row['character_choice'] ?? 'male');
                            $charImg = ($choice === 'female')
                                ? 'img/characters/female.png'
                                : 'img/characters/male.png';

                            $coverArt = (string)($row['favorite_track_artwork'] ?? '');

                            $games  = (int)($row['games_played'] ?? 0);
                            $wins   = (int)($row['correct_answers'] ?? 0);
                            $losses = (int)($row['wrong_answers'] ?? 0);
                            $elo    = (int)($row['elo'] ?? 1200);
                        ?>
                        <article class="leaderboard-row <?= $isSelf ? 'self' : '' ?>">
                            <div class="leaderboard-left">
                                <div class="leaderboard-rank"><?= $rank ?></div>

                                <div class="leaderboard-avatar">
                                    <div class="lb-character-wrapper">
                                        <img src="<?= htmlspecialchars($charImg, ENT_QUOTES, 'UTF-8') ?>" class="lb-character-base" alt="">
                                        <?php if ($coverArt !== ''): ?>
                                            <div class="lb-character-shirt-cover">
                                                <img src="<?= htmlspecialchars($coverArt, ENT_QUOTES, 'UTF-8') ?>" alt="">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="leaderboard-userinfo">
                                    <div class="leaderboard-username">
                                        <?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                    <div class="leaderboard-meta">
                                        <?= $games ?> games <br> W: <?= $wins ?> / L: <?= $losses ?>
                                    </div>
                                </div>
                            </div>

                            <div class="leaderboard-score">
                                <?= htmlspecialchars((string)$elo, ENT_QUOTES, 'UTF-8') ?> <span>ELO</span>
                            </div>
                        </article>
                        <?php
                            $rank++;
                        endforeach;
                        ?>
                    </div>
                <?php endif; ?>

            </section>
        </main>

    </div>
</div>

<nav class="bottom-nav">
    <a href="leaderboard.php?mode=<?= htmlspecialchars($mode, ENT_QUOTES, 'UTF-8') ?>" class="bottom-nav-item bottom-nav-item--active" aria-label="leaderboard">
        <img src="img/nav-bar/leaderboard.svg" alt="leaderboard icon" class="icon">
    </a>
    <a href="dashboard.php" class="bottom-nav-item" aria-label="play">
        <img src="img/nav-bar/play.svg" alt="play icon" class="icon">
    </a>
    <a href="profile.php" class="bottom-nav-item" aria-label="profile">
        <img src="img/nav-bar/profile.svg" alt="profile icon" class="icon">
    </a>
</nav>

</body>
</html>
