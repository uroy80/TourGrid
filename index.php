<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Start session
Session::start();

// Database connection
$database = new Database();
$db = $database->getConnection();

// Get featured tours
$query = "SELECT t.*, d.name as destination_name 
      FROM tours t 
      JOIN destinations d ON t.destination_id = d.destination_id 
      WHERE t.is_featured = 1 
      LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$featured_tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get destinations hierarchy for the dropdown
$destinations_hierarchy = getDestinationsHierarchy();

// Get testimonials
$query = "SELECT f.*, u.full_name, u.profile_image, t.title as tour_title 
      FROM feedback f 
      JOIN users u ON f.user_id = u.user_id 
      JOIN tours t ON f.tour_id = t.tour_id 
      WHERE f.is_published = 1 
      ORDER BY f.created_at DESC 
      LIMIT 3";
$stmt = $db->prepare($query);
$stmt->execute();
$testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Include header
include_once 'includes/header.php';
?>

<!-- Hero Section with Parallax Effect -->
<section class="hero-section position-relative">
  <div class="hero-background"></div>
  <div class="hero-content container position-relative">
      <div class="row min-vh-100 align-items-center">
          <div class="col-lg-7 text-white text-center text-lg-start">
              <h5 class="text-uppercase fw-bold mb-3 animate__animated animate__fadeInUp">Explore India with us</h5>
              <h1 class="display-2 fw-bold mb-4 animate__animated animate__fadeInUp">Discover the Magic of <span class="text-highlight">India</span></h1>
              <p class="lead mb-5 animate__animated animate__fadeInUp animate__delay-1s">Experience ancient temples, vibrant cultures, breathtaking landscapes, and unforgettable adventures across incredible India</p>
              <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start animate__animated animate__fadeInUp animate__delay-2s">
                  <a href="tours.php" class="btn btn-primary btn-lg px-4 py-3 rounded-pill">Explore Tours</a>
                  <a href="#featured-tours" class="btn btn-outline-light btn-lg px-4 py-3 rounded-pill">View Packages</a>
              </div>
          </div>
      </div>
  </div>
</section>

<!-- Search Section -->
<section class="search-section">
  <div class="container">
      <div class="search-container">
          <div class="search-header text-center mb-4">
              <h3 class="fw-bold">Find Your Perfect Tour</h3>
              <p class="text-muted">Customize your search to discover the ideal Indian adventure</p>
          </div>
          <form action="search.php" method="GET" class="search-form">
              <div class="row g-4">
                  <div class="col-md-3">
                      <div class="form-group">
                          <label for="destination" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Destination</label>
                          <select name="destination" id="destination" class="form-select">
                              <option value="">All Destinations</option>
                              
                              <?php foreach ($destinations_hierarchy as $region_id => $region): ?>
                                  <optgroup label="<?php echo $region['info']['name']; ?>">
                                      <?php foreach ($region['states'] as $state_id => $state): ?>
                                          <option value="<?php echo $state_id; ?>"><?php echo $state['info']['name']; ?></option>
                                          
                                          <?php if (!empty($state['cities'])): ?>
                                              <?php foreach ($state['cities'] as $city): ?>
                                                  <option value="<?php echo $city['destination_id']; ?>">&nbsp;&nbsp;&nbsp;<?php echo $city['name']; ?></option>
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
                              <option value="1-3">1-3 Days</option>
                              <option value="4-7">4-7 Days</option>
                              <option value="8-14">8-14 Days</option>
                              <option value="15+">15+ Days</option>
                          </select>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <div class="form-group">
                          <label for="price" class="form-label"><i class="fas fa-rupee-sign me-2"></i>Budget</label>
                          <select name="price" id="price" class="form-select">
                              <option value="">Any Price</option>
                              <option value="0-10000">₹0 - ₹10,000</option>
                              <option value="10000-25000">₹10,000 - ₹25,000</option>
                              <option value="25000-50000">₹25,000 - ₹50,000</option>
                              <option value="50000+">₹50,000+</option>
                          </select>
                      </div>
                  </div>
                  <div class="col-md-3">
                      <button type="submit" class="btn btn-primary w-100 h-100 rounded-3 fw-bold">
                          <i class="fas fa-search me-2"></i>Find Tours
                      </button>
                  </div>
              </div>
          </form>
      </div>
  </div>
