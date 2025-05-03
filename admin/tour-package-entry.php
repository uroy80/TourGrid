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
$title = $description = $price = $duration = $max_people = $inclusions = $exclusions = $image = "";
$destination_id = 0;
$is_featured = false;
$title_err = $description_err = $price_err = $duration_err = $max_people_err = $destination_err = $image_err = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Add this code to get the current admin's ID
    $admin_id = Session::get('user_id');
    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }

    // Validate description
    if (empty(trim($_POST["description"]))) {
        $description_err = "Please enter a description.";
    } else {
        $description = trim($_POST["description"]);
    }

    // Validate price
    if (empty(trim($_POST["price"]))) {
        $price_err = "Please enter a price.";
    } elseif (!is_numeric($_POST["price"]) || floatval($_POST["price"]) <= 0) {
        $price_err = "Please enter a valid price.";
    } else {
        $price = trim($_POST["price"]);
    }

    // Validate duration
    if (empty(trim($_POST["duration"]))) {
        $duration_err = "Please enter a duration.";
    } elseif (!is_numeric($_POST["duration"]) || intval($_POST["duration"]) <= 0) {
        $duration_err = "Please enter a valid duration.";
    } else {
        $duration = trim($_POST["duration"]);
    }

    // Validate max people
    if (empty(trim($_POST["max_people"]))) {
        $max_people_err = "Please enter maximum number of people.";
    } elseif (!is_numeric($_POST["max_people"]) || intval($_POST["max_people"]) <= 0) {
        $max_people_err = "Please enter a valid number.";
    } else {
        $max_people = trim($_POST["max_people"]);
    }

    // Validate destination
    if (empty($_POST["destination_id"])) {
        $destination_err = "Please select a destination.";
    } else {
        $destination_id = $_POST["destination_id"];
    }

    // Get other form data
    $inclusions = !empty($_POST["inclusions"]) ? trim($_POST["inclusions"]) : "";
    $exclusions = !empty($_POST["exclusions"]) ? trim($_POST["exclusions"]) : "";
    $is_featured = isset($_POST["is_featured"]) ? 1 : 0;

    // Handle image upload
    $image = "default-tour.jpg"; // Default image
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

            // Get the absolute path to the upload directory
            $target_dir = dirname(dirname(__FILE__)) . "/assets/images/uploads/tours/";

            // Debug the path
            echo "<script>console.log('Upload directory path: " . $target_dir . "');</script>";

            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                $dir_created = mkdir($target_dir, 0755, true);
                if (!$dir_created) {
                    echo "<script>console.error('Failed to create directory: " . $target_dir . "');</script>";
                    $image_err = "Failed to create upload directory. Please contact administrator.";
                    $image = "default-tour.jpg";
                }
            }

            // Check if directory is writable
            if (!is_writable($target_dir)) {
                echo "<script>console.error('Directory not writable: " . $target_dir . "');</script>";
                $image_err = "Upload directory is not writable. Please contact administrator.";
                $image = "default-tour.jpg";
            } else {
                $target_file = $target_dir . $image;

                if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    // File uploaded successfully
                    echo "<script>console.log('File uploaded successfully to: " . $target_file . "');</script>";
                } else {
                    $image_err = "There was an error uploading your file. Error code: " . $_FILES["image"]["error"];
                    $image = "default-tour.jpg";
                    echo "<script>console.error('Upload failed. Error code: " . $_FILES["image"]["error"] . "');</script>";
                }
            }
        } else {
            $image_err = "There was a problem with the uploaded file. Type: " . $filetype;
            $image = "default-tour.jpg";
        }
    } else if (isset($_FILES["image"]) && $_FILES["image"]["error"] != 0) {
        // Log the error code
        $image_err = "File upload error. Error code: " . $_FILES["image"]["error"];
        echo "<script>console.error('File upload error. Error code: " . $_FILES["image"]["error"] . "');</script>";
    }

    // Check input errors before inserting in database
    if (empty($title_err) && empty($description_err) && empty($price_err) && empty($duration_err) && empty($max_people_err) && empty($destination_err) && empty($image_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO tours (title, description, destination_id, price, duration, max_people, inclusions, exclusions, image, is_featured, admin_id) 
        VALUES (:title, :description, :destination_id, :price, :duration, :max_people, :inclusions, :exclusions, :image, :is_featured, :admin_id)";

        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":title", $param_title, PDO::PARAM_STR);
            $stmt->bindParam(":description", $param_description, PDO::PARAM_STR);
            $stmt->bindParam(":destination_id", $param_destination_id, PDO::PARAM_INT);
            $stmt->bindParam(":price", $param_price);
            $stmt->bindParam(":duration", $param_duration, PDO::PARAM_INT);
            $stmt->bindParam(":max_people", $param_max_people, PDO::PARAM_INT);
            $stmt->bindParam(":inclusions", $param_inclusions, PDO::PARAM_STR);
            $stmt->bindParam(":exclusions", $param_exclusions, PDO::PARAM_STR);
            $stmt->bindParam(":image", $param_image, PDO::PARAM_STR);
            $stmt->bindParam(":is_featured", $param_is_featured, PDO::PARAM_BOOL);
            $stmt->bindParam(":admin_id", $param_admin_id, PDO::PARAM_INT);

            // Set parameters
            $param_title = $title;
            $param_description = $description;
            $param_destination_id = $destination_id;
            $param_price = $price;
            $param_duration = $duration;
            $param_max_people = $max_people;
            $param_inclusions = $inclusions;
            $param_exclusions = $exclusions;
            $param_image = $image;
            $param_is_featured = $is_featured;
            $param_admin_id = $admin_id;

            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to tour package view page
                header("location: tour-package-view.php");
                exit;
            } else {
                echo "Oops! Something went wrong. Please try again later.";
                echo "<script>console.error('Database error: " . implode(", ", $stmt->errorInfo()) . "');</script>";
            }

            // Close statement
            unset($stmt);
        }
    } else {
        // Display all errors for debugging
        echo "<script>console.error('Form has errors: ";
        if (!empty($title_err)) echo "Title: $title_err; ";
        if (!empty($description_err)) echo "Description: $description_err; ";
        if (!empty($price_err)) echo "Price: $price_err; ";
        if (!empty($duration_err)) echo "Duration: $duration_err; ";
        if (!empty($max_people_err)) echo "Max People: $max_people_err; ";
        if (!empty($destination_err)) echo "Destination: $destination_err; ";
        if (!empty($image_err)) echo "Image: $image_err; ";
        echo "');</script>";
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
                <h1 class="h2">Tour Package Entry</h1>
            </div>

            <!-- Debug information for file uploads -->
            <?php if (!empty($image_err)): ?>
                <div class="alert alert-danger">
                    <strong>Image Upload Error:</strong> <?php echo $image_err; ?>
                </div>
            <?php endif; ?>

            <!-- PHP Info for debugging -->
            <div class="card shadow mb-4 collapse" id="phpInfoCard">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">PHP Upload Configuration</h6>
                </div>
                <div class="card-body">
                    <p>Max upload file size: <?php echo ini_get('upload_max_filesize'); ?></p>
                    <p>Max post size: <?php echo ini_get('post_max_size'); ?></p>
                    <p>Upload directory writable:
                        <?php
                        $upload_dir = "../assets/images/uploads/tours/";
                        if (!file_exists($upload_dir)) {
                            echo "Directory doesn't exist";
                        } else {
                            echo is_writable($upload_dir) ? "Yes" : "No";
                        }
                        ?>
                    </p>
                </div>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Tour Package</h6>
                    <button class="btn btn-sm btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#phpInfoCard">
                        Debug Info
                    </button>
                </div>
                <div class="card-body">
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="title" class="form-label">Tour Title</label>
                                <input type="text" name="title" id="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $title; ?>" required>
                                <div class="invalid-feedback"><?php echo $title_err; ?></div>
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
                            <textarea name="description" id="description" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>" rows="5" required><?php echo $description; ?></textarea>
                            <div class="invalid-feedback"><?php echo $description_err; ?></div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="price" class="form-label">Price (₹)</label>
                                <input type="number" name="price" id="price" class="form-control <?php echo (!empty($price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $price; ?>" step="0.01" min="0" required>
                                <div class="invalid-feedback"><?php echo $price_err; ?></div>
                            </div>
                            <div class="col-md-4">
                                <label for="duration" class="form-label">Duration (days)</label>
                                <input type="number" name="duration" id="duration" class="form-control <?php echo (!empty($duration_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $duration; ?>" min="1" required>
                                <div class="invalid-feedback"><?php echo $duration_err; ?></div>
                            </div>
                            <div class="col-md-4">
                                <label for="max_people" class="form-label">Max People</label>
                                <input type="number" name="max_people" id="max_people" class="form-control <?php echo (!empty($max_people_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $max_people; ?>" min="1" required>
                                <div class="invalid-feedback"><?php echo $max_people_err; ?></div>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="inclusions" class="form-label">Inclusions</label>
                                <textarea name="inclusions" id="inclusions" class="form-control" rows="3"><?php echo $inclusions; ?></textarea>
                                <small class="text-muted">Enter items separated by commas</small>
                            </div>
                            <div class="col-md-6">
                                <label for="exclusions" class="form-label">Exclusions</label>
                                <textarea name="exclusions" id="exclusions" class="form-control" rows="3"><?php echo $exclusions; ?></textarea>
                                <small class="text-muted">Enter items separated by commas</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="image" class="form-label">Tour Image</label>
                            <input type="file" name="image" id="image" class="form-control image-upload" data-preview="image-preview">
                            <small class="text-muted">Recommended size: 800x600 pixels, Max size: 5MB</small>
                            <div class="mt-2">
                                <img id="image-preview" src="#" alt="Tour Image Preview" style="max-width: 200px; max-height: 150px; display: none;">
                            </div>
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" name="is_featured" id="is_featured" class="form-check-input" <?php echo $is_featured ? 'checked' : ''; ?>>
                            <label for="is_featured" class="form-check-label">Feature this tour on homepage</label>
                        </div>

                        <div class="mb-3">
                            <button type="submit" class="btn btn-primary">Save Tour Package</button>
                            <a href="tour-package-view.php" class="btn btn-secondary">Cancel</a>
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
