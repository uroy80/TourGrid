<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/helpers.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../tours.php");
    exit;
}

// Get form data
$tour_id = isset($_POST['tour_id']) ? intval($_POST['tour_id']) : 0;
$schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
$num_people = isset($_POST['num_people']) ? intval($_POST['num_people']) : 0;
$special_requests = isset($_POST['special_requests']) ? trim($_POST['special_requests']) : '';
$travelers = isset($_POST['traveler']) ? $_POST['traveler'] : [];

// Validate required fields
if (!$tour_id || !$schedule_id || !$num_people || empty($travelers)) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: booking.php?tour_id=$tour_id&schedule_id=$schedule_id");
    exit;
}

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Get tour and schedule details
    $query = "SELECT t.*, ts.price, ts.max_capacity, ts.start_date, ts.end_date
             FROM tours t 
             JOIN tour_schedules ts ON t.tour_id = ts.tour_id 
             WHERE t.tour_id = :tour_id AND ts.schedule_id = :schedule_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":tour_id", $tour_id);
    $stmt->bindParam(":schedule_id", $schedule_id);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        $_SESSION['error'] = "Invalid tour or schedule.";
        header("Location: ../tours.php");
        exit;
    }
    
    $tour_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check available seats
    $bookings_query = "SELECT SUM(num_people) as booked_seats FROM bookings 
                      WHERE schedule_id = :schedule_id";
    $bookings_stmt = $db->prepare($bookings_query);
    $bookings_stmt->bindParam(":schedule_id", $schedule_id);
    $bookings_stmt->execute();
    $bookings_result = $bookings_stmt->fetch(PDO::FETCH_ASSOC);
    
    $booked_seats = $bookings_result['booked_seats'] ? $bookings_result['booked_seats'] : 0;
    $available_seats = $tour_data['max_capacity'] - $booked_seats;
    
    if ($available_seats < $num_people) {
        $_SESSION['error'] = "Not enough seats available. Only $available_seats seats left.";
        header("Location: booking.php?tour_id=$tour_id&schedule_id=$schedule_id");
        exit;
    }
    
    // Calculate pricing
    $base_amount = $tour_data['price'] * $num_people;
    $tax_amount = $base_amount * 0.05; // 5% tax
    $total_amount = $base_amount + $tax_amount;
    
    // Generate booking reference
    $booking_reference = 'TG' . date('Ymd') . rand(1000, 9999);
    
    // Begin transaction
    $db->beginTransaction();
    
    // Insert booking record
    $booking_query = "INSERT INTO bookings (user_id, tour_id, schedule_id, booking_reference, 
                    num_people, base_amount, tax_amount, total_amount, special_requests) 
                    VALUES (:user_id, :tour_id, :schedule_id, :booking_reference, 
                    :num_people, :base_amount, :tax_amount, :total_amount, :special_requests)";
    
    $booking_stmt = $db->prepare($booking_query);
    $user_id = $_SESSION['user_id'];
    
    $booking_stmt->bindParam(":user_id", $user_id);
    $booking_stmt->bindParam(":tour_id", $tour_id);
    $booking_stmt->bindParam(":schedule_id", $schedule_id);
    $booking_stmt->bindParam(":booking_reference", $booking_reference);
    $booking_stmt->bindParam(":num_people", $num_people);
    $booking_stmt->bindParam(":base_amount", $base_amount);
    $booking_stmt->bindParam(":tax_amount", $tax_amount);
    $booking_stmt->bindParam(":total_amount", $total_amount);
    $booking_stmt->bindParam(":special_requests", $special_requests);
    
    $booking_stmt->execute();
    $booking_id = $db->lastInsertId();
    
    // Insert traveler details
    $traveler_query = "INSERT INTO booking_travelers (booking_id, name, age, gender, id_type, id_number) 
                      VALUES (:booking_id, :name, :age, :gender, :id_type, :id_number)";
    $traveler_stmt = $db->prepare($traveler_query);
    
    foreach ($travelers as $traveler) {
        $traveler_stmt->bindParam(":booking_id", $booking_id);
        $traveler_stmt->bindParam(":name", $traveler['name']);
        $traveler_stmt->bindParam(":age", $traveler['age']);
        $traveler_stmt->bindParam(":gender", $traveler['gender']);
        $traveler_stmt->bindParam(":id_type", $traveler['id_type']);
        $traveler_stmt->bindParam(":id_number", $traveler['id_number']);
        $traveler_stmt->execute();
    }
    
    // Commit transaction
    $db->commit();
    
    // Store booking data in session
    $_SESSION['booking_data'] = [
        'booking_id' => $booking_id,
        'booking_reference' => $booking_reference,
        'tour_name' => $tour_data['title'],
        'departure_date' => $tour_data['start_date'],
        'return_date' => $tour_data['end_date'],
        'num_people' => $num_people,
        'base_amount' => $base_amount,
        'tax_amount' => $tax_amount,
        'total_amount' => $total_amount
    ];
    
    // Redirect to payment page
    header("Location: payment.php?booking_id=$booking_id");
    exit;
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    // Log the error
    error_log("Booking process error: " . $e->getMessage());
    
    // Display error message
    $_SESSION['error'] = "An error occurred while processing your booking. Please try again.";
    header("Location: booking.php?tour_id=$tour_id&schedule_id=$schedule_id");
    exit;
}
