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

// Delete hotel if requested
if (isset($_GET['delete']) && !empty($_GET['delete'])) {
    $hotel_id = $_GET['delete'];
    
    // Check if the hotel exists and belongs to this admin
    $check_query = "SELECT * FROM hotels WHERE hotel_id = :hotel_id AND admin_id = :admin_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":hotel_id", $hotel_id);
    $check_stmt->bindParam(":admin_id", $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Delete the hotel
        $delete_query = "DELETE FROM hotels WHERE hotel_id = :hotel_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->bindParam(":hotel_id", $hotel_id);
        
        if ($delete_stmt->execute()) {
            $success_msg = "Hotel deleted successfully.";
        } else {
            $error_msg = "Error deleting hotel.";
        }
    } else {
        $error_msg = "Hotel not found or you do not have permission to delete it.";
    }
}

// Get all hotels with destination names for this admin
$query = "SELECT h.*, d.name as destination_name 
          FROM hotels h 
          LEFT JOIN destinations d ON h.destination_id = d.destination_id 
          WHERE h.admin_id = :admin_id
          ORDER BY h.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$hotels = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Hotel Details View</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="hotel-details-entry.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add New Hotel
                    </a>
                </div>
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
                    <h6 class="m-0 font-weight-bold text-primary">All Hotels</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Name</th>
                                    <th>Destination</th>
                                    <th>Address</th>
                                    <th>Star Rating</th>
                                    <th>Price/Night</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($hotels)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No hotels found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($hotels as $hotel): ?>
                                        <tr>
                                            <td><?php echo $hotel['hotel_id']; ?></td>
                                            <td>
                                                <img src="../assets/images/hotels/<?php echo $hotel['image']; ?>" alt="<?php echo $hotel['name']; ?>" class="img-thumbnail" style="max-width: 80px;">
                                            </td>
                                            <td><?php echo $hotel['name']; ?></td>
                                            <td><?php echo $hotel['destination_name']; ?></td>
                                            <td><?php echo substr($hotel['address'], 0, 50) . (strlen($hotel['address']) > 50 ? '...' : ''); ?></td>
                                            <td>
                                                <?php for ($i = 0; $i < $hotel['star_rating']; $i++): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php endfor; ?>
                                            </td>
                                            <td>$<?php echo number_format($hotel['price_per_night'], 2); ?></td>
                                            <td>
                                                <a href="hotel-details-edit.php?id=<?php echo $hotel['hotel_id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <a href="hotel-details-view.php?delete=<?php echo $hotel['hotel_id']; ?>" class="btn btn-sm btn-danger btn-delete" onclick="return confirm('Are you sure you want to delete this hotel?');">
                                                    <i class="fas fa-trash"></i>
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
