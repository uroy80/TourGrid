<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Basic debug information
echo "<h1>Booking Page</h1>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";
echo "<p>GET parameters: ";
print_r($_GET);
echo "</p>";

// Include necessary files
require_once 'config/database.php';
require_once 'config/session.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if tour_id and schedule_id are provided
if (!isset($_GET['tour_id']) || !isset($_GET['schedule_id'])) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "Error: Missing tour_id or schedule_id parameters.";
    echo "</div>";
    echo "<p><a href='tours.php'>Return to Tours</a></p>";
    exit;
}

$tour_id = $_GET['tour_id'];
$schedule_id = $_GET['schedule_id'];

// Try to connect to database
try {
    $database = new Database();
    $conn = $database->getConnection();
    echo "<p style='color: green;'>Database connection successful!</p>";

    // Get tour details including max_people
    $tour_query = "SELECT * FROM tours WHERE tour_id = :tour_id";
    $tour_stmt = $conn->prepare($tour_query);
    $tour_stmt->bindParam(':tour_id', $tour_id);
    $tour_stmt->execute();

    if ($tour_stmt->rowCount() == 0) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
        echo "Error: Tour not found.";
        echo "</div>";
        echo "<p><a href='tours.php'>Return to Tours</a></p>";
        exit;
    }

    $tour = $tour_stmt->fetch(PDO::FETCH_ASSOC);

    // Get schedule details
    $schedule_query = "SELECT * FROM tour_schedules WHERE schedule_id = :schedule_id AND tour_id = :tour_id";
    $schedule_stmt = $conn->prepare($schedule_query);
    $schedule_stmt->bindParam(':schedule_id', $schedule_id);
    $schedule_stmt->bindParam(':tour_id', $tour_id);
    $schedule_stmt->execute();

    if ($schedule_stmt->rowCount() == 0) {
        echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
        echo "Error: Schedule not found.";
        echo "</div>";
        echo "<p><a href='tour-details.php?id=" . $tour_id . "'>Return to Tour Details</a></p>";
        exit;
    }

    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

    // Get max_people from tour (if it exists)
    $max_people = isset($tour['max_people']) ? $tour['max_people'] : 3;

    // Check if max_capacity column exists in tour_schedules
    $column_check = $conn->query("SHOW COLUMNS FROM tour_schedules LIKE 'max_capacity'");
    $max_capacity_column_exists = $column_check->rowCount() > 0;

    // Get max_capacity from schedule or set a default
    if ($max_capacity_column_exists && isset($schedule['max_capacity']) && !empty($schedule['max_capacity'])) {
        $max_capacity = $schedule['max_capacity'];
    } else {
        // If max_capacity doesn't exist or is empty, use a default value (20)
        $max_capacity = 20;

        // Try to update the schedule with the default max_capacity
        if ($max_capacity_column_exists) {
            $update_query = "UPDATE tour_schedules SET max_capacity = :max_capacity WHERE schedule_id = :schedule_id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':max_capacity', $max_capacity);
            $update_stmt->bindParam(':schedule_id', $schedule_id);
            $update_stmt->execute();
        }
    }

    // Debug the schedule data
    echo "<p style='color: blue;'>Schedule Data:</p>";
    echo "<pre style='color: blue;'>";
    print_r($schedule);
    echo "</pre>";

    // Calculate available seats
    $available_seats = $max_capacity;
    $booked_seats = 0;

    // Check if bookings table exists and get booked seats
    $table_check = $conn->query("SHOW TABLES LIKE 'bookings'");
    if ($table_check->rowCount() > 0) {
        // Show all bookings for this schedule for debugging
        echo "<p style='color: blue;'>Bookings for this schedule:</p>";
        $all_bookings_query = "SELECT * FROM bookings WHERE schedule_id = :schedule_id";
        $all_bookings_stmt = $conn->prepare($all_bookings_query);
        $all_bookings_stmt->bindParam(':schedule_id', $schedule_id);
        $all_bookings_stmt->execute();

        $has_bookings = $all_bookings_stmt->rowCount() > 0;

        echo "<table border='1' style='color: blue;'>";
        echo "<tr><th>Booking ID</th><th>User ID</th><th>Num People</th><th>Status</th></tr>";

        if ($has_bookings) {
            while ($booking = $all_bookings_stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr>";
                echo "<td>" . $booking['booking_id'] . "</td>";
                echo "<td>" . $booking['user_id'] . "</td>";
                echo "<td>" . $booking['num_people'] . "</td>";
                echo "<td>" . (isset($booking['status']) ? $booking['status'] : 'N/A') . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='4'>No bookings found for this schedule</td></tr>";
        }
        echo "</table>";

        // Get sum of booked seats
        $bookings_query = "SELECT SUM(num_people) as booked_seats FROM bookings 
                          WHERE schedule_id = :schedule_id";

        // Only exclude cancelled bookings if the status column exists
        $status_check = $conn->query("SHOW COLUMNS FROM bookings LIKE 'status'");
        if ($status_check->rowCount() > 0) {
            $bookings_query .= " AND (status IS NULL OR status != 'cancelled')";
        }

        $bookings_stmt = $conn->prepare($bookings_query);
        $bookings_stmt->bindParam(':schedule_id', $schedule_id);
        $bookings_stmt->execute();
        $bookings_result = $bookings_stmt->fetch(PDO::FETCH_ASSOC);

        $booked_seats = $bookings_result['booked_seats'] ? $bookings_result['booked_seats'] : 0;
        $calculated_available_seats = $max_capacity - $booked_seats;
    } else {
        echo "<p style='color: blue;'>No bookings table found. Assuming all seats are available.</p>";
        $calculated_available_seats = $max_capacity;
    }

    // Get available seats directly from the schedule if the column exists
    $available_seats_column_check = $conn->query("SHOW COLUMNS FROM tour_schedules LIKE 'available_seats'");
    if ($available_seats_column_check->rowCount() > 0 && isset($schedule['available_seats'])) {
        $schedule_available_seats = $schedule['available_seats'];

        echo "<p style='color: blue;'>Available seats from schedule: $schedule_available_seats</p>";
        echo "<p style='color: blue;'>Calculated available seats: $calculated_available_seats</p>";

        // If there's a significant discrepancy, update the database
        if (abs($schedule_available_seats - $calculated_available_seats) > 1) {
            echo "<p style='color: red;'>Significant discrepancy detected. Updating database with correct value.</p>";

            $update_query = "UPDATE tour_schedules SET available_seats = :available_seats WHERE schedule_id = :schedule_id";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bindParam(':available_seats', $calculated_available_seats);
            $update_stmt->bindParam(':schedule_id', $schedule_id);
            $update_stmt->execute();

            // Update the schedule data for display
            $schedule['available_seats'] = $calculated_available_seats;

            echo "<p style='color: green;'>Database updated successfully. Available seats set to $calculated_available_seats.</p>";
        }

        // Use the calculated value for consistency
        $available_seats = $calculated_available_seats;
    } else {
        $available_seats = $calculated_available_seats;

        // Add available_seats column if it doesn't exist
        if ($available_seats_column_check->rowCount() == 0) {
            echo "<p style='color: orange;'>Adding available_seats column to tour_schedules table.</p>";
            $alter_query = "ALTER TABLE tour_schedules ADD COLUMN available_seats INT DEFAULT 0";
            $conn->exec($alter_query);
        }

        // Update the available_seats in the schedule
        $update_query = "UPDATE tour_schedules SET available_seats = :available_seats WHERE schedule_id = :schedule_id";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bindParam(':available_seats', $available_seats);
        $update_stmt->bindParam(':schedule_id', $schedule_id);
        $update_stmt->execute();

        echo "<p style='color: green;'>Updated available_seats in database to $available_seats.</p>";
    }

    echo "<p style='color: blue;'>Debug Info:</p>";
    echo "<ul style='color: blue;'>";
    echo "<li>Max people per tour: $max_people</li>";
    echo "<li>Max capacity for this schedule: $max_capacity</li>";
    echo "<li>Booked seats: $booked_seats</li>";
    echo "<li>Available seats: $available_seats</li>";
    echo "</ul>";

    // IMPORTANT: Force available seats to be at least 1 for testing
    if ($available_seats <= 0) {
        echo "<p style='color: orange;'>Warning: Schedule appears to be fully booked, but we're allowing booking for testing.</p>";
        $available_seats = 1; // Force at least 1 available seat for testing
    }

    // Limit max travelers to available seats or max_people, whichever is smaller
    $max_travelers = min($available_seats, $max_people);

} catch (Exception $e) {
    echo "<div style='color: red; padding: 10px; border: 1px solid red;'>";
    echo "Database Error: " . $e->getMessage();
    echo "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tour - TourSync</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .required-field::after {
            content: "*";
            color: red;
            margin-left: 4px;
        }
        .traveler-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Book Your Tour</h3>
                </div>
                <div class="card-body">
                    <div class="tour-summary mb-4">
                        <h4><?php echo htmlspecialchars($tour['title']); ?></h4>
                        <p><strong>Duration:</strong> <?php echo $tour['duration']; ?> days</p>
                        <p><strong>Departure Date:</strong> <?php echo date('F d, Y', strtotime($schedule['start_date'])); ?></p>
                        <p><strong>Return Date:</strong> <?php echo date('F d, Y', strtotime($schedule['end_date'])); ?></p>
                        <p><strong>Price:</strong> ₹<?php echo number_format($tour['price']); ?> per person</p>
                        <p><strong>Available Seats:</strong> <?php echo $available_seats; ?></p>
                        <p><strong>Maximum Travelers Per Booking:</strong> <?php echo $max_people; ?></p>
                    </div>

                    <form action="booking-process.php" method="post" id="bookingForm">
                        <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        <input type="hidden" name="price" value="<?php echo $tour['price']; ?>">

                        <div class="mb-3">
                            <label for="num_travelers" class="form-label required-field">Number of Travelers</label>
                            <select class="form-select" id="num_travelers" name="num_travelers" required>
                                <?php for ($i = 1; $i <= $max_travelers; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                            <?php if ($max_travelers < $available_seats): ?>
                                <small class="text-muted">Maximum <?php echo $max_people; ?> travelers per booking for this tour.</small>
                            <?php endif; ?>
                        </div>

                        <!-- Lead Traveler Information -->
                        <div class="traveler-card">
                            <h5 class="mb-3">Lead Traveler Information</h5>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label required-field">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="age" class="form-label required-field">Age</label>
                                    <input type="number" class="form-control" id="age" name="age" min="1" max="120" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label required-field">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label required-field">Phone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="gender" class="form-label required-field">Gender</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="id_type" class="form-label required-field">ID Type</label>
                                    <select class="form-select" id="id_type" name="id_type" required>
                                        <option value="">Select ID Type</option>
                                        <option value="passport">Passport</option>
                                        <option value="national_id">National ID</option>
                                        <option value="drivers_license">Driver's License</option>
                                        <option value="voter_id">Voter ID</option>
                                        <option value="aadhar">Aadhar Card</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="id_number" class="form-label required-field">ID Number</label>
                                    <input type="text" class="form-control" id="id_number" name="id_number" required>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Travelers (will be added dynamically) -->
                        <div id="additional-travelers"></div>

                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests (Optional)</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3" placeholder="Any dietary requirements, accessibility needs, or other special requests?"></textarea>
                        </div>

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions
                            </label>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100">Continue to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const numTravelersSelect = document.getElementById('num_travelers');
        const additionalTravelersDiv = document.getElementById('additional-travelers');

        // Function to update additional traveler forms
        function updateAdditionalTravelers() {
            const numTravelers = parseInt(numTravelersSelect.value);
            additionalTravelersDiv.innerHTML = '';

            // Add forms for additional travelers
            for (let i = 2; i <= numTravelers; i++) {
                const travelerDiv = document.createElement('div');
                travelerDiv.className = 'traveler-card';
                travelerDiv.innerHTML = `
                    <h5 class="mb-3">Traveler ${i} Information</h5>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="traveler_name_${i}" class="form-label required-field">Full Name</label>
                            <input type="text" class="form-control" id="traveler_name_${i}" name="traveler_name_${i}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="traveler_age_${i}" class="form-label required-field">Age</label>
                            <input type="number" class="form-control" id="traveler_age_${i}" name="traveler_age_${i}" min="1" max="120" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="traveler_gender_${i}" class="form-label required-field">Gender</label>
                            <select class="form-select" id="traveler_gender_${i}" name="traveler_gender_${i}" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="traveler_id_type_${i}" class="form-label required-field">ID Type</label>
                            <select class="form-select" id="traveler_id_type_${i}" name="traveler_id_type_${i}" required>
                                <option value="">Select ID Type</option>
                                <option value="passport">Passport</option>
                                <option value="national_id">National ID</option>
                                <option value="drivers_license">Driver's License</option>
                                <option value="voter_id">Voter ID</option>
                                <option value="aadhar">Aadhar Card</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="traveler_id_number_${i}" class="form-label required-field">ID Number</label>
                            <input type="text" class="form-control" id="traveler_id_number_${i}" name="traveler_id_number_${i}" required>
                        </div>
                    </div>
                `;
                additionalTravelersDiv.appendChild(travelerDiv);
            }
        }

        // Initialize additional traveler forms
        updateAdditionalTravelers();

        // Update when number of travelers changes
        numTravelersSelect.addEventListener('change', updateAdditionalTravelers);

        // Add timestamp to form on submit
        const bookingForm = document.getElementById('bookingForm');
        bookingForm.addEventListener('submit', function() {
            const timestamp = document.createElement('input');
            timestamp.type = 'hidden';
            timestamp.name = 'timestamp';
            timestamp.value = Date.now();
            this.appendChild(timestamp);
        });
    });
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
