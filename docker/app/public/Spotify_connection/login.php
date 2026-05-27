<?php

session_start();

// Load the Spotify client ID from config.php.
$config = require __DIR__ . '/../config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;

if (!$client_id) {
    die('Error: SPOTIFY_CLIENT_ID not found in config.php');
}

// Build the redirect URL Spotify will send the user back to.
// In Docker/Caddy, the browser usually sees localhost:3000.
$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
$redirect_uri = $config['SPOTIFY_REDIRECT_URI'] ?? "$scheme://$host/Spotify_connection/callback.php";

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

// Send the browser to Spotify to log in.
header("Location: https://accounts.spotify.com/authorize?$params");
exit();

?>