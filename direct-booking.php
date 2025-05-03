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
               WHERE t.status = 'active'
               ORDER BY t.title";
$tours_stmt = $db->prepare($tours_query);
$tours_stmt->execute();
$tours = $tours_stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all upcoming schedules
$schedules_query = "SELECT ts.schedule_id, ts.tour_id, ts.start_date, ts.end_date, ts.price, 
                   t.title as tour_title, d.name as destination_name
                   FROM tour_schedules ts
                   JOIN tours t ON ts.tour_id = t.tour_id
                   JOIN destinations d ON t.destination_id = d.destination_id
                   WHERE ts.start_date > CURDATE() 
                   AND ts.status = 'upcoming'
                   ORDER BY ts.start_date";
$schedules_stmt = $db->prepare($schedules_query);
$schedules_stmt->execute();
$schedules = $schedules_stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="mb-4">Direct Booking Test</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Available Tours</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($tours as $tour): ?>
                            <li class="list-group-item">
                                <a href="tour-details.php?id=<?php echo $tour['tour_id']; ?>">
                                    <?php echo $tour['title']; ?> (<?php echo $tour['destination_name']; ?>)
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3>Direct Booking Links</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">These links bypass the tour details page and go directly to the booking page.</p>
                    <ul class="list-group">
                        <?php foreach ($schedules as $schedule): ?>
                            <li class="list-group-item">
                                <a href="user/booking.php?tour_id=<?php echo $schedule['tour_id']; ?>&schedule_id=<?php echo $schedule['schedule_id']; ?>">
                                    <?php echo $schedule['tour_title']; ?> (<?php echo $schedule['destination_name']; ?>)<br>
                                    <small><?php echo date('M d, Y', strtotime($schedule['start_date'])); ?> - 
                                    <?php echo date('M d, Y', strtotime($schedule['end_date'])); ?></small>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
include_once 'includes/footer.php';
?>
