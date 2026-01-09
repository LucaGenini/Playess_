<?php
// api/game_new_round.php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php'; // stellt $pdo bereit

error_reporting(E_ALL);
ini_set('display_errors', 0);

$defaultElo = 1200;

/**
 * Stats-Row holen oder erstellen (leaderboard_stats)
 */
function get_or_create_stats(PDO $pdo, int $userId, string $gameMode, string $category = 'all'): array
{
    $stmt = $pdo->prepare("
        SELECT *
        FROM leaderboard_stats
        WHERE user_id = :user_id
          AND game_mode = :game_mode
          AND category = :category
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':user_id'   => $userId,
        ':game_mode' => $gameMode,
        ':category'  => $category,
    ]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        return $row;
    }

    $insert = $pdo->prepare("
        INSERT INTO leaderboard_stats
            (user_id, game_mode, category, elo, games_played, correct_answers, wrong_answers, best_streak)
        VALUES
            (:user_id, :game_mode, :category, :elo, 0, 0, 0, 0)
    ");
    $insert->execute([
        ':user_id'   => $userId,
        ':game_mode' => $gameMode,
        ':category'  => $category,
        ':elo'       => 1200,
    ]);

    $stmt->execute([
        ':user_id'   => $userId,
        ':game_mode' => $gameMode,
        ':category'  => $category,
    ]);
    return (array) $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * WICHTIG: Aktuelle ELO immer aus DB (neueste Row) holen
 * damit UI nicht auf Session/Default zurückspringt.
 */
function get_current_elo(PDO $pdo, int $userId, string $gameMode, string $category = 'all', int $defaultElo = 1200): int
{
    $stmt = $pdo->prepare("
        SELECT elo
        FROM leaderboard_stats
        WHERE user_id = :u
          AND game_mode = :m
          AND category = :c
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([
        ':u' => $userId,
        ':m' => $gameMode,
        ':c' => $category,
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return isset($row['elo']) ? (int)$row['elo'] : $defaultElo;
}

/**
 * ELO holen: Session, DB oder Default
 * (GELASSEN, aber wir geben nach aussen neu immer DB-ELO zurück)
 */
function get_elo_for_mode(PDO $pdo, int $defaultElo, string $mode, string $category = 'all'): int
{
    $sessionKey = 'elo_' . $mode;

    if (isset($_SESSION[$sessionKey])) {
        return (int) $_SESSION[$sessionKey];
    }

    if (!empty($_SESSION['user_id'])) {
        $stats = get_or_create_stats($pdo, (int) $_SESSION['user_id'], $mode, $category);
        $elo = (int) ($stats['elo'] ?? $defaultElo);
        $_SESSION[$sessionKey] = $elo;
        return $elo;
    }

    $_SESSION[$sessionKey] = $defaultElo;
    return $defaultElo;
}

/**
 * JSON Fehler helper
 */
function json_error(string $msg, int $code = 400): void
{
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error'   => $msg,
    ]);
    exit;
}

/**
 * iTunes JSON fetch helper
 */
function itunes_search(array $params, int $timeoutSeconds = 6): array
{
    $apiUrl = 'https://itunes.apple.com/search?' . http_build_query($params);

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeoutSeconds,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $httpCode !== 200) {
        return [
            'ok'    => false,
            'error' => 'iTunes API error: ' . ($curlErr ?: 'HTTP ' . $httpCode),
            'data'  => null,
        ];
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        return [
            'ok'    => false,
            'error' => 'Invalid JSON from iTunes',
            'data'  => null,
        ];
    }

    return [
        'ok'    => true,
        'error' => null,
        'data'  => $data,
    ];
}

/**
 * Cover URL vergroessern
 */
function upscale_itunes_artwork(?string $url): ?string
{
    if (!$url) return null;
    return str_replace('100x100bb', '600x600bb', $url);
}

/* ----------------------------------------------------
   MODE
---------------------------------------------------- */

$mode = isset($_GET['mode']) ? (string) $_GET['mode'] : 'open';
$allowed = ['open', 'cover'];

if (!in_array($mode, $allowed, true)) {
    json_error('Unsupported mode');
}

/**
 * Einheitliche ELO-Ermittlung fürs Frontend:
 * Immer DB-Stand (neueste Row), nicht Session.
 */
$userId = !empty($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$eloForResponse = $defaultElo;

if ($userId > 0) {
    // Ensure Row existiert, damit DB-Read nicht leer ist
    get_or_create_stats($pdo, $userId, $mode, 'all');
    $eloForResponse = get_current_elo($pdo, $userId, $mode, 'all', $defaultElo);

    // Optional: Session synchronisieren, falls du sie anderswo nutzt
    $_SESSION['elo_' . $mode] = $eloForResponse;
} else {
    $eloForResponse = $defaultElo;
    $_SESSION['elo_' . $mode] = $defaultElo;
}

/* ----------------------------------------------------
   OPEN MODE (Song Preview via iTunes)
---------------------------------------------------- */
if ($mode === 'open') {

    $songPoolByGenre = [
        'rock' => [
            ['artist' => 'Queen',              'title' => 'Bohemian Rhapsody'],
            ['artist' => 'Nirvana',            'title' => 'Smells Like Teen Spirit'],
            ['artist' => 'AC/DC',              'title' => 'Back In Black'],
            ['artist' => 'Guns N Roses',       'title' => "Sweet Child O' Mine"],
            ['artist' => 'The Rolling Stones', 'title' => "(I Can't Get No) Satisfaction"],
            ['artist' => 'Led Zeppelin',       'title' => 'Stairway to Heaven'],
            ['artist' => 'The Beatles',        'title' => 'Come Together'],
            ['artist' => 'Pink Floyd',         'title' => 'Another Brick in the Wall, Pt. 2'],
        ],
        'pop' => [
            ['artist' => 'Michael Jackson',    'title' => 'Billie Jean'],
            ['artist' => 'Madonna',            'title' => 'Like a Prayer'],
            ['artist' => 'Whitney Houston',    'title' => 'I Wanna Dance with Somebody'],
            ['artist' => 'Prince',             'title' => 'Kiss'],
            ['artist' => 'George Michael',     'title' => 'Careless Whisper'],
            ['artist' => 'Toto',               'title' => 'Africa'],
            ['artist' => 'ABBA',               'title' => 'Dancing Queen'],
            ['artist' => 'Cyndi Lauper',       'title' => 'Girls Just Want to Have Fun'],
        ],
        'hiphop' => [
            ['artist' => 'Dr. Dre',              'title' => 'Still D.R.E.'],
            ['artist' => 'The Notorious B.I.G.', 'title' => 'Juicy'],
            ['artist' => '2Pac',                 'title' => 'California Love'],
            ['artist' => 'Eminem',               'title' => 'Lose Yourself'],
            ['artist' => 'Wu-Tang Clan',         'title' => 'C.R.E.A.M.'],
            ['artist' => 'Kanye West',           'title' => 'Stronger'],
            ['artist' => 'Kendrick Lamar',       'title' => 'HUMBLE.'],
            ['artist' => 'OutKast',              'title' => 'Hey Ya!'],
        ],
        'rnb' => [
            ['artist' => 'Beyoncé',            'title' => 'Crazy in Love'],
            ['artist' => 'Rihanna',            'title' => 'Umbrella'],
            ['artist' => 'Usher',              'title' => 'Yeah!'],
            ['artist' => 'Alicia Keys',        'title' => 'Fallin'],
            ['artist' => 'The Weeknd',         'title' => 'Blinding Lights'],
            ['artist' => 'Mary J. Blige',      'title' => 'Family Affair'],
            ['artist' => 'Justin Timberlake',  'title' => 'Cry Me a River'],
            ['artist' => 'Bruno Mars',         'title' => 'Locked Out of Heaven'],
        ],
        'electronic' => [
            ['artist' => 'Daft Punk',           'title' => 'One More Time'],
            ['artist' => 'Darude',              'title' => 'Sandstorm'],
            ['artist' => 'Avicii',              'title' => 'Levels'],
            ['artist' => 'Swedish House Mafia', 'title' => "Don't You Worry Child"],
            ['artist' => 'Deadmau5',            'title' => 'Ghosts N Stuff'],
            ['artist' => 'Tiesto',              'title' => 'Adagio for Strings'],
            ['artist' => 'Calvin Harris',       'title' => 'Summer'],
            ['artist' => 'David Guetta',        'title' => 'Titanium'],
        ],
        'indie' => [
            ['artist' => 'Arctic Monkeys',         'title' => 'Do I Wanna Know?'],
            ['artist' => 'The Killers',            'title' => 'Mr. Brightside'],
            ['artist' => 'MGMT',                   'title' => 'Kids'],
            ['artist' => 'The Strokes',            'title' => 'Last Nite'],
            ['artist' => 'Florence + The Machine', 'title' => 'Dog Days Are Over'],
            ['artist' => 'Tame Impala',            'title' => 'The Less I Know the Better'],
            ['artist' => 'Franz Ferdinand',        'title' => 'Take Me Out'],
            ['artist' => 'Yeah Yeah Yeahs',        'title' => 'Maps'],
        ],
        'soul_funk' => [
            ['artist' => 'Stevie Wonder',          'title' => 'Superstition'],
            ['artist' => 'Marvin Gaye',            'title' => "What's Going On"],
            ['artist' => 'James Brown',            'title' => 'Get Up (I Feel Like Being a) Sex Machine'],
            ['artist' => 'Aretha Franklin',        'title' => 'Respect'],
            ['artist' => 'Earth, Wind & Fire',     'title' => 'September'],
            ['artist' => 'Chic',                   'title' => 'Le Freak'],
            ['artist' => 'Sly & The Family Stone', 'title' => 'Everyday People'],
            ['artist' => 'Bill Withers',           'title' => "Ain't No Sunshine"],
        ],
    ];

    $genres   = array_keys($songPoolByGenre);
    $genreKey = $genres[array_rand($genres)];
    $track    = $songPoolByGenre[$genreKey][array_rand($songPoolByGenre[$genreKey])];

    $term = $track['artist'] . ' ' . $track['title'];

    $r = itunes_search([
        'term'    => $term,
        'media'   => 'music',
        'entity'  => 'musicTrack',
        'limit'   => 1,
        'country' => 'US'
    ], 5);

    if (!$r['ok']) {
        json_error($r['error'], 502);
    }

    $data = $r['data'];
    if (empty($data['results'])) {
        json_error('No results from iTunes for: ' . $term, 404);
    }

    $item = $data['results'][0];

    $previewUrl   = $item['previewUrl']    ?? null;
    $coverUrl     = upscale_itunes_artwork($item['artworkUrl100'] ?? null);
    $trackViewUrl = $item['trackViewUrl']  ?? null;

    if (!$previewUrl) {
        json_error('Track has no preview: ' . $term, 404);
    }

    if (!isset($_SESSION['open_rounds'])) {
        $_SESSION['open_rounds'] = [];
    }

    $roundId = bin2hex(random_bytes(8));

    $_SESSION['open_rounds'][$roundId] = [
        'artist'     => $item['artistName'] ?? $track['artist'],
        'title'      => $item['trackName']  ?? $track['title'],
        'genre'      => $genreKey,
        'previewUrl' => $previewUrl,
        'coverUrl'   => $coverUrl,
        'itunesUrl'  => $trackViewUrl,
        'created_at' => time(),
    ];

    echo json_encode([
        'success'     => true,
        'mode'        => 'open',
        'round_id'    => $roundId,
        'preview_url' => $previewUrl,
        'elo'         => $eloForResponse,
    ]);
    exit;
}

/* ----------------------------------------------------
   COVER MODE (Nur iTunes API)
   Liefert: cover_url + itunesUrl (Apple Music Album-Link)
---------------------------------------------------- */
if ($mode === 'cover') {

    $albumPool = [
        ['artist' => 'Pink Floyd',      'title' => 'The Dark Side of the Moon'],
        ['artist' => 'Nirvana',         'title' => 'Nevermind'],
        ['artist' => 'Michael Jackson', 'title' => 'Thriller'],
        ['artist' => 'Daft Punk',       'title' => 'Discovery'],
        ['artist' => 'Kendrick Lamar',  'title' => 'DAMN.'],
        ['artist' => 'Kanye West',      'title' => 'My Beautiful Dark Twisted Fantasy'],
        ['artist' => 'The Beatles',     'title' => 'Abbey Road'],
        ['artist' => 'Fleetwood Mac',   'title' => 'Rumours'],
        ['artist' => 'Radiohead',       'title' => 'OK Computer'],
        ['artist' => 'Amy Winehouse',   'title' => 'Back to Black'],
        ['artist' => 'The Weeknd',      'title' => 'After Hours'],
        ['artist' => 'Travis Scott',    'title' => 'ASTROWORLD'],
        ['artist' => 'Beyoncé',         'title' => 'Lemonade'],
        ['artist' => 'Adele',           'title' => '21'],
        ['artist' => 'Arctic Monkeys',  'title' => 'AM'],
    ];

    $pick = $albumPool[array_rand($albumPool)];
    $term = $pick['artist'] . ' ' . $pick['title'];

    $r = itunes_search([
        'term'    => $term,
        'media'   => 'music',
        'entity'  => 'album',
        'limit'   => 5,
        'country' => 'US'
    ], 6);

    if (!$r['ok']) {
        json_error($r['error'], 502);
    }

    $data = $r['data'];
    if (empty($data['results'])) {
        json_error('No album results from iTunes for: ' . $term, 404);
    }

    $albumItem = null;
    foreach ($data['results'] as $it) {
        if (!is_array($it)) continue;

        $hasUrl   = !empty($it['collectionViewUrl']);
        $hasArt   = !empty($it['artworkUrl100']);
        $isAlbum  = (isset($it['collectionType']) && $it['collectionType'] === 'Album')
                 || (isset($it['wrapperType']) && $it['wrapperType'] === 'collection');

        if ($hasUrl && $hasArt && $isAlbum) {
            $albumItem = $it;
            break;
        }
    }

    if (!$albumItem) {
        $albumItem = $data['results'][0];
    }

    $coverUrl  = upscale_itunes_artwork($albumItem['artworkUrl100'] ?? null);
    $itunesUrl = $albumItem['collectionViewUrl'] ?? null;

    if (!$coverUrl || !$itunesUrl) {
        json_error('Album result missing cover or url for: ' . $term, 502);
    }

    if (!isset($_SESSION['cover_rounds'])) {
        $_SESSION['cover_rounds'] = [];
    }

    $roundId = bin2hex(random_bytes(8));

    $_SESSION['cover_rounds'][$roundId] = [
        'artist'     => (string) ($albumItem['artistName'] ?? $pick['artist']),
        'title'      => (string) ($albumItem['collectionName'] ?? $pick['title']),
        'cover_url'  => (string) $coverUrl,
        'itunesUrl'  => (string) $itunesUrl,
        'created_at' => time(),
    ];

    echo json_encode([
        'success'   => true,
        'mode'      => 'cover',
        'round_id'  => $roundId,
        'cover_url' => $coverUrl,
        'elo'       => $eloForResponse,
    ]);
    exit;
}
