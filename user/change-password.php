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

try {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();

    // Process form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_id = Session::get('user_id');
        $current_password = trim($_POST["current_password"]);
        $new_password = trim($_POST["new_password"]);
        $confirm_password = trim($_POST["confirm_password"]);
        
        // Validate input
        if (empty($current_password)) {
            $error_msg = "Please enter your current password.";
        } elseif (empty($new_password)) {
            $error_msg = "Please enter a new password.";
        } elseif (strlen($new_password) < 6) {
            $error_msg = "Password must have at least 6 characters.";
        } elseif ($new_password != $confirm_password) {
            $error_msg = "New password and confirm password do not match.";
        } else {
            // Get current user password
            $query = "SELECT password FROM users WHERE user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify current password
            if (!password_verify($current_password, $user['password'])) {
                $error_msg = "Current password is incorrect.";
            } else {
                // Hash new password
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                
                // Update password
                $update_query = "UPDATE users SET password = :password, updated_at = NOW() WHERE user_id = :user_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":password", $hashed_password);
                $update_stmt->bindParam(":user_id", $user_id);
                
                if ($update_stmt->execute()) {
                    $success_msg = "Password changed successfully.";
                } else {
                    $error_msg = "Error changing password. Please try again.";
                }
            }
        }
    }
} catch (Exception $e) {
    $error_msg = "An error occurred. Please try again later.";
    // In production, you would log this error
    // error_log("Change password error: " . $e->getMessage());
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
                <h1 class="h2">Change Password</h1>
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
            
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Update Password</h5>
                        </div>
                        <div class="card-body">
                            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="current-password" class="form-label">Current Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="current-password" name="current_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="current-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please enter your current password.
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new-password" class="form-label">New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="new-password" name="new_password" required minlength="6">
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="new-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Password must have at least 6 characters.
                                    </div>
                                    <div class="mt-2">
                                        <div class="password-strength-meter">
                                            <div id="password-strength-bar" style="width: 0%;"></div>
                                        </div>
                                        <small id="password-strength-text" class="form-text text-muted">Password strength</small>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="confirm-password" class="form-label">Confirm New Password</label>
                                    <div class="input-group">
                                        <input type="password" class="form-control" id="confirm-password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary toggle-password" type="button" data-target="confirm-password">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Please confirm your new password.
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-key me-2"></i>Update Password
                                    </button>
                                    <a href="profile.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Profile
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card shadow-sm border-0 mt-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="mb-0 fw-bold">Password Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex align-items-center border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Use at least 8 characters
                                </li>
                                <li class="list-group-item d-flex align-items-center border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Include uppercase and lowercase letters
                                </li>
                                <li class="list-group-item d-flex align-items-center border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Include at least one number
                                </li>
                                <li class="list-group-item d-flex align-items-center border-0 ps-0">
                                    <i class="fas fa-check-circle text-success me-2"></i>
                                    Include at least one special character (e.g., !@#$%^&*)
                                </li>
                                <li class="list-group-item d-flex align-items-center border-0 ps-0">
                                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                                    Avoid using personal information
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Password strength meter
    const passwordInput = document.getElementById('new-password');
    const strengthBar = document.getElementById('password-strength-bar');
    const strengthText = document.getElementById('password-strength-text');
    
    if (passwordInput && strengthBar && strengthText) {
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let width = '0%';
            let color = '#dc3545'; // red
            let text = 'Weak';
            
            if (password.length >= 8) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/\d/)) strength += 1;
            if (password.match(/[^a-zA-Z\d]/)) strength += 1;
            
            switch (strength) {
                case 0:
                    width = '0%';
                    color = '#dc3545'; // red
                    text = 'Weak';
                    break;
                case 1:
                    width = '25%';
                    color = '#dc3545'; // red
                    text = 'Weak';
                    break;
                case 2:
                    width = '50%';
                    color = '#ffc107'; // yellow
                    text = 'Fair';
                    break;
                case 3:
                    width = '75%';
                    color = '#20c997'; // teal
                    text = 'Good';
                    break;
                case 4:
                    width = '100%';
                    color = '#198754'; // green
                    text = 'Strong';
                    break;
            }
            
            strengthBar.style.width = width;
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = text;
        });
    }
});
</script>

<?php
// Include user footer
include_once 'includes/footer.php';
?>
