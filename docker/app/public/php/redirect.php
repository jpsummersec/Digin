<?php
require_once __DIR__ . '/include-cannot-access-when-loggedin.php';
include("include-dbhandler.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REDIRECT</title>
    <link rel="stylesheet" href="../css/root.css">
    <link rel="stylesheet" href="../css/redirect.css">
</head>

<body>
    <div id="redirect-container">
        <p id="thank-you">Thank you for</p>
        <img id="brand" src="../images/diggingin_logo.svg" alt="Digging In">
        <audio id="thank-you-audio" src="../audio/thank-you-thank-you-thank-you-meme.mp3" preload="auto"></audio>
        <p id="redirect-message">You will be redirected<br>in <span id="countdown">5</span> seconds.</p>
        <p id="stuck">Stuck on this page? <a href="search-page.php">Click here</a></p>
    </div>

    <script>
        let seconds = 5;
        const delayMs = 500;

        const countdown = document.getElementById('countdown');
        const thankYouAudio = document.getElementById('thank-you-audio');

        setTimeout(function() {
            thankYouAudio.play().catch(function(error) {
                console.log('Failed to play thank you audio:', error);
            });
        }, delayMs);

        const interval = setInterval(function() {
            seconds--;
            countdown.textContent = seconds;

            if (seconds <= 0) {
                clearInterval(interval);
                window.location.href = 'search-page.php';
            }
        }, 1000);
    </script>
</body>

</html>
