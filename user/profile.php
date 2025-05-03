<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: ../login.php");
    exit;
}

// Initialize variables
$success_msg = $error_msg = "";
$user = [];

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    // Get user information
    $user_id = Session::get('user_id');
    $query = "SELECT * FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate input
        $full_name = trim($_POST["full_name"]);
        $email = trim($_POST["email"]);
        $phone = trim($_POST["phone"]);

        // Check if email is already taken by another user
        $check_query = "SELECT user_id FROM users WHERE email = :email AND user_id != :user_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":email", $email);
        $check_stmt->bindParam(":user_id", $user_id);
        $check_stmt->execute();

        if ($check_stmt->rowCount() > 0) {
            $error_msg = "Email is already taken by another user.";
        } else {
            // Handle profile image upload
            $profile_image = $user['profile_image']; // Default to current image

            if (isset($_FILES["profile_image"]) && $_FILES["profile_image"]["error"] == 0) {
                $allowed = ["jpg" => "image/jpg", "jpeg" => "image/jpeg", "png" => "image/png", "gif" => "image/gif"];
                $filename = $_FILES["profile_image"]["name"];
                $filetype = $_FILES["profile_image"]["type"];
                $filesize = $_FILES["profile_image"]["size"];

                // Verify file extension
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                if (!array_key_exists($ext, $allowed)) {
                    $error_msg = "Please select a valid file format (JPG, JPEG, PNG, GIF).";
                } else if ($filesize > 5 * 1024 * 1024) { // 5MB max
                    $error_msg = "File size is larger than the allowed limit (5MB).";
                } else if (in_array($filetype, $allowed)) {
                    // Create unique filename
                    $new_filename = uniqid() . "." . $ext;
                    $target_dir = "../assets/images/uploads/users/";

                    // Create directory if it doesn't exist
                    if (!file_exists($target_dir)) {
                        // Try to create the directory with full permissions
                        if (!@mkdir($target_dir, 0777, true)) {
                            // If creation fails, log the error and provide a more helpful message
                            $error_msg = "Unable to create upload directory. Please contact support.";
                            error_log("Failed to create directory: $target_dir - " . error_get_last()['message']);
                        } else {
                            // Ensure the directory has the correct permissions after creation
                            @chmod($target_dir, 0777);
                        }
                    }

                    // Check if directory is writable
                    if (empty($error_msg) && !is_writable($target_dir)) {
                        $error_msg = "Upload directory is not writable. Please contact support.";
                        error_log("Directory not writable: $target_dir");
                    }

                    $target_file = $target_dir . $new_filename;

                    if (empty($error_msg)) {
                        if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                            // File uploaded successfully
                            $profile_image = $new_filename;

                            // Delete old profile image if it exists and is not the default
                            if (!empty($user['profile_image']) && $user['profile_image'] != 'default-user.jpg') {
                                $old_file = $target_dir . $user['profile_image'];
                                if (file_exists($old_file)) {
                                    @unlink($old_file);
                                }
                            }
                        } else {
                            // Get the specific error message
                            $upload_error = error_get_last();
                            $error_msg = "There was an error uploading your file: " .
                                ($upload_error ? $upload_error['message'] : "Unknown error");

                            // Log the error for debugging
                            error_log("Profile image upload error for user $user_id: " . $error_msg);
                        }
                    }
                }
            }

            if (empty($error_msg)) {
                // Update user profile
                $update_query = "UPDATE users SET full_name = :full_name, email = :email, phone = :phone, profile_image = :profile_image, updated_at = NOW() WHERE user_id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":full_name", $full_name);
                $update_stmt->bindParam(":email", $email);
                $update_stmt->bindParam(":phone", $phone);
                $update_stmt->bindParam(":profile_image", $profile_image);
                $update_stmt->bindParam(":user_id", $user_id);

                if ($update_stmt->execute()) {
                    $success_msg = "Profile updated successfully.";

                    // Refresh user data
                    $stmt->execute();
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                } else {
                    $error_msg = "Error updating profile. Please try again.";
                }
            }
        }
    }
} catch (Exception $e) {
    $error_msg = "An error occurred. Please try again later.";
    // In production, you would log this error
    // error_log("Profile error: " . $e->getMessage());
}

