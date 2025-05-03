<?php
session_start();
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'Please login to view your bookings', 'warning');
}

$userId = $_SESSION['user_id'];

// Get filter parameters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$dateRange = isset($_GET['date_range']) ? $_GET['date_range'] : '';
$sortBy = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'booking_date';
$sortOrder = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

// Build query based on filters
$conn = connectDatabase();
$sql = "SELECT b.*, t.title as tour_title, t.image as tour_image, 
        d.name as destination_name, ts.start_date, ts.end_date, ts.status as schedule_status
        FROM bookings b
        JOIN tours t ON b.tour_id = t.tour_id
        JOIN destinations d ON t.destination_id = d.destination_id
        JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
        WHERE b.user_id = ?";

$params = [$userId];
$types = [PDO::PARAM_INT];

// Add status filter
if (!empty($status)) {
    $sql .= " AND b.booking_status = ?";
    $params[] = $status;
    $types[] = PDO::PARAM_STR;
}

// Add date range filter
if (!empty($dateRange)) {
    if ($dateRange === 'upcoming') {
        $sql .= " AND ts.start_date >= CURDATE()";
    } elseif ($dateRange === 'past') {
        $sql .= " AND ts.end_date < CURDATE()";
    } elseif ($dateRange === 'current') {
        $sql .= " AND ts.start_date <= CURDATE() AND ts.end_date >= CURDATE()";
    }
}

// Add sorting
$validSortColumns = ['booking_date', 'start_date', 'total_amount'];
$validSortOrders = ['ASC', 'DESC'];

if (!in_array($sortBy, $validSortColumns)) {
    $sortBy = 'booking_date';
}

if (!in_array($sortOrder, $validSortOrders)) {
    $sortOrder = 'DESC';
}

$sql .= " ORDER BY b.{$sortBy} {$sortOrder}";

$stmt = $conn->prepare($sql);

for ($i = 0; $i < count($params); $i++) {
    $stmt->bindParam($i + 1, $params[$i], $types[$i]);
}

$stmt->execute();
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once 'includes/header.php';
?>

<div class="container mt-4 mb-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>My Bookings</h2>
        <a href="../tours.php" class="btn btn-primary">
            <i class="fas fa-plus-circle me-2"></i> Book New Tour
        </a>
    </div>
    
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <h5 class="mb-0">Filter Bookings</h5>
        </div>
        <div class="card-body">
            <form action="" method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">Booking Status</label>
                    <select name="status" id="status" class="form-select">
                        <option value="">All Statuses</option>
                        <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="date_range" class="form-label">Date Range</label>
                    <select name="date_range" id="date_range" class="form-select">
                        <option value="">All Dates</option>
                        <option value="upcoming" <?php echo $dateRange === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="current" <?php echo $dateRange === 'current' ? 'selected' : ''; ?>>Current</option>
                        <option value="past" <?php echo $dateRange === 'past' ? 'selected' : ''; ?>>Past</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort_by" class="form-label">Sort By</label>
                    <select name="sort_by" id="sort_by" class="form-select">
                        <option value="booking_date" <?php echo $sortBy === 'booking_date' ? 'selected' : ''; ?>>Booking Date</option>
                        <option value="start_date" <?php echo $sortBy === 'start_date' ? 'selected' : ''; ?>>Departure Date</option>
                        <option value="total_amount" <?php echo $sortBy === 'total_amount' ? 'selected' : ''; ?>>Amount</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="sort_order" class="form-label">Sort Order</label>
                    <select name="sort_order" id="sort_order" class="form-select">
                        <option value="DESC" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                        <option value="ASC" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="bookings.php" class="btn btn-secondary ms-2">Reset Filters</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (count($bookings) > 0): ?>
        <div class="row">
            <?php foreach ($bookings as $booking): ?>
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="row g-0">
                            <div class="col-md-4">
                                <img src="<?php echo getImagePath($booking['tour_image'], 'tours'); ?>" class="img-fluid rounded-start h-100" style="object-fit: cover;" alt="<?php echo $booking['tour_title']; ?>">
                            </div>
                            <div class="col-md-8">
                                <div class="card-body d-flex flex-column h-100">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <h5 class="card-title"><?php echo $booking['tour_title']; ?></h5>
                                        <?php if ($booking['booking_status'] === 'confirmed'): ?>
                                            <span class="badge bg-success">Confirmed</span>
                                        <?php elseif ($booking['booking_status'] === 'pending'): ?>
                                            <span class="badge bg-warning">Pending</span>
                                        <?php elseif ($booking['booking_status'] === 'cancelled'): ?>
                                            <span class="badge bg-danger">Cancelled</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="card-text text-muted"><?php echo $booking['destination_name']; ?></p>
                                    <div class="mb-2">
                                        <small class="text-muted">Booking Reference: <?php echo $booking['booking_reference']; ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Departure: <?php echo formatDate($booking['start_date']); ?></small><br>
                                        <small class="text-muted">Return: <?php echo formatDate($booking['end_date']); ?></small>
                                    </div>
                                    <div class="mb-2">
                                        <small class="text-muted">Amount: <?php echo formatCurrency($booking['total_amount']); ?></small>
                                    </div>
                                    <div class="mt-auto">
                                        <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-primary btn-sm">View Details</a>
                                        <?php if ($booking['booking_status'] !== 'cancelled' && strtotime($booking['start_date']) > time()): ?>
                                            <a href="booking-cancel.php?id=<?php echo $booking['booking_id']; ?>" class="btn btn-danger btn-sm">Cancel</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info">
            <h5 class="alert-heading">No bookings found</h5>
            <p>You don't have any bookings yet. Start exploring our tours and book your next adventure!</p>
            <a href="../tours.php" class="btn btn-primary">Explore Tours</a>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; ?>
