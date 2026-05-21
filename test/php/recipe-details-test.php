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

$url = "https://api.spoonacular.com/recipes/$id/information?apiKey=$apiKey&includeNutrition=true";

$response = file_get_contents($url);

if ($response === false) {
    die('Failed to fetch recipe');
}

$recipe = json_decode($response, true);

$calories = 'N/A';

if (isset($recipe['nutrition']['nutrients'])) {
    foreach ($recipe['nutrition']['nutrients'] as $nutrient) {
        if ($nutrient['name'] === 'Calories') {
            $calories = $nutrient['amount'] . ' ' . $nutrient['unit'];
            break;
        }
    }
}

$steps = [];

if (!empty($recipe['analyzedInstructions'])) {
    foreach ($recipe['analyzedInstructions'] as $group) {
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

$showAll = isset($_GET['showAll']) && $_GET['showAll'] == 1;
$ingredients = $recipe['extendedIngredients'];
$previewIngredients = array_slice($ingredients, 0, 5);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($recipe['title']) ?></title>
    <link rel="stylesheet" href="../css/recipetest.css">
</head>
<body>

    <h1><?= htmlspecialchars($recipe['title']) ?></h1>

    <img src="<?= htmlspecialchars($recipe['image']) ?>" width="300">

    <h2>Calories: <?= htmlspecialchars($calories) ?></h2>

    <h2>Cooking time: <?= htmlspecialchars($recipe['readyInMinutes']) ?></h2>

    <p><?= $recipe['summary'] ?></p>

    <?php
        $tags = array_merge(
            $recipe['cuisines'] ?? [],
            $recipe['diets'] ?? [],
            $recipe['dishTypes'] ?? [],
            $recipe['occasions'] ?? []
        );

        if ($recipe['cheap'] ?? false) {
            $tags[] = 'Budget Friendly';
        }

        if ($recipe['veryPopular'] ?? false) {
            $tags[] = 'Popular';
        }

        if ($recipe['sustainable'] ?? false) {
            $tags[] = 'Eco Friendly';
        }

        if (($recipe['healthScore'] ?? 0) >= 80) {
            $tags[] = 'Healthy';
        }

        if (($recipe['readyInMinutes'] ?? 999) <= 20) {
            $tags[] = 'Quick Meal';
        }
        ?>

        <h2>Tags:</h2>

        <?php foreach ($tags as $tag): ?>
        <span class="tag">
            <?= htmlspecialchars($tag) ?>
        </span>
    <?php endforeach; ?>

    <h2>Ingredients</h2>

    <table border="1" cellpadding="8">
        <tr>
            <th>Ingredient</th>
            <th>Portion</th>
        </tr>

        <?php
            $listToShow = $showAll ? $ingredients : $previewIngredients;
        ?>

        <?php foreach ($listToShow as $ingredient): ?>
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

    <div style="margin-top:10px;">
        <?php if (!$showAll): ?>
            <a href="?id=<?= $id ?>&showAll=1">View all ingredients</a>
        <?php else: ?>
            <a href="?id=<?= $id ?>">Show less</a>
        <?php endif; ?>
    </div>

    <h2>Step-by-step Instructions</h2>

    <?php if (!empty($steps) && isset($steps[0])): ?>
        <ol>
            <li><?= htmlspecialchars($steps[0]['step']) ?></li>
        </ol>
    <?php else: ?>
        <p>No instructions available.</p>
    <?php endif; ?>

    <div>
        <a href="cooking-test.php?id=<?= $id ?>">
            <button id="startCooking">Start cooking</button>
        </a>
    </div>
</body>
</html>