</section>

<!-- Popular Destinations Section -->
<section class="destinations-section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase">Explore India</h6>
            <h2 class="display-5 fw-bold">Popular Destinations</h2>
            <p class="text-muted mx-auto">Discover the most breathtaking and culturally rich destinations across India</p>
        </div>
        
        <div class="row g-4">
            <?php if (empty($destinations)): ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No destinations available at the moment.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($destinations as $index => $destination): ?>
                    <?php 
                    // Determine column width based on index for a more dynamic layout
                    $colClass = ($index == 0) ? 'col-md-8' : (($index == 1) ? 'col-md-4' : 'col-md-4');
                    $heightClass = ($index == 0) ? 'destination-card-lg' : (($index == 1) ? 'destination-card-md' : '');
                    ?>
                    <div class="<?php echo $colClass; ?>">
                        <div class="destination-card <?php echo $heightClass; ?>">
                            <?php
                            // Check if the image path contains 'uploads' to determine if it's using the new structure
                            $imagePath = strpos($destination['image'], '/') !== false ? $destination['image'] : 'assets/images/uploads/destinations/' . $destination['image'];
                            
                            // If the file doesn't exist in the new location, fall back to the old location
                            if (!file_exists($imagePath) && strpos($imagePath, 'uploads') !== false) {
                                $imagePath = 'assets/images/destinations/' . $destination['image'];
                            }
                            
                            // If still not found, use placeholder
                            if (!file_exists($imagePath)) {
                                $imagePath = 'assets/images/placeholder.jpg';
                            }
                            ?>
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo $destination['name']; ?>" class="destination-img">
                            <div class="destination-overlay">
                                <div class="destination-content">
                                    <h3><?php echo $destination['name']; ?></h3>
                                    <p class="destination-description"><?php echo substr($destination['description'], 0, 80); ?>...</p>
                                    <a href="destination-details.php?id=<?php echo $destination['destination_id']; ?>" class="btn btn-light btn-sm rounded-pill">Explore <i class="fas fa-arrow-right ms-1"></i></a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Featured Tours Section -->
<section id="featured-tours" class="featured-tours-section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase">Handpicked Packages</h6>
            <h2 class="display-5 fw-bold">Featured Tour Packages</h2>
            <p class="text-muted mx-auto">Discover our most popular and highly-rated tour packages across India</p>
        </div>
        
        <div class="row g-4">
            <?php if (empty($featured_tours)): ?>
                <div class="col-12 text-center">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>No featured tours available at the moment.
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($featured_tours as $tour): ?>
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
                                ?>
                                <img src="<?php echo $imagePath; ?>" class="tour-img" alt="<?php echo $tour['title']; ?>">
                                <div class="tour-card-badges">
                                    <span class="badge bg-primary">Featured</span>
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
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-5">
            <a href="tours.php" class="btn btn-lg btn-primary px-4 py-3 rounded-pill">View All Tours</a>
        </div>
    </div>
</section>

<!-- Why Choose Us Section -->
<section class="features-section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase">Our Advantages</h6>
            <h2 class="display-5 fw-bold">Why Choose TourGrid</h2>
            <p class="text-muted mx-auto">We're committed to making your Indian adventure unforgettable</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-map-marked-alt"></i>
                    </div>
                    <h4>Handpicked Destinations</h4>
                    <p>We carefully select the most exciting and beautiful destinations across India for an authentic experience.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-rupee-sign"></i>
                    </div>
                    <h4>Best Price Guarantee</h4>
                    <p>We offer the best prices with no hidden fees or unexpected charges for your Indian adventure.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-headset"></i>
                    </div>
                    <h4>24/7 Customer Support</h4>
                    <p>Our dedicated support team is always ready to assist you with any questions during your journey.</p>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h4>Safe & Secure</h4>
                    <p>Your safety is our priority. We ensure all tours follow strict safety protocols and guidelines.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-container">
            <div class="row g-0">
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-route"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">500+</h3>
                            <p class="stat-text">Tours Completed</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">1000+</h3>
                            <p class="stat-text">Happy Travelers</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">28+</h3>
                            <p class="stat-text">Indian States & UTs</p>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="stat-item">
                        <div class="stat-icon">
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="stat-content">
                            <h3 class="stat-number">5★</h3>
                            <p class="stat-text">Average Rating</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Testimonials Section -->
