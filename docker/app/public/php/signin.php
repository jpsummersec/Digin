<?php
session_start();

include __DIR__ . '/include-dbhandler.php';

$errors = [];

$email = "";
$password = "";

if (isset($_POST["email"])) {
    $email = trim($_POST["email"]);
} else {
    $email = "";
}

if (isset($_POST["password"])) {
    $password = $_POST["password"];
} else {
    $password = "";
}

if ($email === "") {
    $errors[] = "Email is required.";
}

if ($password === "") {
    $errors[] = "Password is required.";
}

if (empty($errors)) {

    $stmt = $dbHandler->prepare("
            SELECT user_id,
                   first_name,
                   last_name,
                   email_address,
                   password_hash,
                   level,
                   xp
            FROM user
            WHERE email_address = ?
        ");


    $stmt->execute([$email]);

    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user["password_hash"])) {

        $_SESSION["user_id"] = $user["user_id"];
        $_SESSION["first_name"] = $user["first_name"];
        $_SESSION["last_name"] = $user["last_name"];
        $_SESSION["email"] = $user["email_address"];
        $_SESSION["level"] = $user["level"];
        $_SESSION["xp"] = $user["xp"];

        header("Location: search-page.php");
        exit;
    } else {
        $errors[] = "Invalid email or password.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign in</title>
    <link rel="stylesheet" href="../css/create-account.css">
    <link rel="stylesheet" href="../css/root.css">
</head>

<body>
    <form class="container signin-container" action="signin.php" method="POST">
        <h1 class="logo"><img src="../images/logoDigIn.svg" alt="logoDigIn" class="logoDigin"></h1>
        <img src="../images/burger.svg" alt="burger-icon" class="burger-icon">
        <h1>Sign in to continue to your account</h1>

        <div class="input-box">
            <img src="../images/emailIcon.svg" alt="email-icon" class="input-icon">
            <input type="email" name="email" placeholder="email@example.com">
        </div>
        <div class="input-box">
            <img src="../images/password.png" alt="password-icon" class="password-icon">
            <input type="password" name="password" placeholder="password" id="password">
            <button type="button" class="eye-btn" onclick="togglePassword('password', this)">
                <img src="../images/eyeopen.svg" alt="eyeopen-icon" class="eye-icon">
            </button>
        </div>
        <button type="submit" class="signup-btn">Sign in</button>
        <hr>
        <p class="signin-text">
            Don't have an account?
        </p>
        <a href="create-account.php" class="signup-btn">Sign up</a>
    </form>

    <script>
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector("img");

            if (input.type === "password") {
                input.type = "text";
                icon.src = "../images/eyeopen.svg";
            } else {
                input.type = "password";
                icon.src = "../images/eyeclosed.svg";
            }
        }
    </script>

</body>

</html>