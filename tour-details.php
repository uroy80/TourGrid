<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include necessary files
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Check if tour ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: tours.php");
    exit;
}

$tour_id = $_GET['id'];
$tour = getTourById($tour_id);

// If tour not found, redirect to tours page
if (!$tour) {
    header("Location: tours.php");
    exit;
}

// Get tour schedules
$schedules = getTourSchedules($tour_id);

// Get tour itinerary
$itinerary = getTourItinerary($tour_id);

// Get tour hotels
$hotels = getTourHotels($tour_id);

// Get destination information
$destination = getDestinationById($tour['destination_id']);

// Page title
$page_title = $tour['title'] . " | TourSync";

// Include header
include 'includes/header.php';
?>

<div class="container my-5">
    <!-- Debug Information -->
    <div class="card mb-4 bg-light">
        <div class="card-body">
            <h5 class="card-title">Debug Information</h5>
            <p class="mb-1"><strong>Tour ID:</strong> <?php echo $tour_id; ?></p>
            <p class="mb-1"><strong>Current URL:</strong> <?php echo $_SERVER['REQUEST_URI']; ?></p>
            <p class="mb-1"><strong>Script Path:</strong> <?php echo $_SERVER['SCRIPT_FILENAME']; ?></p>
        </div>
    </div>

    <!-- Tour Details -->
    <div class="row">
        <!-- Tour Image -->
        <div class="col-md-6 mb-4">
            <div class="tour-image-container">
                <img src="<?php echo getImagePath($tour['image'], 'tours'); ?>" alt="<?php echo $tour['title']; ?>" class="img-fluid rounded shadow">
            </div>
        </div>

        <!-- Tour Information -->
        <div class="col-md-6">
            <h1 class="tour-title"><?php echo $tour['title']; ?></h1>
            <h2 class="tour-subtitle">
                <i class="fas fa-map-marker-alt"></i> <?php echo $tour['destination_name']; ?> |
                <i class="fas fa-clock"></i> <?php echo $tour['duration']; ?> days
            </h2>

            <div class="tour-description mt-4">
                <h3>Tour Overview</h3>
                <p><?php echo $tour['description']; ?></p>
            </div>

            <div class="destination-info mt-4">
                <h3>Destination Information</h3>
                <p><?php echo $destination['description'] ?? 'No destination information available.'; ?></p>
            </div>

            <div class="tour-features mt-4">
                <div class="row">
                    <div class="col-md-6">
                        <h3>Inclusions</h3>
                        <p><?php echo $tour['inclusions'] ?? 'No inclusions specified.'; ?></p>
                    </div>
                    <div class="col-md-6">
                        <h3>Exclusions</h3>
                        <p><?php echo $tour['exclusions'] ?? 'No exclusions specified.'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Itinerary Section -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Itinerary</h3>
                </div>
                <div class="card-body">
                    <?php if (count($itinerary) > 0): ?>
                        <div class="accordion" id="itineraryAccordion">
                            <?php foreach ($itinerary as $index => $day): ?>
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="heading<?php echo $index; ?>">
                                        <button class="accordion-button <?php echo ($index > 0) ? 'collapsed' : ''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $index; ?>" aria-expanded="<?php echo ($index === 0) ? 'true' : 'false'; ?>" aria-controls="collapse<?php echo $index; ?>">
                                            Day <?php echo $day['day_number']; ?>: <?php echo $day['title']; ?>
                                        </button>
                                    </h2>
                                    <div id="collapse<?php echo $index; ?>" class="accordion-collapse collapse <?php echo ($index === 0) ? 'show' : ''; ?>" aria-labelledby="heading<?php echo $index; ?>" data-bs-parent="#itineraryAccordion">
                                        <div class="accordion-body">
                                            <?php echo $day['description']; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No itinerary details available for this tour.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Tour Details and Booking Section -->
    <div class="row mt-5">
        <!-- Tour Details -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Tour Details</h3>
                </div>
                <div class="card-body">
                    <p class="tour-price fs-2 fw-bold text-primary">
                        <?php echo formatCurrency($tour['price']); ?> per person
                    </p>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <i class="fas fa-calendar-alt me-2"></i> Duration: <?php echo $tour['duration']; ?> days
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-map-marker-alt me-2"></i> Destination: <?php echo $tour['destination_name']; ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-users me-2"></i> Group Size:
                            <?php
                            $min_people = isset($tour['min_people']) ? $tour['min_people'] : 1;
                            $max_people = isset($tour['max_people']) ? $tour['max_people'] : 10;
                            echo $min_people . '-' . $max_people . ' people';
                            ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Booking Section -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0">Upcoming Departures</h3>
                </div>
                <div class="card-body">
                    <?php if (count($schedules) > 0): ?>
                        <form action="booking.php" method="GET" id="bookingForm">
                            <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">

                            <div class="mb-3">
                                <label for="schedule" class="form-label">Select a departure date:</label>
                                <select class="form-select" name="schedule_id" id="schedule" required>
                                    <?php foreach ($schedules as $schedule): ?>
                                        <option value="<?php echo $schedule['schedule_id']; ?>">
                                            <?php echo formatDate($schedule['start_date']); ?> - <?php echo formatDate($schedule['end_date']); ?>
                                            (<?php echo $schedule['available_seats']; ?> seats left)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success btn-lg">Continue to Booking</button>
                            </div>
                        </form>

                        <!-- Direct Booking Links (for debugging) -->
                        <div class="mt-4">
                            <h5>Direct Booking Links:</h5>
                            <div class="list-group">
                                <?php foreach ($schedules as $schedule): ?>
                                    <a href="booking.php?tour_id=<?php echo $tour_id; ?>&schedule_id=<?php echo $schedule['schedule_id']; ?>"
                                       class="list-group-item list-group-item-action">
                                        <?php echo formatDate($schedule['start_date']); ?> - <?php echo formatDate($schedule['end_date']); ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Having trouble section -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h5>Having trouble with the booking form?</h5>
                            <p>Try this direct link:</p>
                            <a href="direct-booking.php?tour_id=<?php echo $tour_id; ?>&schedule_id=<?php echo $schedules[0]['schedule_id']; ?>"
                               class="btn btn-primary">Direct Booking Link</a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning">
                            No upcoming departures available for this tour. Please check back later or contact us for custom dates.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Hotels Section -->
    <?php if (count($hotels) > 0): ?>
        <div class="row mt-5">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Accommodations</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($hotels as $hotel): ?>
                                <div class="col-md-4 mb-4">
                                    <div class="card h-100">
                                        <img src="<?php echo getImagePath($hotel['image'], 'hotels'); ?>" class="card-img-top" alt="<?php echo $hotel['name']; ?>">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo $hotel['name']; ?></h5>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    <?php
                                                    for ($i = 0; $i < $hotel['star_rating']; $i++) {
                                                        echo '<i class="fas fa-star text-warning"></i>';
                                                    }
                                                    ?>
                                                </small>
                                            </p>
                                            <p class="card-text"><?php echo $hotel['nights']; ?> nights | <?php echo $hotel['room_type']; ?></p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- JavaScript for form submission logging -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const bookingForm = document.getElementById('bookingForm');

            if (bookingForm) {
                bookingForm.addEventListener('submit', function(e) {
                    // Log form submission
                    console.log('Form submitted');
                    console.log('Tour ID:', this.elements['tour_id'].value);
                    console.log('Schedule ID:', this.elements['schedule_id'].value);

                    // Add a timestamp to prevent caching issues
                    const timestampField = document.createElement('input');
                    timestampField.type = 'hidden';
                    timestampField.name = 'timestamp';
                    timestampField.value = Date.now();
                    this.appendChild(timestampField);

                    // Continue with form submission
                    return true;
                });
            }
        });
    </script>
</div>

<?php include 'includes/footer.php'; ?>
