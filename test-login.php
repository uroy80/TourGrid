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

echo "<h1>Login Test</h1>";

// Test user credentials
$test_username = "admin";
$test_password = "admin123";

echo "<p>Testing login with username: " . $test_username . "</p>";

// Database connection
$database = new Database();
$db = $database->getConnection();

// Prepare a select statement
$sql = "SELECT user_id, username, password, role, status FROM users WHERE username = :username";

if ($stmt = $db->prepare($sql)) {
    // Bind variables to the prepared statement as parameters
    $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
    
    // Set parameters
    $param_username = $test_username;
    
    // Attempt to execute the prepared statement
    if ($stmt->execute()) {
        // Check if username exists
        if ($stmt->rowCount() == 1) {
            echo "<p style='color:green'>Username found in database.</p>";
            
            if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user_id = $row["user_id"];
                $username = $row["username"];
                $hashed_password = $row["password"];
                $role = $row["role"];
                $status = $row["status"];
                
                echo "<p>User ID: " . $user_id . "</p>";
                echo "<p>Role: " . $role . "</p>";
                echo "<p>Status: " . $status . "</p>";
                
                // Verify password
                if (password_verify($test_password, $hashed_password)) {
                    echo "<p style='color:green'>Password is correct!</p>";
                } else {
                    echo "<p style='color:red'>Password is incorrect!</p>";
                    echo "<p>Stored hash: " . $hashed_password . "</p>";
                    echo "<p>Test password: " . $test_password . "</p>";
                }
            }
        } else {
            echo "<p style='color:red'>Username not found in database.</p>";
        }
    } else {
        echo "<p style='color:red'>Error executing query: " . $stmt->errorInfo()[2] . "</p>";
    }
    
    // Close statement
    unset($stmt);
} else {
    echo "<p style='color:red'>Error preparing query: " . $db->errorInfo()[2] . "</p>";
}

// Close connection
unset($db);
?>
