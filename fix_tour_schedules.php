<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

echo "<h1>Fixing Tour Schedules Table</h1>";

try {
    // Connect to database
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if price column exists
    $checkPriceColumn = $db->query("SHOW COLUMNS FROM tour_schedules LIKE 'price'");
    
    if ($checkPriceColumn->rowCount() == 0) {
        // Price column doesn't exist, add it
        echo "<p>Adding price column to tour_schedules table...</p>";
        $db->exec("ALTER TABLE tour_schedules ADD COLUMN price DECIMAL(10,2) NULL AFTER available_seats");
        echo "<p>Price column added successfully.</p>";
    } else {
        echo "<p>Price column already exists in tour_schedules table.</p>";
    }
    
    // Update tour schedules with prices from tours table
    echo "<p>Updating tour schedule prices from tours table...</p>";
    $updateQuery = "UPDATE tour_schedules ts 
                   JOIN tours t ON ts.tour_id = t.tour_id 
                   SET ts.price = t.price 
                   WHERE ts.price IS NULL OR ts.price = 0";
    $stmt = $db->prepare($updateQuery);
    $result = $stmt->execute();
    
    if ($result) {
        $rowCount = $stmt->rowCount();
        echo "<p>Updated prices for {$rowCount} tour schedules.</p>";
    } else {
        echo "<p>Failed to update tour schedule prices.</p>";
    }
    
    // Show current tour schedules
    echo "<h2>Current Tour Schedules</h2>";
    $schedules = $db->query("SELECT * FROM tour_schedules")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Tour ID</th><th>Start Date</th><th>End Date</th><th>Available Seats</th><th>Price</th></tr>";
    
    foreach ($schedules as $schedule) {
        echo "<tr>";
        echo "<td>{$schedule['schedule_id']}</td>";
        echo "<td>{$schedule['tour_id']}</td>";
        echo "<td>{$schedule['start_date']}</td>";
        echo "<td>{$schedule['end_date']}</td>";
        echo "<td>{$schedule['available_seats']}</td>";
        echo "<td>" . (isset($schedule['price']) ? $schedule['price'] : 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<p>Done! <a href='booking.php?tour_id={$schedule['tour_id']}&schedule_id={$schedule['schedule_id']}'>Try booking again</a></p>";
    
} catch (Exception $e) {
    echo "<div style='color:red; padding:20px; border:1px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Error occurred in file: " . $e->getFile() . " on line " . $e->getLine() . "</p>";
    echo "</div>";
}
?>
