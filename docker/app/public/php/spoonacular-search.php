<?php
require_once __DIR__ . '/include-loginrequired.php';
require_once __DIR__ . '/include-dbhandler.php';
require_once __DIR__ . '/include-spoonacular-api.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$db = $dbHandler;

if (empty($_GET['query'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing search query']);
    exit;
}

$query = trim($_GET['query']);

if (empty($apiKeys)) {
    http_response_code(500);
    echo json_encode(['error' => 'Missing API keys']);
    exit;
}

$cuisine = $_GET['cuisine'] ?? '';
$maxTime = $_GET['maxTime'] ?? ($_GET['maxReadyTime'] ?? '');
$type = $_GET['type'] ?? '';
$intolerances = $_GET['intolerances'] ?? '';
$sort = $_GET['sort'] ?? '';
$diet = $_GET['diet'] ?? '';
$sortDirection = $_GET['sortDirection'] ?? 'asc';

$number = isset($_GET['number']) ? (int)$_GET['number'] : 1;
$number = max(0, min(10, $number));

$addRecipeNutritionValue = (!empty($_GET['addRecipeNutrition']) && $_GET['addRecipeNutrition'] === 'true') ? 'true' : 'false';
$ingredientSearch = (!empty($_GET['ingredientSearch']) && $_GET['ingredientSearch'] === 'true');

function sortRecipes(array $results, string $sort, string $direction = 'asc'): array
{
    if ($sort === '') return $results;

    $dir = ($direction === 'desc') ? -1 : 1;

    usort($results, function ($a, $b) use ($sort, $dir) {

        switch ($sort) {
            case 'readyInMinutes':
                $aVal = $a['readyInMinutes'] ?? PHP_INT_MAX;
                $bVal = $b['readyInMinutes'] ?? PHP_INT_MAX;
                break;

            case 'healthScore':
                $aVal = $a['healthScore'] ?? 0;
                $bVal = $b['healthScore'] ?? 0;
                break;

            case 'spoonacularScore':
                $aVal = $a['spoonacularScore'] ?? 0;
                $bVal = $b['spoonacularScore'] ?? 0;
                break;

            case 'popularity':
                $aVal = $a['popularity'] ?? 0;
                $bVal = $b['popularity'] ?? 0;
                break;
            case 'likes':
                $aVal = $a['likes'] ?? 0;
                $bVal = $b['likes'] ?? 0;
                break;

            case 'price':
                $aVal = $a['pricePerServing'] ?? PHP_INT_MAX;
                $bVal = $b['pricePerServing'] ?? PHP_INT_MAX;
                break;

            case 'calories':
                $aVal = $a['nutrition']['nutrients'][0]['amount'] ?? PHP_INT_MAX;
                $bVal = $b['nutrition']['nutrients'][0]['amount'] ?? PHP_INT_MAX;
                break;

            default:
                return 0;
        }

        return ($aVal <=> $bVal) * $dir;
    });

    return $results;
}

$cacheKey = [
    'query' => strtolower(trim($query)),
    'number' => $number,
    'ingredientSearch' => $ingredientSearch,
    'cuisine' => $cuisine,
    'diet' => $diet,
    'maxTime' => $maxTime,
    'type' => $type,
    'intolerances' => $intolerances,
    'sort' => $sort,
    'sortDirection' => $sortDirection,
    'addRecipeNutrition' => $addRecipeNutritionValue
];

$keyString = json_encode($cacheKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$hash = md5($keyString);

$stmt = $db->prepare("SELECT search_id, search_parameter_string FROM cached_search");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$searchId = null;

foreach ($rows as $row) {
    $stored = json_decode($row['search_parameter_string'], true);
    if (!$stored) continue;

    $storedHash = md5(json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($storedHash === $hash) {
        $searchId = $row['search_id'];
        break;
    }
}

if ($searchId) {

    $stmt = $db->prepare("
        SELECT recipe_id
        FROM cached_search_results
        WHERE search_id = ?
    ");

    $stmt->execute([$searchId]);
    $recipeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($recipeIds)) {

        $placeholders = implode(',', array_fill(0, count($recipeIds), '?'));

        $stmt = $db->prepare("
            SELECT recipe_json
            FROM recipe
            WHERE recipe_id IN ($placeholders)
        ");

        $stmt->execute($recipeIds);

        $results = array_map(
            fn($row) => json_decode($row, true),
            $stmt->fetchAll(PDO::FETCH_COLUMN)
        );

        $results = array_values($results);
        $results = sortRecipes($results, $sort, $sortDirection);

        echo json_encode([
            'source' => 'cache',
            'results' => $results
        ]);
        exit;
    }
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
        'addRecipeInstructions' => 'true',
        'addRecipeInformation' => 'true',
        'fillIngredients' => 'true'
    ];

    if ($cuisine) $params['cuisine'] = $cuisine;
    if ($diet) $params['diet'] = $diet;
    if ($maxTime) $params['maxReadyTime'] = $maxTime;
    if ($type) $params['type'] = $type;
    if ($intolerances) $params['intolerances'] = $intolerances;

    $baseUrl = 'https://api.spoonacular.com/recipes/complexSearch';
}

$response = spoonacularRequestWithKeyRotation($baseUrl, $params);

if (!$response['success']) {
    http_response_code($response['status']);
    echo $response['body'];
    exit;
}

$data = json_decode($response['body'], true);

$results = $ingredientSearch
    ? $data
    : ($data['results'] ?? []);

$results = sortRecipes($results, $sort, $sortDirection);

if (!empty($results)) {

    try {

        $stmt = $db->prepare("
            INSERT INTO cached_search (search_parameter_string)
            VALUES (?)
        ");

        $stmt->execute([$keyString]);
        $searchId = $db->lastInsertId();

    } catch (PDOException $e) {

        $stmt = $db->prepare("
            SELECT search_id
            FROM cached_search
            WHERE search_parameter_string = ?
            LIMIT 1
        ");

        $stmt->execute([$keyString]);
        $searchId = $stmt->fetchColumn();
    }

    if ($searchId) {

        foreach ($results as $recipe) {

            if (!isset($recipe['id'])) continue;

            $stmt = $db->prepare("
                INSERT INTO recipe (recipe_id, recipe_json)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE recipe_json = VALUES(recipe_json)
            ");

            $stmt->execute([
                $recipe['id'],
                json_encode($recipe)
            ]);

            $stmt = $db->prepare("
                INSERT INTO cached_search_results (search_id, recipe_id)
                VALUES (?, ?)
            ");

            $stmt->execute([
                $searchId,
                $recipe['id']
            ]);
        }
    }
}

echo json_encode([
    'source' => 'api',
    'results' => $results
]);