<section class="testimonials-section">
    <div class="container">
        <div class="section-header text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase">Traveler Stories</h6>
            <h2 class="display-5 fw-bold">What Our Customers Say</h2>
            <p class="text-muted mx-auto">Read authentic reviews from travelers who experienced our tours</p>
        </div>
        
        <div class="testimonials-slider">
            <?php if (empty($testimonials)): ?>
                <!-- Default testimonials if none in database -->
                <div class="testimonial-slide">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="testimonial-quote">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-text">"Amazing experience with TourGrid! The Rajasthan tour was perfectly organized and our guide was knowledgeable and friendly. Will definitely book again!"</p>
                        <div class="testimonial-author">
                            <img src="assets/images/testimonials/user1.jpg" alt="User" class="testimonial-author-img">
                            <div class="testimonial-author-info">
                                <h5>Rahul Sharma</h5>
                                <p>Rajasthan Heritage Tour</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-slide">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                        <div class="testimonial-quote">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-text">"Kerala Backwaters tour exceeded all my expectations. The itinerary was perfect and allowed us to see all the major attractions without feeling rushed. Highly recommended!"</p>
                        <div class="testimonial-author">
                            <img src="assets/images/testimonials/user2.jpg" alt="User" class="testimonial-author-img">
                            <div class="testimonial-author-info">
                                <h5>Priya Patel</h5>
                                <p>Kerala Backwaters Explorer</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="testimonial-slide">
                    <div class="testimonial-card">
                        <div class="testimonial-rating">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                        <div class="testimonial-quote">
                            <i class="fas fa-quote-left"></i>
                        </div>
                        <p class="testimonial-text">"Himalayan Adventure was a life-changing experience. The cultural immersion and local experiences were incredible. Will definitely book with TourGrid again!"</p>
                        <div class="testimonial-author">
                            <img src="assets/images/testimonials/user3.jpg" alt="User" class="testimonial-author-img">
                            <div class="testimonial-author-info">
                                <h5>Vikram Singh</h5>
                                <p>Himalayan Adventure</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($testimonials as $testimonial): ?>
                    <div class="testimonial-slide">
                        <div class="testimonial-card">
                            <div class="testimonial-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <?php if ($i <= $testimonial['rating']): ?>
                                        <i class="fas fa-star"></i>
                                    <?php elseif ($i - 0.5 <= $testimonial['rating']): ?>
                                        <i class="fas fa-star-half-alt"></i>
                                    <?php else: ?>
                                        <i class="far fa-star"></i>
                                    <?php endif; ?>
                                <?php endfor; ?>
                            </div>
                            <div class="testimonial-quote">
                                <i class="fas fa-quote-left"></i>
                            </div>
                            <p class="testimonial-text">"<?php echo substr($testimonial['comment'], 0, 150); ?><?php echo (strlen($testimonial['comment']) > 150) ? '...' : ''; ?>"</p>
                            <div class="testimonial-author">
                                <?php
                                $profileImage = !empty($testimonial['profile_image']) ? 'assets/images/users/'.$testimonial['profile_image'] : 'assets/images/users/default-user.jpg';
                                if (!file_exists($profileImage)) {
                                    $profileImage = 'assets/images/users/default-user.jpg';
                                }
                                ?>
                                <img src="<?php echo $profileImage; ?>" alt="<?php echo $testimonial['full_name']; ?>" class="testimonial-author-img">
                                <div class="testimonial-author-info">
                                    <h5><?php echo $testimonial['full_name']; ?></h5>
                                    <p><?php echo $testimonial['tour_title']; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-container">
            <div class="row align-items-center">
                <div class="col-lg-8 mb-4 mb-lg-0">
                    <h2>Ready to Start Your Indian Adventure?</h2>
                    <p>Book your tour today and experience the wonders of India with TourGrid</p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="tours.php" class="btn btn-light btn-lg rounded-pill me-2">Browse Tours</a>
                    <a href="contact.php" class="btn btn-outline-light btn-lg rounded-pill">Contact Us</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Newsletter Section -->
