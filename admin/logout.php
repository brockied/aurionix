<?php
/**
 * ADMIN LOGOUT
 * Place this file as: admin/logout.php
 */

require_once '../config.php';

// Clear all session data
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Redirect to login page
header('Location: login.php?logged_out=1');
exit;
?>