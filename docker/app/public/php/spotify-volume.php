<?php

require_once __DIR__ . '/include-loginrequired.php';

$config = require __DIR__ . '/config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;
$client_secret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

$access_token = $_SESSION['access_token'] ?? null;
$refresh_token = $_SESSION['refresh_token'] ?? null;
$expires_at = $_SESSION['spotify_token_expires'] ?? 0;

if (!$access_token) {
    die('No Spotify access token. Connect Spotify first.');
}

if ($expires_at <= time()) {
    if (!$refresh_token || !$client_id || !$client_secret) {
        die('Spotify token expired. Reconnect Spotify.');
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
        die('Could not refresh Spotify token.');
    }

    $access_token = $refresh_result['access_token'];
    $_SESSION['access_token'] = $access_token;
    $_SESSION['refresh_token'] = $refresh_result['refresh_token'] ?? $refresh_token;
    $_SESSION['spotify_token_expires'] = time() + ($refresh_result['expires_in'] ?? 3600);
}

if (array_key_exists('volume', $_GET)) {
    $volume = (int) $_GET['volume'];
} else {
    $volume = 80;
}

$volume = max(0, min(100, $volume));

//$volume = isset($_GET['volume']) ? (int) $_GET['volume'] : 80;
//$volume = max(0, min(100, $volume));

$url = 'https://api.spotify.com/v1/me/player/volume?' . http_build_query([
    'volume_percent' => $volume
]);

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($http_code !== 204) {
    die("Spotify volume error HTTP $http_code: " . $response);
}

echo "Spotify volume set to $volume";
