<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Start session
Session::start();

// Check if user is logged in and is admin
if (!Session::isLoggedIn() || !Session::isAdmin()) {
    echo "You must be an admin to access this page.";
    exit;
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get tour ID from URL if provided
$tour_id = isset($_GET['tour_id']) ? $_GET['tour_id'] : null;

// Get all schedules
if ($tour_id) {
    $query = "SELECT ts.*, t.title as tour_title 
              FROM tour_schedules ts 
              JOIN tours t ON ts.tour_id = t.tour_id 
              WHERE ts.tour_id = :tour_id
              ORDER BY ts.start_date";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":tour_id", $tour_id);
} else {
    $query = "SELECT ts.*, t.title as tour_title 
              FROM tour_schedules ts 
              JOIN tours t ON ts.tour_id = t.tour_id 
              ORDER BY ts.start_date";
    $stmt = $db->prepare($query);
}

$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Tour Schedules</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Tour Schedules</h1>
        
        <form action="" method="get" class="mb-4">
            <div class="input-group">
                <input type="text" name="tour_id" class="form-control" placeholder="Enter Tour ID" value="<?php echo $tour_id; ?>">
                <button type="submit" class="btn btn-primary">Filter</button>
                <a href="debug-schedules.php" class="btn btn-secondary">Show All</a>
            </div>
        </form>
        
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tour</th>
                    <th>Start Date</th>
                    <th>End Date</th>
                    <th>Available Seats</th>
                    <th>Status</th>
                    <th>Price Adjustment</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($schedules) > 0): ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?php echo $schedule['schedule_id']; ?></td>
                            <td><?php echo $schedule['tour_title']; ?> (ID: <?php echo $schedule['tour_id']; ?>)</td>
                            <td><?php echo $schedule['start_date']; ?></td>
                            <td><?php echo $schedule['end_date']; ?></td>
                            <td><?php echo $schedule['available_seats']; ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $schedule['status'] == 'upcoming' ? 'primary' : 
                                        ($schedule['status'] == 'ongoing' ? 'success' : 
                                            ($schedule['status'] == 'completed' ? 'secondary' : 'danger')); 
                                ?>">
                                    <?php echo $schedule['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $schedule['price_adjustment']; ?></td>
                            <td><?php echo $schedule['created_at']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center">No schedules found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <div class="card mt-4">
            <div class="card-header">
                <h5>Debug Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Query:</strong> <?php echo $query; ?></p>
                <p><strong>Current Date:</strong> <?php echo date('Y-m-d'); ?></p>
                <p><strong>PHP Version:</strong> <?php echo phpversion(); ?></p>
                <p><strong>Database Connection:</strong> <?php echo $db ? 'Success' : 'Failed'; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
