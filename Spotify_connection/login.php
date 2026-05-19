<?php

session_start();

$config = require __DIR__ . '/../config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;

if (!$client_id) {
    die('Error: SPOTIFY_CLIENT_ID not found in config.php');
}

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:3000';
$redirect_uri = $config['SPOTIFY_REDIRECT_URI'] ?? "$scheme://$host/Spotify_connection/callback.php";

$state = bin2hex(random_bytes(16));
$_SESSION['spotify_state'] = $state;

$scope = "user-read-playback-state user-modify-playback-state";

$params = http_build_query([
    "response_type" => "code",
    "client_id" => $client_id,
    "scope" => $scope,
    "redirect_uri" => $redirect_uri,
    "state" => $state,
    "show_dialog" => "true"
]);

header("Location: https://accounts.spotify.com/authorize?$params");
exit();

?>