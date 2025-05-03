<?php
require_once '../config/session.php';
require_once '../includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $tour_id = isset($_POST['tour_id']) ? intval($_POST['tour_id']) : 0;
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : 0;
    $num_people = isset($_POST['num_people']) ? intval($_POST['num_people']) : 0;
    
    if (!$tour_id || !$schedule_id || !$num_people) {
        $error = "Please fill in all required fields.";
    } else {
        // Connect to database
        $conn = connectDatabase();
        
        // Get tour and schedule details
        $stmt = $conn->prepare("SELECT t.*, ts.departure_date, ts.price 
                                FROM tours t 
                                JOIN tour_schedules ts ON t.tour_id = ts.tour_id 
                                WHERE t.tour_id = ? AND ts.schedule_id = ?");
        $stmt->bind_param("ii", $tour_id, $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $tour_data = $result->fetch_assoc();
        $stmt->close();
        
        if (!$tour_data) {
            $error = "Invalid tour or schedule.";
        } else {
            // Calculate pricing
            $base_amount = $tour_data['price'] * $num_people;
            $tax_amount = $base_amount * 0.05; // 5% tax
            $total_amount = $base_amount + $tax_amount;
            $booking_reference = 'TG' . date('Ymd') . rand(1000, 9999);
            
            // Store booking data in session
            $_SESSION['booking_data'] = [
                'booking_id' => 0, // Will be set after insertion
                'booking_reference' => $booking_reference,
                'tour_id' => $tour_id,
                'tour_name' => $tour_data['name'],
                'departure_date' => $tour_data['departure_date'],
                'num_people' => $num_people,
                'base_amount' => $base_amount,
                'tax_amount' => $tax_amount,
                'total_amount' => $total_amount
            ];
            
            // Redirect to payment page
            header('Location: payment.php');
            exit;
        }
        
        // Close database connection
        $conn->close();
    }
}

// Get available tours
$conn = connectDatabase();
$stmt = $conn->prepare("SELECT tour_id, name FROM tours WHERE status = 'active' ORDER BY name");
$stmt->execute();
$result = $stmt->get_result();
$tours = [];
while ($row = $result->fetch_assoc()) {
    $tours[] = $row;
}
$stmt->close();

// Include header
$pageTitle = "Direct Booking";
include_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Direct Booking Form</h4>
                </div>
                <div class="card-body">
                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger">
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
                        <div class="mb-3">
                            <label for="tour_id" class="form-label">Select Tour</label>
                            <select name="tour_id" id="tour_id" class="form-select" required>
                                <option value="">-- Select a tour --</option>
                                <?php foreach ($tours as $tour): ?>
                                    <option value="<?php echo $tour['tour_id']; ?>">
                                        <?php echo htmlspecialchars($tour['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="schedule_id" class="form-label">Schedule ID</label>
                            <input type="number" name="schedule_id" id="schedule_id" class="form-control" required>
                            <small class="text-muted">Enter a valid schedule ID for the selected tour.</small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="num_people" class="form-label">Number of People</label>
                            <select name="num_people" id="num_people" class="form-select" required>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Continue to Payment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tourSelect = document.getElementById('tour_id');
    
    tourSelect.addEventListener('change', function() {
        // In a real application, you would fetch schedules for the selected tour
        // For this simplified version, we'll just clear the schedule ID
        document.getElementById('schedule_id').value = '';
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
