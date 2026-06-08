<?php
session_start();

// If user is logged in, redirect to search-page.php
if (isset($_SESSION['user_id'])) {
    header('Location: /php/search-page.php');
    exit;
}

// If user is not logged in, redirect to landing.php
header('Location: /php/landing.php');
exit;
?>