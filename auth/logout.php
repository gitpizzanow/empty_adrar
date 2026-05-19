<?php
/**
 * Logout — destroy session and redirect home.
 */

require_once '../config/auth.php';

$_SESSION = [];

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

session_destroy();

redirectTo('index.php');
