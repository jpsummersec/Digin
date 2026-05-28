<?php

session_start();

// Load helper functions that read/write Spotify tokens for the current user
require __DIR__ . '/spotify_helper.php';

// Load Spotify app credentials and redirect URI from the shared config
$config = require __DIR__ . '/../config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;
$client_secret = $config['SPOTIFY_CLIENT_SECRET'] ?? null;

if (!$client_id || !$client_secret) {
    die('Error: SPOTIFY_CLIENT_ID or SPOTIFY_CLIENT_SECRET not found in config.php');
}

$redirect_uri = $config['SPOTIFY_REDIRECT_URI'];

// If the user is already logged in try to reuse their stored Spotify token
$userId = getCurrentUserId();
if ($userId !== null) {
    $db = getDbConnection();
    $stored = loadSpotifyTokenFromUser($db, $userId);

    if ($stored) {
        // If the stored access token is still valid continue directly to playback
        if ($stored['expires_at'] > time()) {
            setSpotifySessionTokens([
                'access_token' => $stored['access_token'],
                'refresh_token' => $stored['refresh_token'],
                'expires_in' => $stored['expires_at'] - time(),
            ]);
            header('Location: /Spotify_connection/play.php');
            exit();
        }

        // If the access token expired refresh it using the saved refresh token
        if (!empty($stored['refresh_token'])) {
            $refresh = refreshSpotifyAccessToken($stored['refresh_token'], $client_id, $client_secret);
            if (empty($refresh['error'])) {
                $expiresAt = time() + $refresh['expires_in'];
                saveSpotifyTokenToUser($db, $userId, $refresh['access_token'], $refresh['refresh_token'], $expiresAt);
                setSpotifySessionTokens($refresh);
                header('Location: /Spotify_connection/play.php');
                exit();
            }
        }
    }
}

// Generate a random state token and keep it in the session
// Spotify will send it back so we can verify the callback
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_state'] = $state;

// These scopes let us read playback state and start playback
$scope = "user-read-playback-state user-modify-playback-state";

$params = http_build_query([
    "response_type" => "code",
    "client_id" => $client_id,
    "scope" => $scope,
    "redirect_uri" => $redirect_uri,
    "state" => $state,
    "show_dialog" => "true" // Keep the login prompt visible for fresh auth
]);

$authorize_url = "https://accounts.spotify.com/authorize?$params";

if (isset($_GET['action']) && $_GET['action'] === 'login') {
    header("Location: $authorize_url");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Spotify Login</title>
</head>
<body>
    <div>
        <h1>Login with Spotify</h1>
        <p>Click the button below to authenticate and continue.</p>
        <a class="button" href="?action=login">Login with Spotify</a>
    </div>
</body>
</html>
