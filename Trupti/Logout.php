<?php
session_start();

// Get redirect from URL directly
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : 'login.html';

// Destroy session completely
session_unset();
session_destroy();

// Start fresh session
session_start();
session_regenerate_id(true);

// Save redirect in new session
$_SESSION['redirect_after_login'] = $redirect;

// Go to login
header("Location: login.html");
exit();
?>