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

// Get all customers who have booked tours from this admin
$query = "SELECT DISTINCT u.user_id, u.username, u.full_name, u.email, u.phone, u.created_at,
          (SELECT COUNT(*) FROM bookings b JOIN tours t ON b.tour_id = t.tour_id WHERE b.user_id = u.user_id AND t.admin_id = :admin_id) as booking_count,
          (SELECT SUM(total_amount) FROM bookings b JOIN tours t ON b.tour_id = t.tour_id WHERE b.user_id = u.user_id AND t.admin_id = :admin_id) as total_spent
          FROM users u
          JOIN bookings b ON u.user_id = b.user_id
          JOIN tours t ON b.tour_id = t.tour_id
          WHERE t.admin_id = :admin_id AND u.role = 'user'
          ORDER BY booking_count DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Customer Management</h1>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Customers</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Bookings</th>
                                    <th>Total Spent</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No customers found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?php echo $customer['user_id']; ?></td>
                                            <td><?php echo $customer['full_name']; ?></td>
                                            <td><?php echo $customer['username']; ?></td>
                                            <td><?php echo $customer['email']; ?></td>
                                            <td><?php echo $customer['phone'] ? $customer['phone'] : 'N/A'; ?></td>
                                            <td><?php echo $customer['booking_count']; ?></td>
                                            <td>$<?php echo number_format($customer['total_spent'], 2); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($customer['created_at'])); ?></td>
                                            <td>
                                                <a href="customer-details.php?id=<?php echo $customer['user_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
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
