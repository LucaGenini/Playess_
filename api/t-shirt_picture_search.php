<?php
// api/mb_search.php
// Song-Suche via MusicBrainz + Cover Art Archive

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($q === '') {
    echo json_encode(['tracks' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

// MusicBrainz Search API (Recordings = Songs)
$mbUrl = 'https://musicbrainz.org/ws/2/recording/?query='
    . urlencode($q)
    . '&fmt=json&limit=20';

// cURL Request
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $mbUrl,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERAGENT => 'PlayessApp/1.0 (https://example.com)' // notwendig für MB
]);
$response = curl_exec($ch);
curl_close($ch);

if (!$response) {
    echo json_encode(['tracks' => []], JSON_UNESCAPED_SLASHES);
    exit;
}

$data = json_decode($response, true);
$tracks = [];

if (!empty($data['recordings'])) {
    foreach ($data['recordings'] as $rec) {
        if (empty($rec['id']) || empty($rec['title'])) continue;

        $id     = $rec['id'];
        $title  = $rec['title'];
        $artist = $rec['artist-credit'][0]['name'] ?? 'Unknown Artist';

        // Cover via CoverArtArchive: wir nehmen das erste Release falls vorhanden
        $artwork = '';
        if (!empty($rec['releases'][0]['id'])) {
            $releaseId = $rec['releases'][0]['id'];
            $artwork = "https://coverartarchive.org/release/$releaseId/front-500";
        }

        $tracks[] = [
            'id'      => $id,
            'title'   => $title,
            'artist'  => $artist,
            'artwork' => $artwork // kann leer sein → UI zeigt dann fallback
        ];
    }
}

echo json_encode(['tracks' => $tracks], JSON_UNESCAPED_SLASHES);
