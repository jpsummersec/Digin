<?php

require_once __DIR__ . '/include-loginrequired.php';
require_once __DIR__ . '/include-dbhandler.php';

$userId = (int) $_SESSION['user_id'];
$uploadError = '';
$uploadDirectory = __DIR__ . '/../database/profile-pictures/';
$defaultProfilePicture = '../database/profile-pictures/default.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_picture']))
{
    $profilePicture = $_FILES['profile_picture'];
    $maximumFileSize = 10 * 1024 * 1024;
    $allowedImageTypes = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif'
    ];

    if ($profilePicture['error'] !== UPLOAD_ERR_OK)
    {
        $uploadError = 'The image could not be uploaded.';
    }
    elseif ($profilePicture['size'] > $maximumFileSize)
    {
        $uploadError = 'The image must be smaller than 10 MiB.';
    }
    else
    {
        // Check if image is really valid, not just a renamed file
        $imageInfo = getimagesize($profilePicture['tmp_name']);

        if ($imageInfo === false || !isset($allowedImageTypes[$imageInfo[2]]))
        {
            $uploadError = 'Please upload a JPEG, PNG, WebP, or GIF image.';
        }
        else
        {
            $fileName = 'user-' . $userId . '-' . uniqid() . '.' . $allowedImageTypes[$imageInfo[2]];
            $newFileLocation = $uploadDirectory . $fileName;
            $newDatabasePath = '../database/profile-pictures/' . $fileName;

            if (!move_uploaded_file($profilePicture['tmp_name'], $newFileLocation))
            {
                $uploadError = 'The image could not be saved.';
            }
            else
            {
                try
                {
                    $statement = $dbHandler->prepare('SELECT `path_to_icon` FROM `user` WHERE `user_id` = :userId');
                    $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
                    $statement->execute();
                    $oldDatabasePath = $statement->fetchColumn();

                    $statement = $dbHandler->prepare('UPDATE `user` SET `path_to_icon` = :pathToIcon WHERE `user_id` = :userId');
                    $statement->bindValue(':pathToIcon', $newDatabasePath);
                    $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
                    $statement->execute();

                    // Destroy old profile picture file if it's not the default one.
                    if ($oldDatabasePath !== $defaultProfilePicture)
                    {
                        $oldFileLocation = $uploadDirectory . basename($oldDatabasePath);

                        if (is_file($oldFileLocation))
                        {
                            unlink($oldFileLocation);
                        }
                    }

                    header('Location: profile-page.php');
                    exit;
                }
                catch (PDOException $exception)
                {
                    if (is_file($newFileLocation))
                    {
                        unlink($newFileLocation);
                    }

                    $uploadError = 'The profile picture could not be updated.';
                }
            }
        }
    }
}

// Load the Spotify client ID and callback URL used to build the authorization link.
$config = require __DIR__ . '/config.php';

if (isset($config['SPOTIFY_CLIENT_ID']))
{
    $clientId = $config['SPOTIFY_CLIENT_ID'];
}
else
{
    $clientId = null;
}

if (!$clientId)
{
    die('Error: SPOTIFY_CLIENT_ID not found in config.php');
}

$redirectUri = $config['SPOTIFY_REDIRECT_URI'];

// Spotify returns this state value to the callback so the response can be
// verified against the current session.
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_state'] = $state;

// These scopes allow the application to read and control Spotify playback.
$scope = 'user-read-playback-state user-modify-playback-state';

$spotifyParams = [];
$spotifyParams['response_type'] = 'code';
$spotifyParams['client_id'] = $clientId;
$spotifyParams['scope'] = $scope;
$spotifyParams['redirect_uri'] = $redirectUri;
$spotifyParams['state'] = $state;
$spotifyParams['show_dialog'] = 'true';

$queryParameters = http_build_query($spotifyParams);
$authorizeUrl = "https://accounts.spotify.com/authorize?$queryParameters";

if (isset($_GET['action']) && $_GET['action'] === 'login')
{
    header("Location: $authorizeUrl");
    exit;
}

// Load user achievements and saved recipes.
$achievements = [];
$savedRecipes = [];

