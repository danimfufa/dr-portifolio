<?php
// dr_portfolio/admin/logout.php
session_start(); // Start the session to access session variables

// Unset all session variables.
// This effectively removes all data stored in the current session.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session.
// This removes the session file from the server.
session_destroy();

// Redirect the user to the login page after logging out.
header("Location: login.php");
exit(); // Always call exit() after a header redirect to prevent further script execution.
?>