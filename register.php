<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Start session
Session::start();

// For debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if user is already logged in
if (Session::isLoggedIn()) {
    // Redirect to appropriate dashboard
    if (Session::isAdmin()) {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: user/dashboard.php");
    }
    exit;
}

// Initialize variables
$username = $password = $confirm_password = $email = $full_name = $phone = "";
$username_err = $password_err = $confirm_password_err = $email_err = $full_name_err = "";
$admin_code = "";
$admin_code_err = "";
$register_type = isset($_POST["register_type"]) ? $_POST["register_type"] : "user";
$company_name = $company_address = "";
$company_err = "";

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', trim($_POST["username"]))) {
        $username_err = "Username can only contain letters, numbers, and underscores.";
    } else {
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE username = :username";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = trim($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            unset($stmt);
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
        $email_err = "Please enter a valid email address.";
    } else {
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE email = :email";
        
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                if ($stmt->rowCount() == 1) {
                    $email_err = "This email is already registered.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }
            
            // Close statement
            unset($stmt);
        }
    }
    
    // Validate full name
    if (empty(trim($_POST["full_name"]))) {
        $full_name_err = "Please enter your full name.";
    } else {
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Get phone number
    $phone = !empty($_POST["phone"]) ? trim($_POST["phone"]) : "";
    
    // If registering as admin, validate company details and admin code
    if ($register_type == "admin") {
        // Validate company name
        if (empty(trim($_POST["company_name"]))) {
            $company_err = "Please enter your company name.";
        } else {
            $company_name = trim($_POST["company_name"]);
        }
        
        // Get company address
        $company_address = !empty($_POST["company_address"]) ? trim($_POST["company_address"]) : "";
        
        // Validate admin code
        if (empty(trim($_POST["admin_code"]))) {
            $admin_code_err = "Please enter the admin registration code.";
        } else {
            $admin_code = trim($_POST["admin_code"]);
            // Check if admin code is valid (for demo, we'll use "ADMIN123")
            if ($admin_code !== "ADMIN123") {
                $admin_code_err = "Invalid admin registration code.";
            }
        }
    }
    
    // Check input errors before inserting in database
    $has_errors = !empty($username_err) || !empty($password_err) || !empty($confirm_password_err) || 
                 !empty($email_err) || !empty($full_name_err);
                 
    if ($register_type == "admin") {
        $has_errors = $has_errors || !empty($company_err) || !empty($admin_code_err);
    }
    
    if (!$has_errors) {
        // Prepare an insert statement
        if ($register_type == "admin") {
            $sql = "INSERT INTO users (username, password, email, full_name, phone, role, company_name, company_address) 
                    VALUES (:username, :password, :email, :full_name, :phone, 'admin', :company_name, :company_address)";
        } else {
            $sql = "INSERT INTO users (username, password, email, full_name, phone) 
                    VALUES (:username, :password, :email, :full_name, :phone)";
        }
         
        if ($stmt = $db->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bindParam(":username", $param_username, PDO::PARAM_STR);
            $stmt->bindParam(":password", $param_password, PDO::PARAM_STR);
            $stmt->bindParam(":email", $param_email, PDO::PARAM_STR);
            $stmt->bindParam(":full_name", $param_full_name, PDO::PARAM_STR);
            $stmt->bindParam(":phone", $param_phone, PDO::PARAM_STR);
            
            if ($register_type == "admin") {
                $stmt->bindParam(":company_name", $param_company_name, PDO::PARAM_STR);
                $stmt->bindParam(":company_address", $param_company_address, PDO::PARAM_STR);
            }
            
            // Set parameters
            $param_username = $username;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_email = $email;
            $param_full_name = $full_name;
            $param_phone = $phone;
            
            if ($register_type == "admin") {
                $param_company_name = $company_name;
                $param_company_address = $company_address;
            }
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to login page
                header("location: login.php");
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

// Include header
include_once 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Sign Up</h4>
            </div>
            <div class="card-body">
                <!-- Registration Type Tabs -->
                <ul class="nav nav-tabs mb-4" id="registerTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($register_type == 'user') ? 'active' : ''; ?>" 
                                id="user-reg-tab" data-bs-toggle="tab" data-bs-target="#user-register" 
                                type="button" role="tab" aria-controls="user-register" aria-selected="<?php echo ($register_type == 'user') ? 'true' : 'false'; ?>">
                            <i class="fas fa-user me-2"></i>Register as User
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?php echo ($register_type == 'admin') ? 'active' : ''; ?>" 
                                id="admin-reg-tab" data-bs-toggle="tab" data-bs-target="#admin-register" 
                                type="button" role="tab" aria-controls="admin-register" aria-selected="<?php echo ($register_type == 'admin') ? 'true' : 'false'; ?>">
                            <i class="fas fa-building me-2"></i>Register as Travel Company
                        </button>
                    </li>
                </ul>
                
                <div class="tab-content" id="registerTabsContent">
                    <!-- User Registration Tab -->
                    <div class="tab-pane fade <?php echo ($register_type == 'user') ? 'show active' : ''; ?>" id="user-register" role="tabpanel" aria-labelledby="user-reg-tab">
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="register_type" value="user">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="username" class="form-label">Username</label>
                                    <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err) && $register_type == 'user') ? 'is-invalid' : ''; ?>" value="<?php echo ($register_type == 'user') ? $username : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'user') ? $username_err : ''; ?></div>
                                    <small class="text-muted">Only letters, numbers, and underscores allowed.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" name="email" id="email" class="form-control <?php echo (!empty($email_err) && $register_type == 'user') ? 'is-invalid' : ''; ?>" value="<?php echo ($register_type == 'user') ? $email : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'user') ? $email_err : ''; ?></div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="full_name" class="form-label">Full Name</label>
                                    <input type="text" name="full_name" id="full_name" class="form-control <?php echo (!empty($full_name_err) && $register_type == 'user') ? 'is-invalid' : ''; ?>" value="<?php echo ($register_type == 'user') ? $full_name : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'user') ? $full_name_err : ''; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number (Optional)</label>
                                    <input type="text" name="phone" id="phone" class="form-control" value="<?php echo ($register_type == 'user') ? $phone : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err) && $register_type == 'user') ? 'is-invalid' : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'user') ? $password_err : ''; ?></div>
                                    <small class="text-muted">Minimum 6 characters required.</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err) && $register_type == 'user') ? 'is-invalid' : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'user') ? $confirm_password_err : ''; ?></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Register as User</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                            <p>Already have an account? <a href="login.php">Login here</a>.</p>
                        </form>
                    </div>
                    
                    <!-- Admin/Company Registration Tab -->
                    <div class="tab-pane fade <?php echo ($register_type == 'admin') ? 'show active' : ''; ?>" id="admin-register" role="tabpanel" aria-labelledby="admin-reg-tab">
                        <div class="alert alert-info mb-4">
                            <i class="fas fa-info-circle me-2"></i> Register your travel company to manage tours, bookings, and more. You'll need an admin registration code.
                        </div>
                        
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                            <input type="hidden" name="register_type" value="admin">
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">Company Name</label>
                                    <input type="text" name="company_name" id="company_name" class="form-control <?php echo (!empty($company_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $company_name; ?>" required>
                                    <div class="invalid-feedback"><?php echo $company_err; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="company_address" class="form-label">Company Address</label>
                                    <input type="text" name="company_address" id="company_address" class="form-control" value="<?php echo $company_address; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="admin_username" class="form-label">Admin Username</label>
                                    <input type="text" name="username" id="admin_username" class="form-control <?php echo (!empty($username_err) && $register_type == 'admin') ? 'is-invalid' : ''; ?>" value="<?php echo ($register_type == 'admin') ? $username : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'admin') ? $username_err : ''; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="admin_email" class="form-label">Admin Email</label>
                                    <input type="email" name="email" id="admin_email" class="form-control <?php echo (!empty($email_err) && $register_type == 'admin') ? 'is-invalid' : ''; ?>" value="<?php echo ($register_type == 'admin') ? $email : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'admin') ? $email_err : ''; ?></div>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="admin_full_name" class="form-label">Admin Full Name</label>
                                    <input type="text" name="full_name" id="admin_full_name" class="form-control <?php echo (!empty($full_name_err) && $register_type == 'admin') ? 'is-invalid' : ''; ?>" value="<?php echo ($register_type == 'admin') ? $full_name : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'admin') ? $full_name_err : ''; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="admin_phone" class="form-label">Admin Phone Number</label>
                                    <input type="text" name="phone" id="admin_phone" class="form-control" value="<?php echo ($register_type == 'admin') ? $phone : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="admin_password" class="form-label">Password</label>
                                    <input type="password" name="password" id="admin_password" class="form-control <?php echo (!empty($password_err) && $register_type == 'admin') ? 'is-invalid' : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'admin') ? $password_err : ''; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <label for="admin_confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" name="confirm_password" id="admin_confirm_password" class="form-control <?php echo (!empty($confirm_password_err) && $register_type == 'admin') ? 'is-invalid' : ''; ?>" required>
                                    <div class="invalid-feedback"><?php echo ($register_type == 'admin') ? $confirm_password_err : ''; ?></div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="admin_code" class="form-label">Admin Registration Code</label>
                                <input type="text" name="admin_code" id="admin_code" class="form-control <?php echo (!empty($admin_code_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $admin_code; ?>" required>
                                <div class="invalid-feedback"><?php echo $admin_code_err; ?></div>
                                <small class="text-muted">Enter the admin registration code provided to you.</small>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-danger">Register as Travel Company</button>
                                <button type="reset" class="btn btn-secondary">Reset</button>
                            </div>
                            <p>Already have an account? <a href="login.php">Login here</a>.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add custom script to maintain tab state -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get the tabs
    var userTab = document.getElementById('user-reg-tab');
    var adminTab = document.getElementById('admin-reg-tab');
    
    // Add click event listeners
    userTab.addEventListener('click', function() {
        document.querySelector('#user-register form input[name="register_type"]').value = 'user';
    });
    
    adminTab.addEventListener('click', function() {
        document.querySelector('#admin-register form input[name="register_type"]').value = 'admin';
    });
});
</script>

<?php
// Include footer
include_once 'includes/footer.php';
?>
