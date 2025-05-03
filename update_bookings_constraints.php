<?php
require_once 'config/database.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Adding Foreign Key Constraints</h1>";

try {
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    $dbname = $db->query("SELECT DATABASE()")->fetchColumn();
    
    // Function to check if a constraint exists
    function constraintExists($db, $dbname, $table, $constraintName) {
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE CONSTRAINT_SCHEMA = :dbname 
            AND TABLE_NAME = :table 
            AND CONSTRAINT_NAME = :constraint
        ");
        $stmt->execute([
            ':dbname' => $dbname,
            ':table' => $table,
            ':constraint' => $constraintName
        ]);
        return $stmt->fetchColumn() > 0;
    }
    
    // Function to safely add a constraint
    function addConstraint($db, $table, $constraintName, $constraintSQL) {
        global $dbname;
        
        if (!constraintExists($db, $dbname, $table, $constraintName)) {
            try {
                $db->exec("ALTER TABLE `$table` ADD CONSTRAINT `$constraintName` $constraintSQL");
                echo "<div style='color:green; padding:10px; border:1px solid green; margin-bottom:10px;'>";
                echo "Added constraint $constraintName to $table";
                echo "</div>";
            } catch (PDOException $e) {
                echo "<div style='color:orange; padding:10px; border:1px solid orange; margin-bottom:10px;'>";
                echo "Could not add constraint $constraintName: " . $e->getMessage();
                echo "</div>";
            }
        } else {
            echo "<div style='color:blue; padding:10px; border:1px solid blue; margin-bottom:10px;'>";
            echo "Constraint $constraintName already exists on $table";
            echo "</div>";
        }
    }
    
    // Add constraints for bookings table
    addConstraint($db, 'bookings', 'bookings_ibfk_1', 
        "FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE");
    
    addConstraint($db, 'bookings', 'bookings_ibfk_2', 
        "FOREIGN KEY (`tour_id`) REFERENCES `tours` (`tour_id`) ON DELETE CASCADE");
    
    addConstraint($db, 'bookings', 'bookings_ibfk_3', 
        "FOREIGN KEY (`schedule_id`) REFERENCES `tour_schedules` (`schedule_id`) ON DELETE CASCADE");
    
    // Add constraint for booking_travelers table
    addConstraint($db, 'booking_travelers', 'booking_travelers_ibfk_1', 
        "FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE");
    
    // Add constraints for payments table
    addConstraint($db, 'payments', 'payments_ibfk_1', 
        "FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE");
    
    addConstraint($db, 'payments', 'payments_ibfk_2', 
        "FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE");
    
    // Add constraints for refunds table
    addConstraint($db, 'refunds', 'refunds_ibfk_1', 
        "FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`booking_id`) ON DELETE CASCADE");
    
    addConstraint($db, 'refunds', 'refunds_ibfk_2', 
        "FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE");
    
    echo "<h2>Constraint Addition Complete</h2>";
    
} catch (PDOException $e) {
    echo "<div style='color:red; padding:10px; border:1px solid red;'>";
    echo "Database Error: " . $e->getMessage();
    echo "</div>";
}
?>

<p><a href="user/booking.php?tour_id=17&schedule_id=1" class="btn btn-primary">Try Booking Page Again</a></p>
