<?php
// signup-finish.php – finaler Insert in die DB (Duplicate Email -> Meldung auf register.php)
session_start();
require __DIR__ . '/config.php'; // hier ist deine $pdo DB-Verbindung

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

// 1. Basisdaten aus Session holen (kommen aus register.php / Step 1)
$name  = $_SESSION['signup_name']  ?? null;
$email = $_SESSION['signup_email'] ?? null;
$pass  = $_SESSION['signup_pass']  ?? null;

// Security-Fragen + Antworten aus Session
$sec_q1 = $_SESSION['signup_sec_question_1'] ?? null;
$sec_a1 = $_SESSION['signup_sec_answer_1']   ?? null;
$sec_q2 = $_SESSION['signup_sec_question_2'] ?? null;
$sec_a2 = $_SESSION['signup_sec_answer_2']   ?? null;

// Wenn irgendwas Wichtiges fehlt -> zurück zum Start
if (!$name || !$email || !$pass) {
    header('Location: register.php');
    exit;
}

// 2. Step2-Daten (Character + Lieblingssong) aus POST holen
$characterChoice = $_POST['character_choice']        ?? null;
$fav_id          = $_POST['favorite_track_id']      ?? null;
$fav_title       = $_POST['favorite_track_title']   ?? null;
$fav_artist      = $_POST['favorite_track_artist']  ?? null;
$fav_artwork     = $_POST['favorite_track_artwork'] ?? null;

// Minimal-Check: Song muss gewählt sein
if (!$fav_id || !$fav_title || !$fav_artist || !$fav_artwork) {
    $_SESSION['signup_step2_error'] = 'Please select a favourite song from the search results.';
    header('Location: signup.php');
    exit;
}

// Optional: Email-Format nochmal prüfen
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['signup_register_error'] = 'Please enter a valid email address.';
    header('Location: register.php');
    exit;
}

// 3. Passwörter / Antworten hashen
$pass_hash = password_hash($pass, PASSWORD_DEFAULT);

$a1_hash = $sec_a1 ? password_hash($sec_a1, PASSWORD_DEFAULT) : null;
$a2_hash = $sec_a2 ? password_hash($sec_a2, PASSWORD_DEFAULT) : null;

// 4. In DB speichern – Spaltennamen wie in deiner Tabelle
$sql = "INSERT INTO users 
        (email, password_hash, name,
         favorite_track_id, favorite_track_title, favorite_track_artist, favorite_track_artwork, favorite_song_cover,
         sec_question_1, security_q1_answer_hash,
         sec_question_2, security_q2_answer_hash,
         character_choice)
        VALUES
        (:email, :pass, :name,
         :fid, :ftitle, :fartist, :fartwork, :fscover,
         :q1, :a1,
         :q2, :a2,
         :char)";

$stmt = $pdo->prepare($sql);

try {
    $stmt->execute([
        ':email'    => $email,
        ':pass'     => $pass_hash,
        ':name'     => $name,
        ':fid'      => $fav_id,
        ':ftitle'   => $fav_title,
        ':fartist'  => $fav_artist,
        ':fartwork' => $fav_artwork,
        ':fscover'  => $fav_artwork,   // optional: Lieblingssong-Cover doppelt speichern
        ':q1'       => $sec_q1,
        ':a1'       => $a1_hash,
        ':q2'       => $sec_q2,
        ':a2'       => $a2_hash,
        ':char'     => $characterChoice,
    ]);
} catch (PDOException $e) {
    // Duplicate Email (Unique constraint) abfangen
    // SQLSTATE 23000 = Integrity constraint violation, MySQL 1062 = Duplicate entry
    if ($e->getCode() === '23000') {
        $_SESSION['signup_register_error'] = 'This email address is already registered. Please log in or use another email.';
        header('Location: register.php');
        exit;
    }

    // Alles andere ist ein echter Fehler -> weiterwerfen
    throw $e;
}

// 5. neu erzeugte User-ID holen und User direkt einloggen
$userId = (int)$pdo->lastInsertId();
$_SESSION['user_id']   = $userId;
$_SESSION['user_name'] = $name;

// 6. temporäre Signup-Daten aufräumen
unset(
    $_SESSION['signup_name'],
    $_SESSION['signup_email'],
    $_SESSION['signup_pass'],
    $_SESSION['signup_sec_question_1'],
    $_SESSION['signup_sec_answer_1'],
    $_SESSION['signup_sec_question_2'],
    $_SESSION['signup_sec_answer_2'],
    $_SESSION['signup_step2_error'],
    $_SESSION['signup_register_error']
);

// 7. Weiterleiten – z. B. direkt ins Dashboard
header('Location: dashboard.php');
// oder: header('Location: tutorial.php');
exit;
