<?php
session_start();
require __DIR__ . '/config.php';

$token = trim($_GET['token'] ?? '');

$error = '';
$info = '';
$validToken = false;
$userId = null;

if ($token === '') {
    $error = 'Invalid reset link.';
} else {
    // Token prüfen
    $stmt = $pdo->prepare('SELECT id, reset_expires_at FROM users WHERE reset_token = :token LIMIT 1');
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Invalid or expired reset link.';
    } else {
        // Ablauf prüfen
        $now = new DateTime();
        $expiresAt = null;

        if (!empty($user['reset_expires_at'])) {
            $expiresAt = new DateTime($user['reset_expires_at']);
        }

        if (!$expiresAt || $expiresAt < $now) {
            // Optional: abgelaufene Tokens direkt löschen
            $clean = $pdo->prepare('UPDATE users SET reset_token = NULL, reset_expires_at = NULL WHERE id = :id');
            $clean->execute([':id' => (int)$user['id']]);

            $error = 'This reset link has expired. Please request a new one.';
        } else {
            $validToken = true;
            $userId = (int)$user['id'];
        }
    }
}

// Formular
if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirm = (string)($_POST['password_confirm'] ?? '');

    if ($password === '' || $passwordConfirm === '') {
        $error = 'Please fill in both password fields.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password should be at least 8 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Härter: Update nur, wenn Token noch exakt passt
        $update = $pdo->prepare(
            'UPDATE users
             SET password_hash = :hash,
                 reset_token = NULL,
                 reset_expires_at = NULL
             WHERE id = :id AND reset_token = :token'
        );

        $update->execute([
            ':hash'  => $hash,
            ':id'    => $userId,
            ':token' => $token,
        ]);

        if ($update->rowCount() === 1) {
            $info = 'Password has been updated. You can now log in.';
            $validToken = false;
        } else {
            $error = 'This reset link is no longer valid. Please request a new one.';
            $validToken = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Playess – Reset Password</title>

    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <!-- WICHTIG: gleiches Styling wie index.php -->
    <link rel="stylesheet" href="css/main4.css">
</head>
<body class="start-body bg-startscreen">

    <div class="app-screen container-narrow">

        <header class="start-top">
            <div class="logo-circle">
                <img src="img/startscreen-logo.svg" alt="">
            </div>

            <h1 class="start-title h1">
                Choose a<br>
                new password
            </h1>
        </header>

        <main>

            <?php if ($error): ?>
                <div class="text-center" style="color:#ff9b9b; font-size:12px; margin-bottom:8px;">
                    <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                </div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="text-center" style="color:#EFB082; font-size:12px; margin-bottom:8px;">
                    <?= htmlspecialchars($info, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="text-cta-small mt-3 h3" style="text-align:center;">
                    <a href="index.php">Back to login</a>
                </div>
            <?php endif; ?>

            <?php if ($validToken): ?>
                <form method="post" class="login-form">

                    <input
                        type="password"
                        name="password"
                        placeholder="New password"
                        autocomplete="new-password"
                        required
                    >

                    <input
                        type="password"
                        name="password_confirm"
                        placeholder="Repeat new password"
                        autocomplete="new-password"
                        required
                    >

                    <div class="login-btn-wrapper" style="margin-top:18px; text-align:center;">
                        <button type="submit" class="primary-btn">Save new password</button>
                    </div>

                    <div class="text-cta-small mt-3 h3" style="text-align:center;">
                        <a href="index.php">Back to login</a>
                    </div>

                </form>
            <?php endif; ?>

        </main>

        <footer class="text-center text-muted mt-3 h4-btn">
            © Playess 2025
        </footer>

    </div>

</body>
</html>
