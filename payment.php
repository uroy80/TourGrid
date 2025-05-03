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

// Debug information
echo "<div style='background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
echo "<h3>Debug Information</h3>";
echo "<p><strong>GET Data:</strong></p>";
echo "<pre>";
print_r($_GET);
echo "</pre>";
echo "<p><strong>Session Data:</strong></p>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
echo "</div>";

// Check if booking_id is provided
if (!isset($_GET['booking_id']) || empty($_GET['booking_id'])) {
    echo "<div class='alert alert-danger'>No booking ID provided.</div>";
    exit;
}

$booking_id = intval($_GET['booking_id']);

// Get booking data from database
try {
    $database = new Database();
    $db = $database->getConnection();

    // Get booking details with tour and schedule information
    $query = "SELECT b.*, t.title as tour_name, t.price, 
              ts.start_date, ts.end_date, ts.available_seats
              FROM bookings b
              JOIN tours t ON b.tour_id = t.tour_id
              JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
              WHERE b.booking_id = :booking_id";

    $stmt = $db->prepare($query);
    $stmt->bindParam(":booking_id", $booking_id);
    $stmt->execute();

    if ($stmt->rowCount() == 0) {
        echo "<div class='alert alert-danger'>Booking not found.</div>";
        exit;
    }

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    // Debug: Show the booking data
    echo "<div style='background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
    echo "<h3>Booking Data</h3>";
    echo "<pre>";
    print_r($booking);
    echo "</pre>";
    echo "</div>";

    // Check if the tour schedule is available
    if (isset($booking['available_seats']) && $booking['available_seats'] < 0) {
        echo "<div class='alert alert-danger'>Sorry, this tour schedule is fully booked.</div>";
    }

    // Store booking data in session
    $_SESSION['booking_data'] = [
        'booking_id' => $booking['booking_id'],
        'booking_reference' => isset($booking['booking_reference']) ? $booking['booking_reference'] : 'TG' . str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT),
        'tour_name' => $booking['tour_name'],
        'departure_date' => $booking['start_date'],
        'return_date' => $booking['end_date'],
        'num_people' => $booking['num_people'],
        'base_amount' => $booking['base_amount'],
        'tax_amount' => $booking['tax_amount'],
        'total_amount' => $booking['total_amount']
    ];

} catch (Exception $e) {
    // Log the error
    error_log("Payment page error: " . $e->getMessage());

    // Display error message
    echo "<div class='alert alert-danger'>An error occurred while retrieving booking information: " . $e->getMessage() . "</div>";
    exit;
}

// Get booking data from session
$booking_data = $_SESSION['booking_data'];

// Process payment form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';

    if (empty($payment_method)) {
        echo "<div class='alert alert-danger'>Please select a payment method.</div>";
    } else {
        try {
            // Generate transaction ID
            $transaction_id = 'TXN' . time() . rand(1000, 9999);

            // Check if payments table exists
            $table_exists = $db->query("SHOW TABLES LIKE 'payments'")->rowCount() > 0;

            if (!$table_exists) {
                // Create payments table if it doesn't exist
                $create_table_query = "CREATE TABLE payments (
                    payment_id INT(11) NOT NULL AUTO_INCREMENT,
                    booking_id INT(11) NOT NULL,
                    user_id INT(11),
                    amount DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(50) NOT NULL,
                    payment_status ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
                    transaction_id VARCHAR(100) NOT NULL,
                    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (payment_id),
                    KEY booking_id (booking_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

                $db->exec($create_table_query);
            }

            // Get user ID if logged in
            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

            // Insert payment record
            $query = "INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_status, transaction_id)
                     VALUES (:booking_id, :user_id, :amount, :payment_method, 'completed', :transaction_id)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":booking_id", $booking_data['booking_id']);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":amount", $booking_data['total_amount']);
            $stmt->bindParam(":payment_method", $payment_method);
            $stmt->bindParam(":transaction_id", $transaction_id);
            $stmt->execute();

            // Update booking status
            $update_query = "UPDATE bookings SET status = 'confirmed' WHERE booking_id = :booking_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":booking_id", $booking_data['booking_id']);
            $update_stmt->execute();

            // Clear booking data from session
            unset($_SESSION['booking_data']);

            // Set success message
            echo "<div class='alert alert-success'>Payment successful! Your booking is confirmed.</div>";

            // Redirect to booking details page
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'booking-details.php?id=" . $booking_data['booking_id'] . "';
                }, 2000);
            </script>";
            exit;

        } catch (Exception $e) {
            // Log the error
            error_log("Payment processing error: " . $e->getMessage());

            // Display error message
            echo "<div class='alert alert-danger'>An error occurred while processing your payment: " . $e->getMessage() . "</div>";
        }
    }
}

// Include header
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
                    <div class="booking-summary mb-4">
                        <h4>Booking Summary</h4>
                        <table class="table">
                            <tr>
                                <th>Booking Reference:</th>
                                <td><?php echo isset($booking_data['booking_reference']) ? $booking_data['booking_reference'] : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Tour:</th>
                                <td><?php echo isset($booking_data['tour_name']) ? htmlspecialchars($booking_data['tour_name']) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Departure Date:</th>
                                <td><?php echo isset($booking_data['departure_date']) ? date('F d, Y', strtotime($booking_data['departure_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Return Date:</th>
                                <td><?php echo isset($booking_data['return_date']) ? date('F d, Y', strtotime($booking_data['return_date'])) : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Number of Travelers:</th>
                                <td><?php echo isset($booking_data['num_people']) ? $booking_data['num_people'] : 'N/A'; ?></td>
                            </tr>
                            <tr>
                                <th>Base Amount:</th>
                                <td>₹<?php echo isset($booking_data['base_amount']) ? number_format($booking_data['base_amount'], 2) : '0.00'; ?></td>
                            </tr>
                            <tr>
                                <th>Tax (5%):</th>
                                <td>₹<?php echo isset($booking_data['tax_amount']) ? number_format($booking_data['tax_amount'], 2) : '0.00'; ?></td>
                            </tr>
                            <tr class="table-primary">
                                <th>Total Amount:</th>
                                <td>₹<?php echo isset($booking_data['total_amount']) ? number_format($booking_data['total_amount'], 2) : '0.00'; ?></td>
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
