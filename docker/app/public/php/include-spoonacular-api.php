<?php

$apiKeys = [];

// put all API keys in array
try {
    $statement = $dbHandler->prepare('SELECT `api_key_value` FROM `spoonacular_api_key`');
    $statement->execute();
    $apiKeys = $statement->fetchAll(PDO::FETCH_COLUMN);
    $statement->closeCursor();
} catch (PDOException $exception) {
    $apiKeys = [];
}

// ensure it returns 200
function spoonacularParseStatusCode(array $headers): int
{
    if (!isset($headers[0])) {
        return 0;
    }

    if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $headers[0], $matches)) {
        return (int) $matches[1];
    }

    return 0;
}

// decide if next key should be tried
function spoonacularShouldTryNextKey(int $statusCode, bool $networkFailure): bool
{
    if ($networkFailure) {
        return true;
    }

    if (in_array($statusCode, [401, 402, 403, 429], true)) {
        return true;
    }

    return $statusCode >= 500;
}

function spoonacularRequestWithKeyRotation(string $baseUrl, array $params): array
{
    global $apiKeys;

    foreach ($apiKeys as $apiKey) {
        $requestParams = $params;
        $requestParams['apiKey'] = $apiKey;

        $url = $baseUrl . '?' . http_build_query($requestParams);

        $context = stream_context_create([
            'http' => [
                'ignore_errors' => true,
                'timeout' => 10,
            ],
        ]);

        $response = @file_get_contents($url, false, $context);
        $statusCode = spoonacularParseStatusCode($http_response_header ?? []);
        $networkFailure = $response === false;

        if (!$networkFailure && $statusCode >= 200 && $statusCode < 300) {
            return [
                'success' => true,
                'status' => $statusCode,
                'body' => $response,
            ];
        }

        if (!spoonacularShouldTryNextKey($statusCode, $networkFailure)) {
            return [
                'success' => false,
                'status' => $statusCode > 0 ? $statusCode : 502,
                'body' => $response !== false ? $response : json_encode([
                    'error' => 'Failed to fetch from Spoonacular',
                ]),
            ];
        }
    }

    return [
        'success' => false,
        'status' => 502,
        'body' => json_encode([
            'error' => 'Failed to fetch from Spoonacular',
        ]),
    ];
}
