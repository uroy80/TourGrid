<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Start session
Session::start();

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get all active tours
$tours_query = "SELECT t.tour_id, t.title, d.name as destination_name 
               FROM tours t 
               JOIN destinations d ON t.destination_id = d.destination_id 
               WHERE t.status = 'active'";
$tours_stmt = $db->prepare($tours_query);
$tours_stmt->execute();
$tours = $tours_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h3 class="mb-0">Test Booking Links</h3>
        </div>
        <div class="card-body">
            <p class="lead">This page provides direct links to test the booking functionality.</p>
            
            <h4 class="mt-4">Available Tours</h4>
            <div class="list-group mb-4">
                <?php foreach ($tours as $tour): ?>
                    <div class="list-group-item">
                        <h5><?php echo htmlspecialchars($tour['title']); ?> - <?php echo htmlspecialchars($tour['destination_name']); ?></h5>
                        
                        <?php
                        // Get schedules for this tour
                        $schedules_query = "SELECT * FROM tour_schedules 
                                           WHERE tour_id = :tour_id 
                                           AND start_date > CURDATE() 
                                           ORDER BY start_date";
                        $schedules_stmt = $db->prepare($schedules_query);
                        $schedules_stmt->bindParam(":tour_id", $tour['tour_id']);
                        $schedules_stmt->execute();
                        $schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);
                        ?>
                        
                        <?php if (count($schedules) > 0): ?>
                            <div class="mt-2">
                                <p><strong>Available Schedules:</strong></p>
                                <div class="list-group">
                                    <?php foreach ($schedules as $schedule): ?>
                                        <a href="booking.php?tour_id=<?php echo $tour['tour_id']; ?>&schedule_id=<?php echo $schedule['schedule_id']; ?>" 
                                           class="list-group-item list-group-item-action">
                                            <?php echo date('M d, Y', strtotime($schedule['start_date'])); ?> - 
                                            <?php echo date('M d, Y', strtotime($schedule['end_date'])); ?>
                                            (₹<?php echo number_format($schedule['price']); ?>)
                                        </a>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No upcoming schedules available for this tour.</p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="mt-4">
                <a href="debug-booking.php" class="btn btn-warning">View Debug Information</a>
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
