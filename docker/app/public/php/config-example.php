<?php

// Keep real Spotify credentials in config.php, not in this example file.
require_once __DIR__ . '/include-url-config.php';

return [
    'SPOTIFY_CLIENT_ID' => 'EXAMPLE_ID',
    'SPOTIFY_CLIENT_SECRET' => 'EXAMPLE_SECRET',
    'SPOTIFY_REDIRECT_URI' => $baseUrl . '/php/callback.php'
];

?>
