<?php
declare(strict_types=1);
// <!-- Note: vibe coded ASF, do not use in production -->
$config = [];
$configPath = __DIR__ . '/config.php';

if (is_file($configPath)) {
    $config = require $configPath;
}

$apiKey = getenv('SPOONACULAR_API_KEY') ?: ($config['api_key'] ?? '');

function e(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function getString(string $key, string $default = ''): string
{
    return trim((string)($_GET[$key] ?? $default));
}

function getInt(string $key, int $default, int $min, int $max): int
{
    $value = filter_input(INPUT_GET, $key, FILTER_VALIDATE_INT);

    if ($value === false || $value === null) {
        return $default;
    }

    return max($min, min($max, $value));
}

function addIfFilled(array &$params, string $key, string $value): void
{
    $value = trim($value);

    if ($value !== '') {
        $params[$key] = $value;
    }
}

function spoonacularGet(string $path, array $params, string $apiKey): array
{
    if ($apiKey === '') {
        return [
            'ok' => false,
            'status' => 0,
            'url' => '',
            'headers' => [],
            'data' => null,
            'raw' => '',
            'error' => 'Missing API key. Put it in config.php or set SPOONACULAR_API_KEY.',
        ];
    }

    $baseUrl = 'https://api.spoonacular.com';
    $url = $baseUrl . $path;

    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $headers = [];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'x-api-key: ' . $apiKey,
        ],
        CURLOPT_HEADERFUNCTION => function ($curl, string $headerLine) use (&$headers): int {
            $length = strlen($headerLine);
            $headerLine = trim($headerLine);

            if ($headerLine !== '' && str_contains($headerLine, ':')) {
                [$name, $value] = explode(':', $headerLine, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }

            return $length;
        },
    ]);

    $raw = curl_exec($ch);
    $curlError = curl_error($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);

    curl_close($ch);

    if ($raw === false) {
        return [
            'ok' => false,
            'status' => $status,
            'url' => $url,
            'headers' => $headers,
            'data' => null,
            'raw' => '',
            'error' => $curlError ?: 'Unknown cURL error.',
        ];
    }

    $data = json_decode($raw, true);

    return [
        'ok' => $status >= 200 && $status < 300 && json_last_error() === JSON_ERROR_NONE,
        'status' => $status,
        'url' => $url,
        'headers' => $headers,
        'data' => $data,
        'raw' => $raw,
        'error' => json_last_error() === JSON_ERROR_NONE ? '' : json_last_error_msg(),
    ];
}

function nutrient(array $recipe, string $name): string
{
    $nutrients = $recipe['nutrition']['nutrients'] ?? [];

    foreach ($nutrients as $nutrient) {
        if (($nutrient['name'] ?? '') === $name) {
            return ($nutrient['amount'] ?? '') . ' ' . ($nutrient['unit'] ?? '');
        }
    }

    return '';
}

function names(array $items): string
{
    $names = [];

    foreach ($items as $item) {
        if (is_array($item) && isset($item['name'])) {
            $names[] = (string)$item['name'];
        } elseif (is_string($item)) {
            $names[] = $item;
        }
    }

    return implode(', ', $names);
}

