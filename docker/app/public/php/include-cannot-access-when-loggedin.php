<?php

require_once __DIR__ . '/include-url-config.php';

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

if (isset($_SESSION['user_id']))
{
    header('Location: /php/search-page.php');
    exit;
}
