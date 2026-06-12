<?php

include("include-dbhandler.php");
include("include-loginrequired.php");
include_once __DIR__ . '/include-spoonacular-api.php';

$db = $dbHandler;
error_reporting(E_ALL);
ini_set('display_errors', 1);

$isRecipeCompleted = false;
$recipeId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (
        isset($_POST['recipe_id']) &&
        isset($_POST['isRecipeCompleted']) &&
        $_POST['isRecipeCompleted'] === 'true'
    ) {
        $recipeId = (int) $_POST['recipe_id'];

        if ($recipeId > 0) {
            $isRecipeCompleted = true;
        }
    }

    if ($isRecipeCompleted) {
        try {
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

            if (isset($recipeData['readyInMinutes']) && is_numeric($recipeData['readyInMinutes']) && $recipeData['readyInMinutes'] > 0) {
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

            while ($currentXp >= $xpToNextLevel) {
                $currentXp = $currentXp - $xpToNextLevel;
                $currentLevel++;

                if ($currentLevel == 5 || $currentLevel == 10 || $currentLevel == 15 || $currentLevel == 20) {
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
        } catch (PDOException $exception) {
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

if (empty($apiKeys)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing API keys'
    ]);
    exit;
}

// The recipe ID identifies which recipe should be shown in cooking mode
if (!isset($_GET['id'])) {
    die('Missing recipe ID');
}

$id = (int) $_GET['id'];

$recipe = null;

// Try cache first
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
}

// If recipe doesn't exist in cache OR analyzedInstructions are missing
if (
    !$recipe ||
    empty($recipe['analyzedInstructions'])
) {

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

    if (!$recipe) {
        die('Invalid recipe data');
    }

    // Update cache with full recipe including analyzedInstructions
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

// Spoonacular groups instructions, so flatten all groups into one ordered step list
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

// Use the first recipe cuisine for Spotify, or a generic cooking search as fallback
$cuisine = 'cooking';
if (!empty($recipe['cuisines']) && is_array($recipe['cuisines'])) {
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
        <a id="back-button" href="recipe.php?id=<?php echo htmlspecialchars($id); ?>"><img src="../images/recipe-page/arrow.svg" alt="Back"></a>
        <span class="title">Cooking Mode</span>
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
            <p id="spotify-status" aria-live="polite">Starting Spotify music...</p>
        </div>

        <div id="steps">
            <h2>Cooking Steps</h2>
            <div id="steps-container">
                <!-- Render every step, while steps.js controls which one is visible. -->
                <?php foreach ($steps as $index => $step) {
                    echo "<div class='step " . ($index === 0 ? 'active' : '') . "' data-step='" . $index . "'>";
                    echo "<h3 class='step-title'> Step " . $step['number'] . "</h3>";
                    echo "<div class='step-description'>";
                    echo htmlspecialchars(strip_tags($step['step']));
                    echo "</div>";
                    echo "</div>";
                }
                ?>
            </div>
            <div id="step-navigation">
                <!-- <p id="step-counter">Step <span id="current-step">1</span> of <span id="total-steps"><?php echo count($steps); ?></span></p> -->
                <div id="step-buttons">
                    <button id="prev-step-btn">Previous</button>
                    <button id="next-step-btn">Next</button>
                </div>
                <!-- <button id="reset-to-step-one">Go back to Step 1</button> -->
            </div>
        </div>
    </div>

    <?php include("footer.php"); ?>

    <audio id="gordon-audio"></audio>

    <script src="../js/steps.js"></script>

    <script>
        // Asks the playback to start cuisine music as soon as the page is ready
        document.addEventListener('DOMContentLoaded', async () => {
            // json_encode safely transfers the PHP cuisine string into JavaScript (AI Generated)
            const cuisine = <?php echo json_encode($cuisine); ?>;
            const status = document.getElementById('spotify-status');

            try {
                // play.php searches Spotify then chooses a device and starts the playlist
                const response = await fetch(`play.php?cuisine=${encodeURIComponent(cuisine)}`, {
                    headers: {
                        Accept: 'text/plain'
                    }
                });
                const message = await response.text();

                // Show either the playlist being played or the returned Spotify error
                status.textContent = message || 'Spotify music could not be started.';
                status.dataset.state = response.ok ? 'playing' : 'error';
            } catch (error) {
                status.textContent = 'Spotify music could not be started.';
                status.dataset.state = 'error';
                console.error('Spotify playback error:', error);
            }
        });
    </script>

</body>

</html>