function renderQuotaHeaders(array $headers): void
{
    echo '<h3>Quota headers</h3>';

    echo '<table border="1" cellpadding="5">';
    echo '<tr><th>Header</th><th>Value</th></tr>';

    foreach ([
        'x-api-quota-request',
        'x-api-quota-used',
        'x-api-quota-left',
    ] as $header) {
        echo '<tr>';
        echo '<td>' . e($header) . '</td>';
        echo '<td>' . e($headers[$header] ?? 'Not returned') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

function renderRecipeSearchResults(array $data): void
{
    $results = $data['results'] ?? [];

    echo '<h3>Recipe results</h3>';

    if (empty($results)) {
        echo '<p>No recipes returned.</p>';
        return;
    }

    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Image</th>';
    echo '<th>Title</th>';
    echo '<th>Ready time</th>';
    echo '<th>Servings</th>';
    echo '<th>Cuisines</th>';
    echo '<th>Diets</th>';
    echo '<th>Calories</th>';
    echo '<th>Protein</th>';
    echo '<th>Carbs</th>';
    echo '<th>Fat</th>';
    echo '</tr>';

    foreach ($results as $recipe) {
        echo '<tr>';
        echo '<td>' . e($recipe['id'] ?? '') . '</td>';

        echo '<td>';
        if (!empty($recipe['image'])) {
            echo '<img src="' . e($recipe['image']) . '" alt="" width="120">';
        }
        echo '</td>';

        echo '<td>' . e($recipe['title'] ?? '') . '</td>';
        echo '<td>' . e($recipe['readyInMinutes'] ?? '') . '</td>';
        echo '<td>' . e($recipe['servings'] ?? '') . '</td>';
        echo '<td>' . e(implode(', ', $recipe['cuisines'] ?? [])) . '</td>';
        echo '<td>' . e(implode(', ', $recipe['diets'] ?? [])) . '</td>';
        echo '<td>' . e(nutrient($recipe, 'Calories')) . '</td>';
        echo '<td>' . e(nutrient($recipe, 'Protein')) . '</td>';
        echo '<td>' . e(nutrient($recipe, 'Carbohydrates')) . '</td>';
        echo '<td>' . e(nutrient($recipe, 'Fat')) . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

function renderFridgeResults(array $data): void
{
    echo '<h3>Fridge / ingredient search results</h3>';

    if (empty($data)) {
        echo '<p>No recipes returned.</p>';
        return;
    }

    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Image</th>';
    echo '<th>Title</th>';
    echo '<th>Used ingredients</th>';
    echo '<th>Missed ingredients</th>';
    echo '<th>Unused ingredients</th>';
    echo '<th>Likes</th>';
    echo '</tr>';

    foreach ($data as $recipe) {
        echo '<tr>';
        echo '<td>' . e($recipe['id'] ?? '') . '</td>';

        echo '<td>';
        if (!empty($recipe['image'])) {
            echo '<img src="' . e($recipe['image']) . '" alt="" width="120">';
        }
        echo '</td>';

        echo '<td>' . e($recipe['title'] ?? '') . '</td>';
        echo '<td>' . e(names($recipe['usedIngredients'] ?? [])) . '</td>';
        echo '<td>' . e(names($recipe['missedIngredients'] ?? [])) . '</td>';
        echo '<td>' . e(names($recipe['unusedIngredients'] ?? [])) . '</td>';
        echo '<td>' . e($recipe['likes'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

function renderRecipeInformation(array $data): void
{
    echo '<h3>Recipe information</h3>';

    echo '<p><strong>ID:</strong> ' . e($data['id'] ?? '') . '</p>';
    echo '<p><strong>Title:</strong> ' . e($data['title'] ?? '') . '</p>';
    echo '<p><strong>Ready in minutes:</strong> ' . e($data['readyInMinutes'] ?? '') . '</p>';
    echo '<p><strong>Servings:</strong> ' . e($data['servings'] ?? '') . '</p>';
    echo '<p><strong>Source URL:</strong> ';

    if (!empty($data['sourceUrl'])) {
        echo '<a href="' . e($data['sourceUrl']) . '">' . e($data['sourceUrl']) . '</a>';
    }

    echo '</p>';

    echo '<p><strong>Cuisines:</strong> ' . e(implode(', ', $data['cuisines'] ?? [])) . '</p>';
    echo '<p><strong>Diets:</strong> ' . e(implode(', ', $data['diets'] ?? [])) . '</p>';
    echo '<p><strong>Dish types:</strong> ' . e(implode(', ', $data['dishTypes'] ?? [])) . '</p>';

    echo '<p>';
    echo '<strong>Flags:</strong> ';
    echo 'vegetarian=' . e(($data['vegetarian'] ?? false) ? 'yes' : 'no') . ', ';
    echo 'vegan=' . e(($data['vegan'] ?? false) ? 'yes' : 'no') . ', ';
    echo 'glutenFree=' . e(($data['glutenFree'] ?? false) ? 'yes' : 'no') . ', ';
    echo 'dairyFree=' . e(($data['dairyFree'] ?? false) ? 'yes' : 'no');
    echo '</p>';

    if (!empty($data['image'])) {
        echo '<p><img src="' . e($data['image']) . '" alt="" width="250"></p>';
    }

    echo '<h4>Ingredients</h4>';

    if (!empty($data['extendedIngredients'])) {
        echo '<ul>';

        foreach ($data['extendedIngredients'] as $ingredient) {
            echo '<li>';
            echo e($ingredient['original'] ?? $ingredient['name'] ?? '');
            echo '</li>';
        }

        echo '</ul>';
    } else {
        echo '<p>No ingredients returned.</p>';
    }

    echo '<h4>Instructions</h4>';

    if (!empty($data['instructions'])) {
        echo '<div>' . $data['instructions'] . '</div>';
    } else {
        echo '<p>No plain instructions returned. Try the analyzed instructions test.</p>';
    }
}

function renderAnalyzedInstructions(array $data): void
{
    echo '<h3>Analyzed instructions</h3>';

    if (empty($data)) {
        echo '<p>No analyzed instructions returned.</p>';
        return;
    }

    foreach ($data as $section) {
        echo '<h4>' . e($section['name'] ?: 'Main instructions') . '</h4>';

        echo '<table border="1" cellpadding="5">';
        echo '<tr>';
        echo '<th>Step</th>';
        echo '<th>Instruction</th>';
        echo '<th>Ingredients</th>';
        echo '<th>Equipment</th>';
        echo '<th>Time</th>';
        echo '</tr>';

        foreach ($section['steps'] ?? [] as $step) {
            $length = '';

            if (!empty($step['length'])) {
                $length = ($step['length']['number'] ?? '') . ' ' . ($step['length']['unit'] ?? '');
            }

            echo '<tr>';
            echo '<td>' . e($step['number'] ?? '') . '</td>';
            echo '<td>' . e($step['step'] ?? '') . '</td>';
            echo '<td>' . e(names($step['ingredients'] ?? [])) . '</td>';
            echo '<td>' . e(names($step['equipment'] ?? [])) . '</td>';
            echo '<td>' . e($length) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}

function renderNutrition(array $data): void
{
    echo '<h3>Nutrition</h3>';

    if (empty($data['nutrients'])) {
        echo '<p>No nutrients returned.</p>';
        return;
    }

    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>Name</th>';
    echo '<th>Amount</th>';
    echo '<th>Unit</th>';
    echo '<th>% daily needs</th>';
    echo '</tr>';

    foreach ($data['nutrients'] as $nutrient) {
        echo '<tr>';
        echo '<td>' . e($nutrient['name'] ?? '') . '</td>';
        echo '<td>' . e($nutrient['amount'] ?? '') . '</td>';
        echo '<td>' . e($nutrient['unit'] ?? '') . '</td>';
        echo '<td>' . e($nutrient['percentOfDailyNeeds'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

function renderPriceBreakdown(array $data): void
{
    echo '<h3>Price breakdown</h3>';

    echo '<p><strong>Total cost:</strong> ' . e($data['totalCost'] ?? '') . '</p>';
    echo '<p><strong>Total cost per serving:</strong> ' . e($data['totalCostPerServing'] ?? '') . '</p>';

    if (empty($data['ingredients'])) {
        echo '<p>No price ingredients returned.</p>';
        return;
    }

    echo '<table border="1" cellpadding="5">';
    echo '<tr>';
    echo '<th>Name</th>';
    echo '<th>Amount metric</th>';
    echo '<th>Amount US</th>';
    echo '<th>Price</th>';
    echo '</tr>';

    foreach ($data['ingredients'] as $ingredient) {
        $metric = $ingredient['amount']['metric'] ?? [];
        $us = $ingredient['amount']['us'] ?? [];

        echo '<tr>';
        echo '<td>' . e($ingredient['name'] ?? '') . '</td>';
        echo '<td>' . e(($metric['value'] ?? '') . ' ' . ($metric['unit'] ?? '')) . '</td>';
        echo '<td>' . e(($us['value'] ?? '') . ' ' . ($us['unit'] ?? '')) . '</td>';
        echo '<td>' . e($ingredient['price'] ?? '') . '</td>';
        echo '</tr>';
    }

    echo '</table>';
}

$test = getString('test');
$response = null;

if ($test === 'complex') {
    $params = [
        'query' => getString('query', 'pasta'),
        'number' => getInt('number', 5, 1, 10),
        'instructionsRequired' => 'true',
        'addRecipeInformation' => 'true',
        'addRecipeInstructions' => 'true',
        'addRecipeNutrition' => getString('addRecipeNutrition') === '1' ? 'true' : 'false',
    ];

    addIfFilled($params, 'cuisine', getString('cuisine'));
    addIfFilled($params, 'diet', getString('diet'));
    addIfFilled($params, 'intolerances', getString('intolerances'));
    addIfFilled($params, 'equipment', getString('equipment'));
    addIfFilled($params, 'includeIngredients', getString('includeIngredients'));
    addIfFilled($params, 'excludeIngredients', getString('excludeIngredients'));
    addIfFilled($params, 'type', getString('type'));

    $maxReadyTime = getString('maxReadyTime');
    if ($maxReadyTime !== '') {
        $params['maxReadyTime'] = getInt('maxReadyTime', 30, 1, 300);
    }

    $response = spoonacularGet('/recipes/complexSearch', $params, $apiKey);
}

if ($test === 'fridge') {
    $params = [
        'ingredients' => getString('ingredients', 'eggs,tomato,cheese'),
        'number' => getInt('number', 5, 1, 10),
        'ranking' => getInt('ranking', 1, 1, 2),
        'ignorePantry' => getString('ignorePantry') === '0' ? 'false' : 'true',
    ];

    $response = spoonacularGet('/recipes/findByIngredients', $params, $apiKey);
}

if ($test === 'info') {
    $id = getInt('id', 716429, 1, 999999999);

    $response = spoonacularGet('/recipes/' . $id . '/information', [
        'includeNutrition' => getString('includeNutrition') === '1' ? 'true' : 'false',
    ], $apiKey);
}

if ($test === 'instructions') {
    $id = getInt('id', 716429, 1, 999999999);

    $response = spoonacularGet('/recipes/' . $id . '/analyzedInstructions', [], $apiKey);
}

if ($test === 'nutrition') {
    $id = getInt('id', 716429, 1, 999999999);

    $response = spoonacularGet('/recipes/' . $id . '/nutritionWidget.json', [], $apiKey);
}

if ($test === 'price') {
    $id = getInt('id', 716429, 1, 999999999);

    $response = spoonacularGet('/recipes/' . $id . '/priceBreakdownWidget.json', [], $apiKey);
}

if ($test === 'random') {
    $params = [
        'number' => getInt('number', 1, 1, 5),
    ];

    addIfFilled($params, 'include-tags', getString('includeTags'));
    addIfFilled($params, 'exclude-tags', getString('excludeTags'));

    if (getString('includeNutrition') === '1') {
        $params['includeNutrition'] = 'true';
    }

    $response = spoonacularGet('/recipes/random', $params, $apiKey);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Spoonacular API Test</title>
</head>
<body>
    <h1>Spoonacular API Test</h1>

    <?php if ($apiKey === ''): ?>
        <p><strong>API key missing.</strong> Add it to config.php or set the SPOONACULAR_API_KEY environment variable.</p>
    <?php else: ?>
        <p><strong>API key loaded.</strong></p>
    <?php endif; ?>

    <hr>

    <h2>1. Complex recipe search</h2>
    <p>Tests: normal search, cuisine, diet, intolerances, equipment, ingredients, cooking time, instructions, nutrition.</p>

    <form method="get">
        <input type="hidden" name="test" value="complex">

        <p>
            <label>Query:
                <input type="text" name="query" value="<?= e(getString('query', 'pasta')) ?>">
            </label>
        </p>

        <p>
            <label>Cuisine:
                <input type="text" name="cuisine" value="<?= e(getString('cuisine', 'italian')) ?>">
            </label>
        </p>

        <p>
            <label>Diet:
                <input type="text" name="diet" value="<?= e(getString('diet')) ?>" placeholder="vegetarian, vegan, gluten free">
            </label>
        </p>

        <p>
            <label>Intolerances:
                <input type="text" name="intolerances" value="<?= e(getString('intolerances')) ?>" placeholder="gluten, dairy, egg, peanut">
            </label>
        </p>

        <p>
            <label>Equipment:
                <input type="text" name="equipment" value="<?= e(getString('equipment')) ?>" placeholder="pan, oven, blender">
            </label>
        </p>

        <p>
            <label>Include ingredients:
                <input type="text" name="includeIngredients" value="<?= e(getString('includeIngredients')) ?>" placeholder="tomato, cheese">
            </label>
        </p>

        <p>
            <label>Exclude ingredients:
                <input type="text" name="excludeIngredients" value="<?= e(getString('excludeIngredients')) ?>" placeholder="eggs, pork">
            </label>
        </p>

        <p>
            <label>Meal type:
                <input type="text" name="type" value="<?= e(getString('type')) ?>" placeholder="main course, dessert, breakfast">
            </label>
        </p>

        <p>
            <label>Max ready time in minutes:
                <input type="number" name="maxReadyTime" value="<?= e(getString('maxReadyTime', '30')) ?>">
            </label>
        </p>

        <p>
            <label>Number of results:
                <input type="number" name="number" value="<?= e((string)getInt('number', 5, 1, 10)) ?>">
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="addRecipeNutrition" value="1" <?= getString('addRecipeNutrition', '1') === '1' ? 'checked' : '' ?>>
                Include nutrition
            </label>
        </p>

        <button type="submit">Run complex search</button>
    </form>

    <hr>

    <h2>2. Search by ingredients / fridge test</h2>
    <p>Tests: “what can I cook with what I already have?”</p>

    <form method="get">
        <input type="hidden" name="test" value="fridge">

        <p>
            <label>Ingredients:
                <input type="text" name="ingredients" value="<?= e(getString('ingredients', 'eggs,tomato,cheese')) ?>">
            </label>
        </p>

        <p>
            <label>Number of results:
                <input type="number" name="number" value="<?= e((string)getInt('number', 5, 1, 10)) ?>">
            </label>
        </p>

        <p>
            <label>Ranking:
                <select name="ranking">
                    <option value="1" <?= getString('ranking', '1') === '1' ? 'selected' : '' ?>>Maximize used ingredients</option>
                    <option value="2" <?= getString('ranking') === '2' ? 'selected' : '' ?>>Minimize missing ingredients</option>
                </select>
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="ignorePantry" value="1" <?= getString('ignorePantry', '1') === '1' ? 'checked' : '' ?>>
                Ignore pantry basics
            </label>
        </p>

        <button type="submit">Run fridge search</button>
    </form>

    <hr>

    <h2>3. Recipe information by ID</h2>
    <p>Tests: full recipe details, servings, ready time, ingredients, cuisines, diets, source URL.</p>

    <form method="get">
        <input type="hidden" name="test" value="info">

        <p>
            <label>Recipe ID:
                <input type="number" name="id" value="<?= e(getString('id', '716429')) ?>">
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="includeNutrition" value="1" <?= getString('includeNutrition') === '1' ? 'checked' : '' ?>>
                Include nutrition
            </label>
        </p>

        <button type="submit">Get recipe information</button>
    </form>

    <hr>

    <h2>4. Analyzed instructions by ID</h2>
    <p>Tests: step-by-step mode, equipment per step, ingredients per step, timers where available.</p>

    <form method="get">
        <input type="hidden" name="test" value="instructions">

        <p>
            <label>Recipe ID:
                <input type="number" name="id" value="<?= e(getString('id', '716429')) ?>">
            </label>
        </p>

        <button type="submit">Get analyzed instructions</button>
    </form>

    <hr>

    <h2>5. Nutrition by ID</h2>
    <p>Tests: calories, macros, micronutrients.</p>

    <form method="get">
        <input type="hidden" name="test" value="nutrition">

        <p>
            <label>Recipe ID:
                <input type="number" name="id" value="<?= e(getString('id', '716429')) ?>">
            </label>
        </p>

        <button type="submit">Get nutrition</button>
    </form>

    <hr>

    <h2>6. Price breakdown by ID</h2>
    <p>Tests: estimated recipe cost and cost per serving.</p>

    <form method="get">
        <input type="hidden" name="test" value="price">

        <p>
            <label>Recipe ID:
                <input type="number" name="id" value="<?= e(getString('id', '716429')) ?>">
            </label>
        </p>

        <button type="submit">Get price breakdown</button>
    </form>

    <hr>

    <h2>7. Random recipe</h2>
    <p>Tests: discovery/random recipe generation with optional tags.</p>

    <form method="get">
        <input type="hidden" name="test" value="random">

        <p>
            <label>Include tags:
                <input type="text" name="includeTags" value="<?= e(getString('includeTags', 'vegetarian')) ?>" placeholder="vegetarian,dessert,italian">
            </label>
        </p>

        <p>
            <label>Exclude tags:
                <input type="text" name="excludeTags" value="<?= e(getString('excludeTags')) ?>" placeholder="dairy,quinoa">
            </label>
        </p>

        <p>
            <label>Number:
                <input type="number" name="number" value="<?= e((string)getInt('number', 1, 1, 5)) ?>">
            </label>
        </p>

        <p>
            <label>
                <input type="checkbox" name="includeNutrition" value="1" <?= getString('includeNutrition') === '1' ? 'checked' : '' ?>>
                Include nutrition
            </label>
        </p>

        <button type="submit">Get random recipe</button>
    </form>

    <hr>

    <?php if ($response !== null): ?>
        <h2>API result</h2>

        <p><strong>HTTP status:</strong> <?= e($response['status']) ?></p>
        <p><strong>Request URL:</strong> <?= e($response['url']) ?></p>

        <?php renderQuotaHeaders($response['headers']); ?>

        <?php if (!$response['ok']): ?>
            <h3>Error</h3>
            <p><?= e($response['error']) ?></p>

            <h3>Raw response</h3>
            <pre><?= e($response['raw']) ?></pre>
        <?php else: ?>
            <?php
                $data = $response['data'];

                if ($test === 'complex') {
                    renderRecipeSearchResults($data);
                } elseif ($test === 'fridge') {
                    renderFridgeResults($data);
                } elseif ($test === 'info') {
                    renderRecipeInformation($data);
                } elseif ($test === 'instructions') {
                    renderAnalyzedInstructions($data);
                } elseif ($test === 'nutrition') {
                    renderNutrition($data);
                } elseif ($test === 'price') {
                    renderPriceBreakdown($data);
                } elseif ($test === 'random') {
                    renderRecipeSearchResults(['results' => $data['recipes'] ?? []]);
                }
            ?>

            <h3>Raw JSON</h3>
            <pre><?= e(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre>
        <?php endif; ?>
    <?php endif; ?>
</body>
</html>