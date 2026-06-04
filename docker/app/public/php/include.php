<?php
session_start();
if (!isset($_SESSION['user_id'])) {
	header('Location: signin.php');
	exit;
}

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

<!DOCTYPE html>
<footer>
    &copy; 2026 NHL Stenden
</footer>
</html>