<?php

require_once __DIR__ . '/include-loginrequired.php';

// Load the Spotify application credentials used to refresh expired tokens.
$config = require __DIR__ . '/config.php';
$clientId = $config['SPOTIFY_CLIENT_ID'] ?? null;
$clientSecret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

// Spotify tokens are stored only in the current PHP session.
$accessToken = $_SESSION['access_token'] ?? null;
$refreshToken = $_SESSION['refresh_token'] ?? null;
$expiresAt = $_SESSION['spotify_token_expires'] ?? 0;

if (!$accessToken)
{
    die('Connect Spotify from your profile before starting a recipe.');
}

// Refresh the access token before making playback requests when it has expired.
if ($expiresAt <= time())
{
    if (!$refreshToken || !$clientId || !$clientSecret)
    {
        die('Your Spotify connection has expired. Please reconnect Spotify.');
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
        die('Your Spotify connection could not be refreshed. Please reconnect Spotify.');
    }

    $accessToken = $refreshResult['access_token'];
    $_SESSION['access_token'] = $accessToken;
    $_SESSION['refresh_token'] = $refreshResult['refresh_token'] ?? $refreshToken;
    $_SESSION['spotify_token_expires'] = time() + ($refreshResult['expires_in'] ?? 3600);
}

// Build a playlist search based on the recipe cuisine.
$cuisine = trim($_GET['cuisine'] ?? 'cooking');
$searchQuery = ($cuisine ?: 'cooking') . ' top hits';

$headers = [
    "Authorization: Bearer $accessToken",
    "Content-Type: application/json"
];

// Search several results so an actual top-hits playlist can be selected.
$searchUrl = 'https://api.spotify.com/v1/search?' . http_build_query([
    'q' => $searchQuery,
    'type' => 'playlist',
    'limit' => 10
]);

$curlHandle = curl_init($searchUrl);
curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
$searchResponse = curl_exec($curlHandle);
$searchCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

if (curl_errno($curlHandle))
{
    die('cURL error (search): ' . curl_error($curlHandle));
}

if ($searchCode !== 200)
{
    die("Spotify search error (HTTP $searchCode): " . $searchResponse);
}

$searchResult = json_decode($searchResponse, true);
$playlists = $searchResult['playlists']['items'] ?? [];
$playlist = null;

// Ignore unrelated search results whose names do not contain "top" or "hits".
foreach ($playlists as $candidate)
{
    $playlistName = $candidate['name'] ?? '';

    if (
        !empty($candidate['uri'])
        && (stripos($playlistName, 'top') !== false || stripos($playlistName, 'hits') !== false)
    )
    {
        $playlist = $candidate;
        break;
    }
}

if (!$playlist)
{
    die('No top-hits playlist found for ' . htmlspecialchars($cuisine) . ' cuisine.');
}

$playlistUri = $playlist['uri'] ?? null;
if (!$playlistUri)
{
    die('Failed to resolve playlist URI from Spotify search result.');
}

// Find the user's available Spotify Connect devices.
$devicesUrl = 'https://api.spotify.com/v1/me/player/devices';
$curlHandle = curl_init($devicesUrl);
curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
$devicesResponse = curl_exec($curlHandle);
$devicesCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

if (curl_errno($curlHandle))
{
    die('cURL error (devices): ' . curl_error($curlHandle));
}

if ($devicesCode !== 200)
{
    die("Spotify devices error (HTTP $devicesCode): " . $devicesResponse);
}

$devicesResult = json_decode($devicesResponse, true);
$devices = $devicesResult['devices'] ?? [];
if (empty($devices))
{
    die('No Spotify devices found. Open Spotify on your phone or laptop and try again.');
}

// Prefer the active device and otherwise use the first available device.
$deviceId = null;
foreach ($devices as $device)
{
    if (!empty($device['is_active']))
    {
        $deviceId = $device['id'];
        break;
    }
}

if (!$deviceId)
{
    $deviceId = $devices[0]['id'] ?? null;
}

if (!$deviceId)
{
    die('No playable Spotify device ID found.');
}

// Start the selected playlist on the chosen Spotify device.
$playUrl = 'https://api.spotify.com/v1/me/player/play?' . http_build_query(['device_id' => $deviceId]);
$body = json_encode(['context_uri' => $playlistUri]);

$curlHandle = curl_init($playUrl);
curl_setopt($curlHandle, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $body);
curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curlHandle);
$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

if (curl_errno($curlHandle))
{
    die('cURL error (play): ' . curl_error($curlHandle));
}

if ($httpCode !== 204)
{
    die("Spotify API error (HTTP $httpCode): " . $response . ". Open Spotify on a device and make sure it is available for playback.");
}

echo 'Playing Spotify music for ' . htmlspecialchars($cuisine) . ' cuisine.';

?>