try
{
    $statement = $dbHandler->prepare('SELECT * FROM `user` WHERE `user_id` = :userId');
    $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
    $statement->execute();
    $user = $statement->fetch(PDO::FETCH_ASSOC);

    $statement = $dbHandler->prepare('
        SELECT a.`achievement_id`, a.`achievement_name`, a.`path_to_icon`
        FROM `user_achievement` ua
        INNER JOIN `achievement` a
            ON a.`achievement_id` = ua.`achievement_id`
        WHERE ua.`user_id` = :userId
        ORDER BY a.`achievement_id`
    ');
    $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
    $statement->execute();
    $achievements = $statement->fetchAll(PDO::FETCH_ASSOC);

    $statement = $dbHandler->prepare('
        SELECT r.`recipe_id`, r.`recipe_json`
        FROM `user_saved_recipe` ur
        INNER JOIN `recipe` r
            ON r.`recipe_id` = ur.`recipe_id`
        WHERE ur.`user_id` = :userId
    ');
    $statement->bindValue(':userId', $userId, PDO::PARAM_INT);
    $statement->execute();
    $savedRecipes = $statement->fetchAll(PDO::FETCH_ASSOC);
}
catch (PDOException $exception)
{
    die('Select error: ' . $exception->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Your Profile</title>
        <link rel="stylesheet" href="../css/search-page.css">
        <link rel="stylesheet" href="../css/root.css">
        <link rel="stylesheet" href="../css/profile-page.css">
        <link rel="icon" type="image/svg+xml" href="../images/favicon/favicon.svg" />
    </head>
    <body>
        <?php include __DIR__ . '/menu.php'; ?>
        <h1 class="page-title">Your Profile</h1>
        <?php if ($uploadError !== '') { ?>
            <p class="upload-error"><?php echo htmlspecialchars($uploadError); ?></p>
        <?php } ?>
        <div class="profile-banner">
            <form class="profile-picture" action="profile-page.php" method="post" enctype="multipart/form-data">
                <label for="profile-picture-input">
                    <img src="<?php echo '../' . htmlspecialchars($user['path_to_icon']); ?>" alt="Profile Picture">
                </label>
                <input id="profile-picture-input" type="file" name="profile_picture" accept="image/jpeg,image/png,image/webp,image/gif" onchange="this.form.submit()">
            </form>
            <h1><?php echo htmlspecialchars($user['first_name']) . ' ' . htmlspecialchars($user['last_name']); ?></h1>
            <h2><?php echo htmlspecialchars($user['email_address']); ?></h2>
            <?php
            $currentXp = $user['xp'];
            $currentLevel = $user['level'];
            $xpToNextLevel = 100 * $currentLevel * 1.5;

            $progressPercent = ($currentXp / $xpToNextLevel) * 100;
            $progressPercent = max(0, min(100, $progressPercent));
            ?>
            <div class="xp-bar">
                <div class="xp-bar-fill" style="width: <?php echo $progressPercent; ?>%;"></div>
            </div>
            <h3 class="xp-progress">
                <?php echo $currentXp; ?> / <?php echo $xpToNextLevel; ?> XP
            </h3>
            <h3 class="level-counter">Level <?php echo $currentLevel; ?></h3>
        </div>
        <div class="achievements">
            <h2>Achievements</h2>
            <?php if (empty($achievements)) { ?>
                <p class="empty-achievements">No achievements yet.</p>
            <?php } else { ?>
                <div class="achievement-list">
                    <?php foreach ($achievements as $achievement) { ?>
                        <div class="achievement-item">
                            <img src="<?php echo htmlspecialchars($achievement['path_to_icon']); ?>" alt="<?php echo htmlspecialchars($achievement['achievement_name']); ?>">
                        </div>
                    <?php } ?>
                </div>
            <?php } ?>
            <hr>
        </div>
        <div class="saved-recipes">
            <h2>Saved Recipes</h2>
            <?php if (empty($savedRecipes)) { ?>
                <p class="empty-saved-recipes">No saved recipes yet.</p>
            <?php } else { ?>
                <div class="saved-recipes-list">
                    <?php foreach ($savedRecipes as $savedRecipe) { ?>
                        <?php
                        // Convert JSON to PHP associative array.
                        $recipe = json_decode($savedRecipe['recipe_json'], true);

                        if (!is_array($recipe))
                        {
                            continue;
                        }

                        $recipeId = $recipe['id'];
                        $title = $recipe['title'];
                        $image = $recipe['image'];
                        $time = $recipe['readyInMinutes'] . ' minutes';
                        $calories = 'N/A';

                        $spoonacularScore = $recipe['spoonacularScore'];
                        $starScore = min(max((float) $spoonacularScore / 20, 0), 5);
                        $fullStars = round($starScore);

                        if (isset($recipe['nutrition']['nutrients']))
                        {
                            foreach ($recipe['nutrition']['nutrients'] as $nutrient)
                            {
                                if (isset($nutrient['name']) && $nutrient['name'] === 'Calories')
                                {
                                    $calorieAmount = $nutrient['amount'];
                                    $calorieUnit = $nutrient['unit'];
                                    $calories = round($calorieAmount) . ' ' . $calorieUnit;
                                    break;
                                }
                            }
                        }
                        ?>
                        <article class="recipe saved-recipe-item">
                            <a class="recipe-link" href="<?php echo htmlspecialchars('../php/recipe.php?id=' . $recipeId); ?>">
                                <img class="recipe-image" src="<?php echo htmlspecialchars($image); ?>" alt="<?php echo htmlspecialchars($title); ?>" loading="lazy">
                                <div class="recipe-content">
                                    <h2><?php echo htmlspecialchars($title); ?></h2>
                                    <div class="recipe-meta">
                                        <span><span class="meta-bolt" aria-hidden="true"><img src="../images/search-page/calories.svg"></span><?php echo htmlspecialchars($calories); ?></span>
                                        <span><span class="meta-clock" aria-hidden="true"><img src="../images/search-page/time.svg"></span><?php echo htmlspecialchars($time); ?></span>
                                    </div>
                                    <span class="meta-rating" aria-hidden="true">
                                        <?php for ($star = 0; $star < 5; $star++) { ?>
                                            <?php
                                            $starClass = 'is-empty';

                                            if ($star < $fullStars)
                                            {
                                                $starClass = 'is-filled';
                                            }
                                            ?>
                                            <span class="rating-star <?php echo $starClass; ?>" aria-hidden="true">⭐</span>
                                        <?php } ?>
                                    </span>
                                </div>
                            </a>
                        </article>
                    <?php } ?>
                </div>
            <?php } ?>
            <hr>
        </div>
        <div class="spotify">
            <hr>
            <img src="../images/profile-page/spotify-logo.svg" alt="spotify-logo">
            <a href="?action=login" class="button">Connect to Spotify</a>
            <p>Cook with Spotify. Taste the vibe.</p>
        </div>
        <div class="logout">
            <form action="logout.php" method="post">
                <button type="submit">Logout</button>
            </form>
        </div>
    </body>
</html>
