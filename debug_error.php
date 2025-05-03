<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check PHP version
echo "PHP Version: " . phpversion() . "<br>";

// Try to connect to the database
require_once 'config/database.php';
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "Database connection successful<br>";
    
    // Check if the destinations table has the new columns
    $query = "SHOW COLUMNS FROM destinations LIKE 'parent_id'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "parent_id column exists in destinations table<br>";
    } else {
        echo "parent_id column does NOT exist in destinations table<br>";
    }
    
    // Check if the destinations table has the type column
    $query = "SHOW COLUMNS FROM destinations LIKE 'type'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        echo "type column exists in destinations table<br>";
    } else {
        echo "type column does NOT exist in destinations table<br>";
    }
    
    // Check if the helpers.php file is accessible
    if (file_exists('includes/helpers.php')) {
        echo "helpers.php file exists<br>";
        
        // Try to include it
        try {
            require_once 'includes/helpers.php';
            echo "helpers.php included successfully<br>";
            
            // Test a function from helpers.php
            try {
                $destinations = getAllDestinations();
                echo "getAllDestinations() function works, found " . count($destinations) . " destinations<br>";
            } catch (Exception $e) {
                echo "Error calling getAllDestinations(): " . $e->getMessage() . "<br>";
            }
            
        } catch (Exception $e) {
            echo "Error including helpers.php: " . $e->getMessage() . "<br>";
        }
    } else {
        echo "helpers.php file does NOT exist<br>";
    }
    
} catch (Exception $e) {
    echo "Database connection error: " . $e->getMessage() . "<br>";
}

// Check for errors in the error log
if (file_exists(ini_get('error_log'))) {
    echo "Recent errors from error log:<br>";
    $log = file_get_contents(ini_get('error_log'));
    $lines = explode("\n", $log);
    $last_lines = array_slice($lines, -20); // Get last 20 lines
    foreach ($last_lines as $line) {
        echo htmlspecialchars($line) . "<br>";
    }
} else {
    echo "Error log file not found<br>";
}
?>
