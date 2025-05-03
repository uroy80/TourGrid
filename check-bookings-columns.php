<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';

// Create database connection
$database = new Database();
$conn = $database->getConnection();

// Check bookings table structure
$sql = "DESCRIBE bookings";
$stmt = $conn->prepare($sql);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "<h2>Bookings Table Columns</h2>";
echo "<pre>";
print_r($columns);
echo "</pre>";

// Check if booking_travelers table exists
$sql = "SHOW TABLES LIKE 'booking_travelers'";
$stmt = $conn->prepare($sql);
$stmt->execute();
$tableExists = $stmt->rowCount() > 0;

echo "<h2>booking_travelers Table Exists: " . ($tableExists ? "Yes" : "No") . "</h2>";

// If it exists, check its structure
if ($tableExists) {
    $sql = "DESCRIBE booking_travelers";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo "<h2>booking_travelers Table Columns</h2>";
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
}
?>
