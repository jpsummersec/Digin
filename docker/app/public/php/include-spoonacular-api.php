<?php

require_once __DIR__ . '/include-url-config.php';

$apiKeys = [];

// Load every configured API key so requests can continue when one key reaches
// its quota or temporarily fails.
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

// Extract the HTTP status code from the first response header.
function spoonacularParseStatusCode(array $headers): int
{
    if (!isset($headers[0]))
    {
        return 0;
    }

    if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $headers[0], $matches))
    {
        return (int) $matches[1];
    }

    return 0;
}

// Decide whether a failed request should be retried with the next API key.
function spoonacularShouldTryNextKey(int $statusCode, bool $networkFailure): bool
{
    if ($networkFailure)
    {
        return true;
    }

    if (in_array($statusCode, [401, 402, 403, 429], true))
    {
        return true;
    }

    return $statusCode >= 500;
}

function spoonacularRequestWithKeyRotation(string $baseUrl, array $parameters): array
{
    global $apiKeys;

    // Try each key until a request succeeds or returns a non-retryable error.
    foreach ($apiKeys as $apiKey)
    {
        $requestParameters = $parameters;
        $requestParameters['apiKey'] = $apiKey;

        $url = $baseUrl . '?' . http_build_query($requestParameters);

        $httpOptions = [];
        $httpOptions['ignore_errors'] = true;
        $httpOptions['timeout'] = 10;

        $contextOptions = [];
        $contextOptions['http'] = $httpOptions;

        $context = stream_context_create($contextOptions);

        $response = @file_get_contents($url, false, $context);
        $lastResponseHeaders = http_get_last_response_headers();
        if (is_array($lastResponseHeaders))
        {
            $responseHeaders = $lastResponseHeaders;
        }
        else
        {
            $responseHeaders = [];
        }

        $statusCode = spoonacularParseStatusCode($responseHeaders);
        $networkFailure = $response === false;

        if (!$networkFailure && $statusCode >= 200 && $statusCode < 300)
        {
            $successResult = [];
            $successResult['success'] = true;
            $successResult['status'] = $statusCode;
            $successResult['body'] = $response;

            return $successResult;
        }

        if (!spoonacularShouldTryNextKey($statusCode, $networkFailure))
        {
            if ($statusCode > 0)
            {
                $failedStatusCode = $statusCode;
            }
            else
            {
                $failedStatusCode = 502;
            }

            if ($response !== false)
            {
                $failedBody = $response;
            }
            else
            {
                $errorBody = [];
                $errorBody['error'] = 'Failed to fetch from Spoonacular';
                $failedBody = json_encode($errorBody);
            }

            $failedResult = [];
            $failedResult['success'] = false;
            $failedResult['status'] = $failedStatusCode;
            $failedResult['body'] = $failedBody;

            return $failedResult;
        }
    }

    $errorBody = [];
    $errorBody['error'] = 'Failed to fetch from Spoonacular';

    $failedResult = [];
    $failedResult['success'] = false;
    $failedResult['status'] = 502;
    $failedResult['body'] = json_encode($errorBody);

    return $failedResult;
}
