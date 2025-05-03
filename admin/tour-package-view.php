<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/helpers.php';

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

// Get all tours
$query = "SELECT t.*, d.name as destination_name 
         FROM tours t 
         JOIN destinations d ON t.destination_id = d.destination_id 
         ORDER BY t.tour_id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Tour Packages</h1>
                <a href="tour-package-entry.php" class="btn btn-primary">Add New Tour Package</a>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Tour Packages</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Image</th>
                                    <th>Title</th>
                                    <th>Destination</th>
                                    <th>Price</th>
                                    <th>Duration</th>
                                    <th>Featured</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($tours)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center">No tour packages found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($tours as $tour): ?>
                                        <tr>
                                            <td><?php echo $tour['tour_id']; ?></td>
                                            <td>
                                                <img src="<?php echo '../' . getImagePath($tour['image'], 'tours'); ?>" alt="<?php echo $tour['title']; ?>" width="50" height="50" class="img-thumbnail">
                                            </td>
                                            <td><?php echo $tour['title']; ?></td>
                                            <td><?php echo $tour['destination_name']; ?></td>
                                            <td>₹<?php echo number_format($tour['price']); ?></td>
                                            <td><?php echo $tour['duration']; ?> days</td>
                                            <td>
                                                <?php if ($tour['is_featured']): ?>
                                                    <span class="badge bg-success">Yes</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="tour-package-edit.php?id=<?php echo $tour['tour_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                                <a href="tour-package-delete.php?id=<?php echo $tour['tour_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this tour package?')">Delete</a>
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
