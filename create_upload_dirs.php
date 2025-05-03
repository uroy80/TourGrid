<?php
// Script to create all necessary upload directories with proper permissions
// This can be run directly from the browser or command line

// Define the base path
$base_path = dirname(__FILE__);

// Define all required upload directories
$directories = [
    'assets/images/uploads',
    'assets/images/uploads/tours',
    'assets/images/uploads/hotels',
    'assets/images/uploads/destinations',
    'assets/images/uploads/users',
    'uploads',
    'uploads/tours',
    'uploads/hotels',
    'uploads/destinations',
    'uploads/users'
];

// Function to create directory and set permissions
function createDirWithPermissions($path, $permissions = 0755) {
    if (!file_exists($path)) {
        $result = mkdir($path, $permissions, true);
        if ($result) {
            echo "Created directory: $path<br>";
            // On Unix/Linux systems, try to set group write permissions
            if (function_exists('posix_getgid')) {
                $group = posix_getgid();
                chgrp($path, $group);
                // Add group write permission
                chmod($path, $permissions | 0020);
                echo "Set group write permissions for: $path<br>";
            }
        } else {
            echo "Failed to create directory: $path<br>";
        }
        return $result;
    } else {
        echo "Directory already exists: $path<br>";
        // Check if it's writable
        if (!is_writable($path)) {
            echo "Warning: Directory is not writable: $path<br>";
            // Try to make it writable
            chmod($path, $permissions | 0020);
            echo "Attempted to make directory writable: $path<br>";
        }
        return true;
    }
}

// Create all directories
echo "<h1>Creating Upload Directories</h1>";
echo "<pre>";

foreach ($directories as $dir) {
    $full_path = $base_path . '/' . $dir;
    createDirWithPermissions($full_path);
}

echo "</pre>";
echo "<h2>Directory Creation Complete</h2>";

// Display current permissions
echo "<h2>Current Directory Permissions</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Directory</th><th>Exists</th><th>Writable</th><th>Permissions</th></tr>";

foreach ($directories as $dir) {
    $full_path = $base_path . '/' . $dir;
    $exists = file_exists($full_path);
    $writable = is_writable($full_path);
    $perms = $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A';

    echo "<tr>";
    echo "<td>$dir</td>";
    echo "<td>" . ($exists ? 'Yes' : 'No') . "</td>";
    echo "<td>" . ($writable ? 'Yes' : 'No') . "</td>";
    echo "<td>$perms</td>";
    echo "</tr>";
}

echo "</table>";

// Add a link to go back to admin dashboard
echo "<p><a href='admin/dashboard.php'>Return to Admin Dashboard</a></p>";
?>
