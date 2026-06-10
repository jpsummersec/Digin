<?php
require_once __DIR__ . '/include-loginrequired.php';
require_once __DIR__ . '/include-dbhandler.php';
require_once __DIR__ . '/include-spoonacular-api.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$db = $dbHandler;

if (empty($apiKeys)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing API keys'
    ]);
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: search-page.php');
    exit;
}

$id = (int) $_GET['id'];

$stmt = $db->prepare("
        SELECT recipe_json
        FROM recipe
        WHERE recipe_id = ?
        LIMIT 1
    ");

$stmt->execute([$id]);

$cachedRecipe = $stmt->fetchColumn();

if ($cachedRecipe) {

    $recipe = json_decode($cachedRecipe, true);
} else {

    $responseData = spoonacularRequestWithKeyRotation(
        "https://api.spoonacular.com/recipes/$id/information",
        [
            'includeNutrition' => 'true',
        ]
    );

    if (!$responseData['success']) {
        die('Failed to fetch recipe');
    }

    $recipe = json_decode($responseData['body'], true);

    $stmt = $db->prepare("
            INSERT INTO recipe
            (
                recipe_id,
                recipe_json
            )
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE
            recipe_json = VALUES(recipe_json)
        ");

    $stmt->execute([
        $id,
        json_encode($recipe)
    ]);
}

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

$descriptionWords = explode(' ', strip_tags($recipe['summary'])); //store description as an array of words
$shortDescription = implode(' ', array_slice($descriptionWords, 0, 27)); //short version -> the first 27 words
$longDescription = strip_tags($recipe['summary']);
$descriptionTruncated = count($descriptionWords) > 27; //how many words are there after the initial 27

$previewSteps = array_slice($steps, 0, 3); //preview steps -> the first 3 steps
$stepsTruncated = count($steps) > 3; //how many steps are there after the initial 3
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
    <?php include __DIR__ . '/menu.php'; ?>
    <nav id="nav-bar">
        <a id="back-button" href="search-page.php"><img src="../images/recipe-page/arrow.svg" alt="Back"></a>
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
                <div id="calories-border">
                    <img src="../images/recipe-page/bolt.svg" alt="">
                    <?php echo htmlspecialchars($calories) ?>
                </div>
                <div>
                    <img src="../images/recipe-page/clock.svg" alt="">
                    <?php echo htmlspecialchars($recipe['readyInMinutes']) ?> minutes
                </div>
            </div>
            <div id="recipe-description">
                <span id="desc-short">
                    <?php
                    echo htmlspecialchars($shortDescription);
                    echo $descriptionTruncated ? '...' : '';
                    ?>
                </span>
                <?php if ($descriptionTruncated): ?>
                    <span id="desc-full" style="display:none;"><?php echo htmlspecialchars($longDescription); ?></span>
                    <br><button class="toggle-btn" onclick="toggleDesc()">View all</button>
                <?php endif; ?>
            </div>
            <div id="recipe-tags">
                Tags:
                <?php
                $tags = array_merge($recipe['cuisines'], $recipe['dishTypes']);

                foreach ($tags as $tag) {
                    echo "<span class='tag'>" . htmlspecialchars(ucfirst($tag)) . "</span>";
                };
                ?>
            </div>
        </div>
        <div id="ingredients">
            <h2>
                <ul>Ingredients</ul>
            </h2>
            <div id="ingredients-container">
                <div class="ingredient-details">
                    <?php
                    foreach ($previewIngredients as $ingredient) {
                        echo "<li class='ingredient'>" . htmlspecialchars(ucfirst($ingredient['name'])) . "</li>";
                    };

                    if (count($ingredients) > 5): ?>
                        <div id="ingredient-names-extra" style="display:none;">
                            <?php
                            foreach (array_slice($ingredients, 5) as $ingredient) {
                                echo "<li class='ingredient'>" . htmlspecialchars(ucfirst($ingredient['name'])) . "</li>";
                            };
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ingredient-details">
                    <?php
                    foreach ($previewIngredients as $ingredient) {
                        echo "<li class='amount'>" . htmlspecialchars(ucfirst($ingredient['amount'])) . " " . htmlspecialchars($ingredient['unit']) . "</li>";
                    };

                    if (count($ingredients) > 5) : ?>
                        <div id="ingredient-amounts-extra" style="display:none;">
                            <?php
                            foreach (array_slice($ingredients, 5) as $ingredient) {
                                echo "<li class='amount'>" . htmlspecialchars($ingredient['amount']) . " " . htmlspecialchars($ingredient['unit']) . "</li>";
                            };
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (count($ingredients) > 5): ?>
                <button class="toggle-btn" onclick="toggleIngredients()">View All</button>
            <?php endif; ?>
        </div>

        <div id="steps">
            <h2>Steps</h2>
            <?php
            foreach ($previewSteps as $step) {
                echo "<div class='step'>";
                echo "<h3 class='step-title'>Step " . $step['number'] . "</h3>";
                echo "<div class='step-description'>";
                echo htmlspecialchars(strip_tags($step['step']));
                echo "</div>";
                echo "</div>";
            };

            if ($stepsTruncated): ?>
                <div id="steps-extra" style="display:none;">
                    <?php
                    foreach (array_slice($steps, 3) as $step) {
                        echo "<div class='step'>";
                        echo "<h3 class='step-title'>Step " . $step['number'] . "</h3>";
                        echo "<div class='step-description'>" . htmlspecialchars(strip_tags($step['step'])) . " </div>";
                        echo "</div>";
                    };
                    ?>
                </div>
                <button class="toggle-btn" id="steps-btn" onclick="toggleSteps()">View All</button>
            <?php endif; ?>
        </div>
        <div id="button">
            <a href="steps.php?id=<?php echo htmlspecialchars($id); ?>" onclick="sessionStorage.setItem('playStepOneAudio', 'yes')">
                <button id="cooking-button">
                    Start Cooking
                </button>
            </a>
        </div>
    </div>

    <?php require_once __DIR__ . '/footer.php'; ?>

    <script>
        function toggleDesc() {
            const short = document.getElementById('desc-short');
            const full = document.getElementById('desc-full');
            const btn = event.target;

            if (full.style.display === 'none') {
                short.style.display = 'none';
                full.style.display = 'inline';
                btn.textContent = 'View less';
            } else {
                short.style.display = 'inline';
                full.style.display = 'none';
                btn.textContent = 'View all';
            }
        }

        function toggleIngredients() {
            const namesExtra = document.getElementById('ingredient-names-extra');
            const amountsExtra = document.getElementById('ingredient-amounts-extra');
            const btn = event.target;

            const isHidden = namesExtra.style.display === 'none';
            namesExtra.style.display = isHidden ? 'contents' : 'none';
            amountsExtra.style.display = isHidden ? 'contents' : 'none';
            btn.textContent = isHidden ? 'View less' : 'View all';
        }

        function toggleSteps() {
            const extra = document.getElementById('steps-extra');
            const btn = document.getElementById('steps-btn');

            if (extra.style.display === 'none') {
                extra.style.display = 'block';
                btn.textContent = 'View less';
            } else {
                extra.style.display = 'none';
                btn.textContent = 'View all';
            }
        }
    </script>
</body>

</html>
