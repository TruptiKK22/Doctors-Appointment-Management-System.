<?php
session_start();

// Save where to redirect after login
if (isset($_GET['redirect'])) {
    $_SESSION['redirect_after_login'] = $_GET['redirect'];
}

// Return simple OK
http_response_code(200);
echo "ok";
?>