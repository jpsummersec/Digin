<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// tells header that JSON content is being returned
header('Content-Type: application/json');

$config = [];
$configPath = __DIR__ . '/config.php';
$MIN_SEARCH_RESULTS = 0;
$MAX_SEARCH_RESULTS = 10;

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

if (isset($_GET['query'])) {
    $query = trim($_GET['query']);
} else {
	http_response_code(400);
    echo json_encode([
        'error' => 'Missing search query'
    ]);
    exit;
}

$cuisine = $_GET['cuisine'] ?? '';
$maxTime = $_GET['maxTime'] ?? '';
$type = $_GET['type'] ?? '';
$intolerances = $_GET['intolerances'] ?? '';
$sort = $_GET['sort'] ?? '';

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

    $url = 'https://api.spoonacular.com/recipes/findByIngredients?' . http_build_query([
        'ingredients' => implode(',', preg_split('/[\s,]+/', trim($query))),
        'number' => $number,
        'apiKey' => $apiKey,
    ]);

} else {

    $params = [
        'query' => $query,
        'number' => $number,
        'apiKey' => $apiKey,
        'addRecipeNutrition' => $addRecipeNutritionValue
    ];

    if ($cuisine !== '') {
        $params['cuisine'] = $cuisine;
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
        $params['sort'] = 'popularity';
    }
    $url = 'https://api.spoonacular.com/recipes/complexSearch?' . http_build_query($params);
}

$response = file_get_contents($url);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Failed to fetch from Spoonacular'
    ]);
    exit;
}

echo $response;