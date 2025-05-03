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

// Check if required parameters are provided
if (!isset($_GET['tour_id']) || empty($_GET['tour_id']) || !isset($_GET['schedule_id']) || empty($_GET['schedule_id'])) {
    echo "<div class='alert alert-danger'>Missing tour_id or schedule_id parameters</div>";
    echo "<p>Please go back to <a href='tours.php'>Tours</a> and try again.</p>";
    exit;
}

$tour_id = $_GET['tour_id'];
$schedule_id = $_GET['schedule_id'];

// Include header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Direct Booking Process</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h4>Debug Information</h4>
                        <p>This is a simplified booking process for debugging purposes.</p>
                    </div>
                    
                    <form action="booking-process.php" method="post">
                        <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        <input type="hidden" name="num_people" value="1">
                        <input type="hidden" name="traveler[1][name]" value="Test User">
                        <input type="hidden" name="traveler[1][age]" value="30">
                        <input type="hidden" name="traveler[1][gender]" value="male">
                        <input type="hidden" name="traveler[1][id_type]" value="passport">
                        <input type="hidden" name="traveler[1][id_number]" value="TEST12345">
                        <input type="hidden" name="special_requests" value="Direct booking test">
                        <input type="hidden" name="timestamp" value="<?php echo time(); ?>">
                        
                        <p>This will submit a test booking with the following details:</p>
                        <ul>
                            <li>Tour ID: <?php echo $tour_id; ?></li>
                            <li>Schedule ID: <?php echo $schedule_id; ?></li>
                            <li>Number of People: 1</li>
                            <li>Traveler: Test User, 30, Male, Passport: TEST12345</li>
                        </ul>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Submit Test Booking</button>
                        </div>
                    </form>
                    
                    <hr>
                    
                    <div class="mt-3">
                        <h5>Alternative Options:</h5>
                        <div class="d-grid gap-2">
                            <a href="booking.php?tour_id=<?php echo $tour_id; ?>&schedule_id=<?php echo $schedule_id; ?>" class="btn btn-outline-secondary">
                                Return to Regular Booking Form
                            </a>
                            <a href="test-booking-form.php" class="btn btn-outline-secondary">
                                Go to Test Booking Form
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
