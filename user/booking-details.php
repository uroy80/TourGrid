<?php
session_start();
require_once '../config/database.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Redirect to bookings page if no ID provided
    header("Location: bookings.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['id'];
$error_message = '';
$booking = null;
$travelers = [];

try {
    $db = connectDatabase();

    // Get booking details with tour and schedule information
    // Removed ts.price which doesn't exist in the database
    $stmt = $db->prepare("
        SELECT b.*, 
               t.title as tour_title, 
               t.description as tour_description,
               t.image as tour_image,
               ts.start_date, 
               ts.end_date,
               ts.price_adjustment,
               d.name as destination_name
        FROM bookings b
        JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
        JOIN tours t ON ts.tour_id = t.tour_id
        JOIN destinations d ON t.destination_id = d.destination_id
        WHERE b.booking_id = :booking_id AND b.user_id = :user_id
    ");
    $stmt->bindParam(':booking_id', $booking_id);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$booking) {
        $error_message = "Booking not found or you don't have permission to view it.";
    } else {
        // Get travelers information
        $stmt = $db->prepare("
            SELECT * FROM booking_travelers 
            WHERE booking_id = :booking_id
        ");
        $stmt->bindParam(':booking_id', $booking_id);
        $stmt->execute();
        $travelers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Booking Details</h1>
        <a href="bookings.php" class="btn btn-outline-secondary">Back to Bookings</a>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
    <?php elseif ($booking): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Booking #<?php echo $booking['booking_id']; ?></h5>
                        <span class="badge <?php echo getStatusBadgeClass($booking['status']); ?>">
                            <?php echo ucfirst($booking['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Tour:</strong> <?php echo htmlspecialchars($booking['tour_title']); ?></p>
                                <p><strong>Destination:</strong> <?php echo htmlspecialchars($booking['destination_name']); ?></p>
                                <p><strong>Dates:</strong> <?php echo date('M d, Y', strtotime($booking['start_date'])); ?> - <?php echo date('M d, Y', strtotime($booking['end_date'])); ?></p>
                                <p><strong>Duration:</strong> <?php echo calculateDuration($booking['start_date'], $booking['end_date']); ?> days</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Booking Date:</strong> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></p>
                                <p><strong>Number of Travelers:</strong> <?php echo $booking['num_people']; ?></p>
                                <p><strong>Total Amount:</strong> ₹<?php echo number_format($booking['total_amount'], 2); ?></p>
                                <p><strong>Payment Status:</strong>
                                    <span class="badge <?php echo getPaymentStatusBadgeClass($booking['payment_status']); ?>">
                                        <?php echo ucfirst($booking['payment_status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <?php if ($booking['status'] == 'confirmed'): ?>
                            <div class="mt-3">
                                <a href="booking-voucher.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-success">
                                    <i class="fas fa-download"></i> Download Voucher
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($booking['status'] == 'pending' || $booking['status'] == 'confirmed'): ?>
                            <div class="mt-3">
                                <a href="booking-cancel.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger"
                                   onclick="return confirm('Are you sure you want to cancel this booking?');">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ($booking['payment_status'] == 'pending' && $booking['status'] != 'cancelled'): ?>
                            <div class="mt-3">
                                <a href="payment.php?booking_id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-credit-card"></i> Make Payment
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Tour Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($booking['tour_image'])): ?>
                            <img src="<?php echo htmlspecialchars($booking['tour_image']); ?>" alt="<?php echo htmlspecialchars($booking['tour_title']); ?>" class="img-fluid mb-3" style="max-height: 200px;">
                        <?php endif; ?>

                        <h5><?php echo htmlspecialchars($booking['tour_title']); ?></h5>
                        <p><?php echo nl2br(htmlspecialchars($booking['tour_description'])); ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Traveler Information</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($travelers)): ?>
                            <p>No traveler information available.</p>
                        <?php else: ?>
                            <?php foreach ($travelers as $index => $traveler): ?>
                                <div class="mb-3 pb-3 <?php echo ($index < count($travelers) - 1) ? 'border-bottom' : ''; ?>">
                                    <h6>Traveler #<?php echo $index + 1; ?></h6>
                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($traveler['name']); ?></p>
                                    <p class="mb-1"><strong>Age:</strong> <?php echo $traveler['age']; ?></p>
                                    <p class="mb-1"><strong>Gender:</strong> <?php echo ucfirst($traveler['gender']); ?></p>
                                    <?php if (!empty($traveler['id_type']) && !empty($traveler['id_number'])): ?>
                                        <p class="mb-1"><strong>ID:</strong> <?php echo ucfirst($traveler['id_type']); ?> - <?php echo $traveler['id_number']; ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5>Price Details</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <tbody>
                            <tr>
                                <td>Base Amount</td>
                                <td class="text-end">₹<?php echo number_format($booking['base_amount'], 2); ?></td>
                            </tr>
                            <?php if (isset($booking['tax_amount']) && $booking['tax_amount'] > 0): ?>
                                <tr>
                                    <td>Tax</td>
                                    <td class="text-end">₹<?php echo number_format($booking['tax_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (isset($booking['discount_amount']) && $booking['discount_amount'] > 0): ?>
                                <tr>
                                    <td>Discount</td>
                                    <td class="text-end">-₹<?php echo number_format($booking['discount_amount'], 2); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="table-active">
                                <th>Total Amount</th>
                                <th class="text-end">₹<?php echo number_format($booking['total_amount'], 2); ?></th>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php
// Helper function to calculate duration
function calculateDuration($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    $interval = $start->diff($end);
    return $interval->days + 1; // Include both start and end days
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'confirmed':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        case 'completed':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Helper function to get payment status badge class
function getPaymentStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'bg-warning';
        case 'paid':
            return 'bg-success';
        case 'refunded':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

include 'includes/footer.php';
?>
