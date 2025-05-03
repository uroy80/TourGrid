<?php
require_once '../config/session.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Get tour ID and schedule ID from URL
$tour_id = isset($_GET['tour_id']) ? intval($_GET['tour_id']) : 0;
$schedule_id = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;

if (!$tour_id || !$schedule_id) {
    header('Location: ../tours.php');
    exit;
}

// Connect to database
$conn = connectDatabase();

// Get tour and schedule details
$stmt = $conn->prepare("SELECT t.*, ts.departure_date, ts.price 
                        FROM tours t 
                        JOIN tour_schedules ts ON t.tour_id = ts.tour_id 
                        WHERE t.tour_id = ? AND ts.schedule_id = ?");
$stmt->bind_param("ii", $tour_id, $schedule_id);
$stmt->execute();
$result = $stmt->get_result();
$tour_data = $result->fetch_assoc();
$stmt->close();

if (!$tour_data) {
    header('Location: ../tours.php');
    exit;
}

// Create a test booking
$num_people = 1;
$base_amount = $tour_data['price'] * $num_people;
$tax_amount = $base_amount * 0.05;
$total_amount = $base_amount + $tax_amount;
$booking_reference = 'TEST' . date('Ymd') . rand(1000, 9999);

// Store booking data in session
$_SESSION['booking_data'] = [
    'booking_id' => 0, // This is a test, so no real booking ID
    'booking_reference' => $booking_reference,
    'tour_name' => $tour_data['name'],
    'departure_date' => $tour_data['departure_date'],
    'num_people' => $num_people,
    'base_amount' => $base_amount,
    'tax_amount' => $tax_amount,
    'total_amount' => $total_amount
];

// Close database connection
$conn->close();

// Include header
$pageTitle = "Test Payment";
include_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="alert alert-info">
        <h4>Test Payment Page</h4>
        <p>This is a test page to verify that the payment system is working correctly.</p>
    </div>
    
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Test Booking Summary</h5>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Booking Reference:</strong><br> <?php echo $booking_reference; ?></p>
                    <p><strong>Tour:</strong><br> <?php echo htmlspecialchars($tour_data['name']); ?></p>
                    <p><strong>Departure Date:</strong><br> <?php echo date('F d, Y', strtotime($tour_data['departure_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Number of Travelers:</strong><br> <?php echo $num_people; ?></p>
                    <p><strong>Base Amount:</strong><br> $<?php echo number_format($base_amount, 2); ?></p>
                    <p><strong>Tax (5%):</strong><br> $<?php echo number_format($tax_amount, 2); ?></p>
                    <p><strong>Total Amount:</strong><br> <span class="fw-bold">$<?php echo number_format($total_amount, 2); ?></span></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="d-flex justify-content-between">
        <a href="booking.php?tour_id=<?php echo $tour_id; ?>&schedule_id=<?php echo $schedule_id; ?>" class="btn btn-outline-primary">
            Back to Booking Form
        </a>
        <a href="payment.php?booking_id=0" class="btn btn-primary">
            Continue to Payment Page
        </a>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
