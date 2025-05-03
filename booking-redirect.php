<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get parameters
$tour_id = isset($_GET['tour_id']) ? $_GET['tour_id'] : '';
$schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : '';

// Check if parameters are provided
if (empty($tour_id) || empty($schedule_id)) {
    echo "<div style='padding: 20px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>Error: Missing Parameters</h3>";
    echo "<p>Both tour_id and schedule_id are required.</p>";
    echo "<p><a href='tours.php'>Go back to tours</a></p>";
    echo "</div>";
    exit;
}

// Display information
echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Booking Redirect</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <style>
        body { padding: 20px; }
        .card { margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='row'>
            <div class='col-md-8 mx-auto'>
                <div class='card'>
                    <div class='card-header bg-primary text-white'>
                        <h3>Booking Redirect</h3>
                    </div>
                    <div class='card-body'>
                        <p>This page will redirect you to the booking form in 5 seconds.</p>
                        <p><strong>Tour ID:</strong> {$tour_id}</p>
                        <p><strong>Schedule ID:</strong> {$schedule_id}</p>
                        
                        <div class='alert alert-info'>
                            <p>If you are not redirected automatically, please click one of the links below:</p>
                            <div class='d-grid gap-2'>
                                <a href='booking.php?tour_id={$tour_id}&schedule_id={$schedule_id}' class='btn btn-primary'>
                                    Go to Booking Form
                                </a>
                                <a href='direct-booking-process.php?tour_id={$tour_id}&schedule_id={$schedule_id}' class='btn btn-secondary'>
                                    Use Direct Booking Process
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Redirect after 5 seconds
        setTimeout(function() {
            window.location.href = 'booking.php?tour_id={$tour_id}&schedule_id={$schedule_id}';
        }, 5000);
    </script>
</body>
</html>";
?>