<section class="newsletter-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="newsletter-container">
                    <div class="row g-0">
                        <div class="col-md-6 newsletter-content">
                            <h3>Subscribe to Our Newsletter</h3>
                            <p>Get the latest updates on new tours, special offers, and travel tips for exploring India.</p>
                            <div class="newsletter-decoration">
                                <i class="fas fa-paper-plane"></i>
                            </div>
                        </div>
                        <div class="col-md-6 newsletter-form-container">
                            <form class="newsletter-form">
                                <div class="mb-3">
                                    <label for="newsletter-email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="newsletter-email" placeholder="your@email.com" required>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary rounded-pill">Subscribe</button>
                                </div>
                                <div class="form-text mt-2 text-center">We respect your privacy. Unsubscribe at any time.</div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add custom CSS for the new design -->
<style>
/* General Styles */
:root {
    --primary-color: #ff6b6b;
    --primary-dark: #ff5252;
    --primary-light: #ffeeee;
    --secondary-color: #4a6cf7;
    --dark-color: #2d3436;
    --light-color: #f8f9fa;
    --text-color: #333;
    --text-light: #6c757d;
    --white: #ffffff;
    --border-radius: 10px;
    --border-radius-lg: 15px;
    --box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    --transition: all 0.3s ease;
}

body {
    font-family: 'Poppins', sans-serif;
    color: var(--text-color);
    overflow-x: hidden;
}

.section-header {
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
    margin-bottom: 60px;
}

.section-header h6 {
    letter-spacing: 2px;
    margin-bottom: 10px;
}

.section-header h2 {
    margin-bottom: 20px;
}

.section-header p {
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.text-highlight {
    color: var(--primary-color);
    position: relative;
    display: inline-block;
}

.text-highlight::after {
    content: '';
    position: absolute;
    bottom: 5px;
    left: 0;
    width: 100%;
    height: 8px;
    background-color: rgba(255, 107, 107, 0.3);
    z-index: -1;
}

/* Hero Section */
.hero-section {
    height: 100vh;
    min-height: 700px;
    display: flex;
    align-items: center;
    overflow: hidden;
}

.hero-background {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
    z-index: -1;
}

.hero-content {
    z-index: 1;
    padding: 100px 0;
}

.hero-content h1 {
    font-size: 4rem;
    line-height: 1.2;
    margin-bottom: 30px;
}

.hero-content p {
    font-size: 1.25rem;
    max-width: 600px;
}

.btn {
    font-weight: 600;
    padding: 12px 30px;
    border-radius: 50px;
    transition: var(--transition);
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-primary:hover {
    background-color: var(--primary-dark);
    border-color: var(--primary-dark);
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(255, 107, 107, 0.3);
}

.btn-outline-light:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(255, 255, 255, 0.1);
}

/* Search Section */
.search-section {
    margin-top: -80px;
    position: relative;
    z-index: 10;
    padding: 0 0 80px;
}

.search-container {
    background-color: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--box-shadow);
    padding: 40px;
}

.search-header h3 {
    margin-bottom: 10px;
}

.search-form .form-group {
    margin-bottom: 0;
}

.search-form .form-label {
    font-weight: 500;
    margin-bottom: 8px;
    display: block;
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
    margin-top: 32px; /* Align with inputs that have labels */
}

@media (max-width: 768px) {
    .search-section {
        margin-top: 0;
    }
    
    .search-container {
        padding: 30px 20px;
    }
    
    .search-form .btn {
        margin-top: 0;
    }
}

/* Destinations Section */
.destinations-section {
    padding: 100px 0;
    background-color: var(--light-color);
}

.destination-card {
    position: relative;
    border-radius: var(--border-radius);
    overflow: hidden;
    height: 300px;
    box-shadow: var(--box-shadow);
    transition: var(--transition);
}

.destination-card-lg {
    height: 400px;
}

.destination-card-md {
    height: 400px;
}

.destination-card:hover {
    transform: translateY(-10px);
}

