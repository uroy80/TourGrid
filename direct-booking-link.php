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

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Include header
include_once 'includes/header.php';

// Get tour data
$database = new Database();
$db = $database->getConnection();

$query = "SELECT t.tour_id, t.title, ts.schedule_id, ts.start_date 
          FROM tours t 
          JOIN tour_schedules ts ON t.tour_id = ts.tour_id 
          ORDER BY t.title, ts.start_date";
$stmt = $db->prepare($query);
$stmt->execute();
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Direct Booking Links</h3>
                </div>
                <div class="card-body">
                    <p class="alert alert-info">Click on any of the links below to go directly to the booking page for that tour and schedule.</p>
                    
                    <div class="list-group mt-4">
                        <?php foreach ($tours as $tour): ?>
                            <a href="user/booking.php?tour_id=<?php echo $tour['tour_id']; ?>&schedule_id=<?php echo $tour['schedule_id']; ?>" 
                               class="list-group-item list-group-item-action">
                                <div class="d-flex w-100 justify-content-between">
                                    <h5 class="mb-1"><?php echo htmlspecialchars($tour['title']); ?></h5>
                                    <small><?php echo date('F d, Y', strtotime($tour['start_date'])); ?></small>
                                </div>
                                <p class="mb-1">Tour ID: <?php echo $tour['tour_id']; ?>, Schedule ID: <?php echo $tour['schedule_id']; ?></p>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
