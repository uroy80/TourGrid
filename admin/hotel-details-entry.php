<?php
require_once '../config/database.php';
require_once '../config/session.php';
require_once '../includes/helpers.php';

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

// Get destinations hierarchy for the dropdown
$destinations_hierarchy = getDestinationsHierarchy();

// Initialize variables
$name = $description = $address = $price_per_night = $amenities = $image = "";
$destination_id = 0;
$star_rating = 3;
$name_err = $description_err = $address_err = $price_err = $star_rating_err = $destination_err = "";

// Get current admin ID
$admin_id = Session::get('user_id');

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty(trim($_POST["name"]))) {
        $name_err = "Please enter a hotel name.";
    } else {
        $name = trim($_POST["name"]);
    }
    
    // Validate description
    if (empty(trim($_POST["description"]))) {
        $description_err = "Please enter a description.";
    } else {
        $description = trim($_POST["description"]);
    }
    
    // Validate address
    if (empty(trim($_POST["address"]))) {
        $address_err = "Please enter an address.";
    } else {
        $address = trim($_POST["address"]);
    }
    
    // Validate price
    if (empty(trim($_POST["price_per_night"]))) {
        $price_err = "Please enter a price per night.";
    } elseif (!is_numeric($_POST["price_per_night"]) || floatval($_POST["price_per_night"]) <= 0) {
        $price_err = "Please enter a valid price.";
    } else {
        $price_per_night = trim($_POST["price_per_night"]);
    }
    
    // Validate star rating
    if (empty($_POST["star_rating"])) {
        $star_rating_err = "Please select a star rating.";
    } elseif (!is_numeric($_POST["star_rating"]) || intval($_POST["star_rating"]) < 1 || intval($_POST["star_rating"]) > 5) {
        $star_rating_err = "Please select a valid star rating (1-5).";
    } else {
        $star_rating = $_POST["star_rating"];
    }
    
    // Validate destination
    if (empty($_POST["destination_id"])) {
        $destination_err = "Please select a destination.";
    } else {
        $destination_id = $_POST["destination_id"];
    }
    
    // Get amenities
    $amenities = !empty($_POST["amenities"]) ? trim($_POST["amenities"]) : "";
    
    // Handle image upload
    $image = "default-hotel.jpg"; // Default image
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png"];
        $filename = $_FILES["image"]["name"];
        $filetype = $_FILES["image"]["type"];
        $filesize = $_FILES["image"]["size"];
        
        // Verify file extension
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        if (!array_key_exists($ext, $allowed)) {
            $image_err = "Please select a valid file format (JPG, JPEG, PNG).";
        }
        
        // Verify file size - 5MB maximum
        $maxsize = 5 * 1024 * 1024;
        if ($filesize > $maxsize) {
            $image_err = "File size is larger than the allowed limit (5MB).";
        }
        
        // Verify MIME type of the file
        if (in_array($filetype, $allowed)) {
            // Check if file exists before uploading
            $image = uniqid() . "." . $ext;
            $target_dir = "../assets/images/hotels/";
            
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $target_file = $target_dir . $image;
            
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // File uploaded successfully
            } else {
                $image_err = "There was an error uploading your file.";
                $image = "default-hotel.jpg";
            }
        } else {
            $image_err = "There was a problem with the uploaded file.";
            $image = "default-hotel.jpg";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($name_err) && empty($description_err) && empty($address_err) && empty($price_err) && empty($star_rating_err) && empty($destination_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO hotels (name, description, address, destination_id, star_rating, price_per_night, amenities, image, admin_id) 
                VALUES (:name, :description, :address, :destination_id, :star_rating, :price_per_night, :amenities, :image, :admin_id)";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":name", $param_name, PDO::PARAM_STR);
            $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
            $stmt->bindParam(":address", $param_address, PDO::PARAM_STR);
            $stmt->bindParam(":destination_id", $param_destination_id, PDO::PARAM_INT);
            $stmt->bindParam(":star_rating", $param_star_rating, PDO::PARAM_INT);
            $stmt->bindParam(":price_per_night", $param_price_per_night);
            $stmt->bindParam(":amenities", $param_amenities, PDO::PARAM_STR);
            $stmt->bindParam(":image", $param_image, PDO::PARAM_STR);
            $stmt->bindParam(":admin_id", $param_admin_id, PDO::PARAM_INT);
            
            // Set parameters
            $param_name = $name;
            $param_description = $description;
            $param_address = $address;
            $param_destination_id = $destination_id;
            $param_star_rating = $star_rating;
            $param_price_per_night = $price_per_night;
            $param_amenities = $amenities;
            $param_image = $image;
            $param_admin_id = $admin_id;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to hotel details view page
                header("location: hotel-details-view.php");
                exit;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            unset($stmt);
        }
    }
    
    // Close connection
    unset($db);
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
                <h1 class="h2">Hotel Details Entry</h1>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Hotel</h6>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Hotel Name</label>
                                <input type="text" name="name" id="name" class="form-control <?php echo (!empty($name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $name; ?>" required>
                                <div class="invalid-feedback"><?php echo $name_err; ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="destination_id" class="form-label">Destination</label>
                                <select name="destination_id" id="destination_id" class="form-select <?php echo (!empty($destination_err)) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">Select Destination</option>
                                    
                                    <?php foreach ($destinations_hierarchy as $region_id => $region): ?>
                                        <optgroup label="<?php echo $region['info']['name']; ?>">
                                            <?php foreach ($region['states'] as $state_id => $state): ?>
                                                <option value="<?php echo $state_id; ?>" <?php echo ($destination_id == $state_id) ? 'selected' : ''; ?>>
                                                    <?php echo $state['info']['name']; ?>
                                                </option>
                                                
                                                <?php if (!empty($state['cities'])): ?>
                                                    <?php foreach ($state['cities'] as $city): ?>
                                                        <option value="<?php echo $city['destination_id']; ?>" <?php echo ($destination_id == $city['destination_id']) ? 'selected' : ''; ?>>
                                                            &nbsp;&nbsp;&nbsp;<?php echo $city['name']; ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $destination_err; ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="4" required><?php echo $description; ?></textarea>
                            <div class="invalid-feedback"><?php echo $description_err; ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="address" class="form-label">Address</label>
                            <textarea name="address" id="address" class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" rows="2" required><?php echo $address; ?></textarea>
                            <div class="invalid-feedback"><?php echo $address_err; ?></div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="price_per_night" class="form-label">Price Per Night (₹)</label>
                                <input type="number" name="price_per_night" id="price_per_night" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $price_per_night; ?>" step="0.01" min="0" required>
                                <div class="invalid-feedback"><?php echo $price_err; ?></div>
                            </div>
                            <div class="col-md-6">
                                <label for="star_rating" class="form-label">Star Rating</label>
                                <select name="star_rating" id="star_rating" class="form-select <?php echo (!empty($star_rating_err)) ? 'is-invalid' : ''; ?>" required>
                                    <option value="1" <?php echo ($star_rating == 1) ? 'selected' : ''; ?>>1 Star</option>
                                    <option value="2" <?php echo ($star_rating == 2) ? 'selected' : ''; ?>>2 Stars</option>
                                    <option value="3" <?php echo ($star_rating == 3) ? 'selected' : ''; ?>>3 Stars</option>
                                    <option value="4" <?php echo ($star_rating == 4) ? 'selected' : ''; ?>>4 Stars</option>
                                    <option value="5" <?php echo ($star_rating == 5) ? 'selected' : ''; ?>>5 Stars</option>
                                </select>
                                <div class="invalid-feedback"><?php echo $star_rating_err; ?></div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="amenities" class="form-label">Amenities</label>
                            <textarea name="amenities" id="amenities" class="form-control" rows="3"><?php echo $amenities; ?></textarea>
                            <small class="text-muted">Enter amenities separated by commas (e.g., Swimming pool, Free Wi-Fi, Restaurant)</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="image" class="form-label">Hotel Image</label>
                            <input type="file" name="image" id="image" class="form-control image-upload" data-preview="image-preview">
                            <small class="text-muted">Recommended size: 800x600 pixels, Max size: 5MB</small>
                            <div class="mt-2">
                                <img id="image-preview" src="#" alt="Hotel Image Preview" style="max-width: 200px; max-height: 150px; display: none;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Save Hotel</button>
                            <a href="hotel-details-view.php" class="btn btn-secondary">Cancel</a>
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
