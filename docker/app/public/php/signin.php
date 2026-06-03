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
    <form class="container signin-container" action="create-account.php" method="POST">
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
        <button type="button" class="signup-btn">Sign up</button>
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