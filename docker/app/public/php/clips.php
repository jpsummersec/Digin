<?php
require_once __DIR__ . '/include-url-config.php';

$currentStepNumber = 3;
$stepTitle = "Searing the Protein";
$allocatedMinutes = 0.1;
$allocatedSeconds = (int) round($allocatedMinutes * 60);
$audioFiles = [];

foreach (glob(__DIR__ . '/../audio/*.mp3') ?: [] as $filePath) {
    $audioFiles[] = [
        'name' => basename($filePath),
        'file' => '../audio/' . rawurlencode(basename($filePath)),
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooking Timer Clips</title>
    <link rel="stylesheet" href="../css/root.css">
    <link rel="stylesheet" href="../css/clips.css">
</head>

<body>
    <?php include __DIR__ . '/menu.php'; ?>
    <main class="cooking-container">
        <div id="gordon-alert-banner" role="status" aria-live="assertive"></div>

        <section class="step-card">
            <p class="eyebrow">Cooking step</p>
            <h1>Step <?php echo (int) $currentStepNumber; ?>: <?php echo htmlspecialchars($stepTitle); ?></h1>

            <div class="timer-wrapper">
                <span>Time Remaining</span>
                <strong id="countdown-clock">00:<?php echo str_pad((string) $allocatedSeconds, 2, '0', STR_PAD_LEFT); ?></strong>
            </div>

            <div class="button-row">
                <button type="button" id="start-step-timer" data-minutes="<?php echo htmlspecialchars((string) $allocatedMinutes); ?>">
                    Start Step Timer (<?php echo $allocatedSeconds; ?> Seconds)
                </button>

                <button type="button" id="test-scolding">
                    Test Ramsay Insult
                </button>
            </div>
        </section>

        <section class="step-card audio-test-card">
            <p class="eyebrow">Audio tester</p>
            <h2>Test all audio clips</h2>

            <label class="interval-control" for="audio-interval-seconds">
                Interval between clips
                <span>
                    <input type="number" id="audio-interval-seconds" min="0" max="60" step="1" value="2">
                    seconds
                </span>
            </label>

            <div class="button-row">
                <button type="button" id="play-all-audio">
                    Play All Audio
                </button>

                <button type="button" id="stop-audio-test">
                    Stop Audio Test
                </button>
            </div>

            <p class="audio-status" id="audio-test-status">
                <?php echo count($audioFiles); ?> audio files ready.
            </p>

            <ul class="audio-list" id="audio-file-list"></ul>
        </section>
    </main>

    <script>
        window.availableAudioClips = <?php echo json_encode($audioFiles, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    </script>
    <script src="../js/clips.js"></script>
</body>

</html>
