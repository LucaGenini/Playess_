<?php
// register.php – Schritt 1: Name, Email, Passwort, Security-Fragen
session_start();

$error = '';

// 1) Fehlermeldung aus einem späteren Schritt (z.B. Duplicate Email aus signup-finish.php) übernehmen
if (!empty($_SESSION['signup_register_error'])) {
    $error = (string)$_SESSION['signup_register_error'];
    unset($_SESSION['signup_register_error']); // nur einmal anzeigen
}

// fixe Security-Fragen (werden später in der DB in sec_question_1 / sec_question_2 gespeichert)
$secQuestion1 = 'What is the name of your first pet?';
$secQuestion2 = 'In which city did you grow up?';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Werte aus Formular holen
    $name           = trim($_POST['name'] ?? '');
    $email          = trim($_POST['email'] ?? '');
    $password       = $_POST['password'] ?? '';
    $passwordRepeat = $_POST['password_repeat'] ?? '';
    $secAnswer1     = trim($_POST['sec_answer_1'] ?? '');
    $secAnswer2     = trim($_POST['sec_answer_2'] ?? '');
    $termsAccepted  = isset($_POST['terms']);

    // 2. Mini-Validierung
    if ($name === '' || $email === '' || $password === '' || $passwordRepeat === '' ||
        $secAnswer1 === '' || $secAnswer2 === '') {
        $error = 'Please fill out all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif ($password !== $passwordRepeat) {
        $error = 'Passwords do not match.';
    } elseif (!$termsAccepted) {
        $error = 'Please accept the terms and conditions.';
    } else {
        // 3. In Session speichern (noch nicht in DB)
        $_SESSION['signup_name']  = $name;
        $_SESSION['signup_email'] = $email;
        $_SESSION['signup_pass']  = $password;

        // Security-Fragen + Antworten für späteren Insert
        $_SESSION['signup_sec_question_1'] = $secQuestion1;
        $_SESSION['signup_sec_answer_1']   = $secAnswer1;

        $_SESSION['signup_sec_question_2'] = $secQuestion2;
        $_SESSION['signup_sec_answer_2']   = $secAnswer2;

        // 4. Weiter zu Schritt 2 (signup.php)
        header('Location: signup.php');
        exit;
    }
} else {
    // Prefill aus Session, falls User zurückkommt
    $name  = $_SESSION['signup_name']  ?? '';
    $email = $_SESSION['signup_email'] ?? '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1" />
    <title>Playess – Sign up</title>

    <link rel="stylesheet" href="/css/resets.css">
    <link rel="stylesheet" href="/css/fonts.css">
    <link rel="stylesheet" href="/css/main4.css">
</head>
<body style="background-image: var(--gradient-1);">

<div class="app-screen container-narrow">

    <header class="mt-5">
        <h1 class="h1">Sign up</h1>
        <p class="text-muted mt-1">Create an account to get started</p>
    </header>

    <main class="mt-4">
        <?php if (!empty($error)): ?>
            <p class="text-muted" style="color:#ffb4b4;">
                <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <!-- WICHTIG: action zeigt auf register.php selbst -->
        <form action="register.php" method="post">

            <!-- Name -->
            <p class="text-muted mt-2">Name</p>
            <input
                type="text"
                name="name"
                placeholder="Your name"
                value="<?php echo htmlspecialchars($name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                required
            >

            <!-- Email -->
            <p class="text-muted mt-2">Email Address</p>
            <input
                type="email"
                name="email"
                placeholder="name@email.com"
                value="<?php echo htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                required
            >

            <!-- Passwort -->
            <p class="text-muted mt-2">Password</p>
            <input
                type="password"
                name="password"
                placeholder="Create a password"
                required
            >

            <!-- Passwort Wiederholung -->
            <p class="text-muted mt-2">Repeat Password</p>
            <input
                type="password"
                name="password_repeat"
                placeholder="Repeat your password"
                required
            >

            <!-- Security Question 1 -->
            <p class="text-muted mt-2">
                Security Question 1<br>
                <span style="font-size:12px; opacity:0.8;">
                    <?php echo htmlspecialchars($secQuestion1, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </p>
            <input
                type="text"
                name="sec_answer_1"
                placeholder="Your answer"
                required
            >

            <!-- Security Question 2 -->
            <p class="text-muted mt-2">
                Security Question 2<br>
                <span style="font-size:12px; opacity:0.8;">
                    <?php echo htmlspecialchars($secQuestion2, ENT_QUOTES, 'UTF-8'); ?>
                </span>
            </p>
            <input
                type="text"
                name="sec_answer_2"
                placeholder="Your answer"
                required
            >

            <!-- Terms & Conditions (ersetzt: klickbar + grössere Hitbox + required) -->
            <div class="mt-3 terms-row">
                <input
                    type="checkbox"
                    id="terms"
                    name="terms"
                    value="1"
                    required
                >
                <label for="terms" class="text-muted terms-label">
                    I agree to the <a href="terms.php">terms and conditions</a>.
                </label>
            </div>

            <div class="text-center mt-4">
                <button type="submit" class="primary-btn">Next</button>
            </div>

        </form>
    </main>

    <footer class="footer"></footer>

</div>

</body>
</html>