.destination-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.5s ease;
}

.destination-card:hover .destination-img {
    transform: scale(1.1);
}

.destination-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.1));
    display: flex;
    align-items: flex-end;
    padding: 30px;
    transition: var(--transition);
}

.destination-content {
    color: var(--white);
}

.destination-content h3 {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.destination-description {
    opacity: 0.8;
    margin-bottom: 15px;
    font-size: 0.9rem;
}

/* Featured Tours Section */
.featured-tours-section {
    padding: 100px 0;
}

.tour-card {
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
    background-color: var(--white);
    transition: var(--transition);
    margin-bottom: 30px;
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
    color: var(--text-color);
    transition: var(--transition);
}

.btn-wishlist:hover {
    background-color: var(--primary-color);
    color: var(--white);
}

.tour-card-body {
    padding: 25px;
}

.tour-card-location {
    color: var(--primary-color);
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
    color: var(--text-light);
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
    color: var(--text-light);
}

.tour-feature i {
    color: var(--primary-color);
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
    color: var(--primary-color);
    line-height: 1;
}

.price-text {
    font-size: 0.8rem;
    color: var(--text-light);
}

/* Features Section */
.features-section {
    padding: 100px 0;
    background-color: var(--white);
}

.feature-card {
    text-align: center;
    padding: 40px 30px;
    border-radius: var(--border-radius);
    background-color: var(--light-color);
    transition: var(--transition);
    height: 100%;
}

.feature-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--box-shadow);
}

.feature-icon {
    width: 80px;
    height: 80px;
    margin: 0 auto 25px;
    background-color: var(--primary-light);
    color: var(--primary-color);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
}

.feature-card h4 {
    font-size: 1.25rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.feature-card p {
    color: var(--text-light);
    font-size: 0.9rem;
}

/* Stats Section */
.stats-section {
    padding: 50px 0;
    background-color: var(--light-color);
}

.stats-container {
    background-color: var(--primary-color);
    border-radius: var(--border-radius);
    padding: 50px;
    color: var(--white);
}

.stat-item {
    display: flex;
    align-items: center;
    padding: 20px;
}

.stat-icon {
    font-size: 2.5rem;
    margin-right: 20px;
    opacity: 0.8;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    line-height: 1;
}

.stat-text {
    font-size: 1rem;
    opacity: 0.8;
    margin-bottom: 0;
}

/* Testimonials Section */
.testimonials-section {
    padding: 100px 0;
    background-color: var(--white);
}

.testimonials-slider {
    display: flex;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    gap: 30px;
    padding: 20px 0;
    scrollbar-width: none; /* Firefox */
}

.testimonials-slider::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}

.testimonial-slide {
    flex: 0 0 auto;
    width: 350px;
    scroll-snap-align: start;
}

.testimonial-card {
    background-color: var(--light-color);
    border-radius: var(--border-radius);
    padding: 30px;
    position: relative;
    transition: var(--transition);
    height: 100%;
}

.testimonial-card:hover {
    transform: translateY(-10px);
    box-shadow: var(--box-shadow);
}

.testimonial-rating {
    color: #ffc107;
    margin-bottom: 20px;
    font-size: 1.2rem;
}

.testimonial-quote {
    position: absolute;
    top: 20px;
    right: 20px;
    font-size: 2rem;
    color: var(--primary-color);
    opacity: 0.2;
}

.testimonial-text {
    font-size: 1rem;
    line-height: 1.6;
    margin-bottom: 25px;
    color: var(--text-color);
}

.testimonial-author {
    display: flex;
    align-items: center;
}

.testimonial-author-img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    object-fit: cover;
    margin-right: 15px;
    border: 3px solid var(--white);
}

.testimonial-author-info h5 {
    font-size: 1.1rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.testimonial-author-info p {
    font-size: 0.9rem;
    color: var(--text-light);
    margin-bottom: 0;
}

/* CTA Section */
.cta-section {
    padding: 100px 0;
    background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), url('assets/images/cta-bg.jpg');
    background-size: cover;
    background-position: center;
    background-attachment: fixed;
}

.cta-container {
    color: var(--white);
}

.cta-container h2 {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 15px;
}

.cta-container p {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 0;
}

