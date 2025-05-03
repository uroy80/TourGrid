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

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get current admin ID
$admin_id = Session::get('user_id');

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id']) && !empty($_GET['id'])) {
    $booking_id = $_GET['id'];

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

        $success_msg = "Booking #$booking_id has been deleted successfully.";
    } catch (Exception $e) {
        // Rollback the transaction if something went wrong
        $db->rollBack();
        $error_msg = "Error deleting booking: " . $e->getMessage();
    }
}

// Update booking status if requested
if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['status']) && !empty($_GET['status'])) {
    $booking_id = $_GET['id'];
    $status = $_GET['status'];

    // Check if the booking exists
    $check_query = "SELECT b.* FROM bookings b 
                 WHERE b.booking_id = :booking_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":booking_id", $booking_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        // Update the booking status
        $update_query = "UPDATE bookings SET status = :status, updated_at = NOW() WHERE booking_id = :booking_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":status", $status);
        $update_stmt->bindParam(":booking_id", $booking_id);

        if ($update_stmt->execute()) {
            $success_msg = "Booking status updated successfully.";
        } else {
            $error_msg = "Error updating booking status.";
        }
    } else {
        $error_msg = "Booking not found.";
    }
}

// Get all bookings with user and tour details
$query = "SELECT b.*, u.username, u.full_name, u.email, u.phone, 
        t.title as tour_title, ts.start_date as tour_date, ts.end_date
        FROM bookings b 
        JOIN users u ON b.user_id = u.user_id 
        JOIN tours t ON b.tour_id = t.tour_id 
        JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
        ORDER BY b.booking_date DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Booking Management</h1>
            </div>

            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Bookings</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Customer</th>
                                <th>Contact</th>
                                <th>Tour</th>
                                <th>Tour Date</th>
                                <th>Booking Date</th>
                                <th>People</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th style="min-width: 120px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="10" class="text-center">No bookings found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?php echo $booking['booking_id']; ?></td>
                                        <td><?php echo $booking['full_name']; ?></td>
                                        <td>
                                            <small>
                                                <strong>Email:</strong> <?php echo $booking['email']; ?><br>
                                                <strong>Phone:</strong> <?php echo $booking['phone'] ? $booking['phone'] : 'N/A'; ?>
                                            </small>
                                        </td>
                                        <td><?php echo $booking['tour_title']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['tour_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                        <td><?php echo $booking['num_people']; ?></td>
                                        <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                        <td>
                                            <?php if ($booking['status'] == 'confirmed'): ?>
                                                <span class="badge bg-success">Confirmed</span>
                                            <?php elseif ($booking['status'] == 'pending'): ?>
                                                <span class="badge bg-warning">Pending</span>
                                            <?php elseif ($booking['status'] == 'cancelled'): ?>
                                                <span class="badge bg-danger">Cancelled</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <!-- Simple action buttons instead of dropdown -->
                                            <div class="btn-group btn-group-sm">
                                                <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </a>

                                                <?php if ($booking['status'] == 'pending'): ?>
                                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&status=confirmed" class="btn btn-success" title="Confirm" onclick="return confirm('Are you sure you want to confirm this booking?');">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($booking['status'] != 'cancelled'): ?>
                                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&status=cancelled" class="btn btn-warning" title="Cancel" onclick="return confirm('Are you sure you want to cancel this booking?');">
                                                        <i class="fas fa-ban"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <?php if ($booking['status'] == 'confirmed'): ?>
                                                    <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&status=completed" class="btn btn-info" title="Complete" onclick="return confirm('Are you sure you want to mark this booking as completed?');">
                                                        <i class="fas fa-check-double"></i>
                                                    </a>
                                                <?php endif; ?>

                                                <a href="../booking-voucher.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-secondary" title="Voucher" target="_blank">
                                                    <i class="fas fa-file-alt"></i>
                                                </a>

                                                <a href="bookings.php?id=<?php echo $booking['booking_id']; ?>&action=delete" class="btn btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
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
