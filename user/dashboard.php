<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Initialize variables with default values
$user = ['full_name' => 'User'];
$bookings = [];
$upcoming = 0;
$upcoming_tours = [];
$wishlist_count = 0;
$review_count = 0;
$booking_count = 0;

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get user information - fetch more user details for enhanced UI
    $user_id = Session::get('user_id');
    $query = "SELECT full_name, email, profile_image, created_at FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Use a single query to get all counts for better performance
    $counts_query = "SELECT 
                  (SELECT COUNT(*) FROM bookings WHERE user_id = :user_id) as booking_count,
                  (SELECT COUNT(*) FROM bookings WHERE user_id = :user_id AND status = 'confirmed' AND EXISTS (SELECT 1 FROM tour_schedules ts WHERE ts.schedule_id = bookings.schedule_id AND ts.start_date > NOW())) as upcoming_count,
                  (SELECT COUNT(*) FROM wishlist WHERE user_id = :user_id) as wishlist_count,
                  (SELECT COUNT(*) FROM feedback WHERE user_id = :user_id) as review_count";
    $counts_stmt = $db->prepare($counts_query);
    $counts_stmt->bindParam(":user_id", $user_id);
    $counts_stmt->execute();
    $counts = $counts_stmt->fetch(PDO::FETCH_ASSOC);

    if ($counts) {
        $booking_count = $counts['booking_count'];
        $upcoming = $counts['upcoming_count'];
        $wishlist_count = $counts['wishlist_count'];
        $review_count = $counts['review_count'];
    }

    // Get user's bookings with LIMIT for better performance - use a separate query to avoid blocking
    $query = "SELECT b.booking_id, b.booking_date, b.status, b.num_people, b.total_amount,
            t.title as tour_title, t.image as tour_image, d.name as destination_name,
            ts.start_date as tour_start_date, ts.end_date as tour_end_date,
            DATEDIFF(ts.start_date, NOW()) as days_until_tour
            FROM bookings b
            JOIN tours t ON b.tour_id = t.tour_id
            JOIN destinations d ON t.destination_id = d.destination_id
            JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
            WHERE b.user_id = :user_id
            ORDER BY b.booking_date DESC
            LIMIT 3"; // Only get the 3 most recent bookings for performance
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get upcoming tours
    if ($upcoming > 0) {
        $query = "SELECT b.booking_id, b.booking_date, b.status, b.num_people, b.total_amount,
              t.title as tour_title, t.image as tour_image, d.name as destination_name,
              ts.start_date as tour_start_date, ts.end_date as tour_end_date,
              DATEDIFF(ts.start_date, NOW()) as days_until_tour,
              DATEDIFF(ts.end_date, ts.start_date) + 1 as tour_duration
              FROM bookings b
              JOIN tours t ON b.tour_id = t.tour_id
              JOIN destinations d ON t.destination_id = d.destination_id
              JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
              WHERE b.user_id = :user_id AND b.status = 'confirmed' AND ts.start_date > NOW()
              ORDER BY ts.start_date ASC
              LIMIT 3"; // Only get the 3 upcoming tours
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
        $upcoming_tours = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get recommended tours based on user's previous bookings
    $query = "SELECT t.tour_id, t.title, t.image, t.price, d.name as destination_name
            FROM tours t
            JOIN destinations d ON t.destination_id = d.destination_id
            WHERE t.is_active = 1
            AND t.tour_id NOT IN (SELECT tour_id FROM bookings WHERE user_id = :user_id)
            ORDER BY RAND()
            LIMIT 3";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $recommended_tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Set a flag to show an error message
    $db_error = true;
    error_log("Dashboard error: " . $e->getMessage());
}

// Include user header
include_once 'includes/header.php';

// Include sidebar
include_once 'includes/sidebar.php';

// Function to check if file exists and is readable
function fileExists($path) {
    return file_exists($path) && is_readable($path);
}

// Function to get profile image URL with proper fallback
function getProfileImageUrl($profile_image) {
    // Check all possible locations for the profile image
    $possible_paths = [
        "../assets/images/uploads/users/{$profile_image}",
        "../assets/images/users/{$profile_image}",
        "../assets/images/{$profile_image}"
    ];

    // Check if any of the paths exist
    foreach ($possible_paths as $path) {
        if (fileExists($path)) {
            return $path;
        }
    }

    // If no image found, return default
    return "../assets/images/users/default-user.jpg";
}

