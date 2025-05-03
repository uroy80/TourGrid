<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navigation Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container py-5">
        <h1>Navigation Test</h1>
        
        <div class="card mb-4">
            <div class="card-header">Basic Links</div>
            <div class="card-body">
                <p>Click these links to test basic navigation:</p>
                <ul>
                    <li><a href="index.php">Home Page</a></li>
                    <li><a href="tours.php">Tours Page</a></li>
                    <li><a href="login.php">Login Page</a></li>
                </ul>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">User Directory Links</div>
            <div class="card-body">
                <p>Click these links to test navigation to the user directory:</p>
                <ul>
                    <li><a href="user/dashboard.php">User Dashboard</a></li>
                    <li><a href="user/profile.php">User Profile</a></li>
                    <li><a href="user/bookings.php">User Bookings</a></li>
                </ul>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">Test Booking Links</div>
            <div class="card-body">
                <p>Click these links to test direct booking navigation:</p>
                <ul>
                    <li><a href="user/booking.php?tour_id=17&schedule_id=1">Test Booking Link 1</a></li>
                    <li><a href="user/booking.php?tour_id=17&schedule_id=2">Test Booking Link 2</a></li>
                    <li><a href="/Tourgrid/user/booking.php?tour_id=17&schedule_id=3">Test Booking Link 3 (Absolute Path)</a></li>
                </ul>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">Form Test</div>
            <div class="card-body">
                <p>Test form submission:</p>
                <form action="user/booking.php" method="get">
                    <input type="hidden" name="tour_id" value="17">
                    <input type="hidden" name="schedule_id" value="1">
                    <button type="submit" class="btn btn-primary">Submit Form</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">Server Information</div>
            <div class="card-body">
                <p>Current URL: <?php echo $_SERVER['REQUEST_URI']; ?></p>
                <p>Document Root: <?php echo $_SERVER['DOCUMENT_ROOT']; ?></p>
                <p>Script Filename: <?php echo $_SERVER['SCRIPT_FILENAME']; ?></p>
                <p>PHP Self: <?php echo $_SERVER['PHP_SELF']; ?></p>
            </div>
        </div>
    </div>
</body>
</html>
