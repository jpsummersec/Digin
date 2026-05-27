<?php

session_start();

// Load Spotify credentials from config.php
$config = require __DIR__ . '/../config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;
$client_secret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

if (!$client_id || !$client_secret) {
    die('Error: SPOTIFY_CLIENT_ID or SPOTIFY_CLIENT_SECRET not found in config.php');
}

$redirect_uri = $config['SPOTIFY_REDIRECT_URI'];

// Validate the state token to avoid CSRF attacks.
if (!isset($_GET['state'], $_SESSION['spotify_state']) || $_GET['state'] !== $_SESSION['spotify_state']) {
    die('State mismatch. Please restart the Spotify login process.');
}

$code = $_GET['code'] ?? null;
if (!$code) {
    die('Error: Authorization code not found in callback.');
}

// Exchange the authorization code for access and refresh tokens.
$ch = curl_init('https://accounts.spotify.com/api/token');

$data = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri
];

$headers = [
    'Authorization: Basic ' . base64_encode("$client_id:$client_secret"),
    'Content-Type: application/x-www-form-urlencoded'
];

curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die('cURL error: ' . curl_error($ch));
}

curl_close($ch);

if ($http_code !== 200) {
    die('Spotify API error (HTTP ' . $http_code . '): ' . $response);
}

$result = json_decode($response, true);
if (!$result || empty($result['access_token'])) {
    die('Error: No access token received from Spotify. Check your credentials.');
}

// Save Spotify tokens in the session for the playback page.
$_SESSION['access_token'] = $result['access_token'];
$_SESSION['refresh_token'] = $result['refresh_token'] ?? null;
$_SESSION['spotify_token_expires'] = time() + ($result['expires_in'] ?? 3600);

// Remove the one-time state token after successful auth.
unset($_SESSION['spotify_state']);

header('Location: /Spotify_connection/play.php');
exit();

?>