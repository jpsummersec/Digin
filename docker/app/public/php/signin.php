<?php

require_once __DIR__ . '/include-cannot-access-when-loggedin.php';
require_once __DIR__ . '/include-dbhandler.php';

$errors = [];
// These values control the success overlay and the asynchronous form response.
$showRedirect = false;
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']);

$email = '';
$password = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    if (isset($_POST['email']))
    {
        $email = trim($_POST['email']);
    }
    else
    {
        $email = '';
    }

    if (isset($_POST['password']))
    {
        $password = $_POST['password'];
    }
    else
    {
        $password = '';
    }

    if ($email === '')
    {
        $errors[] = 'Email is required.';
    }

    if ($password === '')
    {
        $errors[] = 'Password is required.';
    }

    if (empty($errors))
    {

        $statement = $dbHandler->prepare("
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


        $statement->execute([$email]);

        $user = $statement->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password_hash']))
        {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['first_name'] = $user['first_name'];
            $_SESSION['last_name'] = $user['last_name'];
            $_SESSION['email'] = $user['email_address'];
            $_SESSION['level'] = $user['level'];
            $_SESSION['xp'] = $user['xp'];

            $showRedirect = true;
        }
        else
        {
            $errors[] = 'Invalid email or password.';
        }
    }
}

if ($isAjax)
{
    // Return validation results to the shared JavaScript form handler.
    header('Content-Type: application/json');
    $response = new stdClass();
    $response->success = $showRedirect;
    $response->errors = $errors;
    echo json_encode($response);
    exit;
}

if ($showRedirect)
{
    // Support successful form submissions when JavaScript is unavailable.
    header('Location: search-page.php');
    exit;
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
    <link rel="stylesheet" href="../css/redirect.css">
    <link rel="icon" type="image/svg+xml" href="../images/favicon/favicon.svg" />
</head>

<body>
    <form class="container signin-container" id="auth-form" action="signin.php" method="post">
        <h1 class="logo"><img src="../images/digin_logo.svg" alt="logoDigIn" class="logoDigin"></h1>
        <img src="../images/burger.svg" alt="burger-icon" class="burger-icon">
        <h1>Sign in to continue to your account</h1>

        <div class="error-message" id="auth-errors" <?php if (empty($errors)) { echo 'hidden'; } ?>>
            <?php echo htmlspecialchars(implode("\n", $errors)); ?>
        </div>

        <div class="input-box">
            <img src="../images/email-icon.svg" alt="email-icon" class="input-icon">
            <input type="email" name="email" placeholder="email@example.com">
        </div>
        <div class="input-box">
            <img src="../images/password.svg" alt="password-icon" class="password-icon">
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
        function togglePassword(inputId, button)
        {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('img');

            if (input.type === 'password')
            {
                input.type = 'text';
                icon.src = '../images/eyeopen.svg';
            }
            else
            {
                input.type = 'password';
                icon.src = '../images/eyeclosed.svg';
            }
        }
    </script>

    <?php
    // Add the shared success overlay and asynchronous form handler.
    include __DIR__ . '/include-redirect.php';
    ?>
</body>

</html>
