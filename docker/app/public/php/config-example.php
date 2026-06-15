<?php

// DO NOT ADD THE SPOTIFY CREDENTIALS TO THIS FILE, THIS IS JUST AN EXAMPLE

require_once __DIR__ . '/include-url-config.php';

return [
    'SPOTIFY_CLIENT_ID' => 'EXAMPLE_ID',
    'SPOTIFY_CLIENT_SECRET' => 'EXAMPLE_SECRET',
    'SPOTIFY_REDIRECT_URI' => $baseUrl . '/php/callback.php'
];

?>

