<?php
// Include necessary files
require_once 'config/database.php';
require_once 'includes/helpers.php';

// Check if GD library is available
if (!extension_loaded('gd')) {
    die("GD library is not available. Please install or enable it.");
}

// Create directories if they don't exist
$directories = [
    'assets/images/placeholders',
    'assets/images/destinations',
    'assets/images/tours',
    'assets/images/hotels'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Get Indian regions and their colors
$regions = [
    'South India' => '#4CAF50', // Green
    'North India' => '#2196F3', // Blue
    'East India' => '#FFC107',  // Yellow
    'West India' => '#FF9800',  // Orange
    'Northeast India' => '#9C27B0', // Purple
    'Islands' => '#00BCD4'      // Cyan
];

// Types of placeholders to create
$types = ['destination', 'tour', 'hotel'];

// Create placeholder images for each region and type
foreach ($regions as $region => $color) {
    $colorCode = str_replace('#', '', $color);
    
    foreach ($types as $type) {
        // Create image
        $width = 800;
        $height = 600;
        $image = imagecreatetruecolor($width, $height);
        
        // Convert hex color to RGB
        $r = hexdec(substr($color, 1, 2));
        $g = hexdec(substr($color, 3, 2));
        $b = hexdec(substr($color, 5, 2));
        
        // Fill background with region color
        $bgColor = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, $bgColor);
        
        // Add some visual elements
        $lightColor = imagecolorallocate($image, min($r + 50, 255), min($g + 50, 255), min($b + 50, 255));
        $darkColor = imagecolorallocate($image, max($r - 50, 0), max($g - 50, 0), max($b - 50, 0));
        
        // Draw pattern
        for ($i = 0; $i < $width; $i += 40) {
            imageline($image, $i, 0, $i, $height, $lightColor);
        }
        
        for ($i = 0; $i < $height; $i += 40) {
            imageline($image, 0, $i, $width, $i, $lightColor);
        }
        
        // Add text
        $textColor = imagecolorallocate($image, 255, 255, 255);
        $text = ucfirst($type) . ": " . $region;
        
        // Center the text
        $fontSize = 5;
        $textBox = imagettfbbox($fontSize, 0, 'arial.ttf', $text);
        
        // If TTF not available, use built-in font
        if (!function_exists('imagettftext')) {
            imagestring($image, 5, $width/2 - 100, $height/2, $text, $textColor);
        } else {
            imagettftext($image, $fontSize, 0, $width/2 - ($textBox[2] - $textBox[0])/2, $height/2, $textColor, 'arial.ttf', $text);
        }
        
        // Save image
        $filename = "assets/images/placeholders/{$type}-{$colorCode}.jpg";
        imagejpeg($image, $filename, 90);
        
        // Free memory
        imagedestroy($image);
        
        echo "Created placeholder: $filename<br>";
    }
}

echo "<p>All placeholder images have been created successfully.</p>";

// Now create a general placeholder
$width = 800;
$height = 600;
$image = imagecreatetruecolor($width, $height);

// Gray background
$bgColor = imagecolorallocate($image, 96, 125, 139); // Material Design Blue Gray
imagefill($image, 0, 0, $bgColor);

// Add pattern
$lightColor = imagecolorallocate($image, 144, 164, 174);
$darkColor = imagecolorallocate($image, 69, 90, 100);

for ($i = 0; $i < $width; $i += 40) {
    imageline($image, $i, 0, $i, $height, $lightColor);
}

for ($i = 0; $i < $height; $i += 40) {
    imageline($image, 0, $i, $width, $i, $lightColor);
}

// Add text
$textColor = imagecolorallocate($image, 255, 255, 255);
$text = "TourGrid Placeholder";

// Center the text
if (!function_exists('imagettftext')) {
    imagestring($image, 5, $width/2 - 100, $height/2, $text, $textColor);
} else {
    $fontSize = 5;
    $textBox = imagettfbbox($fontSize, 0, 'arial.ttf', $text);
    imagettftext($image, $fontSize, 0, $width/2 - ($textBox[2] - $textBox[0])/2, $height/2, $textColor, 'arial.ttf', $text);
}

// Save image
$filename = "assets/images/placeholder.jpg";
imagejpeg($image, $filename, 90);
imagedestroy($image);

echo "Created general placeholder: $filename<br>";
echo "<p>All placeholder images have been created successfully.</p>";
?>
