<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Start session
Session::start();

// Database connection
$database = new Database();
$db = $database->getConnection();

// Initialize variables
$destination_id = isset($_GET['destination']) ? $_GET['destination'] : '';
$duration = isset($_GET['duration']) ? $_GET['duration'] : '';
$price_range = isset($_GET['price']) ? $_GET['price'] : '';
$search_results = [];
$search_performed = false;
$total_results = 0;

// Build search query
if ($_SERVER['REQUEST_METHOD'] === 'GET' && (isset($_GET['destination']) || isset($_GET['duration']) || isset($_GET['price']))) {
    $search_performed = true;
    
    // Get search results using the searchTours function
    $minPrice = $maxPrice = null;
    if (!empty($price_range)) {
        $price_parts = explode('-', $price_range);
        if (count($price_parts) == 2) {
            $minPrice = $price_parts[0];
            $maxPrice = $price_parts[1];
        } elseif (strpos($price_range, '+') !== false) {
            $minPrice = str_replace('+', '', $price_range);
        }
    }
    
    $durationValue = null;
    if (!empty($duration)) {
        $duration_parts = explode('-', $duration);
        if (count($duration_parts) == 2) {
            $durationValue = $duration_parts[1]; // Use the upper limit
        } elseif (strpos($duration, '+') !== false) {
            $durationValue = 100; // A large number for 15+ days
        }
    }
    
    $search_results = searchTours($destination_id, $minPrice, $maxPrice, $durationValue);
    $total_results = count($search_results);
}

// Get destinations hierarchy for the dropdown
$destinations_hierarchy = getDestinationsHierarchy();

// Include header
include_once 'includes/header.php';
?>

<!-- Search Results Hero Section -->
<section class="search-hero-section py-5 text-white text-center">
    <div class="container">
        <h1 class="display-4">Discover India Tours</h1>
        <p class="lead">Find your perfect Indian adventure from Kerala to Kashmir</p>
    </div>
</section>

<!-- Search Form Section -->
<section class="search-form-section py-5 bg-light">
    <div class="container">
        <div class="search-container bg-white p-4 rounded shadow">
            <h3 class="mb-4">Refine Your Search</h3>
            <form action="search.php" method="GET" class="search-form">
                <div class="row g-3">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="destination" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Destination</label>
                            <select name="destination" id="destination" class="form-select">
                                <option value="">All Destinations</option>
                                
                                <?php foreach ($destinations_hierarchy as $region_id => $region): ?>
                                    <optgroup label="<?php echo $region['info']['name']; ?>">
                                        <?php foreach ($region['states'] as $state_id => $state): ?>
                                            <option value="<?php echo $state_id; ?>" <?php echo ($destination_id == $state_id) ? 'selected' : ''; ?>>
                                                <?php echo $state['info']['name']; ?>
                                            </option>
                                            
                                            <?php if (!empty($state['cities'])): ?>
                                                <?php foreach ($state['cities'] as $city): ?>
                                                    <option value="<?php echo $city['destination_id']; ?>" <?php echo ($destination_id == $city['destination_id']) ? 'selected' : ''; ?>>
                                                        &nbsp;&nbsp;&nbsp;<?php echo $city['name']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="duration" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Duration</label>
                            <select name="duration" id="duration" class="form-select">
                                <option value="">Any Duration</option>
                                <option value="1-3" <?php echo ($duration == '1-3') ? 'selected' : ''; ?>>1-3 Days</option>
                                <option value="4-7" <?php echo ($duration == '4-7') ? 'selected' : ''; ?>>4-7 Days</option>
                                <option value="8-14" <?php echo ($duration == '8-14') ? 'selected' : ''; ?>>8-14 Days</option>
                                <option value="15+" <?php echo ($duration == '15+') ? 'selected' : ''; ?>>15+ Days</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label for="price" class="form-label"><i class="fas fa-rupee-sign me-2"></i>Budget</label>
                            <select name="price" id="price" class="form-select">
                                <option value="">Any Price</option>
                                <option value="0-10000" <?php echo ($price_range == '0-10000') ? 'selected' : ''; ?>>₹0 - ₹10,000</option>
                                <option value="10000-25000" <?php echo ($price_range == '10000-25000') ? 'selected' : ''; ?>>₹10,000 - ₹25,000</option>
                                <option value="25000-50000" <?php echo ($price_range == '25000-50000') ? 'selected' : ''; ?>>₹25,000 - ₹50,000</option>
                                <option value="50000+" <?php echo ($price_range == '50000+') ? 'selected' : ''; ?>>₹50,000+</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100 mt-4">
                            <i class="fas fa-search me-2"></i>Search Tours
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- Search Results Section -->
<section class="search-results-section py-5">
    <div class="container">
        <?php if ($search_performed): ?>
            <div class="search-results-header mb-4">
                <h2>Search Results</h2>
                <p class="text-muted">Found <?php echo $total_results; ?> tours matching your criteria</p>
            </div>
            
            <?php if (empty($search_results)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>No tours found matching your search criteria. Please try different filters.
                </div>
                <div class="text-center mt-4">
                    <a href="tours.php" class="btn btn-primary">View All Tours</a>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($search_results as $tour): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="tour-card">
                                <div class="tour-card-header">
                                    <?php
                                    // Check if the image path contains 'uploads' to determine if it's using the new structure
                                    $imagePath = strpos($tour['image'], '/') !== false ? $tour['image'] : 'assets/images/uploads/tours/' . $tour['image'];
                                    
                                    // If the file doesn't exist in the new location, fall back to the old location
                                    if (!file_exists($imagePath) && strpos($imagePath, 'uploads') !== false) {
                                        $imagePath = 'assets/images/tours/' . $tour['image'];
                                    }
                                    
                                    // If still not found, use placeholder
                                    if (!file_exists($imagePath)) {
                                        $imagePath = 'assets/images/placeholder.jpg';
                                    }
                                    ?>
                                    <img src="<?php echo $imagePath; ?>" class="tour-img" alt="<?php echo $tour['title']; ?>">
                                    <div class="tour-card-badges">
                                        <?php if ($tour['is_featured']): ?>
                                            <span class="badge bg-primary">Featured</span>
                                        <?php endif; ?>
                                        <span class="badge bg-dark"><i class="far fa-clock me-1"></i> <?php echo $tour['duration']; ?> days</span>
                                    </div>
                                    <div class="tour-card-wishlist">
                                        <button class="btn-wishlist"><i class="far fa-heart"></i></button>
                                    </div>
                                </div>
                                <div class="tour-card-body">
                                    <div class="tour-card-location">
                                        <i class="fas fa-map-marker-alt"></i> <?php echo $tour['destination_name']; ?>
                                    </div>
                                    <h5 class="tour-card-title"><?php echo $tour['title']; ?></h5>
                                    <p class="tour-card-text"><?php echo substr($tour['description'], 0, 100); ?>...</p>
                                    <div class="tour-card-features">
                                        <span class="tour-feature"><i class="fas fa-users"></i> Max: <?php echo $tour['max_people']; ?></span>
                                        <?php if (!empty($tour['inclusions'])): ?>
                                            <?php 
                                            $inclusions = explode(',', $tour['inclusions']);
                                            $count = 0;
                                            foreach ($inclusions as $inclusion):
                                                if ($count < 2): // Show only first 2 inclusions
                                                    $count++;
                                            ?>
                                                <span class="tour-feature"><i class="fas fa-check"></i> <?php echo trim($inclusion); ?></span>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="tour-card-footer">
                                    <div class="tour-card-price">
                                        <span class="price-value">₹<?php echo number_format($tour['price']); ?></span>
                                        <span class="price-text">per person</span>
                                    </div>
                                    <a href="tour-details.php?id=<?php echo $tour['tour_id']; ?>" class="btn btn-primary rounded-pill">View Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-search fa-4x text-muted"></i>
                </div>
                <h2>Start Your Search</h2>
                <p class="text-muted">Use the search form above to find your perfect tour</p>
                <div class="mt-4">
                    <a href="tours.php" class="btn btn-primary">Browse All Tours</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<style>
/* Search Hero Section */
.search-hero-section {
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/search-bg.jpg');
    background-size: cover;
    background-position: center;
    padding: 80px 0;
}

/* Search Form Section */
.search-form-section {
    margin-top: -30px;
    position: relative;
    z-index: 10;
}

.search-container {
    border-radius: 10px;
}

.search-form .form-group {
    margin-bottom: 0;
}

.search-form .form-label {
    font-weight: 500;
    margin-bottom: 8px;
}

.search-form .form-select {
    height: 50px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 0 15px;
    width: 100%;
    font-size: 0.95rem;
    background-color: #f8f9fa;
}

.search-form .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: none;
}

