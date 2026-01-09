<?php
session_start();
require __DIR__ . '/config.php';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';

$error = '';
$info = '';
$validToken = false;
$userId = null;

if ($token === '') {
    $error = 'Invalid reset link.';
} else {
    // Token prüfen
    $stmt = $pdo->prepare(
        'SELECT id, reset_expires_at FROM users WHERE reset_token = :token LIMIT 1'
    );
    $stmt->execute([':token' => $token]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = 'Invalid or expired reset link.';
    } else {
        // Ablauf prüfen
        $now = new DateTime();
        $expiresAt = new DateTime($user['reset_expires_at']);

        if ($expiresAt < $now) {
            $error = 'This reset link has expired. Please request a new one.';
        } else {
            $validToken = true;
            $userId = $user['id'];
        }
    }
}

// Wenn Formular abgeschickt wird
if ($validToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $passwordConfirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    if ($password === '' || $passwordConfirm === '') {
        $error = 'Please fill in both password fields.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password should be at least 8 characters.';
    } else {
        // Neues Passwort setzen
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $update = $pdo->prepare(
            'UPDATE users
             SET password_hash = :hash,
                 reset_token = NULL,
                 reset_expires_at = NULL
             WHERE id = :id'
        );

        $update->execute([
            ':hash' => $hash,
            ':id'   => $userId,
        ]);

        $info = 'Password has been updated. You can now log in.';
        $validToken = false; // damit das Formular nicht erneut angezeigt wird
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Playess – Reset Password</title>
    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body class="start-body bg-startscreen">

    <div class="app-screen container-narrow">

        <header class="start-top">
            <div class="logo-circle"></div>
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
                <div class="text-center" style="font-size:12px;">
                    <a href="index.php" style="color: var(--color-accent);">Back to login</a>
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
                </form>
            <?php endif; ?>
        </main>

        <footer class="footer">
            © Playess 2025
        </footer>

    </div>

</body>
</html>
