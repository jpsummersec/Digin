<?php

session_start();

// Load the Spotify client ID from config.php.
$config = require __DIR__ . '/../config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;

if (!$client_id) {
    die('Error: SPOTIFY_CLIENT_ID not found in config.php');
}

$redirect_uri = $config['SPOTIFY_REDIRECT_URI'];

// Generate a random state token and keep it in the session.
// Spotify will send it back so we can verify the callback.
$state = bin2hex(random_bytes(16));
$_SESSION['spotify_state'] = $state;

// These scopes let us read playback state and start playback.
$scope = "user-read-playback-state user-modify-playback-state";

$params = http_build_query([
    "response_type" => "code",
    "client_id" => $client_id,
    "scope" => $scope,
    "redirect_uri" => $redirect_uri,
    "state" => $state,
    "show_dialog" => "true" // Keep the login prompt visible for fresh auth.
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
