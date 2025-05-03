<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';
require_once 'includes/helpers.php';

// Check if tour_id and schedule_id are provided
if (!isset($_GET['tour_id']) || !isset($_GET['schedule_id'])) {
    echo "<div class='alert alert-danger'>Missing tour or schedule information.</div>";
    exit;
}

$tour_id = $_GET['tour_id'];
$schedule_id = $_GET['schedule_id'];

// Get tour and schedule details
try {
    $database = new Database();
    $conn = $database->getConnection();

    // Get tour details
    $tour_sql = "SELECT * FROM tours WHERE tour_id = ?";
    $tour_stmt = $conn->prepare($tour_sql);
    $tour_stmt->bindParam(1, $tour_id, PDO::PARAM_INT);
    $tour_stmt->execute();
    $tour = $tour_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tour) {
        echo "<div class='alert alert-danger'>Tour not found.</div>";
        exit;
    }

    // Get schedule details
    $schedule_sql = "SELECT * FROM tour_schedules WHERE schedule_id = ?";
    $schedule_stmt = $conn->prepare($schedule_sql);
    $schedule_stmt->bindParam(1, $schedule_id, PDO::PARAM_INT);
    $schedule_stmt->execute();
    $schedule = $schedule_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$schedule) {
        echo "<div class='alert alert-danger'>Schedule not found.</div>";
        exit;
    }

    // Get price from schedule or tour
    $price = isset($schedule['price']) && !empty($schedule['price']) ? $schedule['price'] : $tour['price'];

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Tour - <?php echo htmlspecialchars($tour['title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Book Your Tour</h2>
                </div>
                <div class="card-body">
                    <div class="tour-summary mb-4">
                        <h3><?php echo htmlspecialchars($tour['title']); ?></h3>
                        <p><strong>Departure:</strong> <?php echo date('F j, Y', strtotime($schedule['start_date'])); ?></p>
                        <p><strong>Return:</strong> <?php echo date('F j, Y', strtotime($schedule['end_date'])); ?></p>
                        <p><strong>Price per person:</strong> $<?php echo number_format($price, 2); ?></p>
                    </div>

                    <form action="booking-process.php" method="post" id="bookingForm">
                        <input type="hidden" name="tour_id" value="<?php echo $tour_id; ?>">
                        <input type="hidden" name="schedule_id" value="<?php echo $schedule_id; ?>">
                        <input type="hidden" name="price" value="<?php echo $price; ?>">

                        <div class="mb-3">
                            <label for="num_travelers" class="form-label">Number of Travelers</label>
                            <select class="form-select" id="num_travelers" name="num_travelers" required onchange="updateTravelerForms()">
                                <?php for ($i = 1; $i <= 10; $i++) : ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <h4 class="mt-4">Lead Traveler Information</h4>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" class="form-control" id="phone" name="phone" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" class="form-control" id="age" name="age" min="1" max="120" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_type" class="form-label">ID Type</label>
                                <select class="form-select" id="id_type" name="id_type" required>
                                    <option value="passport">Passport</option>
                                    <option value="national_id">National ID</option>
                                    <option value="drivers_license">Driver's License</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="id_number" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="id_number" name="id_number" required>
                            </div>
                        </div>

                        <!-- Additional travelers will be added here dynamically -->
                        <div id="additionalTravelers"></div>

                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3"></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                            <label class="form-check-label" for="terms">I agree to the terms and conditions</label>
                        </div>

                        <button type="submit" class="btn btn-primary">Continue to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function updateTravelerForms() {
        const numTravelers = parseInt(document.getElementById('num_travelers').value);
        const container = document.getElementById('additionalTravelers');
        container.innerHTML = '';

        // Start from 2 because traveler 1 is the lead traveler
        for (let i = 2; i <= numTravelers; i++) {
            const travelerForm = `
                    <h4 class="mt-4">Traveler ${i} Information</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="traveler_name_${i}" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="traveler_name_${i}" name="traveler_name_${i}" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="traveler_age_${i}" class="form-label">Age</label>
                            <input type="number" class="form-control" id="traveler_age_${i}" name="traveler_age_${i}" min="1" max="120" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="traveler_gender_${i}" class="form-label">Gender</label>
                            <select class="form-select" id="traveler_gender_${i}" name="traveler_gender_${i}" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="traveler_id_type_${i}" class="form-label">ID Type</label>
                            <select class="form-select" id="traveler_id_type_${i}" name="traveler_id_type_${i}" required>
                                <option value="passport">Passport</option>
                                <option value="national_id">National ID</option>
                                <option value="drivers_license">Driver's License</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="traveler_id_number_${i}" class="form-label">ID Number</label>
                            <input type="text" class="form-control" id="traveler_id_number_${i}" name="traveler_id_number_${i}" required>
                        </div>
                    </div>
                `;
            container.innerHTML += travelerForm;
        }
    }

    // Initialize the form
    document.addEventListener('DOMContentLoaded', function() {
        updateTravelerForms();
    });
</script>
</body>
</html>
