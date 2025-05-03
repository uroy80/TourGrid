<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>Form Submission Debug</h1>";

echo "<h2>GET Parameters</h2>";
echo "<pre>";
print_r($_GET);
echo "</pre>";

echo "<h2>POST Parameters</h2>";
echo "<pre>";
print_r($_POST);
echo "</pre>";

echo "<h2>Server Variables</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>Test Form</h2>";
?>

<form action="/Tourgrid/user/booking.php" method="get">
    <input type="hidden" name="tour_id" value="17">
    <select name="schedule_id">
        <option value="1">Schedule 1</option>
        <option value="2">Schedule 2</option>
    </select>
    <button type="submit">Test Submit</button>
</form>

<p>
    <a href="/Tourgrid/user/booking.php?tour_id=17&schedule_id=1">Direct Link Test</a>
</p>
