<?php

if (session_status() === PHP_SESSION_NONE)
{
    session_start();
}

$_SESSION = [];

if (ini_get('session.use_cookies'))
{
    $parameters = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $parameters['path'],
        $parameters['domain'],
        $parameters['secure'],
        $parameters['httponly']
    );
}

session_destroy();

// Return the user to the public landing page after clearing the session.
header('Location: landing.php');
exit;

?>
