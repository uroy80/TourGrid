<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>File Location Check</h1>";

echo "<h2>Current Script</h2>";
echo "<p>__FILE__: " . __FILE__ . "</p>";
echo "<p>__DIR__: " . __DIR__ . "</p>";
echo "<p>getcwd(): " . getcwd() . "</p>";

echo "<h2>Server Variables</h2>";
echo "<p>DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>SCRIPT_FILENAME: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>PHP_SELF: " . $_SERVER['PHP_SELF'] . "</p>";
echo "<p>REQUEST_URI: " . $_SERVER['REQUEST_URI'] . "</p>";

echo "<h2>File Exists Checks</h2>";
$files_to_check = [
    'booking.php',
    'user/booking.php',
    'booking-process.php',
    'user/booking-process.php'
];

foreach ($files_to_check as $file) {
    echo "<p>" . $file . ": " . (file_exists($file) ? "Exists" : "Does not exist") . "</p>";
}

echo "<h2>Directory Structure</h2>";
function list_directory($dir) {
    if (is_dir($dir)) {
        $files = scandir($dir);
        echo "<ul>";
        foreach ($files as $file) {
            if ($file != "." && $file != "..") {
                echo "<li>" . $file . (is_dir($dir . '/' . $file) ? " (directory)" : "") . "</li>";
            }
        }
        echo "</ul>";
    } else {
        echo "<p>Not a directory: " . $dir . "</p>";
    }
}

echo "<p>Root directory:</p>";
list_directory(".");

echo "<p>User directory:</p>";
list_directory("user");
?>
