<?php
// Display server information and paths
echo "<h1>Path Debugging</h1>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>PHP Self: " . $_SERVER['PHP_SELF'] . "</p>";

// Check if important files exist
$root_path = $_SERVER['DOCUMENT_ROOT'] . '/Toursync/';
echo "<p>Root Path: " . $root_path . "</p>";

$files_to_check = [
    'config/database.php',
    'includes/helpers.php',
    'index.php'
];

echo "<h2>File Existence Check:</h2>";
echo "<ul>";
foreach ($files_to_check as $file) {
    $full_path = $root_path . $file;
    echo "<li>" . $file . ": " . (file_exists($full_path) ? "Exists" : "Not Found") . " at " . $full_path . "</li>";
}
echo "</ul>";

// Check database connection
echo "<h2>Database Connection Test:</h2>";
try {
    require_once $root_path . 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color:green'>Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
}
?>
