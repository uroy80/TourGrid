<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: tours.php");
    exit;
}

// Check if tour ID and schedule ID are provided
if (!isset($_POST['tour_id']) || empty($_POST['tour_id']) || !isset($_POST['schedule_id']) || empty($_POST['schedule_id'])) {
    $_SESSION['error'] = "Missing tour or schedule information.";
    header("Location: tours.php");
    exit;
}

$tour_id = $_POST['tour_id'];
$schedule_id = $_POST['schedule_id'];

// Store the values in session for backup
$_SESSION['booking_tour_id'] = $tour_id;
$_SESSION['booking_schedule_id'] = $schedule_id;

// Redirect to the new booking.php file in the root directory
header("Location: booking.php?tour_id=$tour_id&schedule_id=$schedule_id");
exit;
?>
