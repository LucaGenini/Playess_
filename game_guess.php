<?php
// game_guess.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/config.php';

error_reporting(E_ALL);
ini_set('display_errors', 0);

$DEFAULT_ELO = 1200;
$CATEGORY = 'all';

function respond(array $payload, int $code = 200): void {
    http_response_code($code);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function normalize(string $s): string {
    $s = mb_strtolower($s, 'UTF-8');
    $s = preg_replace('/[\p{P}\p{S}]+/u', ' ', $s);
    $s = preg_replace('/\s+/', ' ', $s);
    return trim($s);
}

function getOrCreateStats(PDO $pdo, int $userId, string $gameMode, string $category, int $defaultElo): array {
    $stmt = $pdo->prepare("
        SELECT *
        FROM leaderboard_stats
        WHERE user_id = :u AND game_mode = :m AND category = :c
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':u' => $userId, ':m' => $gameMode, ':c' => $category]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) return $row;

    $ins = $pdo->prepare("
        INSERT INTO leaderboard_stats
        (user_id, game_mode, category, elo, games_played, correct_answers, wrong_answers, best_streak)
        VALUES (:u, :m, :c, :elo, 0, 0, 0, 0)
    ");
    $ins->execute([':u' => $userId, ':m' => $gameMode, ':c' => $category, ':elo' => $defaultElo]);

    $stmt->execute([':u' => $userId, ':m' => $gameMode, ':c' => $category]);
    return (array)$stmt->fetch(PDO::FETCH_ASSOC);
}

$mode = (string)($_POST['mode'] ?? '');
$roundId = (string)($_POST['round_id'] ?? '');
$guessArtist = trim((string)($_POST['artist'] ?? ''));
$guessTitle  = trim((string)($_POST['title'] ?? ''));

if (!in_array($mode, ['open', 'cover'], true)) {
    respond(['success' => false, 'error' => 'Invalid mode', 'debug' => ['mode' => $mode]], 400);
}
if ($roundId === '') {
    respond(['success' => false, 'error' => 'Missing round_id'], 400);
}

$userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;

$sessionKey = ($mode === 'open') ? 'open_rounds' : 'cover_rounds';
if (empty($_SESSION[$sessionKey]) || empty($_SESSION[$sessionKey][$roundId])) {
    respond([
        'success' => false,
        'error' => 'Round not found or expired',
        'debug' => [
            'mode' => $mode,
            'round_id' => $roundId,
            'sessionKey' => $sessionKey,
            'availableRoundIds' => array_keys($_SESSION[$sessionKey] ?? [])
        ]
    ], 404);
}

$round = $_SESSION[$sessionKey][$roundId];
unset($_SESSION[$sessionKey][$roundId]);

$correctArtist = normalize((string)($round['artist'] ?? ''));
$correctTitle  = normalize((string)($round['title'] ?? ''));

$gA = normalize($guessArtist);
$gT = normalize($guessTitle);

$artistCorrect = false;
$titleCorrect = false;

if ($gA !== '' && $correctArtist !== '') {
    $artistCorrect = ($gA === $correctArtist) || str_contains($gA, $correctArtist) || str_contains($correctArtist, $gA);
}
if ($gT !== '' && $correctTitle !== '') {
    $titleCorrect = ($gT === $correctTitle) || str_contains($gT, $correctTitle) || str_contains($correctTitle, $gT);
}

$deltaArtist = $artistCorrect ? 1 : -1;
$deltaTitle  = $titleCorrect ? 1 : -1;
$deltaTotal  = $deltaArtist + $deltaTitle;

if ($mode === 'open') {
    if ($deltaTotal === 2) $eloChange = 25;
    elseif ($deltaTotal === 0) $eloChange = 0;
    elseif ($deltaTotal === -2) $eloChange = -25;
    else $eloChange = -5;
} else {
    $eloChange = $deltaTotal * 10;
}

$db = [
    'didWrite' => false,
    'rowId' => null,
    'beforeElo' => null,
    'afterElo' => null,
    'pdoOk' => isset($pdo) ? true : false,
];

if ($userId > 0) {
    try {
        $stats = getOrCreateStats($pdo, $userId, $mode, $CATEGORY, $DEFAULT_ELO);
        $beforeElo = (int)($stats['elo'] ?? $DEFAULT_ELO);
        $afterElo  = max(0, $beforeElo + $eloChange);

        $isWin = ($deltaTotal === 2);
        $cDelta = $isWin ? 1 : 0;
        $wDelta = $isWin ? 0 : 1;

        $upd = $pdo->prepare("
            UPDATE leaderboard_stats
            SET elo = :elo,
                games_played = games_played + 1,
                correct_answers = correct_answers + :c,
                wrong_answers = wrong_answers + :w
            WHERE id = :id
            LIMIT 1
        ");
        $upd->execute([
            ':elo' => $afterElo,
            ':c' => $cDelta,
            ':w' => $wDelta,
            ':id' => (int)$stats['id']
        ]);

        $db['didWrite'] = ($upd->rowCount() === 1);
        $db['rowId'] = (int)$stats['id'];
        $db['beforeElo'] = $beforeElo;
        $db['afterElo'] = $afterElo;

    } catch (Throwable $e) {
        respond([
            'success' => false,
            'error' => 'DB error',
            'debug' => [
                'message' => $e->getMessage(),
                'userId' => $userId,
                'mode' => $mode
            ]
        ], 500);
    }
} else {
    $afterElo = max(0, $DEFAULT_ELO + $eloChange);
}

respond([
    'success' => true,
    'mode' => $mode,

    'artistCorrect' => $artistCorrect,
    'titleCorrect' => $titleCorrect,
    'deltaArtist' => $deltaArtist,
    'deltaTitle' => $deltaTitle,
    'deltaTotal' => $deltaTotal,

    'eloAfter' => ($userId > 0 ? $db['afterElo'] : $afterElo),
    'eloChange' => $eloChange,

    'artist' => (string)($round['artist'] ?? ''),
    'title'  => (string)($round['title'] ?? ''),
    'cover'  => (string)($round['coverUrl'] ?? ($round['cover_url'] ?? '')),

    'appleMusicUrl' => $round['itunesUrl'] ?? null,

    'debug' => [
        'userId' => $userId,
        'category' => $CATEGORY,
        'sessionKey' => $sessionKey,
        'db' => $db
    ]
]);
