<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Booking Details Debug</h1>";

// Check session
echo "<h2>Session Check</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>User is logged in with ID: " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p>User is not logged in</p>";
}

// Check GET parameters
echo "<h2>GET Parameters</h2>";
if (isset($_GET['id']) && !empty($_GET['id'])) {
    echo "<p>Booking ID: " . $_GET['id'] . "</p>";
} else {
    echo "<p>No booking ID provided</p>";
}

// Check file existence
echo "<h2>File Checks</h2>";
$helpers_path = '../includes/helpers.php';
if (file_exists($helpers_path)) {
    echo "<p>helpers.php exists at: " . $helpers_path . "</p>";
    include_once $helpers_path;
    echo "<p>helpers.php included successfully</p>";
} else {
    echo "<p>helpers.php does not exist at: " . $helpers_path . "</p>";
}

// Check database connection
echo "<h2>Database Connection</h2>";
try {
    require_once '../config/database.php';
    $db = connectDatabase();
    echo "<p>Database connection successful</p>";

    // If booking ID is provided, check if it exists
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $booking_id = $_GET['id'];
        $user_id = $_SESSION['user_id'] ?? 0;

        // Check the structure of the tour_schedules table
        echo "<h3>Tour Schedules Table Structure:</h3>";
        $stmt = $db->prepare("DESCRIBE tour_schedules");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>";
        print_r($columns);
        echo "</pre>";

        // Get booking without the problematic column
        $stmt = $db->prepare("
            SELECT b.*, t.title as tour_title
            FROM bookings b
            JOIN tour_schedules ts ON b.schedule_id = ts.schedule_id
            JOIN tours t ON ts.tour_id = t.tour_id
            WHERE b.booking_id = :booking_id
        ");
        $stmt->bindParam(':booking_id', $booking_id);
        $stmt->execute();
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($booking) {
            echo "<p>Booking found: " . htmlspecialchars($booking['tour_title']) . "</p>";

            if ($booking['user_id'] == $user_id) {
                echo "<p>Booking belongs to current user</p>";
            } else {
                echo "<p>Warning: Booking does not belong to current user</p>";
            }

            echo "<h3>Booking Data:</h3>";
            echo "<pre>";
            print_r($booking);
            echo "</pre>";

            // Check for travelers
            $stmt = $db->prepare("SELECT * FROM booking_travelers WHERE booking_id = :booking_id");
            $stmt->bindParam(':booking_id', $booking_id);
            $stmt->execute();
            $travelers = $stmt->fetchAll(PDO::FETCH_ASSOC);

            echo "<h3>Travelers Data:</h3>";
            if (count($travelers) > 0) {
                echo "<pre>";
                print_r($travelers);
                echo "</pre>";
            } else {
                echo "<p>No travelers found for this booking</p>";
            }
        } else {
            echo "<p>Booking not found with ID: " . $booking_id . "</p>";
        }
    }
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

// Check template files
$header_path = 'includes/header.php';
if (file_exists($header_path)) {
    echo "<p>header.php exists at: " . $header_path . "</p>";
} else {
    echo "<p>header.php does not exist at: " . $header_path . "</p>";
}

$footer_path = 'includes/footer.php';
if (file_exists($footer_path)) {
    echo "<p>footer.php exists at: " . $footer_path . "</p>";
} else {
    echo "<p>footer.php does not exist at: " . $footer_path . "</p>";
}

echo "<p>End of debug information</p>";
?>
