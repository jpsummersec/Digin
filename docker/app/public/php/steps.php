<?php

require_once __DIR__ . '/include-dbhandler.php';
require_once __DIR__ . '/include-loginrequired.php';
require_once __DIR__ . '/include-spoonacular-api.php';

$isRecipeCompleted = false;
$recipeId = 0;

// A POST request records a completed recipe and awards the related XP.
if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
    header('Content-Type: application/json');

    if (
        isset($_POST['recipe_id']) &&
        isset($_POST['isRecipeCompleted']) &&
        $_POST['isRecipeCompleted'] === 'true'
    )
    {
        $recipeId = (int) $_POST['recipe_id'];

        if ($recipeId > 0)
        {
            $isRecipeCompleted = true;
        }
    }

    if ($isRecipeCompleted)
    {
        try
        {
            $statement = $dbHandler->prepare('
                INSERT INTO `user_cooked_recipe` (`user_id`, `recipe_id`)
                VALUES (:userId, :recipeId)
            ');
            $statement->bindValue(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
            $statement->bindValue(':recipeId', $recipeId, PDO::PARAM_INT);
            $statement->execute();
            $statement->closeCursor();

            $statement = $dbHandler->prepare('
                SELECT `recipe_json`
                FROM `recipe`
                WHERE `recipe_id` = :recipeId
            ');
            $statement->bindValue(':recipeId', $recipeId, PDO::PARAM_INT);
            $statement->execute();
            $recipeJson = $statement->fetchColumn();
            $statement->closeCursor();

            $xpEarned = 0;
            $recipeData = json_decode($recipeJson, true);

            if (isset($recipeData['readyInMinutes']) && is_numeric($recipeData['readyInMinutes']) && $recipeData['readyInMinutes'] > 0)
            {
                $xpEarned = (int) $recipeData['readyInMinutes'] * 10;
            }

            $statement = $dbHandler->prepare('
                SELECT `xp`, `level`
                FROM `user`
                WHERE `user_id` = :userId
            ');
            $statement->bindValue(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
            $statement->execute();
            $user = $statement->fetch(PDO::FETCH_ASSOC);
            $statement->closeCursor();

            $currentXp = $user['xp'] + $xpEarned;
            $currentLevel = $user['level'];
            $xpToNextLevel = 100 * $currentLevel * 2;

            while ($currentXp >= $xpToNextLevel)
            {
                $currentXp = $currentXp - $xpToNextLevel;
                $currentLevel++;

                if ($currentLevel == 5 || $currentLevel == 10 || $currentLevel == 15 || $currentLevel == 20)
                {
                    $achievementId = (int) ($currentLevel / 5);

                    $statement = $dbHandler->prepare('
                        INSERT IGNORE INTO `user_achievement` (`user_id`, `achievement_id`)
                        VALUES (:userId, :achievementId)
                    ');
                    $statement->bindValue(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
                    $statement->bindValue(':achievementId', $achievementId, PDO::PARAM_INT);
                    $statement->execute();
                    $statement->closeCursor();
                }

                $xpToNextLevel = 100 * $currentLevel * 2;
            }

            $statement = $dbHandler->prepare('
                UPDATE `user`
                SET `xp` = :xp, `level` = :level
                WHERE `user_id` = :userId
            ');
            $statement->bindValue(':xp', $currentXp, PDO::PARAM_INT);
            $statement->bindValue(':level', $currentLevel, PDO::PARAM_INT);
            $statement->bindValue(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
            $statement->execute();
            $statement->closeCursor();
        }
        catch (PDOException $exception)
        {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'isRecipeCompleted' => false
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'isRecipeCompleted' => true
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'isRecipeCompleted' => false
    ]);
    exit;
}

if (empty($apiKeys))
{
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing API keys'
    ]);
    exit;
}

// The recipe ID identifies which recipe should be shown in cooking mode.
if (!isset($_GET['id']))
{
    die('Missing recipe ID');
}

$recipeId = (int) $_GET['id'];

$recipe = null;

// Use cached recipe data first to avoid an unnecessary API request.
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

// Fetch fresh data when the recipe is missing or has no analyzed instructions.
if (
    !$recipe ||
    empty($recipe['analyzedInstructions'])
)
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

    if (!$recipe)
    {
        die('Invalid recipe data');
    }

    // Store the complete recipe data, including analyzed instructions.
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

// Use the first recipe cuisine for Spotify, with a generic fallback.
$cuisine = 'cooking';
if (!empty($recipe['cuisines']) && is_array($recipe['cuisines']))
{
    $cuisine = $recipe['cuisines'][0];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cooking Mode - <?php echo htmlspecialchars($recipe['title']); ?></title>
    <link rel="stylesheet" href="../css/root.css">
    <link rel="stylesheet" href="../css/recipe.css">
    <link rel="stylesheet" href="../css/steps.css">
    <link rel="icon" type="image/svg+xml" href="../images/favicon/favicon.svg" />
</head>

<body>
    <?php include __DIR__ . '/menu.php'; ?>
    <nav id="nav-bar">
        <a id="back-button" href="recipe.php?id=<?php echo htmlspecialchars($recipeId); ?>"><img src="../images/recipe-page/arrow.svg" alt="Back"></a>
        <span class="title">Cooking Mode</span>
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
            <p id="spotify-status" aria-live="polite">Starting Spotify music...</p>
        </div>

        <div id="steps">
            <h2>Cooking Steps</h2>
            <div id="steps-container">
                <!-- Render every step; steps.js controls which step is visible. -->
                <?php foreach ($steps as $index => $step)
                {
                    echo '<div class="step ' . ($index === 0 ? 'active' : '') . '" data-step="' . $index . '">';
                    echo '<h3 class="step-title">Step ' . $step['number'] . '</h3>';
                    echo '<div class="step-description">';
                    echo htmlspecialchars(strip_tags($step['step']));
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
            <div id="step-navigation">
                <div id="step-buttons">
                    <button type="button" id="prev-step-btn">Previous</button>
                    <button type="button" id="next-step-btn">Next</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/footer.php'; ?>

    <audio id="gordon-audio"></audio>

    <script src="../js/steps.js"></script>

    <script>
        // Start cuisine-based Spotify music as soon as cooking mode is ready.
        document.addEventListener('DOMContentLoaded', async () =>
        {
            const cuisine = <?php echo json_encode($cuisine); ?>;
            const status = document.getElementById('spotify-status');

            try
            {
                // play.php finds a playlist and starts it on an available device.
                const response = await fetch(`play.php?cuisine=${encodeURIComponent(cuisine)}`,
                {
                    headers:
                    {
                        Accept: 'text/plain'
                    }
                });
                const message = await response.text();

                // Show either the playback confirmation or the returned error.
                status.textContent = message || 'Spotify music could not be started.';
                status.dataset.state = response.ok ? 'playing' : 'error';
            }
            catch
            {
                status.textContent = 'Spotify music could not be started.';
                status.dataset.state = 'error';
            }
        });
    </script>

</body>

</html>
