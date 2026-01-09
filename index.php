<?php
// index.php – Playess Login Startscreen mit main.css
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Playess – Login</title>

    <link rel="stylesheet" href="css/resets.css">
    <link rel="stylesheet" href="css/fonts.css">
    <link rel="stylesheet" href="css/main4.css">
</head>
<body class="start-body bg-startscreen">

    <div class="app-screen container-narrow">

        <!-- Logo + Claim -->
        <header class="start-top">
            <div class="logo-circle"> 
                <img src="img/startscreen-logo.svg" alt="">
            </div>

            <h1 class="start-title h1">
                Show how<br>
                much of a music<br>
                nerd you really are.
            </h1>
        </header>

        <!-- Login-Bereich -->
        <main>

            <form action="login.php" method="post" class="login-form">

                <!-- Fehlermeldung aus Session -->
                <?php if (!empty($_SESSION['login_error'])): ?>
                    <div class="text-center" style="color:#ff9b9b; font-size:12px; margin-bottom:8px;">
                        <?= htmlspecialchars($_SESSION['login_error'], ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php unset($_SESSION['login_error']); ?>
                <?php endif; ?>

                <input
                    type="email"
                    name="email"
                    placeholder="Email Address"
                    autocomplete="email"
                    required
                >

                <input
                    type="password"
                    name="password"
                    placeholder="Password"
                    autocomplete="current-password"
                    required
                >

                <div class="text-cta-small mt-3 h3">
                    <a  href="forgot_password.php">Forgot password?</a>
                </div>

                <div class="login-btn-wrapper" style="margin-top:18px; text-align:center;">
                    <button type="submit" class="primary-btn">
                        Login
                    </button>
                </div>

            </form>

            <div class="start-options mt-3 h3">
                <div class="text-center">
                    <span class="text-muted">Not a member?</span>
                    <a class="text-cta-small" href="register.php">Register now</a>
                </div>
            </div>
        </main>

        <footer class="text-center text-muted mt-3 h4-btn">
            © Playess 2025
        </footer>

    </div>

</body>
</html>
