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

// Check if user is admin for extra security
$user_id = Session::get('user_id');
$is_admin = false;

try {
    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT role FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['role'] === 'admin') {
        $is_admin = true;
    }
} catch (Exception $e) {
    // Silently fail
}

// Only allow admins to access this page
if (!$is_admin) {
    header("Location: dashboard.php");
    exit;
}

// Check upload directory
$upload_dir = "../assets/images/users/";
$upload_dir_exists = file_exists($upload_dir);
$upload_dir_writable = is_writable($upload_dir);

// Check PHP upload settings
$max_upload_size = ini_get('upload_max_filesize');
$max_post_size = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');
$max_execution_time = ini_get('max_execution_time');

// Check if GD library is installed for image processing
$gd_installed = extension_loaded('gd') && function_exists('gd_info');

// Test file creation
$test_file_path = $upload_dir . "test_" . time() . ".txt";
$test_file_created = false;
$test_file_error = "";

try {
    $test_file = @fopen($test_file_path, "w");
    if ($test_file) {
        fwrite($test_file, "Test file for upload debugging");
        fclose($test_file);
        $test_file_created = true;
        // Clean up
        @unlink($test_file_path);
    } else {
        $test_file_error = "Could not create test file";
    }
} catch (Exception $e) {
    $test_file_error = $e->getMessage();
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
                <h1 class="h2">Upload Diagnostics</h1>
            </div>

            <div class="alert alert-warning">
                <strong>Warning:</strong> This page is for administrators only and displays sensitive system information.
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Upload Directory Status</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Upload Directory Path</th>
                            <td><?php echo $upload_dir; ?></td>
                        </tr>
                        <tr>
                            <th>Directory Exists</th>
                            <td>
                                <?php if ($upload_dir_exists): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                    <button class="btn btn-sm btn-primary ms-2" onclick="createDirectory()">Create Directory</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Directory Writable</th>
                            <td>
                                <?php if ($upload_dir_writable): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                    <button class="btn btn-sm btn-primary ms-2" onclick="fixPermissions()">Fix Permissions</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Test File Creation</th>
                            <td>
                                <?php if ($test_file_created): ?>
                                    <span class="badge bg-success">Success</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Failed</span>
                                    <span class="ms-2"><?php echo $test_file_error; ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">PHP Upload Settings</h5>
                </div>
                <div class="card-body">
                    <table class="table">
                        <tr>
                            <th>Max Upload Size</th>
                            <td><?php echo $max_upload_size; ?></td>
                        </tr>
                        <tr>
                            <th>Max Post Size</th>
                            <td><?php echo $max_post_size; ?></td>
                        </tr>
                        <tr>
                            <th>Memory Limit</th>
                            <td><?php echo $memory_limit; ?></td>
                        </tr>
                        <tr>
                            <th>Max Execution Time</th>
                            <td><?php echo $max_execution_time; ?> seconds</td>
                        </tr>
                        <tr>
                            <th>GD Library Installed</th>
                            <td>
                                <?php if ($gd_installed): ?>
                                    <span class="badge bg-success">Yes</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">No</span> (Recommended for image processing)
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title">Test Upload</h5>
                </div>
                <div class="card-body">
                    <form action="process-test-upload.php" method="post" enctype="multipart/form-data" class="mb-3">
                        <div class="mb-3">
                            <label for="test_file" class="form-label">Select a file to test upload</label>
                            <input type="file" class="form-control" id="test_file" name="test_file">
                        </div>
                        <button type="submit" class="btn btn-primary">Test Upload</button>
                    </form>

                    <div id="uploadResult"></div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
    function createDirectory() {
        fetch('ajax-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=create_directory&path=<?php echo urlencode($upload_dir); ?>'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Directory created successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
    }

    function fixPermissions() {
        fetch('ajax-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=fix_permissions&path=<?php echo urlencode($upload_dir); ?>'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Permissions fixed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
    }
</script>

<?php
// Include user footer
include_once 'includes/footer.php';
?>