/* Newsletter Section */
.newsletter-section {
    padding: 100px 0;
    background-color: var(--light-color);
}

.newsletter-container {
    background-color: var(--white);
    border-radius: var(--border-radius);
    overflow: hidden;
    box-shadow: var(--box-shadow);
}

.newsletter-content {
    background-color: var(--primary-color);
    color: var(--white);
    padding: 50px 40px;
    position: relative;
    overflow: hidden;
}

.newsletter-content h3 {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 20px;
    position: relative;
    z-index: 1;
}

.newsletter-content p {
    font-size: 1rem;
    opacity: 0.9;
    margin-bottom: 0;
    position: relative;
    z-index: 1;
}

.newsletter-decoration {
    position: absolute;
    bottom: -30px;
    right: -30px;
    font-size: 8rem;
    opacity: 0.1;
}

.newsletter-form-container {
    padding: 50px 40px;
}

.newsletter-form .form-label {
    font-weight: 600;
}

.newsletter-form .form-control {
    padding: 12px 20px;
    border-radius: 50px;
    border: 1px solid #eee;
}

.newsletter-form .form-control:focus {
    box-shadow: none;
    border-color: var(--primary-color);
}

.newsletter-form .btn {
    padding: 12px 30px;
}

/* Responsive Styles */
@media (max-width: 992px) {
    .hero-content h1 {
        font-size: 3rem;
    }
    
    .destination-card, .destination-card-lg, .destination-card-md {
        height: 250px;
    }
    
    .search-container {
        padding: 30px;
    }
    
    .stats-container {
        padding: 30px;
    }
    
    .stat-number {
        font-size: 2rem;
    }
    
    .newsletter-content, .newsletter-form-container {
        padding: 40px 30px;
    }
}

@media (max-width: 768px) {
    .hero-content h1 {
        font-size: 2.5rem;
    }
    
    .hero-section {
        min-height: 600px;
    }
    
    .search-section {
        margin-top: 0;
    }
    
    .destination-card, .destination-card-lg, .destination-card-md {
        height: 200px;
        margin-bottom: 20px;
    }
    
    .feature-card {
        margin-bottom: 20px;
    }
    
    .testimonial-slide {
        width: 300px;
    }
    
    .cta-container h2 {
        font-size: 2rem;
    }
}

@media (max-width: 576px) {
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .btn {
        padding: 10px 20px;
    }
    
    .search-container {
        padding: 20px;
    }
    
    .destination-card, .destination-card-lg, .destination-card-md {
        height: 180px;
    }
    
    .tour-img {
        height: 200px;
    }
    
    .stats-container {
        padding: 20px;
    }
    
    .stat-item {
        padding: 10px;
    }
    
    .stat-icon {
        font-size: 2rem;
        margin-right: 15px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .testimonial-slide {
        width: 280px;
    }
    
    .newsletter-content, .newsletter-form-container {
        padding: 30px 20px;
    }
}
</style>

<!-- Add Animate.css for animations -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">

<!-- Add custom JavaScript for the testimonials slider -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-scroll testimonials
    const testimonialsSlider = document.querySelector('.testimonials-slider');
    if (testimonialsSlider) {
        let scrollAmount = 0;
        const slideWidth = 380; // Width of slide + gap
        const maxScroll = testimonialsSlider.scrollWidth - testimonialsSlider.clientWidth;
        
        setInterval(() => {
            scrollAmount += slideWidth;
            if (scrollAmount > maxScroll) {
                scrollAmount = 0;
            }
            testimonialsSlider.scrollTo({
                left: scrollAmount,
                behavior: 'smooth'
            });
        }, 5000);
    }
    
    // Animate elements when they come into view
    const animateOnScroll = function() {
        const elements = document.querySelectorAll('.feature-card, .destination-card, .tour-card, .testimonial-card');
        
        elements.forEach(element => {
            const elementPosition = element.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (elementPosition < windowHeight - 50) {
                element.classList.add('animate__animated', 'animate__fadeInUp');
            }
        });
    };
    
    // Run once on page load
    animateOnScroll();
    
    // Run on scroll
    window.addEventListener('scroll', animateOnScroll);
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
