<?php
session_start();
$_SESSION = array(); // Kosongkan variabel session

// Hapus cookie session
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy(); // Hancurkan session

// Redirect ke landing page
header("Location: ../index.php");
exit;