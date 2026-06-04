<?php
if (!isset($dbHandler)) {
    require_once __DIR__ . '/include-dbhandler.php';
}

$apiKeys = [];
$spoonacularApiKeyLoadError = null;

try {
    $statement = $dbHandler->prepare('SELECT `api_key_value` FROM `spoonacular_api_key` ORDER BY `api_key_value` ASC');
    $statement->execute();
    $apiKeys = $statement->fetchAll(PDO::FETCH_COLUMN);
    $statement->closeCursor();
} catch (PDOException $exception) {
    $spoonacularApiKeyLoadError = $exception->getMessage();
}

$apiKey = $apiKeys[0] ?? null;

if (!function_exists('spoonacularParseStatusCode')) {
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
}

if (!function_exists('spoonacularShouldTryNextKey')) {
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
}

if (!function_exists('spoonacularBodyShouldTryNextKey')) {
    function spoonacularBodyShouldTryNextKey(string $responseBody): bool
    {
        $responseData = json_decode($responseBody, true);

        if (!is_array($responseData)) {
            return false;
        }

        $message = strtolower((string) ($responseData['message'] ?? $responseData['error'] ?? ''));
        $status = strtolower((string) ($responseData['status'] ?? ''));

        if ($status !== 'failure' && $message === '') {
            return false;
        }

        foreach (['api key', 'unauthorized', 'quota', 'points', 'tokens', 'rate limit'] as $retryMessage) {
            if (strpos($message, $retryMessage) !== false) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('spoonacularRequestWithKeyRotation')) {
    function spoonacularRequestWithKeyRotation(string $baseUrl, array $params): array
    {
        global $apiKeys, $apiKey;

        foreach ($apiKeys as $currentApiKey) {
            $requestParams = $params;
            $requestParams['apiKey'] = $currentApiKey;

            $separator = strpos($baseUrl, '?') === false ? '?' : '&';
            $url = $baseUrl . $separator . http_build_query($requestParams);

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
                if (spoonacularBodyShouldTryNextKey($response)) {
                    continue;
                }

                $apiKey = $currentApiKey;

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
}
