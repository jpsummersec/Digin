<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sign Up</title>
    <link rel="stylesheet" href="../css/create-account.css">
</head>

<body>
    <div class="container">
        <h1 class="logo"><img src="../images/logoDigIn.svg" alt="logoDigIn" class="logoDigin"></h1>
        <img src="../images/createaccounticon.svg" alt="chefhat" class="chefhat-ic">
        <h2>Your next bite starts here</h2>
        <p class="subtitle">
            Create an account and join us today
        </p>
        <div class="row">
            <div class="input-box">
                <img src="../images/nameIcon.svg" alt="name-icon" class="input-icon">
                <input type="text" placeholder="first name">
            </div>
            <div class="input-box">
                <img src="../images/nameIcon.svg" alt="name-icon" class="input-icon">
                <input type="text" placeholder="last name">
            </div>
        </div>
        <div class="input-box">
            <img src="../images/emailIcon.svg" alt="email-icon" class="input-icon">
            <input type="email" placeholder="email@example.com">
        </div>
        <div class="input-box">
            <img src="../images/password.png" alt="password-icon" class="password-icon">
            <input type="password" placeholder="password" id="password">
            <button type="button" class="eye-btn" onclick="togglePassword('password', this)">
                <img src="../images/eyeopen.svg" alt="eyeopen-icon" class="eye-icon">
            </button>
        </div>
        <div class="input-box">
            <img src="../images/password.png" alt="password-icon" class="password-icon">
            <input type="password" placeholder="confirm password" id="confirmPassword">
            <button type="button" class="eye-btn" onclick="togglePassword('confirmPassword', this)">
                <img src="../images/eyeclosed.svg" alt="eyeclosed-icon" class="eye-icon">
            </button>
        </div>
        <button class="signup-btn">Create account</button>
        <hr>
        <p class="signin-text">
            Already have an account?
        </p>
        <button class="signup-btn">Sign in</button>
    </div>
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