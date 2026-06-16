<?php

require_once __DIR__ . '/include-url-config.php';

function getRequiredEnvironmentVariable($name)
{
    $value = getenv($name);

    if ($value === false || $value === '')
    {
        die('Configuration Error: Missing required environment variable ' . $name);
    }

    return $value;
}

$host = getRequiredEnvironmentVariable('DB_SERVER');
$username = getRequiredEnvironmentVariable('DB_USER');
$password = getRequiredEnvironmentVariable('DB_PASSWORD');
$database = getRequiredEnvironmentVariable('DB_DATABASE');

try
{
    $dbHandler = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $dbHandler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}
catch (PDOException $exception)
{
    die('Connection Error (PDOException): ' . $exception->getMessage());
}
