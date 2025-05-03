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
    exit;
}

$booking_id = intval($_GET['id']);

// Get booking data from database
try {
    $database = new Database();
    $db = $database->getConnection();

    // Get booking details with tour and schedule information
    $query = "SELECT b.*, t.title as tour_name, t.price, t.description,
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
        echo "<div class='alert alert-danger'>Booking not found.</div>";
        exit;
    }

    $booking = $stmt->fetch(PDO::FETCH_ASSOC);

    // Get traveler details
    $travelers_query = "SELECT * FROM booking_travelers WHERE booking_id = :booking_id";
    $travelers_stmt = $db->prepare($travelers_query);
    $travelers_stmt->bindParam(":booking_id", $booking_id);
    $travelers_stmt->execute();
    $travelers = $travelers_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if booking is confirmed
    if ($booking['status'] !== 'confirmed') {
        echo "<div class='alert alert-warning'>This booking is not confirmed yet. Please complete the payment first.</div>";
        exit;
    }

} catch (Exception $e) {
    // Log the error
    error_log("Booking voucher page error: " . $e->getMessage());

    // Display error message
    echo "<div class='alert alert-danger'>An error occurred while retrieving booking information: " . $e->getMessage() . "</div>";
    exit;
}

// Generate booking reference if not available
$booking_reference = isset($booking['booking_reference']) ? $booking['booking_reference'] : 'TG' . str_pad($booking['booking_id'], 6, '0', STR_PAD_LEFT);

// Set content type to PDF
header('Content-Type: text/html');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Voucher - <?php echo $booking_reference; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #007bff;
        }
        .voucher-title {
            font-size: 22px;
            margin: 10px 0;
        }
        .booking-ref {
            font-size: 16px;
            color: #666;
        }
        .section {
            margin-bottom: 30px;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 14px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .print-btn {
            display: block;
            text-align: center;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <button class="print-btn" onclick="window.print()">Print Voucher</button>

    <div class="header">
        <div class="logo">TourSync</div>
        <div class="voucher-title">BOOKING VOUCHER</div>
        <div class="booking-ref">Booking Reference: <?php echo $booking_reference; ?></div>
    </div>

    <div class="section">
        <div class="section-title">Tour Details</div>
        <table>
            <tr>
                <th>Tour Name:</th>
                <td><?php echo htmlspecialchars($booking['tour_name']); ?></td>
            </tr>
            <tr>
                <th>Departure Date:</th>
                <td><?php echo date('F d, Y', strtotime($booking['start_date'])); ?></td>
            </tr>
            <tr>
                <th>Return Date:</th>
                <td><?php echo date('F d, Y', strtotime($booking['end_date'])); ?></td>
            </tr>
            <tr>
                <th>Number of Travelers:</th>
                <td><?php echo $booking['num_people']; ?></td>
            </tr>
            <?php if (isset($booking['description']) && !empty($booking['description'])): ?>
                <tr>
                    <th>Tour Description:</th>
                    <td><?php echo nl2br(htmlspecialchars($booking['description'])); ?></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Traveler Information</div>
        <table>
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
            <?php if (!empty($travelers)): ?>
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
            <?php else: ?>
                <tr>
                    <td colspan="6">No traveler information available</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Payment Information</div>
        <table>
            <tr>
                <th>Base Amount:</th>
                <td>₹<?php echo number_format($booking['base_amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Tax:</th>
                <td>₹<?php echo number_format($booking['tax_amount'], 2); ?></td>
            </tr>
            <?php if (isset($booking['discount_amount']) && $booking['discount_amount'] > 0): ?>
                <tr>
                    <th>Discount:</th>
                    <td>₹<?php echo number_format($booking['discount_amount'], 2); ?></td>
                </tr>
            <?php endif; ?>
            <tr>
                <th>Total Amount:</th>
                <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Payment Method:</th>
                <td><?php echo isset($booking['payment_method']) ? ucfirst(str_replace('_', ' ', $booking['payment_method'])) : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Transaction ID:</th>
                <td><?php echo isset($booking['transaction_id']) ? $booking['transaction_id'] : 'N/A'; ?></td>
            </tr>
            <tr>
                <th>Payment Date:</th>
                <td><?php echo isset($booking['payment_date']) ? date('F d, Y H:i:s', strtotime($booking['payment_date'])) : 'N/A'; ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Important Information</div>
        <ul>
            <li>Please arrive at the departure point at least 30 minutes before the scheduled departure time.</li>
            <li>Carry a valid photo ID proof for all travelers.</li>
            <li>For any queries or changes to your booking, please contact our customer support at admin@kiwi.ind.in or call +91-9735770574.</li>
            <li>Cancellation policy: Cancellations made 7 days or more before the departure date are eligible for a full refund. Cancellations made within 7 days of the departure date are subject to a 50% cancellation fee.</li>
        </ul>
    </div>

    <div class="footer">
        <p>Thank you for choosing TourSync for your travel needs!</p>
        <p>This is a computer-generated voucher and does not require a signature.</p>
        <p>Booking Date: <?php echo date('F d, Y', strtotime($booking['booking_date'] ?? $booking['created_at'])); ?></p>
    </div>
</div>
</body>
</html>
