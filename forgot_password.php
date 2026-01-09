<?php
session_start();
require __DIR__ . '/config.php';

$info = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';

    if ($email === '') {
        $error = 'Please enter your email address.';
    } else {
        // Prüfen, ob es den User gibt
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            // Aus Security-Gründen könnte man hier eine generische Meldung anzeigen
            $error = 'If this email exists, we have created a reset link.';
        } else {
            // Token generieren
            $token = bin2hex(random_bytes(32));
            $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            $update = $pdo->prepare(
                'UPDATE users SET reset_token = :token, reset_expires_at = :expires_at WHERE id = :id'
            );
            $update->execute([
                ':token' => $token,
                ':expires_at' => $expiresAt,
                ':id' => $user['id'],
            ]);

            // In Produktion würdest du diesen Link per E-Mail schicken.
            // Für jetzt zeigen wir ihn einfach an:
            $resetLink = 'reset_password.php?token=' . urlencode($token);

            $info = 'Reset link (nur für Entwicklung): <a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '">'
                . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '</a>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Playess – Forgot Password</title>
    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main.css">
</head>
<body class="start-body bg-startscreen">

    <div class="app-screen container-narrow">

        <header class="start-top">
            <div class="logo-circle"></div>
            <h1 class="start-title h1">
                Reset your<br>
                Playess password
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
                    <?= $info ?>
                </div>
            <?php endif; ?>

            <form method="post" class="login-form">
                <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    autocomplete="email"
                    required
                >

                <div class="login-btn-wrapper" style="margin-top:18px; text-align:center;">
                    <button type="submit" class="primary-btn">Send reset link</button>
                </div>
            </form>

            <div class="start-options mt-3">
                <div class="text-center" style="font-size:12px;">
                    <a href="index.php" style="color: var(--color-accent);">Back to login</a>
                </div>
            </div>
        </main>

        <footer class="footer">
            © Playess 2025
        </footer>

    </div>

</body>
</html>
