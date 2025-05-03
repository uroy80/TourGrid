<?php
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'includes/helpers.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// Include header
include_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">Test Booking Form</h3>
                </div>
                <div class="card-body">
                    <p class="alert alert-info">This is a test form to verify the booking process works correctly.</p>
                    
                    <form action="booking-process.php" method="post">
                        <input type="hidden" name="tour_id" value="1">
                        <input type="hidden" name="schedule_id" value="1">
                        
                        <div class="mb-3">
                            <label for="num_people" class="form-label">Number of Travelers</label>
                            <select class="form-select" id="num_people" name="num_people" required>
                                <option value="1">1</option>
                                <option value="2">2</option>
                                <option value="3">3</option>
                            </select>
                        </div>
                        
                        <div id="traveler-details">
                            <h5 class="mb-3">Lead Traveler Details</h5>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_name_1" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="traveler_name_1" name="traveler[1][name]" value="Test User" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_age_1" class="form-label">Age</label>
                                    <input type="number" class="form-control" id="traveler_age_1" name="traveler[1][age]" value="30" min="1" max="120" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_gender_1" class="form-label">Gender</label>
                                    <select class="form-select" id="traveler_gender_1" name="traveler[1][gender]" required>
                                        <option value="male" selected>Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="traveler_id_type_1" class="form-label">ID Type</label>
                                    <select class="form-select" id="traveler_id_type_1" name="traveler[1][id_type]" required>
                                        <option value="passport" selected>Passport</option>
                                        <option value="national_id">National ID</option>
                                        <option value="drivers_license">Driver's License</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="traveler_id_number_1" class="form-label">ID Number</label>
                                <input type="text" class="form-control" id="traveler_id_number_1" name="traveler[1][id_number]" value="AB123456" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="special_requests" class="form-label">Special Requests (Optional)</label>
                            <textarea class="form-control" id="special_requests" name="special_requests" rows="3">Test special request</textarea>
                        </div>
                        
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="terms" checked required>
                            <label class="form-check-label" for="terms">
                                I agree to the terms and conditions
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Continue to Payment</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    
    form.addEventListener('submit', function(e) {
        console.log('Form submitted');
        console.log('Action:', this.action);
        console.log('Method:', this.method);
        
        // Add a hidden field with timestamp to prevent caching
        const timestampField = document.createElement('input');
        timestampField.type = 'hidden';
        timestampField.name = 'timestamp';
        timestampField.value = Date.now();
        this.appendChild(timestampField);
    });
});
</script>

<?php include_once 'includes/footer.php'; ?>
