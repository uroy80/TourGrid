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

// Get current admin ID
$admin_id = Session::get('user_id');

// Get all tours for this admin for the dropdown
$query = "SELECT * FROM tours WHERE admin_id = :admin_id ORDER BY title";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$tours = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Initialize variables
$tour_id = $start_date = $end_date = $available_seats = $guide_name = "";
$price_adjustment = 0;
$tour_id_err = $start_date_err = $end_date_err = $available_seats_err = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate tour
    if (empty($_POST["tour_id"])) {
        $tour_id_err = "Please select a tour.";
    } else {
        // Verify the tour belongs to this admin
        $check_query = "SELECT * FROM tours WHERE tour_id = :tour_id AND admin_id = :admin_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":tour_id", $_POST["tour_id"]);
        $check_stmt->bindParam(":admin_id", $admin_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() > 0) {
            $tour_id = $_POST["tour_id"];
        } else {
            $tour_id_err = "Invalid tour selection.";
        }
    }
    
    // Validate start date
    if (empty($_POST["start_date"])) {
        $start_date_err = "Please enter a start date.";
    } else {
        $start_date = $_POST["start_date"];
    }
    
    // Validate end date
    if (empty($_POST["end_date"])) {
        $end_date_err = "Please enter an end date.";
    } else {
        $end_date = $_POST["end_date"];
        
        // Check if end date is after start date
        if (!empty($start_date) && strtotime($end_date) <= strtotime($start_date)) {
            $end_date_err = "End date must be after start date.";
        }
    }
    
    // Validate available seats
    if (empty($_POST["available_seats"])) {
        $available_seats_err = "Please enter available seats.";
    } elseif (!is_numeric($_POST["available_seats"]) || intval($_POST["available_seats"]) <= 0) {
        $available_seats_err = "Please enter a valid number of seats.";
    } else {
        $available_seats = $_POST["available_seats"];
    }
    
    // Get other form data
    $guide_name = !empty($_POST["guide_name"]) ? trim($_POST["guide_name"]) : "";
    $price_adjustment = !empty($_POST["price_adjustment"]) ? floatval($_POST["price_adjustment"]) : 0;
    
    // Check input errors before inserting in database
    if (empty($tour_id_err) && empty($start_date_err) && empty($end_date_err) && empty($available_seats_err)) {
        // Prepare an insert statement
        $sql = "INSERT INTO tour_schedules (tour_id, start_date, end_date, available_seats, guide_name, price_adjustment, status) 
                VALUES (:tour_id, :start_date, :end_date, :available_seats, :guide_name, :price_adjustment, 'upcoming')";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":tour_id", $param_tour_id, PDO::PARAM_INT);
            $stmt->bindParam(":start_date", $param_start_date);
            $stmt->bindParam(":end_date", $param_end_date);
            $stmt->bindParam(":available_seats", $param_available_seats, PDO::PARAM_INT);
            $stmt->bindParam(":guide_name", $param_guide_name, PDO::PARAM_STR);
            $stmt->bindParam(":price_adjustment", $param_price_adjustment);
            
            // Set parameters
            $param_tour_id = $tour_id;
            $param_start_date = $start_date;
            $param_end_date = $end_date;
            $param_available_seats = $available_seats;
            $param_guide_name = $guide_name;
            $param_price_adjustment = $price_adjustment;
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to tour schedule view page
                header("location: tour-schedule-view.php");
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
                <h1 class="h2">Tour Schedule Entry</h1>
            </div>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Add New Tour Schedule</h6>
                </div>
                <div class="card-body">
                    <?php if (empty($tours)): ?>
                        <div class="alert alert-warning">
                            <p>You need to create tour packages before you can schedule them. <a href="tour-package-entry.php">Click here</a> to add a tour package.</p>
                        </div>
                    <?php else: ?>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="tour_id" class="form-label">Tour Package</label>
                                <select name="tour_id" id="tour_id" class="form-select <?php echo (!empty($tour_id_err)) ? 'is-invalid' : ''; ?>" required>
                                    <option value="">Select Tour Package</option>
                                    <?php foreach ($tours as $tour): ?>
                                        <option value="<?php echo $tour['tour_id']; ?>" <?php echo ($tour_id == $tour['tour_id']) ? 'selected' : ''; ?>>
                                            <?php echo $tour['title']; ?> (<?php echo $tour['duration']; ?> days)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="invalid-feedback"><?php echo $tour_id_err; ?></div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" name="start_date" id="start_date" class="form-control <?php echo (!empty($start_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $start_date; ?>" required>
                                    <div class="invalid-feedback"><?php echo $start_date_err; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" name="end_date" id="end_date" class="form-control <?php echo (!empty($end_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $end_date; ?>" required>
                                    <div class="invalid-feedback"><?php echo $end_date_err; ?></div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="available_seats" class="form-label">Available Seats</label>
                                    <input type="number" name="available_seats" id="available_seats" class="form-control <?php echo (!empty($available_seats_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $available_seats; ?>" min="1" required>
                                    <div class="invalid-feedback"><?php echo $available_seats_err; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="guide_name" class="form-label">Tour Guide (Optional)</label>
                                    <input type="text" name="guide_name" id="guide_name" class="form-control" value="<?php echo $guide_name; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="price_adjustment" class="form-label">Price Adjustment ($)</label>
                                <input type="number" name="price_adjustment" id="price_adjustment" class="form-control" value="<?php echo $price_adjustment; ?>" step="0.01">
                                <small class="text-muted">Use positive values for price increase, negative for discounts</small>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Save Schedule</button>
                                <a href="tour-schedule-view.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include admin footer
include_once 'includes/footer.php';
?>
