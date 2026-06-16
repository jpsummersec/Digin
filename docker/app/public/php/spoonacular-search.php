<?php

require_once __DIR__ . '/include-loginrequired.php';
require_once __DIR__ . '/include-dbhandler.php';
require_once __DIR__ . '/include-spoonacular-api.php';

header('Content-Type: application/json');

if (empty($_GET['query']))
{
    http_response_code(400);
    echo json_encode(['error' => 'Missing search query']);
    exit;
}

$query = trim($_GET['query']);

if (empty($apiKeys))
{
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
$minCalories = normalizeCalorieBound($_GET['minCalories'] ?? '');
$maxCalories = normalizeCalorieBound($_GET['maxCalories'] ?? '');

if ($minCalories !== '' && $maxCalories !== '' && (int) $minCalories > (int) $maxCalories)
{
    [$minCalories, $maxCalories] = [$maxCalories, $minCalories];
}

$number = isset($_GET['number']) ? (int) $_GET['number'] : 1;
$number = max(0, min(10, $number));

$addRecipeNutritionValue = (!empty($_GET['addRecipeNutrition']) && $_GET['addRecipeNutrition'] === 'true') ? 'true' : 'false';
$ingredientSearch = (!empty($_GET['ingredientSearch']) && $_GET['ingredientSearch'] === 'true');

// Sort cached and API results using the values exposed by the search page.
function sortRecipes(array $results, string $sort, string $direction = 'asc'): array
{
    if ($sort === '')
    {
        return $results;
    }

    $directionMultiplier = ($direction === 'desc') ? -1 : 1;

    usort($results, function ($firstRecipe, $secondRecipe) use ($sort, $directionMultiplier)
    {
        switch ($sort)
        {
            case 'readyInMinutes':
                $firstValue = $firstRecipe['readyInMinutes'] ?? PHP_INT_MAX;
                $secondValue = $secondRecipe['readyInMinutes'] ?? PHP_INT_MAX;
                break;

            case 'healthScore':
                $firstValue = $firstRecipe['healthScore'] ?? 0;
                $secondValue = $secondRecipe['healthScore'] ?? 0;
                break;

            case 'spoonacularScore':
                $firstValue = $firstRecipe['spoonacularScore'] ?? 0;
                $secondValue = $secondRecipe['spoonacularScore'] ?? 0;
                break;

            case 'popularity':
                $firstValue = $firstRecipe['popularity'] ?? 0;
                $secondValue = $secondRecipe['popularity'] ?? 0;
                break;

            case 'likes':
                $firstValue = $firstRecipe['likes'] ?? 0;
                $secondValue = $secondRecipe['likes'] ?? 0;
                break;

            case 'calories':
                $firstValue = $firstRecipe['nutrition']['nutrients'][0]['amount'] ?? PHP_INT_MAX;
                $secondValue = $secondRecipe['nutrition']['nutrients'][0]['amount'] ?? PHP_INT_MAX;
                break;

            default:
                return 0;
        }

        return ($firstValue <=> $secondValue) * $directionMultiplier;
    });

    return $results;
}

function normalizeCalorieBound($value): string
{
    if ($value === '' || !is_numeric($value))
    {
        return '';
    }

    $calories = (int) $value;

    if ($calories < 50 || $calories > 800)
    {
        return '';
    }

    return (string)$calories;
}

// Use all active search options to identify an equivalent cached search.
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

if ($minCalories !== '')
{
    $cacheKey['minCalories'] = $minCalories;
}

if ($maxCalories !== '')
{
    $cacheKey['maxCalories'] = $maxCalories;
}

$keyString = json_encode($cacheKey, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$cacheHash = md5($keyString);

$statement = $dbHandler->prepare('SELECT search_id, search_parameter_string FROM cached_search');
$statement->execute();
$rows = $statement->fetchAll(PDO::FETCH_ASSOC);

$searchId = null;

foreach ($rows as $row)
{
    $stored = json_decode($row['search_parameter_string'], true);
    if (!$stored)
    {
        continue;
    }

    $storedHash = md5(json_encode($stored, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    if ($storedHash === $cacheHash)
    {
        $searchId = $row['search_id'];
        break;
    }
}

// Return cached recipes before making a Spoonacular request.
if ($searchId)
{
    $statement = $dbHandler->prepare("
        SELECT recipe_id
        FROM cached_search_results
        WHERE search_id = ?
    ");

    $statement->execute([$searchId]);
    $recipeIds = $statement->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($recipeIds))
    {
        $placeholders = implode(',', array_fill(0, count($recipeIds), '?'));

        $statement = $dbHandler->prepare("
            SELECT recipe_json
            FROM recipe
            WHERE recipe_id IN ($placeholders)
        ");

        $statement->execute($recipeIds);

        $results = array_map(
            fn($row) => json_decode($row, true),
            $statement->fetchAll(PDO::FETCH_COLUMN)
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

// Ingredient searches and normal recipe searches use different endpoints.
if ($ingredientSearch)
{
    $baseUrl = 'https://api.spoonacular.com/recipes/findByIngredients';

    $parameters = [
        'ingredients' => implode(',', preg_split('/[\s,]+/', trim($query))),
        'number' => $number,
    ];
}
else
{
    $parameters = [
        'query' => $query,
        'number' => $number,
        'addRecipeNutrition' => $addRecipeNutritionValue,
        'addRecipeInstructions' => 'true',
        'addRecipeInformation' => 'true',
        'fillIngredients' => 'true'
    ];

    if ($cuisine)
    {
        $parameters['cuisine'] = $cuisine;
    }

    if ($diet)
    {
        $parameters['diet'] = $diet;
    }

    if ($maxTime)
    {
        $parameters['maxReadyTime'] = $maxTime;
    }

    if ($type)
    {
        $parameters['type'] = $type;
    }

    if ($intolerances)
    {
        $parameters['intolerances'] = $intolerances;
    }

    if ($minCalories !== '')
    {
        $parameters['minCalories'] = $minCalories;
    }

    if ($maxCalories !== '')
    {
        $parameters['maxCalories'] = $maxCalories;
    }

    $baseUrl = 'https://api.spoonacular.com/recipes/complexSearch';
}

$response = spoonacularRequestWithKeyRotation($baseUrl, $parameters);

if (!$response['success'])
{
    http_response_code($response['status']);
    echo $response['body'];
    exit;
}

$data = json_decode($response['body'], true);

$results = $ingredientSearch
    ? $data
    : ($data['results'] ?? []);

$results = sortRecipes($results, $sort, $sortDirection);

// Cache successful search results and their recipe data for future requests.
if (!empty($results))
{
    try
    {
        $statement = $dbHandler->prepare("
            INSERT INTO cached_search (search_parameter_string)
            VALUES (?)
        ");

        $statement->execute([$keyString]);
        $searchId = $dbHandler->lastInsertId();

    }
    catch (PDOException $exception)
    {
        $statement = $dbHandler->prepare("
            SELECT search_id
            FROM cached_search
            WHERE search_parameter_string = ?
            LIMIT 1
        ");

        $statement->execute([$keyString]);
        $searchId = $statement->fetchColumn();
    }

    if ($searchId)
    {
        foreach ($results as $recipe)
        {
            if (!isset($recipe['id']))
            {
                continue;
            }

            $statement = $dbHandler->prepare("
                INSERT INTO recipe (recipe_id, recipe_json)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE recipe_json = VALUES(recipe_json)
            ");

            $statement->execute([
                $recipe['id'],
                json_encode($recipe)
            ]);

            $statement = $dbHandler->prepare("
                INSERT INTO cached_search_results (search_id, recipe_id)
                VALUES (?, ?)
            ");

            $statement->execute([
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
