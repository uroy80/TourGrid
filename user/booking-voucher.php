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
$stmt = $conn->prepare("SELECT b.*, t.name as tour_name, t.image_url, t.duration, t.inclusions,
                        d.name as destination_name, ts.departure_date, ts.return_date,
                        u.first_name, u.last_name, u.email, u.phone,
                        p.payment_reference, p.payment_method, p.payment_date
                        FROM bookings b
                        JOIN tours t ON b.tour_id = t.tour_id
                        JOIN destinations d ON t.destination_id = d.destination_id
                        JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
                        JOIN users u ON b.user_id = u.user_id
                        LEFT JOIN payments p ON b.booking_id = p.booking_id
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

// Get traveler details
$stmt = $conn->prepare("SELECT * FROM booking_travelers WHERE booking_id = ?");
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$travelers = [];
while ($row = $result->fetch_assoc()) {
    $travelers[] = $row;
}
$stmt->close();

// Close database connection
$conn->close();

// Set page title
$pageTitle = "Booking Voucher";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TourSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
        }
        .voucher-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .voucher-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .voucher-logo {
            max-width: 200px;
            margin-bottom: 15px;
        }
        .voucher-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .voucher-subtitle {
            font-size: 16px;
            color: #666;
        }
        .voucher-section {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
        }
        .voucher-section-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        .voucher-info-row {
            display: flex;
            margin-bottom: 10px;
        }
        .voucher-info-label {
            font-weight: bold;
            width: 40%;
        }
        .voucher-info-value {
            width: 60%;
        }
        .voucher-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }
        .voucher-qr {
            text-align: center;
            margin: 20px 0;
        }
        .voucher-qr img {
            max-width: 150px;
        }
        .print-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
        }
        @media print {
            .print-button {
                display: none;
            }
            .voucher-container {
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="voucher-container">
        <div class="voucher-header">
            <div class="voucher-logo">
                <img src="../assets/images/logo.png" alt="TourSync Logo" class="img-fluid">
            </div>
            <div class="voucher-title">BOOKING VOUCHER</div>
            <div class="voucher-subtitle">Please present this voucher upon arrival</div>
        </div>
        
        <div class="voucher-section">
            <div class="voucher-section-title">Booking Information</div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Booking Reference:</div>
                <div class="voucher-info-value"><?php echo $booking['booking_reference']; ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Booking Date:</div>
                <div class="voucher-info-value"><?php echo date('F d, Y', strtotime($booking['booking_date'])); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Booking Status:</div>
                <div class="voucher-info-value"><?php echo ucfirst($booking['booking_status']); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Payment Status:</div>
                <div class="voucher-info-value"><?php echo ucfirst($booking['payment_status']); ?></div>
            </div>
            <?php if ($booking['payment_status'] === 'paid'): ?>
                <div class="voucher-info-row">
                    <div class="voucher-info-label">Payment Reference:</div>
                    <div class="voucher-info-value"><?php echo $booking['payment_reference']; ?></div>
                </div>
                <div class="voucher-info-row">
                    <div class="voucher-info-label">Payment Method:</div>
                    <div class="voucher-info-value"><?php echo ucfirst($booking['payment_method']); ?></div>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="voucher-section">
            <div class="voucher-section-title">Tour Details</div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Tour Name:</div>
                <div class="voucher-info-value"><?php echo htmlspecialchars($booking['tour_name']); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Destination:</div>
                <div class="voucher-info-value"><?php echo htmlspecialchars($booking['destination_name']); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Duration:</div>
                <div class="voucher-info-value"><?php echo $booking['duration']; ?> days</div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Departure Date:</div>
                <div class="voucher-info-value"><?php echo date('F d, Y', strtotime($booking['departure_date'])); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Return Date:</div>
                <div class="voucher-info-value"><?php echo date('F d, Y', strtotime($booking['return_date'])); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Number of Travelers:</div>
                <div class="voucher-info-value"><?php echo $booking['num_people']; ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Inclusions:</div>
                <div class="voucher-info-value"><?php echo nl2br(htmlspecialchars($booking['inclusions'])); ?></div>
            </div>
        </div>
        
        <div class="voucher-section">
            <div class="voucher-section-title">Customer Information</div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Name:</div>
                <div class="voucher-info-value"><?php echo htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Email:</div>
                <div class="voucher-info-value"><?php echo htmlspecialchars($booking['email']); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Phone:</div>
                <div class="voucher-info-value"><?php echo htmlspecialchars($booking['phone']); ?></div>
            </div>
        </div>
        
        <div class="voucher-section">
            <div class="voucher-section-title">Traveler Information</div>
            <table class="table table-bordered">
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
                            <td><?php echo formatIdType($traveler['id_type']); ?></td>
                            <td><?php echo htmlspecialchars($traveler['id_number']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="voucher-section">
            <div class="voucher-section-title">Payment Information</div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Base Amount:</div>
                <div class="voucher-info-value">$<?php echo number_format($booking['base_amount'], 2); ?></div>
            </div>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Tax Amount:</div>
                <div class="voucher-info-value">$<?php echo number_format($booking['tax_amount'], 2); ?></div>
            </div>
            <?php if ($booking['discount_amount'] > 0): ?>
                <div class="voucher-info-row">
                    <div class="voucher-info-label">Discount:</div>
                    <div class="voucher-info-value">-$<?php echo number_format($booking['discount_amount'], 2); ?></div>
                </div>
            <?php endif; ?>
            <div class="voucher-info-row">
                <div class="voucher-info-label">Total Amount:</div>
                <div class="voucher-info-value">$<?php echo number_format($booking['total_amount'], 2); ?></div>
            </div>
        </div>
        
        <div class="voucher-qr">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=<?php echo urlencode($booking['booking_reference']); ?>" alt="QR Code">
            <p>Scan this QR code to verify your booking</p>
        </div>
        
        <div class="voucher-footer">
            <p>This voucher is valid only for the specified tour and date. Please keep this voucher with you during the tour.</p>
            <p>For any inquiries, please contact us at admin@kiwi.ind.in or call +91-9735770574.</p>
            <p>&copy; <?php echo date('Y'); ?> TourSync. All rights reserved.</p>
        </div>
    </div>
    
    <button class="btn btn-primary print-button" onclick="window.print()">
        <i class="fas fa-print me-2"></i> Print Voucher
    </button>
    
    <?php
    // Helper function to format ID type
    function formatIdType($idType) {
        switch ($idType) {
            case 'passport':
                return 'Passport';
            case 'national_id':
                return 'National ID';
            case 'drivers_license':
                return 'Driver\'s License';
            default:
                return ucfirst($idType);
        }
    }
    ?>
</body>
</html>
