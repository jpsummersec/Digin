<?php

require_once __DIR__ . '/include-loginrequired.php';

// Load the Spotify app credentials used when an expired token must be refreshed.
$config = require __DIR__ . '/../test/php/config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;
$client_secret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

// Spotify tokens are stored only in the current PHP session.
$access_token = $_SESSION['access_token'] ?? null;
$refresh_token = $_SESSION['refresh_token'] ?? null;
$expires_at = $_SESSION['spotify_token_expires'] ?? 0;

if (!$access_token) {
    die('Connect Spotify from your profile before starting a recipe.');
}

// Refresh the access token when it has expired, then keep the new token in the session.
if ($expires_at <= time()) {
    if (!$refresh_token || !$client_id || !$client_secret) {
        die('Your Spotify connection has expired. Please reconnect Spotify.');
    }

    $ch = curl_init('https://accounts.spotify.com/api/token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . base64_encode("$client_id:$client_secret"),
        'Content-Type: application/x-www-form-urlencoded',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $refresh_response = curl_exec($ch);
    $refresh_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $refresh_result = json_decode($refresh_response, true);

    if ($refresh_code !== 200 || empty($refresh_result['access_token'])) {
        die('Your Spotify connection could not be refreshed. Please reconnect Spotify.');
    }

    $access_token = $refresh_result['access_token'];
    $_SESSION['access_token'] = $access_token;
    $_SESSION['refresh_token'] = $refresh_result['refresh_token'] ?? $refresh_token;
    $_SESSION['spotify_token_expires'] = time() + ($refresh_result['expires_in'] ?? 3600);
}

// Build a playlist search from the recipe cuisine.
$cuisine = trim($_GET['cuisine'] ?? 'cooking');
$search_query = ($cuisine ?: 'cooking') . ' top hits';

$headers = [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
];

// Search several results so we can select an actual top-hits playlist.
$search_url = 'https://api.spotify.com/v1/search?' . http_build_query([
    'q' => $search_query,
    'type' => 'playlist',
    'limit' => 10
]);

$ch = curl_init($search_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$search_response = curl_exec($ch);
$search_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die('cURL error (search): ' . curl_error($ch));
}

if ($search_code !== 200) {
    die("Spotify search error (HTTP $search_code): " . $search_response);
}

$search_result = json_decode($search_response, true);
$playlists = $search_result['playlists']['items'] ?? [];
$playlist = null;

// Do not use just any result: its title must contain "top" or "hits".
foreach ($playlists as $candidate) {
    $playlist_name = $candidate['name'] ?? '';

    if (
        !empty($candidate['uri'])
        && (stripos($playlist_name, 'top') !== false || stripos($playlist_name, 'hits') !== false)
    ) {
        $playlist = $candidate;
        break;
    }
}

if (!$playlist) {
    die('No top-hits playlist found for ' . htmlspecialchars($cuisine) . ' cuisine.');
}

$playlist_uri = $playlist['uri'] ?? null;
if (!$playlist_uri) {
    die('Failed to resolve playlist URI from Spotify search result.');
}

// Find a Spotify Connect device where playback can start.
$devices_url = 'https://api.spotify.com/v1/me/player/devices';
$ch = curl_init($devices_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$devices_response = curl_exec($ch);
$devices_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die('cURL error (devices): ' . curl_error($ch));
}

if ($devices_code !== 200) {
    die("Spotify devices error (HTTP $devices_code): " . $devices_response);
}

$devices_result = json_decode($devices_response, true);
$devices = $devices_result['devices'] ?? [];
if (empty($devices)) {
    die('No Spotify devices found. Open Spotify on your phone or laptop and try again.');
}

// Prefer the active device and otherwise use the first available device.
$device_id = null;
foreach ($devices as $device) {
    if (!empty($device['is_active'])) {
        $device_id = $device['id'];
        break;
    }
}

if (!$device_id) {
    $device_id = $devices[0]['id'] ?? null;
}

if (!$device_id) {
    die('No playable Spotify device ID found.');
}

// Start the cuisine playlist on the selected Spotify device.
$play_url = 'https://api.spotify.com/v1/me/player/play?' . http_build_query(['device_id' => $device_id]);
$body = json_encode(['context_uri' => $playlist_uri]);

$ch = curl_init($play_url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die('cURL error (play): ' . curl_error($ch));
}

if ($http_code !== 204) {
    die("Spotify API error (HTTP $http_code): " . $response . ". Open Spotify on a device and make sure it is available for playback.");
}

echo 'Playing Spotify music for ' . htmlspecialchars($cuisine) . ' cuisine.';

?>
