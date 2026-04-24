<?php

session_start();

$config = require __DIR__ . '/../config.php';
$client_id = $config['SPOTIFY_CLIENT_ID'] ?? null;

if (!$client_id) {
    die('Error: SPOTIFY_CLIENT_ID not found in config.php');
}

$redirect_uri = "http://127.0.0.1:8000/callback.php";

$state = bin2hex(random_bytes(16));
$_SESSION['spotify_state'] = $state;

$scope = "user-read-playback-state user-modify-playback-state";

$params = http_build_query([
    "response_type" => "code",
    "client_id" => $client_id,
    "scope" => $scope,
    "redirect_uri" => $redirect_uri,
    "state" => $state
]);

header("Location: https://accounts.spotify.com/authorize?$params");
exit();

?>