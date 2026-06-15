<?php

require_once __DIR__ . '/php/include-url-config.php';

session_start();

if (isset($_SESSION['user_id']))
{
    header('Location: /php/search-page.php');
    exit;
}

header('Location: /php/landing.php');
exit;
