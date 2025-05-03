<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Start session
Session::start();

// Check if user is logged in and is admin
if (!Session::isLoggedIn() || !Session::isAdmin()) {
   header("Location: ../login.php");
   exit;
}

// Database connection
$database = new Database();
$db = $database->getConnection();

$success_msg = $error_msg = "";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['generate_placeholders'])) {
    // Define the directories where we'll save the placeholder images
    $dirs = [
        '../assets/images/uploads/destinations/',
        '../assets/images/uploads/tours/',
        '../assets/images/uploads/hotels/'
    ];
    
    // Create directories if they don't exist
    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
    
    // Define colors for different regions
    $colors = [
        'South India' => ['bg' => '#4CAF50', 'text' => '#FFFFFF'], // Green
        'North India' => ['bg' => '#2196F3', 'text' => '#FFFFFF'], // Blue
        'East India' => ['bg' => '#FFC107', 'text' => '#000000'],  // Yellow
        'West India' => ['bg' => '#FF5722', 'text' => '#FFFFFF'],  // Orange
        'Northeast India' => ['bg' => '#9C27B0', 'text' => '#FFFFFF'], // Purple
        'Islands' => ['bg' => '#00BCD4', 'text' => '#000000']      // Cyan
    ];
    
    // Define Indian regions and destinations
    $regions = [
        'South India' => [
            'Kerala' => ['Munnar', 'Wayanad', 'Alleppey', 'Cochin', 'Kovalam', 'Trivandrum', 'Kumily', 'Gavi'],
            'Tamil Nadu' => ['Ooty', 'Kodaikanal', 'Yercaud', 'Coonoor', 'Chennai', 'Mahabalipuram', 'Pondicherry'],
            'Karnataka' => ['Coorg', 'Chikmagalur', 'Sakleshpur', 'Bangalore', 'Mysore', 'Hampi', 'Gokarna'],
            'Andhra Pradesh' => ['Tirupati', 'Visakhapatnam', 'Warangal'],
            'Telangana' => ['Hyderabad'],
            'Andaman and Nicobar Islands' => ['Port Blair', 'Havelock Island']
        ],
        'North India' => [
            'Jammu and Kashmir' => ['Srinagar', 'Gulmarg', 'Sonmarg', 'Leh'],
            'Himachal Pradesh' => ['Shimla', 'Manali', 'Dharamshala', 'Kullu'],
            'Uttarakhand' => ['Nainital', 'Mussoorie', 'Rishikesh', 'Haridwar'],
            'Rajasthan' => ['Jaipur', 'Udaipur', 'Jodhpur', 'Jaisalmer'],
            'Delhi' => ['Delhi'],
            'Punjab' => ['Amritsar'],
            'Uttar Pradesh' => ['Agra', 'Varanasi']
        ],
        'East India' => [
            'West Bengal' => ['Darjeeling', 'Kolkata'],
            'Odisha' => ['Puri', 'Bhubaneswar'],
            'Bihar' => ['Patna', 'Gaya'],
            'Assam' => ['Kaziranga', 'Guwahati'],
            'Meghalaya' => ['Shillong']
        ],
        'West India' => [
            'Maharashtra' => ['Mumbai', 'Pune'],
            'Goa' => ['North Goa', 'South Goa'],
            'Gujarat' => ['Ahmedabad', 'Vadodara', 'Diu'],
            'Madhya Pradesh' => ['Khajuraho', 'Bhopal']
        ],
        'Northeast India' => [
            'Sikkim' => ['Gangtok'],
            'Arunachal Pradesh' => ['Tawang'],
            'Nagaland' => ['Kohima'],
            'Mizoram' => ['Aizawl'],
            'Tripura' => ['Agartala'],
            'Manipur' => ['Imphal']
        ],
        'Islands' => [
            'Lakshadweep' => ['Agatti Island']
        ]
    ];
    
    $generated_count = 0;
    
    // Generate placeholder images for each destination
    foreach ($regions as $region => $states) {
        $color = $colors[$region];
        
        foreach ($states as $state => $cities) {
            // Generate state placeholder
            $filename = strtolower(str_replace(' ', '-', $state)) . '.jpg';
            $filepath = $dirs[0] . $filename;
            
            if (!file_exists($filepath)) {
                generatePlaceholder($filepath, $state, $region, $color['bg'], $color['text']);
                $generated_count++;
            }
            
            // Generate city placeholders
            foreach ($cities as $city) {
                $filename = strtolower(str_replace(' ', '-', $city)) . '.jpg';
                $filepath = $dirs[0] . $filename;
                
                if (!file_exists($filepath)) {
                    generatePlaceholder($filepath, $city, $state, $color['bg'], $color['text']);
                    $generated_count++;
                }
            }
        }
    }
    
    // Generate tour placeholders
    $tour_types = [
        'Adventure', 'Cultural', 'Heritage', 'Beach', 'Wildlife', 'Hill Station', 
        'Pilgrimage', 'Honeymoon', 'Family', 'Backpacking'
    ];
    
    foreach ($regions as $region => $states) {
        $color = $colors[$region];
        
        foreach ($states as $state => $cities) {
            foreach ($tour_types as $type) {
                $tour_name = $state . ' ' . $type . ' Tour';
                $filename = strtolower(str_replace(' ', '-', $tour_name)) . '.jpg';
                $filepath = $dirs[1] . $filename;
                
                if (!file_exists($filepath)) {
                    generatePlaceholder($filepath, $tour_name, $region, $color['bg'], $color['text']);
                    $generated_count++;
                }
            }
        }
    }
    
    // Generate hotel placeholders
    $hotel_categories = ['Luxury', 'Budget', 'Resort', 'Homestay', 'Heritage'];
    
    foreach ($regions as $region => $states) {
        $color = $colors[$region];
        
        foreach ($states as $state => $cities) {
            foreach ($cities as $city) {
                foreach ($hotel_categories as $category) {
                    $hotel_name = $city . ' ' . $category . ' Hotel';
                    $filename = strtolower(str_replace(' ', '-', $hotel_name)) . '.jpg';
                    $filepath = $dirs[2] . $filename;
                    
                    if (!file_exists($filepath)) {
                        generatePlaceholder($filepath, $hotel_name, $state, $color['bg'], $color['text']);
                        $generated_count++;
                    }
                }
            }
        }
    }
    
    if ($generated_count > 0) {
        $success_msg = "Successfully generated $generated_count placeholder images.";
    } else {
        $error_msg = "No new placeholder images were generated.";
    }
}

