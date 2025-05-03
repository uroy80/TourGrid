<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include necessary files
require_once '../config/database.php';
require_once '../config/session.php';

// Start session
Session::start();

// Check if user is logged in and is admin
if (!Session::isLoggedIn() || !Session::isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Check if booking ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: bookings.php");
    exit;
}

$booking_id = $_GET['id'];

// Database connection
$database = new Database();
$db = $database->getConnection();

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete') {
    // Start transaction to ensure data consistency
    $db->beginTransaction();

    try {
        // First delete related travelers (to avoid foreign key constraint errors)
        $delete_travelers = "DELETE FROM booking_travelers WHERE booking_id = :booking_id";
        $stmt = $db->prepare($delete_travelers);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        // Then delete the booking
        $delete_booking = "DELETE FROM bookings WHERE booking_id = :booking_id";
        $stmt = $db->prepare($delete_booking);
        $stmt->bindParam(":booking_id", $booking_id);
        $stmt->execute();

        // Commit the transaction
        $db->commit();

        // Redirect to bookings page with success message
        header("Location: bookings.php?deleted=1");
        exit;
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $db->rollBack();
        $error_msg = "Error deleting booking: " . $e->getMessage();
    }
}

// Get booking details - FIXED QUERY by removing ts.price which doesn't exist
$query = "SELECT b.*, u.username, u.full_name, u.email, u.phone, 
        t.title as tour_title, t.description as tour_description, t.price as tour_price,
        ts.start_date as tour_date, ts.end_date
        FROM bookings b 
        JOIN users u ON b.user_id = u.user_id 
        JOIN tours t ON b.tour_id = t.tour_id 
        JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
        WHERE b.booking_id = :booking_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":booking_id", $booking_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    // Booking not found
    header("Location: bookings.php");
    exit;
}

$booking = $stmt->fetch(PDO::FETCH_ASSOC);

// Get travelers for this booking
$query = "SELECT * FROM booking_travelers WHERE booking_id = :booking_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":booking_id", $booking_id);
$stmt->execute();
$travelers = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include admin header
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Booking Details</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="bookings.php" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                </div>
            </div>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Booking Summary -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Booking Summary</h6>
                            <span class="badge <?php
                            if ($booking['status'] == 'confirmed') echo 'bg-success';
                            elseif ($booking['status'] == 'pending') echo 'bg-warning';
                            elseif ($booking['status'] == 'cancelled') echo 'bg-danger';
                            else echo 'bg-secondary';
                            ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Booking ID:</strong> #<?php echo $booking['booking_id']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Tour Package:</strong> <?php echo $booking['tour_title']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Departure Date:</strong> <?php echo date('M d, Y', strtotime($booking['tour_date'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Return Date:</strong> <?php echo date('M d, Y', strtotime($booking['end_date'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Number of Travelers:</strong> <?php echo $booking['num_people']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Booking Date:</strong> <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Base Amount:</strong> ₹<?php echo number_format($booking['base_amount'], 2); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Tax Amount:</strong> ₹<?php echo number_format($booking['tax_amount'], 2); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Discount:</strong> ₹<?php echo number_format($booking['discount_amount'], 2); ?>
                            </div>
                            <div class="mb-3">
                                <strong>Total Amount:</strong> ₹<?php echo number_format($booking['total_amount'], 2); ?>
                            </div>
                            <?php if (!empty($booking['special_requests'])): ?>
                                <div class="mb-3">
                                    <strong>Special Requests:</strong> <?php echo $booking['special_requests']; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Customer Information -->
                <div class="col-md-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Customer Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <strong>Name:</strong> <?php echo $booking['full_name']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Email:</strong> <?php echo $booking['email']; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Phone:</strong> <?php echo $booking['phone'] ? $booking['phone'] : 'N/A'; ?>
                            </div>
                            <div class="mb-3">
                                <strong>Username:</strong> <?php echo $booking['username']; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="card shadow mt-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($booking['status'] == 'pending'): ?>
                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&status=confirmed" class="btn btn-success" onclick="return confirm('Are you sure you want to confirm this booking?');">
                                        <i class="fas fa-check"></i> Confirm Booking
                                    </a>
                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&status=cancelled" class="btn btn-warning" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                        <i class="fas fa-ban"></i> Cancel Booking
                                    </a>
                                <?php elseif ($booking['status'] == 'confirmed'): ?>
                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&status=completed" class="btn btn-info" onclick="return confirm('Are you sure you want to mark this booking as completed?');">
                                        <i class="fas fa-check-double"></i> Mark as Completed
                                    </a>
                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&status=cancelled" class="btn btn-warning" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                        <i class="fas fa-ban"></i> Cancel Booking
                                    </a>
                                <?php endif; ?>
                                <a href="../booking-voucher.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-secondary" target="_blank">
                                    <i class="fas fa-file-alt"></i> Generate Voucher
                                </a>
                                <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>&action=delete" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                    <i class="fas fa-trash"></i> Delete Booking
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Travelers Information -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Travelers Information</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>ID Type</th>
                                <th>ID Number</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($travelers)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">No traveler information available</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($travelers as $traveler): ?>
                                    <tr>
                                        <td><?php echo $traveler['name']; ?></td>
                                        <td><?php echo $traveler['age']; ?></td>
                                        <td><?php echo ucfirst($traveler['gender']); ?></td>
                                        <td><?php echo ucfirst($traveler['id_type']); ?></td>
                                        <td><?php echo $traveler['id_number']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include admin footer
include_once 'includes/footer.php';
?>
