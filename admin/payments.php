<?php
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

// Update payment status if requested
if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['status']) && !empty($_GET['status'])) {
    $payment_id = $_GET['id'];
    $status = $_GET['status'];
    
    // Check if the payment exists and belongs to this admin's tour
    $check_query = "SELECT p.* FROM payments p 
                   JOIN bookings b ON p.booking_id = b.booking_id
                   JOIN tours t ON b.tour_id = t.tour_id 
                   WHERE p.payment_id = :payment_id AND t.admin_id = :admin_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":payment_id", $payment_id);
    $check_stmt->bindParam(":admin_id", $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Update the payment status
        $update_query = "UPDATE payments SET status = :status, updated_at = NOW() WHERE payment_id = :payment_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":status", $status);
        $update_stmt->bindParam(":payment_id", $payment_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Payment status updated successfully.";
        } else {
            $error_msg = "Error updating payment status.";
        }
    } else {
        $error_msg = "Payment not found or you do not have permission to update it.";
    }
}

// Get all payments with booking and user details for this admin
$query = "SELECT p.*, b.booking_id, b.total_amount as booking_amount, 
          u.username, u.full_name, t.title as tour_title
          FROM payments p 
          JOIN bookings b ON p.booking_id = b.booking_id 
          JOIN users u ON b.user_id = u.user_id
          JOIN tours t ON b.tour_id = t.tour_id 
          WHERE t.admin_id = :admin_id
          ORDER BY p.payment_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Payment Management</h1>
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
                    <h6 class="m-0 font-weight-bold text-primary">All Payments</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Booking</th>
                                    <th>Customer</th>
                                    <th>Tour</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Transaction ID</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($payments)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No payments found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($payments as $payment): ?>
                                        <tr>
                                            <td><?php echo $payment['payment_id']; ?></td>
                                            <td><a href="booking-details.php?id=<?php echo $payment['booking_id']; ?>">#<?php echo $payment['booking_id']; ?></a></td>
                                            <td><?php echo $payment['full_name']; ?></td>
                                            <td><?php echo $payment['tour_title']; ?></td>
                                            <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                            <td>
                                                <?php 
                                                    $method = ucfirst(str_replace('_', ' ', $payment['payment_method']));
                                                    echo $method;
                                                ?>
                                            </td>
                                            <td><?php echo $payment['transaction_id'] ? $payment['transaction_id'] : 'N/A'; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                            <td>
                                                <?php if ($payment['status'] == 'completed'): ?>
                                                    <span class="badge bg-success">Completed</span>
                                                <?php elseif ($payment['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php elseif ($payment['status'] == 'failed'): ?>
                                                    <span class="badge bg-danger">Failed</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Refunded</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-secondary dropdown-toggle" type="button" id="dropdownMenuButton<?php echo $payment['payment_id']; ?>" data-bs-toggle="dropdown" aria-expanded="false">
                                                        Actions
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?php echo $payment['payment_id']; ?>">
                                                        <?php if ($payment['status'] == 'pending'): ?>
                                                            <li><a class="dropdown-item" href="payments.php?id=<?php echo $payment['payment_id']; ?>&status=completed" onclick="return confirm('Are you sure you want to mark this payment as completed?');">Mark as Completed</a></li>
                                                            <li><a class="dropdown-item" href="payments.php?id=<?php echo $payment['payment_id']; ?>&status=failed" onclick="return confirm('Are you sure you want to mark this payment as failed?');">Mark as Failed</a></li>
                                                        <?php elseif ($payment['status'] == 'completed'): ?>
                                                            <li><a class="dropdown-item" href="payments.php?id=<?php echo $payment['payment_id']; ?>&status=refunded" onclick="return confirm('Are you sure you want to mark this payment as refunded?');">Mark as Refunded</a></li>
                                                        <?php endif; ?>
                                                        <li><a class="dropdown-item" href="payment-details.php?id=<?php echo $payment['payment_id']; ?>">View Details</a></li>
                                                    </ul>
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
