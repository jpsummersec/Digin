<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


$config = [];
$configPath = __DIR__ . '/config.php';
$MIN_SEARCH_RESULTS = 0;
$MAX_SEARCH_RESULTS = 10;

if (is_file($configPath)) {
    $config = require $configPath;
}

if (isset($config['api_key'])) {
    $apiKey = $config['api_key'];
} else {
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing API key'
    ]);
    exit;
}

if (!isset($_GET['id'])) {
    die('Missing recipe ID');
}

$id = (int) $_GET['id'];

$url = "https://api.spoonacular.com/recipes/$id/analyzedInstructions?apiKey=$apiKey";

$instructionsResponse = file_get_contents($url);

$instructions = [];

if ($instructionsResponse !== false) {
    $instructions = json_decode($instructionsResponse, true);
}

$steps = [];

if (!empty($instructions)) {
    foreach ($instructions as $group) {
        if (!empty($group['steps'])) {
            foreach ($group['steps'] as $step) {
                $steps[] = $step;
            }
        }
    }
}

if ($instructionsResponse === false) {
    die('Failed to fetch recipe');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../css/lorenzotest.css">
    <title>Cooking Mode</title>

</head>

<body>

<h1>Cooking Mode</h1>

<div id="stepsContainer">

<?php foreach ($steps as $index => $step): ?>

<div class="step <?= $index === 0 ? 'active' : '' ?>"
     data-step="<?= $index ?>">

    <h2>Step <?= $step['number'] ?></h2>

    <p><?= htmlspecialchars($step['step']) ?></p>

</div>

<?php endforeach; ?>

</div>

<div id="currentStepBox">

    <h2>Current Step</h2>

    <p id="currentStepText">
        <?= htmlspecialchars($steps[0]['step'] ?? 'No steps') ?>
    </p>

    <button id="doneBtn">
        Mark as Done
    </button>

</div>
<script src="../js/cooking-mode.js"></script>
</body>
</html>