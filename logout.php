<?php
require_once 'config/session.php';

// Start session
Session::start();

// Destroy the session
Session::destroy();

// Redirect to login page
header("location: login.php");
exit;
?>
