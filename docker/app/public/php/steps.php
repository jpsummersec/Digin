<?php

include("include-dbhandler.php");
include("include-loginrequired.php");
include_once __DIR__ . '/include-spoonacular-api.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (empty($apiKeys)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing API keys'
    ]);
    exit;
}

if (!isset($_GET['id'])) {
    die('Missing recipe ID');
}

$id = (int) $_GET['id'];

// Fetch all recipe information
$responseData = spoonacularRequestWithKeyRotation("https://api.spoonacular.com/recipes/$id/information", [
    'includeNutrition' => 'true',
]);

if (!$responseData['success']) {
    die('Failed to fetch recipe');
}

$recipe = json_decode($responseData['body'], true);

if (!$recipe) {
    die('Invalid recipe data');
}

// Extract steps from analyzedInstructions
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

// Get the cuisine for Spotify music
$cuisine = 'cooking'; // default fallback
if (!empty($recipe['cuisines']) && is_array($recipe['cuisines'])) {
    $cuisine = $recipe['cuisines'][0]; // use first cuisine
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
</head>

<body>

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
        </div>

        <div id="steps">
            <h2>Cooking Steps</h2>
            <div id="steps-container">
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
                <p id="step-counter">Step <span id="current-step">1</span> of <span id="total-steps"><?php echo count($steps); ?></span></p>
                <div id="step-buttons">
                    <button id="prev-step-btn">Previous</button>
                    <button id="next-step-btn">Next</button>
                </div>
                <button id="reset-to-step-one">Go back to Step 1</button>
            </div>
        </div>
    </div>

    <?php include("footer.php"); ?>

    <audio id="gordon-audio" src="../audio/gordontest.mp3"></audio>
    <audio id="background-audio" src="../audio/kitchendramaticsound.mp3" loop></audio>

    <script src="../js/steps.js"></script>

    <script>
        // Start playing Spotify music based on recipe cuisine when page loads
        window.addEventListener('load', () => {
            const cuisine = '<?php echo htmlspecialchars($cuisine); ?>';

            // Call play.php to start Spotify playback
            fetch(`../play.php?cuisine=${encodeURIComponent(cuisine)}`)
                .then(response => response.text())
                .then(data => console.log('Spotify:', data))
                .catch(error => console.log('Spotify playback note:', error));
        });
    </script>

</body>

</html>