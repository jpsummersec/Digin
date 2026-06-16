<?php

require_once __DIR__ . '/include-url-config.php';

$apiKeys = [];

// Get all API keys from database into apiKeys array
try
{
    $statement = $dbHandler->prepare('SELECT `api_key_value` FROM `spoonacular_api_key`');
    $statement->execute();

    $apiKeys = $statement->fetchAll(PDO::FETCH_COLUMN);
    $statement->closeCursor();
}
catch (PDOException $exception)
{
    $apiKeys = [];
}

function spoonacularRequestWithKeyRotation(string $baseUrl, array $parameters): array
{
    global $apiKeys;

    foreach ($apiKeys as $apiKey)
    {
        $requestParameters = $parameters;
        // Add the API key into the requestParamaters array.
        $requestParameters['apiKey'] = $apiKey;

        $url = $baseUrl . '?' . http_build_query($requestParameters);

        // Configure a timeout of 10 seconds.
        $context = stream_context_create([
            'http' => [
                'timeout' => 10
            ]
        ]);

        // Actual API request.
        $response = @file_get_contents($url, false, $context);

        if ($response !== false)
        {
            return [
                'success' => true,
                'status' => 200,
                'body' => $response
            ];
        }
    }

    // If none of the keys worked...
    $errorBody = [];
    $errorBody['error'] = 'Failed to fetch from Spoonacular';

    return [
        'success' => false,
        'status' => 502,
        'body' => json_encode($errorBody)
    ];
}
