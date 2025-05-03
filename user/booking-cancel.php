<?php
require_once '../config/session.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if booking ID is provided
$booking_id = isset($_GET['booking_id']) ? intval($_GET['booking_id']) : 0;

if (!$booking_id) {
    header('Location: bookings.php');
    exit;
}

// Connect to database
$conn = connectDatabase();

// Get booking details
$stmt = $conn->prepare("SELECT b.*, t.name as tour_name, ts.departure_date 
                        FROM bookings b
                        JOIN tours t ON b.tour_id = t.tour_id
                        JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
                        WHERE b.booking_id = ? AND b.user_id = ?");
$stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();
$stmt->close();

if (!$booking) {
    header('Location: bookings.php');
    exit;
}

// Check if booking is already cancelled
if ($booking['booking_status'] === 'cancelled') {
    header('Location: booking-details.php?booking_id=' . $booking_id);
    exit;
}

// Calculate days until departure
$departure_date = new DateTime($booking['departure_date']);
$current_date = new DateTime();
$days_until_departure = $current_date->diff($departure_date)->days;

// Calculate refund amount based on cancellation policy
$refund_percentage = 0;
if ($days_until_departure > 30) {
    $refund_percentage = 90; // 90% refund if cancelled more than 30 days before departure
} elseif ($days_until_departure > 15) {
    $refund_percentage = 70; // 70% refund if cancelled 15-30 days before departure
} elseif ($days_until_departure > 7) {
    $refund_percentage = 50; // 50% refund if cancelled 7-15 days before departure
} elseif ($days_until_departure > 3) {
    $refund_percentage = 25; // 25% refund if cancelled 3-7 days before departure
} else {
    $refund_percentage = 0; // No refund if cancelled within 3 days of departure
}

$refund_amount = $booking['total_amount'] * ($refund_percentage / 100);

// Process cancellation form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cancellation_reason = isset($_POST['cancellation_reason']) ? trim($_POST['cancellation_reason']) : '';
    
    // Begin transaction
    $conn->begin_transaction();
    
    try {
        // Update booking status
        $stmt = $conn->prepare("UPDATE bookings SET booking_status = 'cancelled', cancellation_date = NOW(), 
                                cancellation_reason = ?, refund_amount = ? WHERE booking_id = ?");
        $stmt->bind_param("sdi", $cancellation_reason, $refund_amount, $booking_id);
        $stmt->execute();
        $stmt->close();
        
        // If payment was made, create refund record
        if ($booking['payment_status'] === 'paid') {
            $refund_reference = 'REF' . date('Ymd') . rand(1000, 9999);
            $stmt = $conn->prepare("INSERT INTO refunds (booking_id, refund_reference, amount, refund_date, status) 
                                    VALUES (?, ?, ?, NOW(), 'processed')");
            $stmt->bind_param("isd", $booking_id, $refund_reference, $refund_amount);
            $stmt->execute();
            $stmt->close();
            
            // Update payment status
            $stmt = $conn->prepare("UPDATE payments SET payment_status = 'refunded' WHERE booking_id = ?");
            $stmt->bind_param("i", $booking_id);
            $stmt->execute();
            $stmt->close();
        }
        
        // Update available seats in schedule
        $stmt = $conn->prepare("UPDATE tour_schedules SET available_seats = available_seats + ? 
                                WHERE schedule_id = ?");
        $stmt->bind_param("ii", $booking['num_people'], $booking['schedule_id']);
        $stmt->execute();
        $stmt->close();
        
        // Commit transaction
        $conn->commit();
        
        // Redirect to booking details page
        header('Location: booking-details.php?booking_id=' . $booking_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $error = "An error occurred while processing your cancellation request. Please try again.";
    }
}

// Close database connection
$conn->close();

// Include header
$pageTitle = "Cancel Booking";
include_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="mb-3">Cancel Booking</h1>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="booking-details.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left me-2"></i> Back to Booking Details
            </a>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Cancellation Details</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i> 
                        You are about to cancel your booking for <strong><?php echo htmlspecialchars($booking['tour_name']); ?></strong> 
                        departing on <strong><?php echo date('F d, Y', strtotime($booking['departure_date'])); ?></strong>.
                    </div>
                    
                    <h5 class="mt-4">Cancellation Policy</h5>
                    <ul class="list-group mb-4">
                        <li class="list-group-item">More than 30 days before departure: 90% refund</li>
                        <li class="list-group-item">15-30 days before departure: 70% refund</li>
                        <li class="list-group-item">7-15 days before departure: 50% refund</li>
                        <li class="list-group-item">3-7 days before departure: 25% refund</li>
                        <li class="list-group-item">Less than 3 days before departure: No refund</li>
                    </ul>
                    
                    <div class="alert alert-info">
                        <p><strong>Days until departure:</strong> <?php echo $days_until_departure; ?> days</p>
                        <p><strong>Refund percentage:</strong> <?php echo $refund_percentage; ?>%</p>
                        <p><strong>Refund amount:</strong> $<?php echo number_format($refund_amount, 2); ?></p>
                    </div>
                    
                    <form action="booking-cancel.php?booking_id=<?php echo $booking_id; ?>" method="post" onsubmit="return confirm('Are you sure you want to cancel this booking?');">
                        <div class="mb-3">
                            <label for="cancellation_reason" class="form-label">Reason for Cancellation</label>
                            <textarea name="cancellation_reason" id="cancellation_reason" class="form-control" rows="3" required></textarea>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-danger">Confirm Cancellation</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 20px;">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Booking Summary</h5>
                </div>
                <div class="card-body">
                    <p><strong>Booking Reference:</strong><br> <?php echo $booking['booking_reference']; ?></p>
                    <p><strong>Tour:</strong><br> <?php echo htmlspecialchars($booking['tour_name']); ?></p>
                    <p><strong>Departure Date:</strong><br> <?php echo date('F d, Y', strtotime($booking['departure_date'])); ?></p>
                    <p><strong>Number of Travelers:</strong><br> <?php echo $booking['num_people']; ?></p>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between mb-2 fw-bold">
                        <span>Total Paid:</span>
                        <span>$<?php echo number_format($booking['total_amount'], 2); ?></span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-2 fw-bold text-danger">
                        <span>Refund Amount:</span>
                        <span>$<?php echo number_format($refund_amount, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
