<?php

require_once __DIR__ . '/include-loginrequired.php';

$config = require __DIR__ . '/config.php';
$clientId = $config['SPOTIFY_CLIENT_ID'] ?? null;
$clientSecret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

$accessToken = $_SESSION['access_token'] ?? null;
$refreshToken = $_SESSION['refresh_token'] ?? null;
$expiresAt = $_SESSION['spotify_token_expires'] ?? 0;

if (!$accessToken)
{
    die('No Spotify access token. Connect Spotify first.');
}

if ($expiresAt <= time())
{
    if (!$refreshToken || !$clientId || !$clientSecret)
    {
        die('Spotify token expired. Reconnect Spotify.');
    }

    $curlHandle = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($curlHandle, CURLOPT_POST, true);
    curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refreshToken,
    ]));
    curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("$clientId:$clientSecret"),
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

    $refreshResponse = curl_exec($curlHandle);
    $refreshCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
    $refreshResult = json_decode($refreshResponse, true);

    if ($refreshCode !== 200 || empty($refreshResult['access_token']))
    {
        die('Could not refresh Spotify token.');
    }

    $accessToken = $refreshResult['access_token'];
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['refresh_token'] = $refreshResult['refresh_token'] ?? $refreshToken;
    $_SESSION['spotify_token_expires'] = time() + ($refreshResult['expires_in'] ?? 3600);
}

if (array_key_exists('volume', $_GET))
{
    $volume = (int) $_GET['volume'];
}
else
{
    $volume = 80;
}

$volume = max(0, min(100, $volume));

$url = 'https://api.spotify.com/v1/me/player/volume?' . http_build_query([
    'volume_percent' => $volume
]);

$curlHandle = curl_init($url);
curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($curlHandle, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $accessToken",
    "Content-Type: application/json"
]);
curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curlHandle);
$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

if ($httpCode !== 204)
{
    die("Spotify volume error HTTP $httpCode: " . $response);
}

echo "Spotify volume set to $volume";
