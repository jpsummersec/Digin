<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Test</title>
</head>

<body>

    <button id="startBtn">Start Cooking</button>

    <audio id="audioPlayer" src="../audio/gordontest.mp3"></audio>

    <script>
        document.getElementById("startBtn").addEventListener("click", () => {
            const audio = document.getElementById("audioPlayer");

            audio.currentTime = 0; // restart if clicked again
            audio.play();
        });
    </script>

</body>

</html>