.search-form .btn {
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Tour Cards (same as in index.php) */
.tour-card {
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    background-color: #fff;
    transition: all 0.3s ease;
    margin-bottom: 30px;
    height: 100%;
}

.tour-card:hover {
    transform: translateY(-10px);
}

.tour-card-header {
    position: relative;
}

.tour-img {
    width: 100%;
    height: 250px;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.tour-card:hover .tour-img {
    transform: scale(1.1);
}

.tour-card-badges {
    position: absolute;
    top: 15px;
    left: 15px;
    display: flex;
    gap: 10px;
}

.tour-card-badges .badge {
    padding: 8px 15px;
    font-size: 0.8rem;
    font-weight: 600;
    border-radius: 50px;
}

.tour-card-wishlist {
    position: absolute;
    top: 15px;
    right: 15px;
}

.btn-wishlist {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.8);
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    color: #333;
    transition: all 0.3s ease;
}

.btn-wishlist:hover {
    background-color: #ff6b6b;
    color: #fff;
}

.tour-card-body {
    padding: 25px;
}

.tour-card-location {
    color: #ff6b6b;
    font-size: 0.9rem;
    font-weight: 500;
    margin-bottom: 10px;
}

.tour-card-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.tour-card-text {
    color: #6c757d;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

.tour-card-features {
    display: flex;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 15px;
}

.tour-feature {
    font-size: 0.85rem;
    color: #6c757d;
}

.tour-feature i {
    color: #ff6b6b;
    margin-right: 5px;
}

.tour-card-footer {
    padding: 20px 25px;
    border-top: 1px solid #eee;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.tour-card-price {
    display: flex;
    flex-direction: column;
}

.price-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #ff6b6b;
    line-height: 1;
}

.price-text {
    font-size: 0.8rem;
    color: #6c757d;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .search-form-section {
        margin-top: 0;
    }
    
    .search-container {
        padding: 20px !important;
    }
    
    .search-form .btn {
        margin-top: 0 !important;
    }
}
</style>

<?php
// Include footer
include_once 'includes/footer.php';
?>
