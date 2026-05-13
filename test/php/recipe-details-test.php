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

$url = "https://api.spoonacular.com/recipes/$id/information?apiKey=$apiKey";

$response = file_get_contents($url);

if ($response === false) {
    die('Failed to fetch recipe');
}

$recipe = json_decode($response, true);

// NUTRITION REQUEST
$nutritionUrl = "https://api.spoonacular.com/recipes/$id/nutritionWidget.json?apiKey=$apiKey";

$nutritionResponse = file_get_contents($nutritionUrl);

if ($nutritionResponse !== false) {
    $nutrition = json_decode($nutritionResponse, true);
} else {
    $nutrition = null;
}

$instructionsUrl = "https://api.spoonacular.com/recipes/$id/analyzedInstructions?apiKey=$apiKey";

$instructionsResponse = file_get_contents($instructionsUrl);

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

if (!$recipe) {
    die('Invalid recipe data');
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recipe['title']) ?></title>
    <link rel="stylesheet" href="../css/lorenzotest.css">
</head>
<body>

    <h1><?= htmlspecialchars($recipe['title']) ?></h1>

    <img src="<?= htmlspecialchars($recipe['image']) ?>" width="300">

    <h2>Calories: <?= htmlspecialchars($nutrition['calories']) ?></h2>

    <h2>Cooking time: <?= htmlspecialchars($recipe['readyInMinutes']) ?></h2>

    <p><?= $recipe['summary'] ?></p>

    <?php
        $tags = array_merge($recipe['cuisines'], $recipe['diets']);
        ?>

        <h2>Tags:</h2>

        <?php foreach ($tags as $tag): ?>
        <span><?= htmlspecialchars($tag) ?>,</span>
    <?php endforeach; ?>

    <h2>Ingredients</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>Ingredient</th>
            <th>Portion</th>
        </tr>

        <?php foreach ($recipe['extendedIngredients'] as $ingredient): ?>
            <tr>
                <td>
                    <?= htmlspecialchars($ingredient['name']) ?>
                </td>

                <td>
                    <?= htmlspecialchars($ingredient['amount']) ?>
                    <?= htmlspecialchars($ingredient['unit']) ?>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Step-by-step Instructions</h2>

    <?php if (!empty($steps)): ?>
        <ol>
            <?php foreach ($steps as $step): ?>
                <li><?= htmlspecialchars($step['step']) ?></li>
            <?php endforeach; ?>
        </ol>
    <?php else: ?>
        <p>No instructions available.</p>
    <?php endif; ?>

</body>
</html>