// Function to get tour image URL with proper fallback
function getTourImageUrl($tour_image) {
    // Check all possible locations for the tour image
    $possible_paths = [
        "../assets/images/uploads/tours/{$tour_image}",
        "../assets/images/tours/{$tour_image}",
        "../assets/images/{$tour_image}"
    ];

    // Check if any of the paths exist
    foreach ($possible_paths as $path) {
        if (fileExists($path)) {
            return $path;
        }
    }

    // If no image found, return default
    return "../assets/images/placeholder.jpg";
}

// Debug function to log image paths
function debugImagePaths($image_name, $type = 'profile') {
    $base_paths = [
        'profile' => [
            "../assets/images/uploads/users/{$image_name}",
            "../assets/images/users/{$image_name}",
            "../assets/images/{$image_name}"
        ],
        'tour' => [
            "../assets/images/uploads/tours/{$image_name}",
            "../assets/images/tours/{$image_name}",
            "../assets/images/{$image_name}"
        ]
    ];

    $paths = $base_paths[$type];
    $debug_info = "Checking paths for {$type} image '{$image_name}':\n";

    foreach ($paths as $path) {
        $exists = file_exists($path) ? "EXISTS" : "NOT FOUND";
        $readable = is_readable($path) ? "READABLE" : "NOT READABLE";
        $debug_info .= "- {$path}: {$exists}, {$readable}\n";
    }

    error_log($debug_info);
}

// Debug the user's profile image
if (!empty($user['profile_image'])) {
    debugImagePaths($user['profile_image'], 'profile');
}
?>

