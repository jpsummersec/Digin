<?php

include __DIR__ . '/include-dbhandler.php';

$errors = [];

$firstName = "";
$lastName = "";
$email = "";
$password = "";
$confirmPassword = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (isset($_POST["first_name"])) {
        $firstName = trim($_POST["first_name"]);
    } else {
        $firstName = "";
    }

    if (isset($_POST["last_name"])) {
        $lastName = trim($_POST["last_name"]);
    } else {
        $lastName = "";
    }

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

    if (isset($_POST["confirm_password"])) {
        $confirmPassword = $_POST["confirm_password"];
    } else {
        $confirmPassword = "";
    }

    $errors = [];
    //first name//
    if ($firstName === "") {
        $errors[] = "First name is required.";
    }

    //last name//
    if ($lastName === "") {
        $errors[] = "Last name is required.";
    }

    //email//
    if ($email === "") {
        $errors[] = "Email is required.";
    } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = "Invalid email address.";
    }

    //password//
    if ($password === "") {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    //confirmpassword//
    if ($confirmPassword === "") {
        $errors[] = "Confirm password is required.";
    } elseif ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    if (empty($errors)) {
        $checkEmail = $dbHandler->prepare("SELECT user_id FROM user WHERE email_address = ?");
        $checkEmail->execute([$email]);

        if ($checkEmail->fetch()) {
            $errors[] = "Email address already exists.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $dbHandler->prepare("
                INSERT INTO user
                (first_name, last_name, email_address, password_hash, level, xp, spotify_token)
                VALUES (?, ?, ?, ?, 1, 0, NULL)
            ");

            $stmt->execute([
                $firstName,
                $lastName,
                $email,
                $hashedPassword
            ]);


            $newUserId = (int) $dbHandler->lastInsertId();

            session_regenerate_id(true);
            $_SESSION['user_id'] = $newUserId;

            header("Location: signin.php");
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up</title>
    <link rel="stylesheet" href="../css/create-account.css">
    <link rel="stylesheet" href="../css/root.css">
</head>

<body>
    <form class="container" action="create-account.php" method="POST">
        <h1 class="logo"><img src="../images/logoDigIn.svg" alt="logoDigIn" class="logoDigin"></h1>
        <img src="../images/cheficon-createacc.svg" alt="chefIcon" class="chefhat-icon">
        <h2>Your next bite starts here</h2>
        <p class="subtitle">
            Create an account and join us today
        </p>

        <?php
        if (!empty($errors)) {
            echo '<div class="error-message">';

            foreach ($errors as $error) {
                echo htmlspecialchars($error) . '<br>';
            }

            echo '</div>';
        }
        ?>

        <div class="row">
            <div class="input-box">
                <img src="../images/nameIcon.svg" alt="name-icon" class="input-icon">
                <input type="text" name="first_name" placeholder="first name" value="<?php echo htmlspecialchars($firstName); ?>">
            </div>
            <div class="input-box">
                <img src="../images/nameIcon.svg" alt="name-icon" class="input-icon">
                <input type="text" name="last_name" placeholder="last name" value="<?php echo htmlspecialchars($lastName); ?>">
            </div>
        </div>
        <div class="input-box">
            <img src="../images/emailIcon.svg" alt="email-icon" class="input-icon">
            <input type="email" name="email" placeholder="email@example.com" value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="input-box">
            <img src="../images/password.png" alt="password-icon" class="password-icon">
            <input type="password" name="password" placeholder="password" id="password">
            <button type="button" class="eye-btn" onclick="togglePassword('password', this)">
                <img src="../images/eyeopen.svg" alt="eyeopen-icon" class="eye-icon">
            </button>
        </div>
        <div class="input-box">
            <img src="../images/password.png" alt="password-icon" class="password-icon">
            <input type="password" name="confirm_password" placeholder="confirm password" id="confirmPassword">
            <button type="button" class="eye-btn" onclick="togglePassword('confirmPassword', this)">
                <img src="../images/eyeclosed.svg" alt="eyeclosed-icon" class="eye-icon">
            </button>
        </div>
        <button type="submit" class="signup-btn">Create account</button>
        <hr>
        <p class="signin-text">
            Already have an account?
        </p>
        <a href="signin.php" class="signup-btn">Sign in</a>
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