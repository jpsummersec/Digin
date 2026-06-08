<?php

// Keep OAuth on the configured application URL and open the user's PHP session
// which stores the token during the session
require_once __DIR__ . '/include-url-config.php';
session_start();

// This loads the Spotify application credentials and registered callback URL
$config = require __DIR__ . '/config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;
$client_secret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

if (!$client_id || !$client_secret) {
    die('Error: SPOTIFY_CLIENT_ID or SPOTIFY_CLIENT_SECRET not found in config.php');
}

$redirect_uri = $config['SPOTIFY_REDIRECT_URI'];

// Spotify returns the same state value created on the profile page
// Rejecting a mismatch prevents another site from completing OAuth in this session
if (!isset($_GET['state'], $_SESSION['spotify_state']) || $_GET['state'] !== $_SESSION['spotify_state']) {
    die('State mismatch. Please restart the Spotify login process.');
}

$code = $_GET['code'] ?? null;
if (!$code) {
    die('Error: Authorization code not found in callback.');
}

// Here we exchange Spotifys short-lived authorization code for session tokens
$ch = curl_init('https://accounts.spotify.com/api/token');

// Spotify requires the exact same redirect URI used during authorization
$data = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirect_uri
];

$headers = [
    'Authorization: Basic ' . base64_encode("$client_id:$client_secret"),
    'Content-Type: application/x-www-form-urlencoded'
];

// Send the token request using HTTP Basic authentication for this Spotify app
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die('cURL error: ' . curl_error($ch));
}

if ($http_code !== 200) {
    die('Spotify API error (HTTP ' . $http_code . '): ' . $response);
}

$result = json_decode($response, true);
if (!$result || empty($result['access_token'])) {
    die('Error: No access token received from Spotify. Check your credentials.');
}

// Store the connection only in this PHP session no token is written to the database can change this for later
$refreshToken = $result['refresh_token'] ?? null;
$expiresAt = time() + ($result['expires_in'] ?? 3600);
$_SESSION['access_token'] = $result['access_token'];
$_SESSION['refresh_token'] = $refreshToken;
$_SESSION['spotify_token_expires'] = $expiresAt;

// The state token is single-use so remove it after successful authentication
unset($_SESSION['spotify_state']);

// Return the user to their profile after Spotify has been connected
header('Location: /php/profile-page.php?spotify=connected');
exit();

?>
