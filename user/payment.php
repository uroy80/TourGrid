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

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Check if booking data exists in session or booking_id is provided
if (!isset($_SESSION['booking_data']) && !isset($_GET['booking_id'])) {
    header("Location: ../tours.php");
    exit;
}

// Get booking data from database if not in session
if (!isset($_SESSION['booking_data']) && isset($_GET['booking_id'])) {
    $booking_id = intval($_GET['booking_id']);
    
    try {
        // Database connection
        $database = new Database();
        $db = $database->getConnection();
        
        // Get booking details
        $query = "SELECT b.*, t.title as tour_name, ts.start_date as departure_date, ts.end_date as return_date
                 FROM bookings b
                 JOIN tours t ON b.tour_id = t.tour_id
                 JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
                 WHERE b.booking_id = :booking_id AND b.user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->bindParam(":user_id", $_SESSION['user_id']);
        $stmt->execute();
        
        if ($stmt->rowCount() == 0) {
            $_SESSION['error'] = "Invalid booking.";
            header("Location: ../tours.php");
            exit;
        }
        
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Store booking data in session
        $_SESSION['booking_data'] = [
            'booking_id' => $booking['booking_id'],
            'booking_reference' => $booking['booking_reference'],
            'tour_name' => $booking['tour_name'],
            'departure_date' => $booking['departure_date'],
            'return_date' => $booking['return_date'],
            'num_people' => $booking['num_people'],
            'base_amount' => $booking['base_amount'],
            'tax_amount' => $booking['tax_amount'],
            'total_amount' => $booking['total_amount']
        ];
        
    } catch (Exception $e) {
        // Log the error
        error_log("Payment page error: " . $e->getMessage());
        
        // Display error message
        $_SESSION['error'] = "An error occurred while retrieving booking information.";
        header("Location: ../tours.php");
        exit;
    }
}

// Get booking data from session
$booking_data = $_SESSION['booking_data'];

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    
    if (empty($payment_method)) {
        $_SESSION['error'] = "Please select a payment method.";
    } else {
        try {
            // Database connection
            $database = new Database();
            $db = $database->getConnection();
            
            // Generate transaction ID
            $transaction_id = 'TXN' . time() . rand(1000, 9999);
            
            // Insert payment record
            $query = "INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_status, transaction_id)
                     VALUES (:booking_id, :user_id, :amount, :payment_method, 'completed', :transaction_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":booking_id", $booking_data['booking_id']);
            $stmt->bindParam(":user_id", $_SESSION['user_id']);
            $stmt->bindParam(":amount", $booking_data['total_amount']);
            $stmt->bindParam(":payment_method", $payment_method);
            $stmt->bindParam(":transaction_id", $transaction_id);
            $stmt->execute();
            
            // Update booking status (if the column exists)
            $column_exists = $db->query("SHOW COLUMNS FROM bookings LIKE 'booking_status'")->rowCount() > 0;
            $payment_status_exists = $db->query("SHOW COLUMNS FROM bookings LIKE 'payment_status'")->rowCount() > 0;
            
            if ($column_exists && $payment_status_exists) {
                $update_query = "UPDATE bookings SET booking_status = 'confirmed', payment_status = 'paid', 
                               payment_method = :payment_method WHERE booking_id = :booking_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":payment_method", $payment_method);
                $update_stmt->bindParam(":booking_id", $booking_data['booking_id']);
                $update_stmt->execute();
            }
            
            // Clear booking data from session
            unset($_SESSION['booking_data']);
            
            // Set success message
            $_SESSION['success'] = "Payment successful! Your booking is confirmed.";
            
            // Redirect to booking details page
            header("Location: booking-details.php?id=" . $booking_data['booking_id']);
            exit;
            
        } catch (Exception $e) {
            // Log the error
            error_log("Payment processing error: " . $e->getMessage());
            
            // Display error message
            $_SESSION['error'] = "An error occurred while processing your payment. Please try again.";
        }
    }
}

// Include user header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Payment</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="booking-summary mb-4">
                        <h4>Booking Summary</h4>
                        <table class="table">
                            <tr>
                                <th>Booking Reference:</th>
                                <td><?php echo $booking_data['booking_reference']; ?></td>
                            </tr>
                            <tr>
                                <th>Tour:</th>
                                <td><?php echo htmlspecialchars($booking_data['tour_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Departure Date:</th>
                                <td><?php echo date('F d, Y', strtotime($booking_data['departure_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td><?php echo date('F d, Y', strtotime($booking_data['return_date'])); ?></td>
                            </tr>
                            <tr>
                                <th>Number of Travelers:</th>
                                <td><?php echo $booking_data['num_people']; ?></td>
                            </tr>
                            <tr>
                                <th>Base Amount:</th>
                                <td>₹<?php echo number_format($booking_data['base_amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Tax (5%):</th>
                                <td>₹<?php echo number_format($booking_data['tax_amount'], 2); ?></td>
                            </tr>
                            <tr class="table-primary">
                                <th>Total Amount:</th>
                                <td>₹<?php echo number_format($booking_data['total_amount'], 2); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <form method="post">
                        <div class="mb-4">
                            <h5>Select Payment Method</h5>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="credit_card" value="credit_card" required>
                                <label class="form-check-label" for="credit_card">
                                    Credit Card
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="debit_card" value="debit_card">
                                <label class="form-check-label" for="debit_card">
                                    Debit Card
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="net_banking" value="net_banking">
                                <label class="form-check-label" for="net_banking">
                                    Net Banking
                                </label>
                            </div>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="payment_method" id="upi" value="upi">
                                <label class="form-check-label" for="upi">
                                    UPI
                                </label>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            This is a demo payment page. No actual payment will be processed.
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg">Complete Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