<!-- Main Content -->
<div id="main-content" class="main-content <?php echo isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] === 'true' ? 'expanded' : ''; ?>">
    <?php if (isset($db_error)): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <strong>Notice:</strong> We're experiencing some technical difficulties. Some features may be limited.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Enhanced Welcome Banner with User Profile Summary -->
    <div class="welcome-banner p-4 mb-4 rounded-3 text-white position-relative overflow-hidden">
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background: linear-gradient(135deg, #4a6cf7 0%, #2a3f9d 100%); z-index: -2;"></div>
        <div class="position-absolute top-0 start-0 w-100 h-100" style="background-image: url('../assets/images/pattern.svg'); background-size: cover; opacity: 0.1; z-index: -1;"></div>

        <div class="row align-items-center">
            <div class="col-md-8 position-relative z-index-1">
                <h2 class="display-6 fw-bold mb-2">Welcome back, <?php echo htmlspecialchars($user['full_name'] ?? 'User'); ?>!</h2>
                <p class="lead mb-3">Explore your dashboard to manage your bookings, reviews, and profile.</p>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="../tours.php" class="btn btn-light">
                        <i class="fas fa-search me-2"></i>Explore Tours
                    </a>
                    <a href="profile.php" class="btn btn-outline-light">
                        <i class="fas fa-user-edit me-2"></i>Update Profile
                    </a>
                </div>
            </div>
            <div class="col-md-4 d-none d-md-block text-end">
                <div class="position-relative">
                    <div class="bg-white p-2 rounded-circle shadow-sm d-inline-block">
                        <?php
                        // Get profile image URL with proper fallback
                        $profile_image_url = getProfileImageUrl($user['profile_image'] ?? '');
                        ?>
                        <img src="<?php echo $profile_image_url; ?>"
                             class="rounded-circle" width="100" height="100"
                             alt="Profile Image"
                             style="object-fit: cover;">
                    </div>
                    <div class="position-absolute bottom-0 end-0">
                      <span class="badge bg-success rounded-pill p-2">
                          <i class="fas fa-check"></i>
                      </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Dashboard stats with improved visuals -->
    <div class="row mb-4">
        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box rounded-circle bg-primary-light p-3 me-3">
                            <i class="fas fa-calendar-check text-primary fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Total Bookings</h6>
                            <h3 class="mb-0 fw-bold"><?php echo $booking_count; ?></h3>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo min($booking_count * 10, 100); ?>%"></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-end">
                    <a href="bookings.php" class="text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box rounded-circle bg-success-light p-3 me-3">
                            <i class="fas fa-plane-departure text-success fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Upcoming Tours</h6>
                            <h3 class="mb-0 fw-bold"><?php echo $upcoming; ?></h3>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo min($upcoming * 33, 100); ?>%"></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-end">
                    <a href="bookings.php?filter=upcoming" class="text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box rounded-circle bg-info-light p-3 me-3">
                            <i class="fas fa-heart text-info fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Wishlist</h6>
                            <h3 class="mb-0 fw-bold"><?php echo $wishlist_count; ?></h3>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min($wishlist_count * 20, 100); ?>%"></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-end">
                    <a href="wishlist.php" class="text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="col-md-3 col-sm-6 mb-4">
            <div class="card border-0 shadow-sm h-100 hover-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="icon-box rounded-circle bg-warning-light p-3 me-3">
                            <i class="fas fa-star text-warning fa-2x"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-1">Reviews</h6>
                            <h3 class="mb-0 fw-bold"><?php echo $review_count; ?></h3>
                        </div>
                    </div>
                    <div class="progress mt-3" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo min($review_count * 20, 100); ?>%"></div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 text-end">
                    <a href="reviews.php" class="text-decoration-none">View All <i class="fas fa-arrow-right ms-1"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Tours Section with improved cards -->
    <?php if ($upcoming > 0): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-plane-departure me-2"></i>Your Upcoming Tours
                </h5>
                <a href="bookings.php?filter=upcoming" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($upcoming_tours as $tour): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm hover-card">
                                <div class="position-relative">
                                    <?php
                                    // Debug the tour image
                                    if (!empty($tour['tour_image'])) {
                                        debugImagePaths($tour['tour_image'], 'tour');
                                    }

                                    // Get tour image URL with proper fallback
                                    $tour_image_url = getTourImageUrl($tour['tour_image'] ?? '');
                                    ?>
                                    <img src="<?php echo $tour_image_url; ?>"
                                         class="card-img-top"
                                         alt="<?php echo htmlspecialchars($tour['tour_title']); ?>"
                                         style="height: 180px; object-fit: cover;">

                                    <!-- Countdown badge -->
                                    <?php if ($tour['days_until_tour'] > 0): ?>
                                        <div class="position-absolute top-0 start-0 m-2">
                              <span class="badge bg-primary rounded-pill px-3 py-2">
                                  <i class="fas fa-clock me-1"></i>
                                  <?php echo $tour['days_until_tour']; ?> days to go
                              </span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Duration badge -->
                                    <div class="position-absolute bottom-0 start-0 m-2">
                              <span class="badge bg-dark bg-opacity-75 rounded-pill px-3 py-2">
                                  <i class="fas fa-calendar-day me-1"></i>
                                  <?php echo $tour['tour_duration']; ?> days
                              </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($tour['tour_title']); ?></h5>
                                    <p class="card-text text-muted mb-1">
                                        <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($tour['destination_name']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                              <span>
                                  <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                  <?php echo date('M d', strtotime($tour['tour_start_date'])); ?> -
                                  <?php echo date('M d, Y', strtotime($tour['tour_end_date'])); ?>
                              </span>
                                        <span class="badge bg-success">Confirmed</span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                              <span>
                                  <i class="fas fa-users me-2 text-primary"></i>
                                  <?php echo $tour['num_people']; ?> People
                              </span>
                                        <span>
                                  <i class="fas fa-rupee-sign me-1 text-primary"></i>
                                  <?php echo number_format($tour['total_amount'], 2); ?>
                              </span>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0">
                                    <div class="d-grid gap-2">
                                        <a href="booking-details.php?id=<?php echo $tour['booking_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-info-circle me-2"></i>View Details
                                        </a>
                                        <a href="booking-voucher.php?id=<?php echo $tour['booking_id']; ?>" class="btn btn-outline-success">
                                            <i class="fas fa-ticket-alt me-2"></i>Download Voucher
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Bookings with improved cards -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-history me-2"></i>Recent Bookings
            </h5>
            <a href="bookings.php" class="btn btn-sm btn-outline-primary">View All</a>
        </div>
        <div class="card-body">
            <?php if (empty($bookings)): ?>
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-calendar-times fa-4x text-muted"></i>
                    </div>
                    <h5>You haven't made any bookings yet</h5>
                    <p class="text-muted">Explore our tour packages and book your next adventure!</p>
                    <a href="../tours.php" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Browse Tours
                    </a>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 border-0 shadow-sm hover-card">
                                <div class="position-relative">
                                    <?php
                                    // Get tour image URL with proper fallback
                                    $tour_image_url = getTourImageUrl($booking['tour_image'] ?? '');
                                    ?>
                                    <img src="<?php echo $tour_image_url; ?>"
                                         class="card-img-top"
                                         alt="<?php echo htmlspecialchars($booking['tour_title']); ?>"
                                         style="height: 180px; object-fit: cover;">

                                    <!-- Status badge -->
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <?php if ($booking['status'] == 'confirmed'): ?>
                                            <span class="badge bg-success px-3 py-2">
                                              <i class="fas fa-check-circle me-1"></i>Confirmed
                                          </span>
                                        <?php elseif ($booking['status'] == 'pending'): ?>
                                            <span class="badge bg-warning px-3 py-2">
                                              <i class="fas fa-clock me-1"></i>Pending
                                          </span>
                                        <?php elseif ($booking['status'] == 'cancelled'): ?>
                                            <span class="badge bg-danger px-3 py-2">
                                              <i class="fas fa-times-circle me-1"></i>Cancelled
                                          </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-3 py-2">
                                              <i class="fas fa-check-double me-1"></i>Completed
                                          </span>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Booking date badge -->
                                    <div class="position-absolute bottom-0 start-0 m-2">
                                      <span class="badge bg-dark bg-opacity-75 rounded-pill px-3 py-2">
                                          <i class="fas fa-calendar-check me-1"></i>
                                          Booked: <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                      </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($booking['tour_title']); ?></h5>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                        <span class="text-muted"><?php echo htmlspecialchars($booking['destination_name']); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <i class="fas fa-calendar-alt text-primary me-2"></i>
                                            <span><?php echo date('M d', strtotime($booking['tour_start_date'])); ?> -
                                          <?php echo date('M d, Y', strtotime($booking['tour_end_date'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <i class="fas fa-users text-success me-2"></i>
                                            <span><?php echo $booking['num_people']; ?> People</span>
                                        </div>
                                        <div>
                                            <i class="fas fa-rupee-sign text-dark me-1"></i>
                                            <span class="fw-bold"><?php echo number_format($booking['total_amount'], 2); ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0">
                                    <a href="booking-details.php?id=<?php echo $booking['booking_id']; ?>"
                                       class="btn btn-outline-primary w-100">
                                        <i class="fas fa-info-circle me-2"></i>View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recommended Tours Section (New) -->
    <?php if (!empty($recommended_tours)): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="fas fa-thumbs-up me-2"></i>Recommended For You
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($recommended_tours as $tour): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="card h-100 border-0 shadow-sm hover-card">
                                <div class="position-relative">
                                    <?php
                                    // Get tour image URL with proper fallback
                                    $tour_image_url = getTourImageUrl($tour['image'] ?? '');
                                    ?>
                                    <img src="<?php echo $tour_image_url; ?>"
                                         class="card-img-top"
                                         alt="<?php echo htmlspecialchars($tour['title']); ?>"
                                         style="height: 180px; object-fit: cover;">

                                    <!-- Price badge -->
                                    <div class="position-absolute top-0 end-0 m-2">
                                  <span class="badge bg-primary px-3 py-2">
                                      <i class="fas fa-rupee-sign me-1"></i><?php echo number_format($tour['price'], 2); ?>
                                  </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($tour['title']); ?></h5>
                                    <p class="card-text text-muted">
                                        <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($tour['destination_name']); ?>
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 pt-0">
                                    <div class="d-grid gap-2">
                                        <a href="../tour-details.php?id=<?php echo $tour['tour_id']; ?>" class="btn btn-primary">
                                            <i class="fas fa-info-circle me-2"></i>View Details
                                        </a>
                                        <a href="add-to-wishlist.php?tour_id=<?php echo $tour['tour_id']; ?>" class="btn btn-outline-danger">
                                            <i class="fas fa-heart me-2"></i>Add to Wishlist
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Actions with improved design -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-bolt me-2"></i>Quick Actions
            </h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-lg-3 col-md-6">
                    <a href="../tours.php" class="card text-center h-100 border-0 shadow-sm hover-card p-4 text-decoration-none">
                        <div class="icon-box mx-auto rounded-circle bg-primary-light p-3 mb-3">
                            <i class="fas fa-search text-primary fa-2x"></i>
                        </div>
                        <h5 class="text-dark">Browse Tours</h5>
                        <p class="text-muted small mb-0">Explore our tour packages</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="profile.php" class="card text-center h-100 border-0 shadow-sm hover-card p-4 text-decoration-none">
                        <div class="icon-box mx-auto rounded-circle bg-success-light p-3 mb-3">
                            <i class="fas fa-user-edit text-success fa-2x"></i>
                        </div>
                        <h5 class="text-dark">Update Profile</h5>
                        <p class="text-muted small mb-0">Manage your account details</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="wishlist.php" class="card text-center h-100 border-0 shadow-sm hover-card p-4 text-decoration-none">
                        <div class="icon-box mx-auto rounded-circle bg-danger-light p-3 mb-3">
                            <i class="fas fa-heart text-danger fa-2x"></i>
                        </div>
                        <h5 class="text-dark">My Wishlist</h5>
                        <p class="text-muted small mb-0">View your saved tours</p>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="reviews.php" class="card text-center h-100 border-0 shadow-sm hover-card p-4 text-decoration-none">
                        <div class="icon-box mx-auto rounded-circle bg-warning-light p-3 mb-3">
                            <i class="fas fa-star text-warning fa-2x"></i>
                        </div>
                        <h5 class="text-dark">My Reviews</h5>
                        <p class="text-muted small mb-0">Manage your tour reviews</p>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove debug tools from production -->
    <?php if (false): // Set to true for debugging ?>
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Debug Tools</h5>
            </div>
            <div class="card-body">
                <button class="btn btn-primary" onclick="toggleSidebar()">Toggle Sidebar</button>
                <button class="btn btn-secondary" onclick="directToggleSidebar()">Direct Toggle Sidebar</button>
                <button class="btn btn-info" onclick="console.log(document.getElementById('sidebar').className)">Log Sidebar Class</button>
                <button class="btn btn-warning" onclick="document.cookie = 'sidebar_collapsed=false; path=/; max-age=31536000'; location.reload();">Reset Sidebar Cookie</button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Help & Support Section (New) -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0 fw-bold text-primary">
                <i class="fas fa-headset me-2"></i>Help & Support
            </h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5>Need assistance with your bookings?</h5>
                    <p class="text-muted">Our customer support team is available 24/7 to help you with any questions or concerns.</p>
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="contact.php" class="btn btn-primary">
                            <i class="fas fa-envelope me-2"></i>Contact Support
                        </a>
                        <a href="faq.php" class="btn btn-outline-secondary">
                            <i class="fas fa-question-circle me-2"></i>View FAQs
                        </a>
                    </div>
                </div>
                <div class="col-md-4 d-none d-md-block text-center">
                    <i class="fas fa-headset fa-5x text-primary opacity-75"></i>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Update destination names if needed
        document.addEventListener('DOMContentLoaded', function() {
            const destinationNames = document.querySelectorAll('.destination-name');
            destinationNames.forEach(function(element) {
                const originalText = element.textContent.trim();
                if (originalText === 'Paris, France') {
                    element.textContent = 'Kerala, India';
                } else if (originalText === 'Tokyo, Japan') {
                    element.textContent = 'Rajasthan, India';
                } else if (originalText === 'New York, USA') {
                    element.textContent = 'Goa, India';
                }
            });

            // Add animation to stats cards
            const statCards = document.querySelectorAll('.progress-bar');
            setTimeout(() => {
                statCards.forEach(card => {
                    card.style.transition = 'width 1s ease-in-out';
                    card.style.width = card.getAttribute('style').split('width:')[1];
                });
            }, 300);
        });
    </script>
</div>

<?php
// Include user footer
include_once 'includes/footer.php';
?>
