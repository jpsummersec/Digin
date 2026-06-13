<?php

require_once __DIR__ . '/include-loginrequired.php';
require_once __DIR__ . '/include-dbhandler.php';

header('Content-Type: application/json');

if (!isset($_POST['recipe_id']) || !isset($_POST['isFavorite'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false
    ]);
    exit;
}

$recipeId = (int) $_POST['recipe_id'];
$isFavorite = $_POST['isFavorite'];

try {
    if ($isFavorite === 'true') {
        $statement = $dbHandler->prepare('
            INSERT IGNORE INTO `user_saved_recipe` (`user_id`, `recipe_id`)
            VALUES (:userId, :recipeId)
        ');
    } else {
        $statement = $dbHandler->prepare('
            DELETE FROM `user_saved_recipe`
            WHERE `user_id` = :userId AND `recipe_id` = :recipeId
        ');
    }

    $statement->bindValue(':userId', $_SESSION['user_id'], PDO::PARAM_INT);
    $statement->bindValue(':recipeId', $recipeId, PDO::PARAM_INT);
    $statement->execute();
    $statement->closeCursor();

    echo json_encode([
        'success' => true
    ]);
}
catch(PDOException $exception) {
    http_response_code(500);
    echo json_encode([
        'success' => false
    ]);
}
