<?php
    include("include-dbhandler.php");
    include("include-loginrequired.php");

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
    
    $id = 642540;

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
                $calories = round($nutrient['amount']) . ' ' . $nutrient['unit'];
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

    /*
    var_dump($recipe);
    exit;
    */
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
    <nav id="nav-bar">
        <a id="back-button" href="recipe.php"><img src="../images/recipe-page/arrow.svg" alt="Back"></a>
        <span class="title">Recipe</span>
    </nav>

    <?php if (!empty($recipe['image'])): ?>
        <div class="hero">
            <img class="hero-image" src="<?php echo htmlspecialchars($recipe['image']); ?>" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
        </div>
    <?php else: ?>
        <div class="hero">
            <img class="hero-image" src="../images/hero-image-fallback.svg" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
        </div>
    <?php endif; ?>
    
    <div id="content">
        <div id="recipe">
            <div id="recipe-title">
                <span class="title"><?php echo htmlspecialchars($recipe['title']); ?></span>
            </div>
            <div id="recipe-data">
                <div id ="calories-border">
                    <img src="../images/recipe-page/bolt.svg" alt="">
                    <?php echo htmlspecialchars($calories)?>
                </div>
                <div>
                    <img src="../images/recipe-page/clock.svg" alt="">
                    <?php echo htmlspecialchars($recipe['readyInMinutes']) ?> minutes
                </div> 
            </div>
            <div id="recipe-description">
                <?php echo htmlspecialchars(strip_tags($recipe['summary'])); ?>
            </div>
            <div id="recipe-tags">
                Tags: 
                <?php
                    $tags = array_merge($recipe['cuisines'], $recipe['dishTypes']);

                    foreach ($tags as $tag) {
                        echo "<span class='tag'>" . htmlspecialchars(ucfirst($tag)) . "</span>";
                    }
                ?>
            </div>
        </div>
        <div id="ingredients">
            <h2><ul>Ingredients</ul></h2>
            <div id="ingredients-container">
                <div id="ingredient-details">
                    <?php foreach ($ingredients as $ingredient) {
                            echo "<li class='ingredient'>" . htmlspecialchars(ucfirst($ingredient['name'])) . "</li>";
                        };
                    ?>
                </div>
                <div id="ingredient-details">
                    <?php foreach ($ingredients as $ingredient) {
                            echo "<li class='amount'>" . htmlspecialchars(ucfirst($ingredient['amount'])) . " " . htmlspecialchars(ucfirst($ingredient['unit'])) . "</li>";
                        };
                    ?>
                </div>
            </div>
        </div>
        <div id="steps">
            <h2>Steps</h2>
            <?php foreach ($steps as $step) {
                    echo "<div class='step'>";
                        echo "<h3 class='step-title'> Step " . $step['number'] . "</h3>";
                        echo "<div class='step-description'>";
                            echo htmlspecialchars(strip_tags($step['step']));
                        echo "</div>";
                    echo "</div>";
                }
            ?>
            
        </div>
        <div id="button">
            <button id="cooking-button">
                Start Cooking
            </button>
        </div>
    </div>

    <?php include("footer.php"); ?>
</body>
</html>