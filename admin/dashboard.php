<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Start session
Session::start();

// For debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is logged in and is admin
if (!Session::isLoggedIn() || !Session::isAdmin()) {
    header("Location: ../login.php");
    exit;
}

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get admin/company info
$user_id = Session::get('user_id');
$query = "SELECT * FROM users WHERE user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":user_id", $user_id);
$stmt->execute();
$admin_info = $stmt->fetch(PDO::FETCH_ASSOC);
$admin_id = $user_id; // Use the current user's ID

// Get counts for dashboard
// Total users
$query = "SELECT COUNT(*) as total_users FROM users WHERE role = 'user'";
$stmt = $db->prepare($query);
$stmt->execute();
$user_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

// Total tours
$query = "SELECT COUNT(*) as total_tours FROM tours WHERE admin_id = :admin_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$tour_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_tours'];

// Total bookings
$query = "SELECT COUNT(*) as total_bookings FROM bookings b 
          JOIN tours t ON b.tour_id = t.tour_id 
          WHERE t.admin_id = :admin_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$booking_count = $stmt->fetch(PDO::FETCH_ASSOC)['total_bookings'];

// Total revenue
$query = "SELECT SUM(b.total_amount) as total_revenue 
          FROM bookings b 
          JOIN tours t ON b.tour_id = t.tour_id 
          WHERE b.status = 'confirmed' AND t.admin_id = :admin_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'];
$revenue = $revenue ? $revenue : 0;

// Recent bookings
$query = "SELECT b.booking_id, b.booking_date, ts.start_date as tour_date, b.total_amount, b.status, 
         u.username, u.full_name, t.title as tour_title
         FROM bookings b
         JOIN users u ON b.user_id = u.user_id
         JOIN tours t ON b.tour_id = t.tour_id
         JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
         WHERE t.admin_id = :admin_id
         ORDER BY b.booking_date DESC
         LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$recent_bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include admin header
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <!-- Company Welcome Card -->
            <?php if (!empty($admin_info['company_name'])): ?>
            <div class="card mb-4 bg-light">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-building fa-3x text-primary"></i>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h4 class="mb-1"><?php echo htmlspecialchars($admin_info['company_name']); ?></h4>
                            <?php if (!empty($admin_info['company_address'])): ?>
                                <p class="mb-0 text-muted"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($admin_info['company_address']); ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="ms-auto">
                            <a href="company-profile.php" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-edit me-1"></i> Edit Company Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary">Export</button>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle">
                        <i class="fas fa-calendar"></i> This week
                    </button>
                </div>
            </div>
            
            <!-- Dashboard stats -->
            <div class="row">
                <div class="col-md-3 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Customers</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $user_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Tour Packages</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $tour_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-map-marked-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Total Bookings</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $booking_count; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Total Revenue</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">₹<?php echo number_format($revenue, 2); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-rupee-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3 mb-3">
                                    <a href="tour-package-entry.php" class="btn btn-primary btn-block w-100">
                                        <i class="fas fa-plus-circle me-2"></i> Add New Tour Package
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="hotel-details-entry.php" class="btn btn-success btn-block w-100">
                                        <i class="fas fa-hotel me-2"></i> Add New Hotel
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="tour-schedule-entry.php" class="btn btn-info btn-block w-100 text-white">
                                        <i class="fas fa-calendar-plus me-2"></i> Create Tour Schedule
                                    </a>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <a href="bookings.php" class="btn btn-warning btn-block w-100">
                                        <i class="fas fa-list me-2"></i> View All Bookings
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Bookings -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Bookings</h6>
                    <a href="bookings.php" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Tour</th>
                                    <th>Booking Date</th>
                                    <th>Tour Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_bookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No bookings found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recent_bookings as $booking): ?>
                                        <tr>
                                            <td><?php echo $booking['booking_id']; ?></td>
                                            <td><?php echo $booking['full_name']; ?></td>
                                            <td><?php echo $booking['tour_title']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></td>
                                            <td><?php echo isset($booking['tour_date']) ? date('M d, Y', strtotime($booking['tour_date'])) : 'N/A'; ?></td>
                                            <td>₹<?php echo number_format($booking['total_amount'], 2); ?></td>
                                            <td>
                                                <?php if ($booking['status'] == 'confirmed'): ?>
                                                    <span class="badge bg-success">Confirmed</span>
                                                <?php elseif ($booking['status'] == 'pending'): ?>
                                                    <span class="badge bg-warning">Pending</span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">Cancelled</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Analytics Overview -->
            <div class="row">
                <div class="col-xl-8 col-lg-7">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Monthly Bookings Overview</h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                    <div class="dropdown-header">View Options:</div>
                                    <a class="dropdown-item" href="#">This Year</a>
                                    <a class="dropdown-item" href="#">Last Year</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">Export Data</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="bookingsChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-xl-4 col-lg-5">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Revenue Sources</h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                    <div class="dropdown-header">View Options:</div>
                                    <a class="dropdown-item" href="#">This Month</a>
                                    <a class="dropdown-item" href="#">Last Month</a>
                                    <div class="dropdown-divider"></div>
                                    <a class="dropdown-item" href="#">Export Data</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="revenueChart"></canvas>
                            </div>
                            <div class="mt-4 text-center small">
                                <span class="me-2">
                                    <i class="fas fa-circle text-primary"></i> Tour Packages
                                </span>
                                <span class="me-2">
                                    <i class="fas fa-circle text-success"></i> Hotel Bookings
                                </span>
                                <span class="me-2">
                                    <i class="fas fa-circle text-info"></i> Add-ons
                                </span>
                            </div>
                        </div>
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
