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

// Debug information
echo "<div style='background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
echo "<h3>Debug Information</h3>";
echo "<p><strong>POST Data:</strong></p>";
echo "<pre>";
print_r($_POST);
echo "</pre>";
echo "</div>";

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<div class='alert alert-danger'>Invalid request method. Please use the booking form.</div>";
    exit;
}

// Check required fields
$required_fields = ['tour_id', 'schedule_id', 'price', 'num_travelers', 'name', 'email', 'phone', 'terms'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo "<div class='alert alert-danger'>Missing required fields: " . implode(', ', $missing_fields) . "</div>";
    exit;
}

// Get form data
$tour_id = $_POST['tour_id'];
$schedule_id = $_POST['schedule_id'];
$price = $_POST['price'];
$num_people = $_POST['num_travelers']; // Get from form as num_travelers but use as num_people in DB
$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$special_requests = isset($_POST['special_requests']) ? $_POST['special_requests'] : '';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "<div class='alert alert-danger'>Invalid email address.</div>";
    exit;
}

// Get user ID if logged in, otherwise set to null
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Calculate booking amount
$base_amount = $price * $num_people;
$tax_rate = 0.05; // 5% tax
$tax_amount = $base_amount * $tax_rate;
$discount_amount = 0; // No discount by default
$total_amount = $base_amount + $tax_amount - $discount_amount;

// Connect to database
try {
    $database = new Database();
    $conn = $database->getConnection();

    // Start transaction
    $conn->beginTransaction();

    // Insert booking record - using the exact column names from your database
    $booking_sql = "INSERT INTO bookings (user_id, tour_id, schedule_id, booking_date, num_people, 
                base_amount, tax_amount, discount_amount, total_amount, status, special_requests) 
                VALUES (?, ?, ?, NOW(), ?, ?, ?, ?, ?, 'pending', ?)";

    $stmt = $conn->prepare($booking_sql);
    $stmt->bindParam(1, $user_id, PDO::PARAM_INT);
    $stmt->bindParam(2, $tour_id, PDO::PARAM_INT);
    $stmt->bindParam(3, $schedule_id, PDO::PARAM_INT);
    $stmt->bindParam(4, $num_people, PDO::PARAM_INT);
    $stmt->bindParam(5, $base_amount, PDO::PARAM_STR);
    $stmt->bindParam(6, $tax_amount, PDO::PARAM_STR);
    $stmt->bindParam(7, $discount_amount, PDO::PARAM_STR);
    $stmt->bindParam(8, $total_amount, PDO::PARAM_STR);
    $stmt->bindParam(9, $special_requests, PDO::PARAM_STR);
    $stmt->execute();

    $booking_id = $conn->lastInsertId();

    // Insert traveler details - using the exact column names from your database
    $traveler_sql = "INSERT INTO booking_travelers (booking_id, name, age, gender, id_type, id_number) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $traveler_stmt = $conn->prepare($traveler_sql);

    // Add lead traveler
    $age = isset($_POST['age']) ? $_POST['age'] : '30'; // Default age if not provided
    $gender = isset($_POST['gender']) ? $_POST['gender'] : 'male'; // Default gender if not provided
    $id_type = isset($_POST['id_type']) ? $_POST['id_type'] : 'passport'; // Default ID type if not provided
    $id_number = isset($_POST['id_number']) ? $_POST['id_number'] : 'TEMP' . rand(10000, 99999); // Default ID number if not provided

    $traveler_stmt->bindParam(1, $booking_id, PDO::PARAM_INT);
    $traveler_stmt->bindParam(2, $name, PDO::PARAM_STR);
    $traveler_stmt->bindParam(3, $age, PDO::PARAM_STR);
    $traveler_stmt->bindParam(4, $gender, PDO::PARAM_STR);
    $traveler_stmt->bindParam(5, $id_type, PDO::PARAM_STR);
    $traveler_stmt->bindParam(6, $id_number, PDO::PARAM_STR);
    $traveler_stmt->execute();

    // Add additional travelers
    for ($i = 2; $i <= $num_people; $i++) {
        $traveler_name = isset($_POST["traveler_name_$i"]) ? $_POST["traveler_name_$i"] : '';
        $traveler_age = isset($_POST["traveler_age_$i"]) ? $_POST["traveler_age_$i"] : '30';
        $traveler_gender = isset($_POST["traveler_gender_$i"]) ? $_POST["traveler_gender_$i"] : 'male';
        $traveler_id_type = isset($_POST["traveler_id_type_$i"]) ? $_POST["traveler_id_type_$i"] : 'passport';
        $traveler_id_number = isset($_POST["traveler_id_number_$i"]) ? $_POST["traveler_id_number_$i"] : 'TEMP' . rand(10000, 99999);

        if (!empty($traveler_name)) {
            $traveler_stmt->bindParam(1, $booking_id, PDO::PARAM_INT);
            $traveler_stmt->bindParam(2, $traveler_name, PDO::PARAM_STR);
            $traveler_stmt->bindParam(3, $traveler_age, PDO::PARAM_STR);
            $traveler_stmt->bindParam(4, $traveler_gender, PDO::PARAM_STR);
            $traveler_stmt->bindParam(5, $traveler_id_type, PDO::PARAM_STR);
            $traveler_stmt->bindParam(6, $traveler_id_number, PDO::PARAM_STR);
            $traveler_stmt->execute();
        }
    }

    // Update available seats if the column exists
    try {
        $update_seats_sql = "UPDATE tour_schedules SET available_seats = available_seats - ? WHERE schedule_id = ?";
        $update_seats_stmt = $conn->prepare($update_seats_sql);
        $update_seats_stmt->bindParam(1, $num_people, PDO::PARAM_INT);
        $update_seats_stmt->bindParam(2, $schedule_id, PDO::PARAM_INT);
        $update_seats_stmt->execute();
    } catch (PDOException $e) {
        // If the column doesn't exist, just log the error and continue
        error_log("Could not update available_seats: " . $e->getMessage());
    }

    // Commit transaction
    $conn->commit();

    // Store booking ID in session for payment
    $_SESSION['booking_id'] = $booking_id;
    $_SESSION['total_amount'] = $total_amount;

    // Success message
    echo "<div class='alert alert-success'>Booking created successfully! Redirecting to payment page...</div>";

    // Redirect to payment page after 2 seconds
    echo "<script>
        setTimeout(function() {
            window.location.href = 'payment.php?booking_id=" . $booking_id . "';
        }, 2000);
    </script>";

} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    echo "<div class='alert alert-danger'>Error creating booking: " . $e->getMessage() . "</div>";

    // Debug: Show the error details
    echo "<div style='background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px;'>";
    echo "<h3>Error Details</h3>";
    echo "<p>Error Code: " . $e->getCode() . "</p>";
    echo "<p>Error Message: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "</div>";
    exit;
}

// Close database connection
$conn = null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Booking - TourGrid</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h2 class="mb-0">Processing Your Booking</h2>
                </div>
                <div class="card-body text-center">
                    <div class="spinner-border text-primary mb-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h4>Please wait while we process your booking...</h4>
                    <p>You will be redirected to the payment page shortly.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
