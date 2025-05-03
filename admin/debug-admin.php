<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Admin Debug Information</h1>";

// Check if bookings.php exists
$bookingsFile = __DIR__ . '/bookings.php';
echo "<h2>Checking bookings.php file</h2>";
if (file_exists($bookingsFile)) {
    echo "<p style='color:green'>✓ bookings.php file exists at: " . $bookingsFile . "</p>";
    echo "<p>File size: " . filesize($bookingsFile) . " bytes</p>";
    echo "<p>File permissions: " . substr(sprintf('%o', fileperms($bookingsFile)), -4) . "</p>";
    echo "<p>Last modified: " . date("F d Y H:i:s", filemtime($bookingsFile)) . "</p>";
} else {
    echo "<p style='color:red'>✗ bookings.php file does not exist at: " . $bookingsFile . "</p>";
}

// Check if we can include the file
echo "<h2>Testing file inclusion</h2>";
echo "<p>Attempting to include bookings.php...</p>";
try {
    // Only check if the file exists, don't actually include it
    if (file_exists($bookingsFile)) {
        echo "<p style='color:green'>✓ File can be included</p>";
    } else {
        echo "<p style='color:red'>✗ File cannot be included</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check server information
echo "<h2>Server Information</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";
echo "<p>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p>Current Script: " . $_SERVER['SCRIPT_FILENAME'] . "</p>";

// List all files in the admin directory
echo "<h2>Files in Admin Directory</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        echo "<li>" . $file . " (" . (is_dir(__DIR__ . '/' . $file) ? "directory" : "file") . ")</li>";
    }
}
echo "</ul>";

// Check database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once '../config/database.php';
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color:green'>✓ Database connection successful</p>";

    // Check if bookings table exists
    $stmt = $conn->prepare("SHOW TABLES LIKE 'bookings'");
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "<p style='color:green'>✓ Bookings table exists</p>";

        // Get booking count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM bookings");
        $stmt->execute();
        $count = $stmt->fetchColumn();
        echo "<p>Total bookings: " . $count . "</p>";
    } else {
        echo "<p style='color:red'>✗ Bookings table does not exist</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
}

// Provide links to test
echo "<h2>Test Links</h2>";
echo "<p><a href='bookings.php' target='_blank'>Open bookings.php in new tab</a></p>";
echo "<p><a href='dashboard.php' target='_blank'>Open dashboard.php in new tab</a></p>";
?>
