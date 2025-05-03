<?php
require_once 'config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Updating Booking Tables</h1>";

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Read the SQL file
    $sql = file_get_contents('database/update_bookings_table.sql');
    
    // Execute the SQL
    $result = $db->exec($sql);
    
    echo "<div style='color:green; padding:10px; border:1px solid green; margin-bottom:10px;'>";
    echo "Database tables created/updated successfully!";
    echo "</div>";
    
    // Check if the bookings table has the booking_status column
    $stmt = $db->query("SHOW COLUMNS FROM bookings LIKE 'booking_status'");
    if ($stmt->rowCount() > 0) {
        echo "<div style='color:green; padding:10px; border:1px solid green; margin-bottom:10px;'>";
        echo "The 'booking_status' column exists in the bookings table.";
        echo "</div>";
    } else {
        echo "<div style='color:red; padding:10px; border:1px solid red; margin-bottom:10px;'>";
        echo "The 'booking_status' column does NOT exist in the bookings table.";
        echo "</div>";
    }
    
    // List all tables
    echo "<h2>Database Tables</h2>";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Show bookings table structure
    echo "<h2>Bookings Table Structure</h2>";
    $columns = $db->query("DESCRIBE bookings")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($columns as $column) {
        echo "<tr>";
        foreach ($column as $key => $value) {
            echo "<td>" . ($value === null ? "NULL" : $value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p>To add foreign key constraints, <a href='update_bookings_constraints.php'>click here</a>.</p>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
    echo "Database Error: " . $e->getMessage();
    echo "</div>";
}
?>

<p><a href="user/booking.php?tour_id=17&schedule_id=1" class="btn btn-primary">Try Booking Page Again</a></p>
