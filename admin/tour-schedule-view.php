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

// Process status update if requested
if (isset($_GET['action']) && isset($_GET['id']) && !empty($_GET['id'])) {
    $schedule_id = $_GET['id'];
    $action = $_GET['action'];

    // Check if the schedule exists and belongs to this admin's tour
    $check_query = "SELECT ts.*, t.title FROM tour_schedules ts 
                   JOIN tours t ON ts.tour_id = t.tour_id 
                   WHERE ts.schedule_id = :schedule_id AND t.admin_id = :admin_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":schedule_id", $schedule_id);
    $check_stmt->bindParam(":admin_id", $admin_id);
    $check_stmt->execute();

    if ($check_stmt->rowCount() > 0) {
        $schedule = $check_stmt->fetch(PDO::FETCH_ASSOC);

        if ($action == 'delete') {
            // Check if there are any bookings for this schedule
            $booking_check = "SELECT COUNT(*) as booking_count FROM bookings WHERE schedule_id = :schedule_id";
            $booking_stmt = $db->prepare($booking_check);
            $booking_stmt->bindParam(":schedule_id", $schedule_id);
            $booking_stmt->execute();
            $booking_count = $booking_stmt->fetch(PDO::FETCH_ASSOC)['booking_count'];

            if ($booking_count > 0) {
                $error_msg = "Cannot delete schedule as there are bookings associated with it.";
            } else {
                // Delete the schedule
                $delete_query = "DELETE FROM tour_schedules WHERE schedule_id = :schedule_id";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->bindParam(":schedule_id", $schedule_id);

                if ($delete_stmt->execute()) {
                    $success_msg = "Tour schedule deleted successfully.";
                } else {
                    $error_msg = "Error deleting tour schedule.";
                }
            }
        } elseif (in_array($action, ['upcoming', 'ongoing', 'completed', 'cancelled'])) {
            // Update the status
            $update_query = "UPDATE tour_schedules SET status = :status WHERE schedule_id = :schedule_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":status", $action);
            $update_stmt->bindParam(":schedule_id", $schedule_id);

            if ($update_stmt->execute()) {
                $success_msg = "Tour schedule status updated to " . ucfirst($action) . ".";
            } else {
                $error_msg = "Error updating tour schedule status.";
            }
        }
    } else {
        $error_msg = "Tour schedule not found or you do not have permission to modify it.";
    }
}

