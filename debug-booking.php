<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Booking Debug Page</h1>";

// Check if booking.php exists
$booking_file = __DIR__ . '/booking.php';
echo "<p>Checking if booking.php exists at: " . $booking_file . "</p>";

if (file_exists($booking_file)) {
    echo "<p style='color: green;'>booking.php file exists!</p>";

    // Check file permissions
    $perms = fileperms($booking_file);
    echo "<p>File permissions: " . decoct($perms & 0777) . "</p>";

    // Check file size
    $size = filesize($booking_file);
    echo "<p>File size: " . $size . " bytes</p>";

    // Try to include the file
    echo "<p>Attempting to include booking.php...</p>";
    try {
        include_once $booking_file;
        echo "<p style='color: green;'>Successfully included booking.php!</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error including booking.php: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>booking.php file does not exist!</p>";
}

// Check PHP version and loaded extensions
echo "<h2>PHP Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Loaded Extensions: </p>";
echo "<ul>";
$extensions = get_loaded_extensions();
sort($extensions);
foreach ($extensions as $ext) {
    echo "<li>" . $ext . "</li>";
}
echo "</ul>";

// Check server information
echo "<h2>Server Information</h2>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";
echo "<p>Request URI: " . $_SERVER['REQUEST_URI'] . "</p>";

// Check if we can connect to the database
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green;'>Successfully connected to database!</p>";

    // Check if tours table exists
    $result = $conn->query("SHOW TABLES LIKE 'tours'");
    if ($result->rowCount() > 0) {
        echo "<p style='color: green;'>tours table exists!</p>";
    } else {
        echo "<p style='color: red;'>tours table does not exist!</p>";
    }

    // Check if tour_schedules table exists
    $result = $conn->query("SHOW TABLES LIKE 'tour_schedules'");
    if ($result->rowCount() > 0) {
        echo "<p style='color: green;'>tour_schedules table exists!</p>";
    } else {
        echo "<p style='color: red;'>tour_schedules table does not exist!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database connection error: " . $e->getMessage() . "</p>";
}
?>
