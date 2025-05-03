<?php
echo "<h1>File System Debug</h1>";

// List all files in the user directory
echo "<h2>Files in user directory:</h2>";
$files = scandir(__DIR__);
echo "<ul>";
foreach ($files as $file) {
    if ($file != "." && $file != "..") {
        echo "<li>" . $file . " - " . (is_file(__DIR__ . "/" . $file) ? "File" : "Directory") . " - " . (is_readable(__DIR__ . "/" . $file) ? "Readable" : "Not Readable") . " - " . (is_writable(__DIR__ . "/" . $file) ? "Writable" : "Not Writable") . "</li>";
    }
}
echo "</ul>";

// Check if specific files exist
$critical_files = [
    "booking.php",
    "booking-process.php",
    "payment.php",
    "booking-details.php",
    "booking-cancel.php",
    "booking-voucher.php"
];

echo "<h2>Critical Files Check:</h2>";
echo "<ul>";
foreach ($critical_files as $file) {
    $file_path = __DIR__ . "/" . $file;
    echo "<li>" . $file . " - " . (file_exists($file_path) ? "Exists" : "Missing") . "</li>";
    if (file_exists($file_path)) {
        echo "<ul>";
        echo "<li>Size: " . filesize($file_path) . " bytes</li>";
        echo "<li>Permissions: " . substr(sprintf('%o', fileperms($file_path)), -4) . "</li>";
        echo "<li>Last modified: " . date("Y-m-d H:i:s", filemtime($file_path)) . "</li>";
        echo "</ul>";
    }
}
echo "</ul>";

// Check PHP configuration
echo "<h2>PHP Configuration:</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "</li>";
echo "<li>Current Script: " . $_SERVER['PHP_SELF'] . "</li>";
echo "</ul>";

// Check URL structure
echo "<h2>URL Structure:</h2>";
echo "<ul>";
echo "<li>HTTP Host: " . $_SERVER['HTTP_HOST'] . "</li>";
echo "<li>Request URI: " . $_SERVER['REQUEST_URI'] . "</li>";
echo "<li>Script Name: " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "</ul>";

// Generate test links
echo "<h2>Test Links:</h2>";
echo "<ul>";
echo "<li><a href='booking.php?tour_id=1&schedule_id=1'>Test Booking Page</a></li>";
echo "<li><a href='test-payment.php?tour_id=1&schedule_id=1'>Test Payment Page</a></li>";
echo "<li><a href='payment.php?booking_id=1'>Direct Payment Page</a></li>";
echo "</ul>";
?>
