<?php

session_start();

// We need the access token from the Spotify auth flow.
$access_token = $_SESSION['access_token'] ?? null;

if (!$access_token) {
    die('Error: No access token. Please log in first by visiting login.php');
}

// Search query based on cuisine or fallback text.
$cuisine = trim($_GET['cuisine'] ?? 'brazilian');
$search_query = $cuisine ?: 'brazilian';

$headers = [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
];

// Find the first playlist matching the cuisine query.
$search_url = 'https://api.spotify.com/v1/search?' . http_build_query([
    'q' => $search_query,
    'type' => 'playlist',
    'limit' => 1
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
$playlist = $search_result['playlists']['items'][0] ?? null;
if (!$playlist) {
    die('No playlist found for query: ' . htmlspecialchars($search_query));
}

$playlist_uri = $playlist['uri'] ?? null;
if (!$playlist_uri) {
    die('Failed to resolve playlist URI from Spotify search result.');
}

// Look for an available Spotify Connect device.
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

echo 'Playing playlist for cuisine: ' . htmlspecialchars($search_query) . ' on device ' . htmlspecialchars($device_id) . '.';

?>