// Include user header
include_once 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <?php include_once 'includes/sidebar.php'; ?>

        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Profile</h1>
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

            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow-sm border-0">
                        <div class="card-body text-center">
                            <div class="mb-4">
                                <img src="<?php echo !empty($user['profile_image']) ? '../assets/images/uploads/users/'.$user['profile_image'] : '../assets/images/users/default-user.jpg'; ?>" class="rounded-circle img-thumbnail profile-img" alt="Profile Image" id="profile-image-preview" onerror="this.src='../assets/images/users/default-user.jpg'" style="transform: translateZ(0); backface-visibility: hidden; -webkit-backface-visibility: hidden; will-change: transform;">
                            </div>
                            <h5 class="card-title"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                            <p class="text-muted mb-1"><?php echo htmlspecialchars($user['username']); ?></p>
                            <p class="text-muted mb-3">Member since <?php echo date('M Y', strtotime($user['created_at'])); ?></p>

                            <div class="d-grid">
                                <label for="profile-image-upload" class="btn btn-outline-primary">
                                    <i class="fas fa-camera me-2"></i>Change Profile Picture
                                </label>
                            </div>
                        </div>
                        <div class="card-footer bg-white py-3">
                            <div class="row text-center">
                                <div class="col">
                                    <a href="bookings.php" class="text-decoration-none">
                                        <h5 class="mb-0 text-primary">
                                            <?php
                                            // Get booking count
                                            try {
                                                $query = "SELECT COUNT(*) as booking_count FROM bookings WHERE user_id = :user_id";
                                                $stmt = $db->prepare($query);
                                                $stmt->bindParam(":user_id", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo $result ? $result['booking_count'] : 0;
                                            } catch (Exception $e) {
                                                echo 0;
                                            }
                                            ?>
                                        </h5>
                                        <small class="text-muted">Bookings</small>
                                    </a>
                                </div>
                                <div class="col">
                                    <a href="reviews.php" class="text-decoration-none">
                                        <h5 class="mb-0 text-warning">
                                            <?php
                                            // Get review count
                                            try {
                                                $query = "SELECT COUNT(*) as review_count FROM feedback WHERE user_id = :user_id";
                                                $stmt = $db->prepare($query);
                                                $stmt->bindParam(":user_id", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo $result ? $result['review_count'] : 0;
                                            } catch (Exception $e) {
                                                echo 0;
                                            }
                                            ?>
                                        </h5>
                                        <small class="text-muted">Reviews</small>
                                    </a>
                                </div>
                                <div class="col">
                                    <a href="wishlist.php" class="text-decoration-none">
                                        <h5 class="mb-0 text-danger">
                                            <?php
                                            // Get wishlist count
                                            try {
                                                $query = "SELECT COUNT(*) as wishlist_count FROM wishlist WHERE user_id = :user_id";
                                                $stmt = $db->prepare($query);
                                                $stmt->bindParam(":user_id", $user_id);
                                                $stmt->execute();
                                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                                echo $result ? $result['wishlist_count'] : 0;
                                            } catch (Exception $e) {
                                                echo 0;
                                            }
                                            ?>
                                        </h5>
                                        <small class="text-muted">Wishlist</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Edit Profile</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                                <!-- Hidden file input for profile image -->
                                <input type="file" name="profile_image" id="profile-image-upload" class="d-none" accept="image/*">

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly disabled>
                                        <small class="text-muted">Username cannot be changed</small>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                        <div class="invalid-feedback">
                                            Please enter your full name.
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        <div class="invalid-feedback">
                                            Please enter a valid email address.
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                        <small class="text-muted">Optional</small>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="role" class="form-label">Account Type</label>
                                        <input type="text" class="form-control" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly disabled>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="status" class="form-label">Account Status</label>
                                        <input type="text" class="form-control" id="status" value="<?php echo ucfirst($user['status']); ?>" readonly disabled>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                    <a href="change-password.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Hidden div to prevent JavaScript from showing on the page -->
<div id="js-container" style="display: none;"></div>

<?php
// Include user footer
include_once 'includes/footer.php';
?>
