<?php

// Keep OAuth on the configured application URL and use the session that stores
// the user's temporary Spotify connection.
require_once __DIR__ . '/include-url-config.php';
session_start();

// Load the Spotify application credentials and registered callback URL.
$config = require __DIR__ . '/config.php';
$clientId = $config['SPOTIFY_CLIENT_ID'] ?? null;
$clientSecret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

if (!$clientId || !$clientSecret)
{
    die('Error: SPOTIFY_CLIENT_ID or SPOTIFY_CLIENT_SECRET not found in config.php');
}

$redirectUri = $config['SPOTIFY_REDIRECT_URI'];

// Reject callbacks whose state value does not match the one created on the
// profile page.
if (!isset($_GET['state'], $_SESSION['spotify_state']) || $_GET['state'] !== $_SESSION['spotify_state'])
{
    die('State mismatch. Please restart the Spotify login process.');
}

$code = $_GET['code'] ?? null;
if (!$code)
{
    die('Error: Authorization code not found in callback.');
}

// Exchange Spotify's short-lived authorization code for session tokens.
$curlHandle = curl_init('https://accounts.spotify.com/api/token');

$requestData = [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'redirect_uri' => $redirectUri
];

$headers = [
    'Authorization: Basic ' . base64_encode("$clientId:$clientSecret"),
    'Content-Type: application/x-www-form-urlencoded'
];

// Spotify requires HTTP Basic authentication and the same redirect URI used
// during authorization.
curl_setopt($curlHandle, CURLOPT_POST, true);
curl_setopt($curlHandle, CURLOPT_POSTFIELDS, http_build_query($requestData));
curl_setopt($curlHandle, CURLOPT_HTTPHEADER, $headers);
curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curlHandle);
$httpCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);

if (curl_errno($curlHandle))
{
    die('cURL error: ' . curl_error($curlHandle));
}

if ($httpCode !== 200)
{
    die('Spotify API error (HTTP ' . $httpCode . '): ' . $response);
}

$result = json_decode($response, true);
if (!$result || empty($result['access_token']))
{
    die('Error: No access token received from Spotify. Check your credentials.');
}

// Spotify tokens are stored only in the current PHP session.
$refreshToken = $result['refresh_token'] ?? null;
$expiresAt = time() + ($result['expires_in'] ?? 3600);
$_SESSION['access_token'] = $result['access_token'];
$_SESSION['refresh_token'] = $refreshToken;
$_SESSION['spotify_token_expires'] = $expiresAt;

// The state token is single-use.
unset($_SESSION['spotify_state']);

header('Location: /php/profile-page.php?spotify=connected');
exit;

?>
