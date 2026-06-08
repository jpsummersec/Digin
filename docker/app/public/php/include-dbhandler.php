<?php

require_once __DIR__ . '/include-url-config.php';

$host = 'mysql';
$username = 'root';
$password = 'qwerty';
$database = 'digin';

try {
    $dbHandler = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $dbHandler->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $ex) {
    die("Connection Error (PDOException): " . $ex->getMessage());
}
