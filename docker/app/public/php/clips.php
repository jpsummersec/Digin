<?php

$currentStepNumber = 3;
$stepTitle = "Searing the Protein";
$allocatedMinutes = 0.1; 
?>

<link rel="stylesheet" href="../css/clips.css">


<div id="gordon-alert-banner"></div>

<div class="cooking-container">
    <h2>Step <?php echo $currentStepNumber; ?>: <?php echo htmlspecialchars($stepTitle); ?></h2>
    
    <div class="timer-wrapper">
        Time Remaining: <span id="countdown-clock">00:00</span>
    </div>
    
    <button onclick="window.startCookingStepTimer(<?php echo $allocatedMinutes; ?>)">
        Start Step Timer (<?php echo $allocatedMinutes * 60; ?> Seconds)
    </button>
    
    <button onclick="window.triggerGordonScolding()">
        Test Ramsay Insult Button
    </button>
</div>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Test</title>
</head>

<body>

    <button id="startBtn">Start Cooking</button>

    <audio id="audioPlayer" src="../audio/herbs.mp3"></audio>

    <script>
        document.getElementById("startBtn").addEventListener("click", () => {
            const audio = document.getElementById("audioPlayer");

            audio.currentTime = 0; // restart if clicked again
            audio.play();
        });
    </script>

</body>

</html>

<script src="../js/clips.js"></script>