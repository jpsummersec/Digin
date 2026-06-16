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

// Get the HTTP status code from the first response header with regex.
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

    // Spoonacular-side issues.
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

        // Build the full Spoonacular request URL.
        $url = $baseUrl . '?' . http_build_query($requestParameters);

        $httpOptions = [];
        $httpOptions['ignore_errors'] = true;
        $httpOptions['timeout'] = 10;

        $contextOptions = [];
        $contextOptions['http'] = $httpOptions;

        $context = stream_context_create($contextOptions);

        // Suppress PHP warnings here because failures are handled below
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

        // A 2xx response means the request worked. Return the raw response body
        if (!$networkFailure && $statusCode >= 200 && $statusCode < 300)
        {
            $successResult = [];
            $successResult['success'] = true;
            $successResult['status'] = $statusCode;
            $successResult['body'] = $response;

            return $successResult;
        }

        // Stop immediately for errors that are not likely to be fixed by using another API key
        if (!spoonacularShouldTryNextKey($statusCode, $networkFailure))
        {
            // If no HTTP status was captured, use 502 to indicate an issue.
            if ($statusCode > 0)
            {
                $failedStatusCode = $statusCode;
            }
            else
            {
                $failedStatusCode = 502;
            }

            // Preserve Spoonacular's error body when it was returned. Otherwise
            // create a small JSON error response.
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

    // If every key failed with a retryable error, return one generic failure response after the loop.
    $errorBody = [];
    $errorBody['error'] = 'Failed to fetch from Spoonacular';

    $failedResult = [];
    $failedResult['success'] = false;
    $failedResult['status'] = 502;
    $failedResult['body'] = json_encode($errorBody);

    return $failedResult;
}