// Get all schedules with tour details for this admin
$query = "SELECT ts.*, t.title as tour_title, t.price, t.duration, 
          (SELECT COUNT(*) FROM bookings b WHERE b.schedule_id = ts.schedule_id) as booking_count
          FROM tour_schedules ts 
          JOIN tours t ON ts.tour_id = t.tour_id 
          WHERE t.admin_id = :admin_id
          ORDER BY ts.start_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Tour Schedule View</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="tour-schedule-entry.php" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus"></i> Add New Schedule
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
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">All Tour Schedules</h6>
                    <div>
                        <button class="btn btn-sm btn-outline-secondary" id="toggleFilters">
                            <i class="fas fa-filter"></i> Filters
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div id="filterOptions" class="mb-4" style="display: none;">
                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="statusFilter" class="form-label">Status</label>
                                <select id="statusFilter" class="form-select">
                                    <option value="">All Statuses</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="ongoing">Ongoing</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="tourFilter" class="form-label">Tour</label>
                                <select id="tourFilter" class="form-select">
                                    <option value="">All Tours</option>
                                    <?php
                                    $tour_query = "SELECT tour_id, title FROM tours WHERE admin_id = :admin_id ORDER BY title";
                                    $tour_stmt = $db->prepare($tour_query);
                                    $tour_stmt->bindParam(":admin_id", $admin_id);
                                    $tour_stmt->execute();
                                    $tours = $tour_stmt->fetchAll(PDO::FETCH_ASSOC);

                                    foreach ($tours as $tour) {
                                        echo '<option value="' . $tour['tour_id'] . '">' . htmlspecialchars($tour['title']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="dateFilter" class="form-label">Date Range</label>
                                <select id="dateFilter" class="form-select">
                                    <option value="">All Dates</option>
                                    <option value="upcoming">Upcoming</option>
                                    <option value="past">Past</option>
                                    <option value="next30">Next 30 Days</option>
                                    <option value="last30">Last 30 Days</option>
                                </select>
                            </div>
                            <div class="col-md-3 mb-3 d-flex align-items-end">
                                <button id="applyFilters" class="btn btn-primary me-2">Apply</button>
                                <button id="resetFilters" class="btn btn-secondary">Reset</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered" id="schedulesTable" width="100%" cellspacing="0">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tour</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Duration</th>
                                <th>Available Seats</th>
                                <th>Bookings</th>
                                <th>Guide</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th style="min-width: 150px;">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (empty($schedules)): ?>
                                <tr>
                                    <td colspan="11" class="text-center">No tour schedules found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($schedules as $schedule): ?>
                                    <tr class="schedule-row"
                                        data-status="<?php echo $schedule['status']; ?>"
                                        data-tour="<?php echo $schedule['tour_id']; ?>"
                                        data-start="<?php echo $schedule['start_date']; ?>">
                                        <td><?php echo $schedule['schedule_id']; ?></td>
                                        <td><?php echo htmlspecialchars($schedule['tour_title']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($schedule['start_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($schedule['end_date'])); ?></td>
                                        <td><?php echo $schedule['duration']; ?> days</td>
                                        <td><?php echo $schedule['available_seats']; ?></td>
                                        <td>
                                            <?php if ($schedule['booking_count'] > 0): ?>
                                                <a href="bookings.php?schedule_id=<?php echo $schedule['schedule_id']; ?>" class="badge bg-info text-decoration-none">
                                                    <?php echo $schedule['booking_count']; ?> bookings
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">No bookings</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo $schedule['guide_name'] ? htmlspecialchars($schedule['guide_name']) : 'Not assigned'; ?></td>
                                        <td>
                                            ₹<?php echo number_format($schedule['price'] + $schedule['price_adjustment'], 2); ?>
                                            <?php if ($schedule['price_adjustment'] != 0): ?>
                                                <small class="text-<?php echo $schedule['price_adjustment'] > 0 ? 'danger' : 'success'; ?>">
                                                    (<?php echo $schedule['price_adjustment'] > 0 ? '+' : ''; ?><?php echo number_format($schedule['price_adjustment'], 2); ?>)
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($schedule['status']) {
                                                case 'upcoming':
                                                    $status_class = 'bg-primary';
                                                    break;
                                                case 'ongoing':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'bg-secondary';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger';
                                                    break;
                                                default:
                                                    $status_class = 'bg-info';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                    <?php echo ucfirst($schedule['status']); ?>
                                                </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="tour-schedule-edit.php?id=<?php echo $schedule['schedule_id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>

                                                <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Change Status">
                                                    <i class="fas fa-exchange-alt"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li><a class="dropdown-item" href="tour-schedule-view.php?action=upcoming&id=<?php echo $schedule['schedule_id']; ?>">Mark as Upcoming</a></li>
                                                    <li><a class="dropdown-item" href="tour-schedule-view.php?action=ongoing&id=<?php echo $schedule['schedule_id']; ?>">Mark as Ongoing</a></li>
                                                    <li><a class="dropdown-item" href="tour-schedule-view.php?action=completed&id=<?php echo $schedule['schedule_id']; ?>">Mark as Completed</a></li>
                                                    <li><a class="dropdown-item" href="tour-schedule-view.php?action=cancelled&id=<?php echo $schedule['schedule_id']; ?>">Mark as Cancelled</a></li>
                                                </ul>

                                                <?php if ($schedule['booking_count'] == 0): ?>
                                                    <a href="tour-schedule-view.php?action=delete&id=<?php echo $schedule['schedule_id']; ?>"
                                                       class="btn btn-sm btn-danger"
                                                       onclick="return confirm('Are you sure you want to delete this schedule? This action cannot be undone.');"
                                                       title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Cannot delete - has bookings">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Toggle filter options
        const toggleFilters = document.getElementById('toggleFilters');
        const filterOptions = document.getElementById('filterOptions');

        toggleFilters.addEventListener('click', function() {
            filterOptions.style.display = filterOptions.style.display === 'none' ? 'block' : 'none';
        });

        // Apply filters
        const applyFilters = document.getElementById('applyFilters');
        const resetFilters = document.getElementById('resetFilters');
        const statusFilter = document.getElementById('statusFilter');
        const tourFilter = document.getElementById('tourFilter');
        const dateFilter = document.getElementById('dateFilter');
        const scheduleRows = document.querySelectorAll('.schedule-row');

        applyFilters.addEventListener('click', function() {
            const statusValue = statusFilter.value;
            const tourValue = tourFilter.value;
            const dateValue = dateFilter.value;

            scheduleRows.forEach(row => {
                let showRow = true;

                // Status filter
                if (statusValue && row.dataset.status !== statusValue) {
                    showRow = false;
                }

                // Tour filter
                if (tourValue && row.dataset.tour !== tourValue) {
                    showRow = false;
                }

                // Date filter
                if (dateValue) {
                    const startDate = new Date(row.dataset.start);
                    const today = new Date();

                    if (dateValue === 'upcoming' && startDate < today) {
                        showRow = false;
                    } else if (dateValue === 'past' && startDate >= today) {
                        showRow = false;
                    } else if (dateValue === 'next30') {
                        const thirtyDaysLater = new Date();
                        thirtyDaysLater.setDate(today.getDate() + 30);
                        if (startDate < today || startDate > thirtyDaysLater) {
                            showRow = false;
                        }
                    } else if (dateValue === 'last30') {
                        const thirtyDaysAgo = new Date();
                        thirtyDaysAgo.setDate(today.getDate() - 30);
                        if (startDate > today || startDate < thirtyDaysAgo) {
                            showRow = false;
                        }
                    }
                }

                row.style.display = showRow ? '' : 'none';
            });
        });

        resetFilters.addEventListener('click', function() {
            statusFilter.value = '';
            tourFilter.value = '';
            dateFilter.value = '';

            scheduleRows.forEach(row => {
                row.style.display = '';
            });
        });

        // Auto-update status based on dates
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        scheduleRows.forEach(row => {
            const startDate = new Date(row.dataset.start);
            startDate.setHours(0, 0, 0, 0);

            const endDateCell = row.cells[3].textContent;
            const endDate = new Date(endDateCell);
            endDate.setHours(0, 0, 0, 0);

            const status = row.dataset.status;

            // Only suggest status updates if not cancelled
            if (status !== 'cancelled') {
                if (today > endDate && status !== 'completed') {
                    row.style.backgroundColor = '#fff3cd';
                    row.title = 'This tour has ended and should be marked as completed';
                } else if (today >= startDate && today <= endDate && status !== 'ongoing') {
                    row.style.backgroundColor = '#d1e7dd';
                    row.title = 'This tour is currently running and should be marked as ongoing';
                }
            }
        });
    });
</script>

<?php
// Include admin footer
include_once 'includes/footer.php';
?>
