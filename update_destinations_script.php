<?php
// This script is for administrators to update the database with Indian destinations
// It should be run only once to replace the existing destinations with Indian ones

// Include necessary files
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Check if user is logged in and is an admin
if (!isLoggedIn() || !isAdmin()) {
    die("Access denied. This script can only be run by administrators.");
}

// Connect to database
$conn = connectDatabase();

// Function to run SQL from file
function runSQLFile($conn, $filename) {
    $sql = file_get_contents($filename);
    
    // Split SQL file into individual statements
    $statements = explode(';', $sql);
    
    $success = true;
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (!empty($statement)) {
            if (!$conn->query($statement)) {
                echo "Error executing statement: " . $conn->error . "<br>";
                echo "Statement: " . $statement . "<br><br>";
                $success = false;
            }
        }
    }
    
    return $success;
}

// Start transaction
$conn->begin_transaction();

try {
    // Update destinations
    echo "<h2>Updating destinations...</h2>";
    if (runSQLFile($conn, 'database/update_destinations.sql')) {
        echo "<p>Destinations updated successfully.</p>";
    } else {
        throw new Exception("Error updating destinations.");
    }
    
    // Update tours
    echo "<h2>Updating tours...</h2>";
    if (runSQLFile($conn, 'database/update_tours.sql')) {
        echo "<p>Tours updated successfully.</p>";
    } else {
        throw new Exception("Error updating tours.");
    }
    
    // Update hotels
    echo "<h2>Updating hotels...</h2>";
    if (runSQLFile($conn, 'database/update_hotels.sql')) {
        echo "<p>Hotels updated successfully.</p>";
    } else {
        throw new Exception("Error updating hotels.");
    }
    
    // Create placeholder images
    echo "<h2>Creating placeholder images...</h2>";
    include 'create_placeholder_images.php';
    
    // Commit transaction
    $conn->commit();
    echo "<h2>All updates completed successfully!</h2>";
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo "<h2>Error: " . $e->getMessage() . "</h2>";
    echo "<p>All changes have been rolled back.</p>";
}

// Close connection
$conn->close();
?>

<p><a href="admin/dashboard.php">Return to Admin Dashboard</a></p>
