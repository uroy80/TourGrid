<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/helpers.php';

// Start session
Session::start();

// Debug information
echo "<div style='background-color: #f8f9fa; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd;'>";
echo "<h3>Debug Information</h3>";
echo "<p>GET Parameters: ";
print_r($_GET);
echo "</p>";
echo "<p>Current URL: " . $_SERVER['REQUEST_URI'] . "</p>";
echo "</div>";

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Check if tour ID and schedule ID are provided
if (!isset($_GET['tour_id']) || empty($_GET['tour_id']) || !isset($_GET['schedule_id']) || empty($_GET['schedule_id'])) {
    echo "<div class='alert alert-danger'>Missing tour_id or schedule_id parameters</div>";
    echo "<p>Please go back to <a href='../tours.php'>Tours</a> and try again.</p>";
    exit;
}

$tour_id = $_GET['tour_id'];
$schedule_id = $_GET['schedule_id'];

// Database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get tour details
    $query = "SELECT t.*, d.name as destination_name 
             FROM tours t 
             JOIN destinations d ON t.destination_id = d.destination_id 
             WHERE t.tour_id = :tour_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":tour_id", $tour_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        header("Location: ../tours.php");
        exit;
    }
    
    $tour = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get schedule details
    $schedule_query = "SELECT * FROM tour_schedules WHERE schedule_id = :schedule_id AND tour_id = :tour_id";
    $schedule_stmt = $db->prepare($schedule_query);
    $schedule_stmt->bindParam(":schedule_id", $schedule_id);
    $schedule_stmt->bindParam(":tour_id", $tour_id);
    $schedule_stmt->execute();
    
    if ($schedule_stmt->rowCount() == 0) {
        header("Location: ../tour-details.php?id=" . $tour_id);
        exit;
    }
    
    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if bookings table exists
    $table_exists = $db->query("SHOW TABLES LIKE 'bookings'")->rowCount() > 0;
    
    // Calculate available seats
    $available_seats = $schedule['max_capacity'];
    
    if ($table_exists) {
        // Simple query without using booking_status
        $bookings_query = "SELECT SUM(num_people) as booked_seats FROM bookings 
                          WHERE schedule_id = :schedule_id";
        
        $bookings_stmt = $db->prepare($bookings_query);
        $bookings_stmt->bindParam(":schedule_id", $schedule_id);
        $bookings_stmt->execute();
        $bookings_result = $bookings_stmt->fetch(PDO::FETCH_ASSOC);
        
        $booked_seats = $bookings_result['booked_seats'] ? $bookings_result['booked_seats'] : 0;
        $available_seats = $schedule['max_capacity'] - $booked_seats;
    }
    
    // Check if there are available seats
    if ($available_seats <= 0) {
        $_SESSION['error'] = "Sorry, this tour schedule is fully booked.";
        header("Location: ../tour-details.php?id=" . $tour_id);
        exit;
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Database error in booking.php: " . $e->getMessage());
    echo "<div style='color:red; padding:20px; border:1px solid red;'>";
    echo "<h3>Error:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Error occurred in file: " . $e->getFile() . " on line " . $e->getLine() . "</p>";
    echo "<p><a href='../update_bookings_tables.php' class='btn btn-warning'>Run Database Update Script</a></p>";
    echo "</div>";
    exit;
}

// Include user header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Book Your Tour</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="tour-summary mb-4">
                        <h4><?php echo htmlspecialchars($tour['title']); ?></h4>
                        <p><strong>Destination:</strong> <?php echo htmlspecialchars($tour['destination_name']); ?></p>
                        <p><strong>Duration:</strong> <?php echo $tour['duration']; ?> days</p>
                        <p><strong>Departure Date:</strong> <?php echo date('F d, Y', strtotime($schedule['start_date'])); ?></p>
                        <p><strong>Return Date:</strong> <?php echo date('F d, Y', strtotime($schedule['end_date'])); ?></p>
                        <p><strong>Price:</strong> ₹<?php echo number_format($schedule['price']); ?> per person</p>
                        <p><strong>Available Seats:</strong> <?php echo $available_seats; ?></p>
                    </div>
                    
                    <form action="../booking-process.php" method="post">
                        <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        
                        <div class="mb-3">
                            <label for="num_people" class="form-label">Number of Travelers</label>
                            <select class="form-select" id="num_people" name="num_people" required>
                                <?php for ($i = 1; $i <= min(10, $available_seats); $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div id="traveler-details">
                            <h5 class="mb-3">Lead Traveler Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_name_1" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="traveler_name_1" name="traveler[1][name]" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_age_1" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="traveler_age_1" name="traveler[1][age]" min="1" max="120" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_gender_1" class="form-label">Gender</label>
                                    <select class="form-select" id="traveler_gender_1" name="traveler[1][gender]" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_id_type_1" class="form-label">ID Type</label>
                                    <select class="form-select" id="traveler_id_type_1" name="traveler[1][id_type]" required>
                                        <option value="">Select ID Type</option>
                                        <option value="passport">Passport</option>
                                        <option value="national_id">National ID</option>
                                        <option value="drivers_license">Driver's License</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="traveler_id_number_1" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="traveler_id_number_1" name="traveler[1][id_number]" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests (Optional)</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3"></textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Continue to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const bookingForm = document.querySelector('form');
    
    bookingForm.addEventListener('submit', function(e) {
        // For debugging
        console.log('Form submitted');
        console.log('Action:', this.action);
        console.log('Method:', this.method);
        
        // Add a hidden field with timestamp to prevent caching
        const timestampField = document.createElement('input');
        timestampField.type = 'hidden';
        timestampField.name = 'timestamp';
        timestampField.value = Date.now();
        this.appendChild(timestampField);
        
        // Continue with form submission
        return true;
    });
});
</script>
