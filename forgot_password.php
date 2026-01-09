<?php
session_start();
require __DIR__ . '/config.php';

$info = '';
$error = '';

// DEV MODE: true zeigt den Link zusätzlich an (praktisch auf localhost)
$devMode = false;

// WICHTIG: Domain anpassen (für absolute Links in E-Mails)
$baseUrl = 'https://playess.tld'; // TODO anpassen

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Immer gleiche Antwort (kein Leak ob Mail existiert)
        $info = 'If an account exists for this email, we have sent a reset link.';

        // User holen (optional: AND is_active = 1)
        $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :email LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if ($user) {
            // Token + Ablauf
            $token = bin2hex(random_bytes(32));
            $expiresAt = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

            // Speichern
            $update = $pdo->prepare(
                'UPDATE users
                 SET reset_token = :token,
                     reset_expires_at = :expires_at
                 WHERE id = :id'
            );
            $update->execute([
                ':token' => $token,
                ':expires_at' => $expiresAt,
                ':id' => (int)$user['id'],
            ]);

            // Absoluter Link
            $resetLink = rtrim($baseUrl, '/') . '/reset_password.php?token=' . urlencode($token);

            // Mail (simpel via mail())
            $to = $user['email'];
            $subject = 'Playess – Password reset';

            $message =
                "Hi\n\n" .
                "Someone requested a password reset for your Playess account.\n\n" .
                "Reset your password here:\n" . $resetLink . "\n\n" .
                "This link expires in 1 hour.\n" .
                "If you did not request this, you can ignore this email.\n\n" .
                "Playess";

            $headers = [];
            $headers[] = 'From: Playess <no-reply@playess.tld>'; // TODO anpassen
            $headers[] = 'Reply-To: no-reply@playess.tld';
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';

            // Fehler nicht nach aussen zeigen
            @mail($to, $subject, $message, implode("\r\n", $headers));

            // DEV: zusätzlich anzeigen
            if ($devMode) {
                $info .= '<br><br>DEV reset link: <a href="' . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '">'
                    . htmlspecialchars($resetLink, ENT_QUOTES, 'UTF-8') . '</a>';
            }
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

    <footer class="footer">© Playess 2025</footer>

</div>
</body>
</html>
