<?php

session_start();

// We need the access token from the Spotify auth flow.
$access_token = $_SESSION['access_token'] ?? null;

if (!$access_token) {
    die('Error: No access token. Please log in first by visiting login.php');
}

// Example playlist to play. Swap this with your own playlist URI if you want.
$playlist_uri = "spotify:playlist:37i9dQZF1DX0r3x8OtiwEM";

// Call Spotify's player API to start playback.
$ch = curl_init("https://api.spotify.com/v1/me/player/play");

$data = [
    "context_uri" => $playlist_uri
];

$headers = [
    "Authorization: Bearer $access_token",
    "Content-Type: application/json"
];

curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if (curl_errno($ch)) {
    die("cURL error: " . curl_error($ch));
}

curl_close($ch);

if ($http_code !== 204) {
    die("Spotify API error (HTTP $http_code): " . $response . ". Make sure Spotify is open on a device and you have playback control enabled.");
}

echo "Playing playlist!";

?>