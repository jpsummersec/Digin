<?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);


    $config = [];
    $configPath = __DIR__ . '/../test/php/config.php';


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

    /*
    if (!isset($_GET['id'])) {
        die('Missing recipe ID');
    }

    $id = (int) $_GET['id'];
    */
    
    $id = 657933;

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RECIPE - <?php echo htmlspecialchars($recipe['title']); ?></title>
    <link rel="stylesheet" href="../css/root.css">
    <link rel="stylesheet" href="../css/recipe.css">
</head>
<body>
    <div id="page-wrapper">
        <nav id="nav-bar">
            <a id="back-button" href="recipe.php">&#8592;</a>
            <span id="page-title">Recipe</span>
        </nav>

        <?php if (!empty($recipe['image'])): ?>
            <div class="page-section">
                <img class="hero-image" src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
            </div>
        <?php else: ?>
            <div class="page-section">
                <img class="hero-image" src="../images/hero-image-fallback.png" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
            </div>
        <?php endif; ?>
        
        <div id="content">
            <div id="recipe-description">
                <div id="">
            </div>
        </div>
    </div>
</body>
</html>