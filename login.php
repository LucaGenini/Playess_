<?php
// login.php verarbeitet das Login Formular

session_start();
require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? (string)$_POST['password'] : '';

if ($email === '' || $password === '') {
    $_SESSION['login_error'] = 'Please fill in all fields.';
    header('Location: index.php');
    exit;
}

// User laden
$stmt = $pdo->prepare('
    SELECT id, email, password_hash, is_active, email_verified_at
    FROM users
    WHERE email = :email
    LIMIT 1
');
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user || (int)$user['is_active'] !== 1) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: index.php');
    exit;
}



if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['login_error'] = 'Invalid email or password.';
    header('Location: index.php');
    exit;
}

// Session haerten
session_regenerate_id(true);

// last_login_at aktualisieren
$update = $pdo->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
$update->execute([':id' => (int)$user['id']]);

$_SESSION['user_id'] = (int)$user['id'];
$_SESSION['user_email'] = (string)$user['email'];

header('Location: dashboard.php');
exit;
