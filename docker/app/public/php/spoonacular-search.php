<?php
include __DIR__ . '/include-loginrequired.php';
include __DIR__ . '/include-dbhandler.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// tells header that JSON content is being returned
header('Content-Type: application/json');

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
} else {
	http_response_code(400);
    echo json_encode([
        'error' => 'Missing search query'
    ]);
    exit;
}

include_once __DIR__ . '/include-spoonacular-api.php';

$MIN_SEARCH_RESULTS = 0;
$MAX_SEARCH_RESULTS = 10;

if (empty($apiKeys)) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Missing API keys'
    ]);
    exit;
}

$cuisine = $_GET['cuisine'] ?? '';
$maxTime = $_GET['maxTime'] ?? ($_GET['maxReadyTime'] ?? '');
$type = $_GET['type'] ?? '';
$intolerances = $_GET['intolerances'] ?? '';
$sort = $_GET['sort'] ?? '';
$diet = $_GET['diet'] ?? '';

if (isset($_GET['number'])) {
    $number = (int) $_GET['number'];
} else {
	$number = 1;
}

$number = max($MIN_SEARCH_RESULTS, min($MAX_SEARCH_RESULTS, $number));

if (isset($_GET['addRecipeNutrition']) && $_GET['addRecipeNutrition'] === 'true') {
	$addRecipeNutritionValue = 'true';
} else {
	$addRecipeNutritionValue = 'false';
}

if (isset($_GET['ingredientSearch']) && $_GET['ingredientSearch'] === 'true') {
    $ingredientSearch = true;
} else {
    $ingredientSearch = false;
}

if ($ingredientSearch) {

    $baseUrl = 'https://api.spoonacular.com/recipes/findByIngredients';
    $params = [
        'ingredients' => implode(',', preg_split('/[\s,]+/', trim($query))),
        'number' => $number,
    ];

} else {

    $params = [
        'query' => $query,
        'number' => $number,
        'addRecipeNutrition' => $addRecipeNutritionValue,
        'addRecipeInformation' => 'true',
        'fillIngredients' => 'true'
    ];

    if ($cuisine !== '') {
        $params['cuisine'] = $cuisine;
    }

    if ($diet !== '') {
        $params['diet'] = $diet;
    }

    if ($maxTime !== '') {
        $params['maxReadyTime'] = $maxTime;
    }

    if ($type !== '') {
        $params['type'] = $type;
    }

    if ($intolerances !== '') {
        $params['intolerances'] = $intolerances;
    }

    if ($sort !== '') {
        $params['sort'] = $sort;
    }
    $baseUrl = 'https://api.spoonacular.com/recipes/complexSearch';
}

$response = spoonacularRequestWithKeyRotation($baseUrl, $params);

if (!$response['success']) {
    http_response_code($response['status']);
    echo $response['body'];
    exit;
}

echo $response['body'];
