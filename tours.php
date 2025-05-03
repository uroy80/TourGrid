<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Start session
Session::start();

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

// Include header
include_once 'includes/header.php';
?>

<!-- Tours Hero Section -->
<section class="tours-hero-section py-5 text-white text-center">
    <div class="container">
        <h1 class="display-4">Explore Our Tours</h1>
        <p class="lead">Discover the best of India with our carefully curated tour packages</p>
    </div>
</section>

<!-- Tours Listing Section -->
<section class="tours-listing py-5">
    <div class="container">
        <div class="row">
            <?php if (empty($tours)): ?>
                <div class="col-12 text-center">
                    <p>No tours available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($tours as $tour): ?>
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100">
                            <img src="<?php echo getImagePath($tour['image'], 'tours'); ?>" class="card-img-top" alt="<?php echo $tour['title']; ?>">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo $tour['title']; ?></h5>
                                <p class="card-text text-muted"><?php echo $tour['destination_name']; ?></p>
                                <p class="card-text"><?php echo substr($tour['description'], 0, 100); ?>...</p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="price-tag">₹<?php echo number_format($tour['price']); ?></span>
                                    <span class="duration"><i class="far fa-clock"></i> <?php echo $tour['duration']; ?> days</span>
                                </div>
                            </div>
                            <div class="card-footer bg-white">
                                <a href="tour-details.php?id=<?php echo $tour['tour_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Include footer
include_once 'includes/footer.php';
?>
