<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include necessary files
require_once 'config/database.php';
require_once 'config/session.php';

// Start session
Session::start();

// Check PHP version
echo "<h2>PHP Environment</h2>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server Software: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Check session status
echo "<h2>Session Status</h2>";
echo "<p>Session Status: " . (session_status() == PHP_SESSION_ACTIVE ? "Active" : "Not active") . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";

// Display session variables
echo "<h2>Session Variables</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test database connection
echo "<h2>Database Connection Test</h2>";
try {
    $database = new Database();
    $db = $database->getConnection();
    if ($db) {
        echo "<p style='color:green'>Database connection successful!</p>";
        
        // Check if users table exists
        $stmt = $db->query("SHOW TABLES LIKE 'users'");
        if ($stmt->rowCount() > 0) {
            echo "<p style='color:green'>Users table exists.</p>";
            
            // Count users
            $stmt = $db->query("SELECT COUNT(*) as count FROM users");
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            echo "<p>Total users: " . $row['count'] . "</p>";
        } else {
            echo "<p style='color:red'>Users table does not exist!</p>";
        }
    } else {
        echo "<p style='color:red'>Database connection failed!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$files = [
    'config/database.php',
    'config/session.php',
    'login.php',
    'register.php',
    'index.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "<p>" . $file . ": " . substr(sprintf('%o', fileperms($file)), -4) . " (Readable: " . (is_readable($file) ? "Yes" : "No") . ")</p>";
    } else {
        echo "<p style='color:red'>" . $file . ": File not found!</p>";
    }
}

// Check upload directories
echo "<h2>Upload Directories</h2>";
$dirs = [
    'assets/images/uploads',
    'assets/images/uploads/tours',
    'assets/images/uploads/hotels',
    'assets/images/uploads/users'
];

foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<p>" . $dir . ": " . substr(sprintf('%o', fileperms($dir)), -4) . " (Writable: " . (is_writable($dir) ? "Yes" : "No") . ")</p>";
    } else {
        echo "<p style='color:red'>" . $dir . ": Directory not found!</p>";
    }
}

// PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "<p>upload_max_filesize: " . ini_get('upload_max_filesize') . "</p>";
echo "<p>post_max_size: " . ini_get('post_max_size') . "</p>";
echo "<p>max_execution_time: " . ini_get('max_execution_time') . "</p>";
echo "<p>memory_limit: " . ini_get('memory_limit') . "</p>";
?>
