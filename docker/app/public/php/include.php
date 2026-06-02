<?php
// // Start PHP session if not already set
// if (session_status() === PHP_SESSION_NONE) {
//     session_start();
// }

// // If user not logged, in, give 401 error
// if (!isset($_SESSION['user_id'])) {
//     http_response_code(401);
//     echo "<h1>You must log in first<h1>";
//     exit;
// }

// Create dbHandler
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
?>