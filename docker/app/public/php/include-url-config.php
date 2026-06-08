<?php

// Change this URL when the application is moved to another server.
$baseUrl = 'http://127.0.0.1:3000';

$expectedHost = parse_url($baseUrl, PHP_URL_HOST);
$expectedPort = parse_url($baseUrl, PHP_URL_PORT);
$expectedHostWithPort = $expectedHost;

if ($expectedPort) {
    $expectedHostWithPort = $expectedHostWithPort . ':' . $expectedPort;
}

if (isset($_SERVER['HTTP_HOST'])) {
    $currentHost = $_SERVER['HTTP_HOST'];
} else {
    $currentHost = '';
}

if (isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
} else {
    $requestUri = '/';
}

if ($currentHost !== '' && $currentHost !== $expectedHostWithPort) {
    header('Location: ' . $baseUrl . $requestUri);
    exit;
}
