<?php
/**
 * User Logout Processing
 * Destroys session and redirects to home page
 */

session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}

// Destroy the session
session_destroy();

// Redirect to home page
header('Location: ../index.php');
exit();
?>
