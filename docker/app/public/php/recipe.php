<?php

require_once __DIR__ . '/include-loginrequired.php';
require_once __DIR__ . '/include-dbhandler.php';
require_once __DIR__ . '/include-spoonacular-api.php';

if (empty($apiKeys))
{
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing API keys'
    ]);
    exit;
}

if (!isset($_GET['id']))
{
    header('Location: search-page.php');
    exit;
}

$recipeId = (int) $_GET['id'];

// Use cached recipe data before requesting it from Spoonacular.
$statement = $dbHandler->prepare("
    SELECT recipe_json
    FROM recipe
    WHERE recipe_id = ?
    LIMIT 1
");

$statement->execute([$recipeId]);

$cachedRecipe = $statement->fetchColumn();

if ($cachedRecipe)
{
    $recipe = json_decode($cachedRecipe, true);
}
else
{
    $responseData = spoonacularRequestWithKeyRotation(
        "https://api.spoonacular.com/recipes/$recipeId/information",
        [
            'includeNutrition' => 'true',
        ]
    );

    if (!$responseData['success'])
    {
        die('Failed to fetch recipe');
    }

    $recipe = json_decode($responseData['body'], true);

    $statement = $dbHandler->prepare("
        INSERT INTO recipe
        (
            recipe_id,
            recipe_json
        )
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE
            recipe_json = VALUES(recipe_json)
    ");

    $statement->execute([
        $recipeId,
        json_encode($recipe)
    ]);
}

$calories = 'N/A';

if (isset($recipe['nutrition']['nutrients']))
{
    foreach ($recipe['nutrition']['nutrients'] as $nutrient)
    {
        if ($nutrient['name'] === 'Calories')
        {
            $calories = round($nutrient['amount']) . ' ' . $nutrient['unit'];
            break;
        }
    }
}

// Spoonacular groups instructions, so flatten them into one ordered step list.
$steps = [];

if (!empty($recipe['analyzedInstructions']))
{
    foreach ($recipe['analyzedInstructions'] as $group)
    {
        if (!empty($group['steps']))
        {
            foreach ($group['steps'] as $step)
            {
                $steps[] = $step;
            }
        }
    }
}

if (!$recipe)
{
    die('Invalid recipe data');
}

$ingredients = $recipe['extendedIngredients'];
$previewIngredients = array_slice($ingredients, 0, 5);

// Prepare shortened descriptions and steps for the expandable preview sections.
$descriptionWords = explode(' ', strip_tags($recipe['summary']));
$shortDescription = implode(' ', array_slice($descriptionWords, 0, 27));
$longDescription = strip_tags($recipe['summary']);
$descriptionTruncated = count($descriptionWords) > 27;

$previewSteps = array_slice($steps, 0, 3);
$stepsTruncated = count($steps) > 3;

$isFavorite = false;

// Determine the initial state of the favorite button for this user.
try
{
    $statement = $dbHandler->prepare('
        SELECT `recipe_id`
        FROM `user_saved_recipe`
        WHERE `user_id` = :userId AND `recipe_id` = :recipeId
    ');
    $statement->bindValue(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
    $statement->bindValue(':recipeId', $recipeId, PDO::PARAM_INT);
    $statement->execute();

    if ($statement->fetchColumn())
    {
        $isFavorite = true;
    }

    $statement->closeCursor();
}
catch (PDOException $exception)
{
    die('Select error: ' . $exception->getMessage());
}

$favoriteAction = 'Add';
$favoriteDirection = 'to';
$favoritePressed = 'false';
$heartImage = 'heart-empty.png';

if ($isFavorite)
{
    $favoriteAction = 'Remove';
    $favoriteDirection = 'from';
    $favoritePressed = 'true';
    $heartImage = 'heart-full.png';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RECIPE - <?php echo htmlspecialchars($recipe['title']); ?></title>
    <link rel="stylesheet" href="../css/root.css">
    <link rel="stylesheet" href="../css/recipe.css">
    <link rel="icon" type="image/svg+xml" href="../images/favicon/favicon.svg" />
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
            <img class="hero-image" src="../images/hero-food2.jpeg" alt="<?php echo htmlspecialchars($recipe['title']); ?>">
        </div>
    <?php endif; ?>

    <div id="content">
        <div id="recipe">
            <div id="recipe-title">
                <span class="title"><?php echo htmlspecialchars($recipe['title']); ?></span>
            </div>
            <div id="favorite-button-div">
                <button type="button" class="favorite-btn" aria-label="<?php echo $favoriteAction . ' ' . htmlspecialchars($recipe['title']) . ' ' . $favoriteDirection; ?> favorites" aria-pressed="<?php echo $favoritePressed; ?>">
                    <img src="../images/search-page/<?php echo $heartImage; ?>" alt="" aria-hidden="true">
                </button>
            </div>
            <div id="recipe-data">
                <div id="calories-border">
                    <img src="../images/recipe-page/bolt.svg" alt="">
                    <?php echo htmlspecialchars($calories); ?>
                </div>
                <div>
                    <img src="../images/recipe-page/clock.svg" alt="">
                    <?php echo htmlspecialchars($recipe['readyInMinutes']); ?> minutes
                </div>
            </div>
            <div id="recipe-description">
                <span id="desc-short">
                    <?php
                    echo htmlspecialchars($shortDescription);
                    echo $descriptionTruncated ? '...' : ''; // If description has been truncated, echo "...", otherwise echo "".
                    ?>
                </span>
                <?php if ($descriptionTruncated): ?>
                    <span id="desc-full" style="display:none;"><?php echo htmlspecialchars($longDescription); ?></span>
                    <br><button type="button" class="toggle-btn" onclick="toggleDesc()">View all</button>
                <?php endif; ?>
            </div>
            <div id="recipe-tags">
                Tags:
                <?php
                $tags = array_merge($recipe['cuisines'], $recipe['dishTypes']);

                foreach ($tags as $tag)
                {
                    echo '<span class="tag">' . htmlspecialchars(ucfirst($tag)) . '</span>';
                }
                ?>
            </div>
        </div>
        <div id="ingredients">
            <h2>Ingredients</h2>
            <div id="ingredients-container">
                <div class="ingredient-details">
                    <?php
                    foreach ($previewIngredients as $ingredient)
                    {
                        echo '<div class="ingredient">' . htmlspecialchars(ucfirst($ingredient['name'])) . '</div>';
                    }

                    if (count($ingredients) > 5): ?>
                        <div id="ingredient-names-extra" style="display:none;">
                            <?php
                            foreach (array_slice($ingredients, 5) as $ingredient)
                            {
                                echo '<div class="ingredient">' . htmlspecialchars(ucfirst($ingredient['name'])) . '</div>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="ingredient-details">
                    <?php
                    foreach ($previewIngredients as $ingredient)
                    {
                        echo '<div class="amount">' . htmlspecialchars(ucfirst($ingredient['amount'])) . ' ' . htmlspecialchars($ingredient['unit']) . '</div>';
                    }

                    if (count($ingredients) > 5) : ?>
                        <div id="ingredient-amounts-extra" style="display:none;">
                            <?php
                            foreach (array_slice($ingredients, 5) as $ingredient)
                            {
                                echo '<div class="amount">' . htmlspecialchars($ingredient['amount']) . ' ' . htmlspecialchars($ingredient['unit']) . '</div>';
                            }
                            ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php if (count($ingredients) > 5): ?>
                <button type="button" class="toggle-btn" onclick="toggleIngredients()">View All</button>
            <?php endif; ?>
        </div>

        <div id="steps">
            <h2>Steps</h2>
            <?php
            foreach ($previewSteps as $step)
            {
                echo '<div class="step">';
                echo '<h3 class="step-title">Step ' . $step['number'] . '</h3>';
                echo '<div class="step-description">';
                echo htmlspecialchars(strip_tags($step['step']));
                echo '</div>';
                echo '</div>';
            }

            if ($stepsTruncated): ?>
                <div id="steps-extra" style="display:none;">
                    <?php
                    foreach (array_slice($steps, 3) as $step)
                    {
                        echo '<div class="step">';
                        echo '<h3 class="step-title">Step ' . $step['number'] . '</h3>';
                        echo '<div class="step-description">' . htmlspecialchars(strip_tags($step['step'])) . ' </div>';
                        echo '</div>';
                    }
                    ?>
                </div>
                <button type="button" class="toggle-btn" id="steps-btn" onclick="toggleSteps()">View All</button>
            <?php endif; ?>
        </div>
        <div id="button">
            <a id="cooking-button" href="steps.php?id=<?php echo htmlspecialchars($recipeId); ?>" onclick="sessionStorage.setItem('playStepOneAudio', 'yes')">Start Cooking</a>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>
</body>
<script>
        function toggleDesc()
        {
            const short = document.getElementById('desc-short');
            const full = document.getElementById('desc-full');
            const btn = event.target;

            if (full.style.display === 'none')
            {
                short.style.display = 'none';
                full.style.display = 'inline';
                btn.textContent = 'View less';
            }
            else
            {
                short.style.display = 'inline';
                full.style.display = 'none';
                btn.textContent = 'View all';
            }
        }

        function toggleIngredients()
        {
            const namesExtra = document.getElementById('ingredient-names-extra');
            const amountsExtra = document.getElementById('ingredient-amounts-extra');
            const btn = event.target;

            const isHidden = namesExtra.style.display === 'none';
            namesExtra.style.display = isHidden ? 'contents' : 'none';
            amountsExtra.style.display = isHidden ? 'contents' : 'none';
            btn.textContent = isHidden ? 'View less' : 'View all';
        }

        function toggleSteps()
        {
            const extra = document.getElementById('steps-extra');
            const btn = document.getElementById('steps-btn');

            if (extra.style.display === 'none')
            {
                extra.style.display = 'block';
                btn.textContent = 'View less';
            }
            else
            {
                extra.style.display = 'none';
                btn.textContent = 'View all';
            }
        }

        const favoriteButton = document.querySelector('.favorite-btn');

        favoriteButton.addEventListener('click', () =>
        {
            const isFavorite = favoriteButton.getAttribute('aria-pressed') === 'true';
            const recipeTitle = <?php echo json_encode($recipe['title']); ?>;
            const newFavoriteState = !isFavorite;
            const formData = new FormData();
            formData.append('recipe_id', <?php echo $recipeId; ?>);
            formData.append('isFavorite', String(newFavoriteState));

            fetch('favorite-recipe.php',
            {
                method: 'POST',
                body: formData,
            })
                .then(response => response.json())
                .then(result =>
                {
                    if (!result.success)
                    {
                        return;
                    }

                    const favoriteImage = favoriteButton.querySelector('img');
                    favoriteButton.setAttribute('aria-pressed', String(newFavoriteState));

                    if (newFavoriteState)
                    {
                        favoriteButton.setAttribute('aria-label', `Remove ${recipeTitle} from favorites`);
                        favoriteImage.src = '../images/search-page/heart-full.png';
                    }
                    else
                    {
                        favoriteButton.setAttribute('aria-label', `Add ${recipeTitle} to favorites`);
                        favoriteImage.src = '../images/search-page/heart-empty.png';
                    }
                });
        });
    </script>
</html>