<?php

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


$url = 'https://api.spoonacular.com/recipes/complexSearch?' . http_build_query([
    'query' => $query,
    'number' => $number,
    'addRecipeNutrition' => $addRecipeNutritionValue,
    'apiKey' => $apiKey,
]);

$response = file_get_contents($url);

if ($response === false) {
    http_response_code(502);
    echo json_encode([
        'error' => 'Failed to fetch from Spoonacular'
    ]);
    exit;
}

echo $response;