// Function to generate a placeholder image
function generatePlaceholder($filepath, $title, $subtitle, $bg_color, $text_color) {
    // Create a 800x600 image
    $img = imagecreatetruecolor(800, 600);
    
    // Convert hex colors to RGB
    $bg_rgb = hex2rgb($bg_color);
    $text_rgb = hex2rgb($text_color);
    
    // Allocate colors
    $bg = imagecolorallocate($img, $bg_rgb['r'], $bg_rgb['g'], $bg_rgb['b']);
    $text = imagecolorallocate($img, $text_rgb['r'], $text_rgb['g'], $text_rgb['b']);
    
    // Fill the background
    imagefill($img, 0, 0, $bg);
    
    // Add some design elements
    for ($i = 0; $i < 10; $i++) {
        $shade = imagecolorallocatealpha($img, $bg_rgb['r'], $bg_rgb['g'], $bg_rgb['b'], 100 - ($i * 10));
        imagefilledrectangle($img, $i * 80, 0, ($i + 1) * 80, 600, $shade);
    }
    
    // Add the title
    $font = 5; // Built-in font
    $title_width = imagefontwidth($font) * strlen($title);
    $title_x = (800 - $title_width) / 2;
    imagestring($img, $font, $title_x, 250, $title, $text);
    
    // Add the subtitle
    $subtitle_width = imagefontwidth($font - 1) * strlen($subtitle);
    $subtitle_x = (800 - $subtitle_width) / 2;
    imagestring($img, $font - 1, $subtitle_x, 300, $subtitle, $text);
    
    // Add "Placeholder Image" text
    $placeholder_text = "Placeholder Image";
    $placeholder_width = imagefontwidth($font - 2) * strlen($placeholder_text);
    $placeholder_x = (800 - $placeholder_width) / 2;
    imagestring($img, $font - 2, $placeholder_x, 350, $placeholder_text, $text);
    
    // Save the image
    imagejpeg($img, $filepath, 90);
    
    // Free memory
    imagedestroy($img);
}

// Function to convert hex color to RGB
function hex2rgb($hex) {
    $hex = str_replace('#', '', $hex);
    
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
        $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
        $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
    } else {
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));
    }
    
    return ['r' => $r, 'g' => $g, 'b' => $b];
}

// Include admin header
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>
        
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Generate Destination Placeholders</h1>
            </div>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Generate Placeholder Images for Indian Destinations</h6>
                </div>
                <div class="card-body">
                    <p>This tool will generate placeholder images for all Indian destinations, tours, and hotels. Each image will be color-coded by region and include the name of the destination.</p>
                    <p>The images will be saved in the following directories:</p>
                    <ul>
                        <li><code>assets/images/uploads/destinations/</code></li>
                        <li><code>assets/images/uploads/tours/</code></li>
                        <li><code>assets/images/uploads/hotels/</code></li>
                    </ul>
                    <p>Note: This will only generate images that don't already exist. Existing images will not be overwritten.</p>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <button type="submit" name="generate_placeholders" class="btn btn-primary">Generate Placeholder Images</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include admin footer
include_once 'includes/footer.php';
?>
