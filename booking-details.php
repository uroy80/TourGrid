<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<div class='alert alert-danger'>No booking ID provided.</div>";
    include_once 'includes/header.php';
    echo "<div class='container py-5'><div class='alert alert-danger'>No booking ID provided. <a href='index.php'>Return to homepage</a></div></div>";
    include_once 'includes/footer.php';
    exit;
}

$booking_id = intval($_GET['id']);

// Get booking data from database
try {
    $database = new Database();
    $db = $database->getConnection();

    // Get booking details with tour and schedule information
    $query = "SELECT b.*, t.title as tour_name, t.price, 
              ts.start_date, ts.end_date, ts.available_seats,
              p.payment_method, p.transaction_id, p.payment_date
              FROM bookings b
              JOIN tours t ON b.tour_id = t.tour_id
              JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
              LEFT JOIN payments p ON b.booking_id = p.booking_id
              WHERE b.booking_id = :booking_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":booking_id", $booking_id);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        include_once 'includes/header.php';
        echo "<div class='container py-5'><div class='alert alert-danger'>Booking not found. <a href='index.php'>Return to homepage</a></div></div>";
        include_once 'includes/footer.php';
        exit;
    }

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get traveler details
    $travelers_query = "SELECT * FROM booking_travelers WHERE booking_id = :booking_id";
    $travelers_stmt = $db->prepare($travelers_query);
    $travelers_stmt->bindParam(":booking_id", $booking_id);
    $travelers_stmt->execute();
    $travelers = $travelers_stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Log the error
    error_log("Booking details page error: " . $e->getMessage());

    // Display error message
    include_once 'includes/header.php';
    echo "<div class='container py-5'><div class='alert alert-danger'>An error occurred while retrieving booking information: " . $e->getMessage() . " <a href='index.php'>Return to homepage</a></div></div>";
    include_once 'includes/footer.php';
    exit;
}

// Include header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Booking Details</h3>
                </div>
                <div class="card-body">
                    <?php if ($booking['status'] === 'confirmed'): ?>
                        <div class="alert alert-success mb-4">
                            <i class="fas fa-check-circle me-2"></i>
                            Your booking is confirmed! Thank you for choosing TourGrid.
                        </div>
                    <?php elseif ($booking['status'] === 'pending'): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            Your booking is pending. Please complete the payment to confirm your booking.
                            <a href="payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary btn-sm ms-3">Pay Now</a>
                        </div>
                    <?php elseif ($booking['status'] === 'cancelled'): ?>
                        <div class="alert alert-danger mb-4">
                            <i class="fas fa-times-circle me-2"></i>
                            This booking has been cancelled.
                            <?php if (isset($booking['cancellation_reason']) && !empty($booking['cancellation_reason'])): ?>
                                <p class="mb-0 mt-2">Reason: <?php echo htmlspecialchars($booking['cancellation_reason']); ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="booking-summary mb-4">
                        <h4>Booking Information</h4>
                        <table class="table">
                            <tr>
                                <th>Booking Reference:</th>
                                <td><?php echo isset($booking['booking_reference']) ? $booking['booking_reference'] : 'TG' . str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT); ?></td>
                            </tr>
                            <tr>
                                <th>Tour:</th>
                                <td><?php echo isset($booking['tour_name']) ? htmlspecialchars($booking['tour_name']) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Departure Date:</th>
                                <td><?php echo isset($booking['start_date']) ? date('F d, Y', strtotime($booking['start_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td><?php echo isset($booking['end_date']) ? date('F d, Y', strtotime($booking['end_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Number of Travelers:</th>
                                <td><?php echo isset($booking['num_people']) ? $booking['num_people'] : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Base Amount:</th>
                                <td>₹<?php echo isset($booking['base_amount']) ? number_format($booking['base_amount'], 2) : '0.00'; ?></td>
                            </tr>
                            <tr>
                                <th>Tax:</th>
                                <td>₹<?php echo isset($booking['tax_amount']) ? number_format($booking['tax_amount'], 2) : '0.00'; ?></td>
                            </tr>
                            <?php if (isset($booking['discount_amount']) && $booking['discount_amount'] > 0): ?>
                                <tr>
                                    <th>Discount:</th>
                                    <td>₹<?php echo number_format($booking['discount_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="table-primary">
                                <th>Total Amount:</th>
                                <td>₹<?php echo isset($booking['total_amount']) ? number_format($booking['total_amount'], 2) : '0.00'; ?></td>
                            </tr>
                            <?php if (isset($booking['special_requests']) && !empty($booking['special_requests'])): ?>
                                <tr>
                                    <th>Special Requests:</th>
                                    <td><?php echo htmlspecialchars($booking['special_requests']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Booking Date:</th>
                                <td><?php echo isset($booking['booking_date']) ? date('F d, Y', strtotime($booking['booking_date'])) : date('F d, Y', strtotime($booking['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Status:</th>
                                <td>
                                    <?php
                                    switch($booking['status']) {
                                        case 'confirmed':
                                            echo '<span class="badge bg-success">Confirmed</span>';
                                            break;
                                        case 'pending':
                                            echo '<span class="badge bg-warning text-dark">Pending</span>';
                                            break;
                                        case 'cancelled':
                                            echo '<span class="badge bg-danger">Cancelled</span>';
                                            break;
                                        default:
                                            echo '<span class="badge bg-secondary">Unknown</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <?php if (isset($booking['payment_method']) && !empty($booking['payment_method'])): ?>
                        <div class="payment-info mb-4">
                            <h4>Payment Information</h4>
                            <table class="table">
                                <tr>
                                    <th>Payment Method:</th>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $booking['payment_method'])); ?></td>
                                </tr>
                                <tr>
                                    <th>Transaction ID:</th>
                                    <td><?php echo $booking['transaction_id']; ?></td>
                                </tr>
                                <tr>
                                    <th>Payment Date:</th>
                                    <td><?php echo date('F d, Y H:i:s', strtotime($booking['payment_date'])); ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($travelers)): ?>
                        <div class="travelers-info mb-4">
                            <h4>Traveler Information</h4>
                            <table class="table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>ID Type</th>
                                    <th>ID Number</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($travelers as $index => $traveler): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td><?php echo htmlspecialchars($traveler['name']); ?></td>
                                        <td><?php echo $traveler['age']; ?></td>
                                        <td><?php echo ucfirst($traveler['gender']); ?></td>
                                        <td><?php echo ucfirst($traveler['id_type']); ?></td>
                                        <td><?php echo htmlspecialchars($traveler['id_number']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between">
                        <a href="index.php" class="btn btn-secondary">Back to Home</a>

                        <?php if ($booking['status'] === 'confirmed'): ?>
                            <a href="booking-voucher.php?id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                <i class="fas fa-download me-2"></i> Download Voucher
                            </a>
                        <?php elseif ($booking['status'] === 'pending'): ?>
                            <a href="payment.php?booking_id=<?php echo $booking_id; ?>" class="btn btn-primary">
                                <i class="fas fa-credit-card me-2"></i> Complete Payment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
