<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Form Submission Debug</h1>";

echo "<h2>POST Data</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>GET Data</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>Server Variables</h2>";
echo "<pre>";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "<br>";
echo "REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "<br>";
echo "SCRIPT_NAME: " . $_SERVER['SCRIPT_NAME'] . "<br>";
echo "PHP_SELF: " . $_SERVER['PHP_SELF'] . "<br>";
echo "HTTP_REFERER: " . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'Not set') . "<br>";
echo "</pre>";

echo "<h2>Session Data</h2>";
echo "<pre>";
session_start();
print_r($_SESSION);
echo "</pre>";

echo "<p><a href='javascript:history.back()'>Go Back</a></p>";
?>
