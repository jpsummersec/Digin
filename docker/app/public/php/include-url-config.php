<?php

// Change this URL when the application is moved to another server.
$baseUrl = 'http://127.0.0.1:3000';

$expectedHost = parse_url($baseUrl, PHP_URL_HOST);
$expectedPort = parse_url($baseUrl, PHP_URL_PORT);
$expectedHostWithPort = $expectedHost;

if ($expectedPort)
{
    $expectedHostWithPort = $expectedHostWithPort . ':' . $expectedPort;
}

// HTTP_HOST is the host the visitor used in the browser, such as 127.0.0.1:3000 or localhost:3000.
if (isset($_SERVER['HTTP_HOST']))
{
    $currentHost = $_SERVER['HTTP_HOST'];
}
else
{
    $currentHost = '';
}

// REQUEST_URI is the current path and query string, such as
// /php/profile-page.php?spotify=connected.
if (isset($_SERVER['REQUEST_URI']))
{
    $requestUri = $_SERVER['REQUEST_URI'];
}
else
{
    $requestUri = '/';
}

// Redirect visitor if they're using a wrong URL
if ($currentHost !== '' && $currentHost !== $expectedHostWithPort)
{
    header('Location: ' . $baseUrl . $requestUri);
    exit;
}
