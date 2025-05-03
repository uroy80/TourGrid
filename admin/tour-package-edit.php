<?php
session_start();
include('../config/config.php');
include('../config/checklogin.php');
check_login();

// After the database connection code, add:
$admin_id = Session::get('user_id');

// Get tour_id from the URL
if (isset($_GET['tour_id'])) {
    $param_tour_id = $_GET['tour_id'];

    // Fetch tour details based on tour_id
    try {
        $query = "SELECT * FROM tours WHERE tour_id = :tour_id AND admin_id = :admin_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":tour_id", $param_tour_id);
        $stmt->bindParam(":admin_id", $admin_id);
        $stmt->execute();

        if ($stmt->rowCount() == 0) {
            // Tour not found or doesn't belong to this admin
            header("Location: tour-package-view.php");
            exit;
        }

        $tour = $stmt->fetch(PDO::FETCH_ASSOC);

        // Pre-populate form fields with existing data
        $tour_name = $tour['tour_name'];
        $tour_description = $tour['tour_description'];
        $tour_price = $tour['tour_price'];
        $tour_location = $tour['tour_location'];
        $tour_duration = $tour['tour_duration'];
        $tour_image = $tour['tour_image']; // Assuming you have an image path stored

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    // Redirect if tour_id is not provided
    header("Location: tour-package-view.php");
    exit;
}

// Handle form submission for updating the tour package
if (isset($_POST['update_tour'])) {
    // Retrieve form data
    $tour_name = $_POST['tour_name'];
    $tour_description = $_POST['tour_description'];
    $tour_price = $_POST['tour_price'];
    $tour_location = $_POST['tour_location'];
    $tour_duration = $_POST['tour_duration'];

    // Handle image upload (if a new image is uploaded)
    if (!empty($_FILES["tour_image"]["name"])) {
        // Generate a unique filename to prevent overwriting
        $image_name = uniqid() . '.' . strtolower(pathinfo($_FILES["tour_image"]["name"], PATHINFO_EXTENSION));
        $target_dir = dirname(dirname(__FILE__)) . "/assets/images/uploads/tours/";
        $target_file = $target_dir . $image_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["tour_image"]["tmp_name"]);
        if ($check === false) {
            echo "File is not an image.";
            exit;
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            echo "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            exit;
        }

        // Create directory if it doesn't exist
        if (!file_exists($target_dir)) {
            if (!mkdir($target_dir, 0755, true)) {
                echo "Failed to create upload directory.";
                exit;
            }
        }

        // Try to upload file
        if (move_uploaded_file($_FILES["tour_image"]["tmp_name"], $target_file)) {
            // Store the relative path for database storage
            $tour_image = "assets/images/uploads/tours/" . $image_name;
        } else {
            echo "Sorry, there was an error uploading your file. Error code: " . $_FILES["tour_image"]["error"];
            exit;
        }
    }

    // Update the tour package in the database
    try {
        $query = "UPDATE tours SET tour_name = :tour_name, tour_description = :tour_description, tour_price = :tour_price, tour_location = :tour_location, tour_duration = :tour_duration, tour_image = :tour_image WHERE tour_id = :tour_id AND admin_id = :admin_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":tour_name", $tour_name);
        $stmt->bindParam(":tour_description", $tour_description);
        $stmt->bindParam(":tour_price", $tour_price);
        $stmt->bindParam(":tour_location", $tour_location);
        $stmt->bindParam(":tour_duration", $tour_duration);
        $stmt->bindParam(":tour_image", $tour_image);
        $stmt->bindParam(":tour_id", $param_tour_id);
        $stmt->bindParam(":admin_id", $admin_id);

        $stmt->execute();

        // Redirect to tour package view or display success message
        header("Location: tour-package-view.php");
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tour Package</title>
</head>
<body>

<h2>Edit Tour Package</h2>

<form method="post" enctype="multipart/form-data">
    <label for="tour_name">Tour Name:</label><br>
    <input type="text" id="tour_name" name="tour_name" value="<?php echo htmlspecialchars($tour_name); ?>"><br><br>

    <label for="tour_description">Tour Description:</label><br>
    <textarea id="tour_description" name="tour_description"><?php echo htmlspecialchars($tour_description); ?></textarea><br><br>

    <label for="tour_price">Tour Price:</label><br>
    <input type="number" id="tour_price" name="tour_price" value="<?php echo htmlspecialchars($tour_price); ?>"><br><br>

    <label for="tour_location">Tour Location:</label><br>
    <input type="text" id="tour_location" name="tour_location" value="<?php echo htmlspecialchars($tour_location); ?>"><br><br>

    <label for="tour_duration">Tour Duration:</label><br>
    <input type="text" id="tour_duration" name="tour_duration" value="<?php echo htmlspecialchars($tour_duration); ?>"><br><br>

    <label for="tour_image">Tour Image:</label><br>
    <input type="file" id="tour_image" name="tour_image"><br><br>
    <img src="<?php echo htmlspecialchars($tour_image); ?>" alt="Current Tour Image" width="200"><br><br>

    <input type="submit" name="update_tour" value="Update Tour">
</form>

<a href="tour-package-view.php">Back to Tour Packages</a>

</body>
</html>
