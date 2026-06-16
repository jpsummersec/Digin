<?php

// Change this URL when the application is moved to another server.
$baseUrl = 'http://127.0.0.1:3000';

$expectedHost = parse_url($baseUrl, PHP_URL_HOST);
$expectedPort = parse_url($baseUrl, PHP_URL_PORT);

if ($expectedPort !== null)
{
    $expectedHost = $expectedHost . ':' . $expectedPort;
}

// If server is not on expected host, then redirect (necessary for Spotify compatability, 
// as URLs must stay consistent for callbacks).
if ($_SERVER['HTTP_HOST'] !== $expectedHost)
{
    header('Location: ' . $baseUrl . $_SERVER['REQUEST_URI']);
    exit;
}
