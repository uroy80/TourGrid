<?php
// Debug script to check upload directory permissions and PHP configuration
session_start();
include('../config/config.php');
include('../config/checklogin.php');
check_login();

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit;
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
                <h1 class="h2">Upload Directory Debug</h1>
            </div>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Upload Directory Information</h6>
                </div>
                <div class="card-body">
                    <h5>PHP Upload Configuration</h5>
                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Max upload file size
                            <span class="badge bg-primary rounded-pill"><?php echo ini_get('upload_max_filesize'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Max post size
                            <span class="badge bg-primary rounded-pill"><?php echo ini_get('post_max_size'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Memory limit
                            <span class="badge bg-primary rounded-pill"><?php echo ini_get('memory_limit'); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Max execution time
                            <span class="badge bg-primary rounded-pill"><?php echo ini_get('max_execution_time'); ?> seconds</span>
                        </li>
                    </ul>

                    <h5>Upload Directories</h5>
                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th>Directory</th>
                            <th>Exists</th>
                            <th>Writable</th>
                            <th>Permissions</th>
                            <th>Path</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php
                        // Define directories to check
                        $base_path = dirname(dirname(__FILE__));
                        $directories = [
                            'assets/images/uploads',
                            'assets/images/uploads/tours',
                            'assets/images/uploads/hotels',
                            'assets/images/uploads/destinations',
                            'assets/images/uploads/users'
                        ];

                        foreach ($directories as $dir) {
                            $full_path = $base_path . '/' . $dir;
                            $exists = file_exists($full_path);
                            $writable = is_writable($full_path);
                            $perms = $exists ? substr(sprintf('%o', fileperms($full_path)), -4) : 'N/A';

                            echo "<tr>";
                            echo "<td>{$dir}</td>";
                            echo "<td>" . ($exists ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>') . "</td>";
                            echo "<td>" . ($writable ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>') . "</td>";
                            echo "<td>{$perms}</td>";
                            echo "<td>{$full_path}</td>";
                            echo "</tr>";
                        }
                        ?>
                        </tbody>
                    </table>

                    <h5 class="mt-4">Create Missing Directories</h5>
                    <form method="post" action="">
                        <input type="hidden" name="create_directories" value="1">
                        <button type="submit" class="btn btn-primary">Create Missing Directories</button>
                    </form>

                    <?php
                    // Handle directory creation
                    if (isset($_POST['create_directories'])) {
                        echo '<div class="mt-3">';
                        echo '<h6>Directory Creation Results:</h6>';
                        echo '<ul class="list-group">';

                        foreach ($directories as $dir) {
                            $full_path = $base_path . '/' . $dir;
                            if (!file_exists($full_path)) {
                                $created = mkdir($full_path, 0755, true);
                                echo '<li class="list-group-item">';
                                echo $dir . ': ';
                                echo $created ?
                                    '<span class="text-success">Created successfully</span>' :
                                    '<span class="text-danger">Failed to create</span>';
                                echo '</li>';
                            }
                        }

                        echo '</ul>';
                        echo '</div>';
                    }
                    ?>

                    <h5 class="mt-4">Test Upload</h5>
                    <form method="post" action="" enctype="multipart/form-data" class="mb-4">
                        <div class="mb-3">
                            <label for="test_file" class="form-label">Select a file to upload</label>
                            <input type="file" name="test_file" id="test_file" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label for="upload_dir" class="form-label">Select directory</label>
                            <select name="upload_dir" id="upload_dir" class="form-select">
                                <?php foreach ($directories as $dir): ?>
                                    <option value="<?php echo $dir; ?>"><?php echo $dir; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" name="test_upload" class="btn btn-success">Test Upload</button>
                    </form>

                    <?php
                    // Handle test upload
                    if (isset($_POST['test_upload']) && isset($_FILES['test_file'])) {
                        $upload_dir = $_POST['upload_dir'];
                        $full_path = $base_path . '/' . $upload_dir . '/';
                        $target_file = $full_path . basename($_FILES["test_file"]["name"]);

                        echo '<div class="alert ';

                        if ($_FILES["test_file"]["error"] == 0) {
                            if (move_uploaded_file($_FILES["test_file"]["tmp_name"], $target_file)) {
                                echo 'alert-success">File uploaded successfully to: ' . $target_file;
                            } else {
                                echo 'alert-danger">Failed to move uploaded file. Check directory permissions.';
                            }
                        } else {
                            echo 'alert-danger">Upload error: ' . $_FILES["test_file"]["error"];
                        }

                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include admin footer
include_once 'includes/footer.php';
?>
