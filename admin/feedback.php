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

// Update feedback if requested
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['feedback_id']) && isset($_POST['admin_response'])) {
    $feedback_id = $_POST['feedback_id'];
    $admin_response = trim($_POST['admin_response']);
    
    // Check if the feedback exists and belongs to this admin's tour
    $check_query = "SELECT f.* FROM feedback f 
                   JOIN tours t ON f.tour_id = t.tour_id 
                   WHERE f.feedback_id = :feedback_id AND t.admin_id = :admin_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":feedback_id", $feedback_id);
    $check_stmt->bindParam(":admin_id", $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Update the feedback with admin response
        $update_query = "UPDATE feedback SET admin_response = :admin_response, updated_at = NOW() WHERE feedback_id = :feedback_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":admin_response", $admin_response);
        $update_stmt->bindParam(":feedback_id", $feedback_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Response added successfully.";
        } else {
            $error_msg = "Error adding response.";
        }
    } else {
        $error_msg = "Feedback not found or you do not have permission to respond to it.";
    }
}

// Toggle feedback published status if requested
if (isset($_GET['id']) && !empty($_GET['id']) && isset($_GET['action']) && ($_GET['action'] == 'publish' || $_GET['action'] == 'unpublish')) {
    $feedback_id = $_GET['id'];
    $is_published = ($_GET['action'] == 'publish') ? 1 : 0;
    
    // Check if the feedback exists and belongs to this admin's tour
    $check_query = "SELECT f.* FROM feedback f 
                   JOIN tours t ON f.tour_id = t.tour_id 
                   WHERE f.feedback_id = :feedback_id AND t.admin_id = :admin_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":feedback_id", $feedback_id);
    $check_stmt->bindParam(":admin_id", $admin_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        // Update the feedback published status
        $update_query = "UPDATE feedback SET is_published = :is_published, updated_at = NOW() WHERE feedback_id = :feedback_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":is_published", $is_published);
        $update_stmt->bindParam(":feedback_id", $feedback_id);
        
        if ($update_stmt->execute()) {
            $success_msg = "Feedback " . ($_GET['action'] == 'publish' ? 'published' : 'unpublished') . " successfully.";
        } else {
            $error_msg = "Error updating feedback status.";
        }
    } else {
        $error_msg = "Feedback not found or you do not have permission to update it.";
    }
}

// Get all feedback with user and tour details for this admin
$query = "SELECT f.*, u.username, u.full_name, t.title as tour_title
          FROM feedback f 
          JOIN users u ON f.user_id = u.user_id 
          JOIN tours t ON f.tour_id = t.tour_id 
          WHERE t.admin_id = :admin_id
          ORDER BY f.created_at DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":admin_id", $admin_id);
$stmt->execute();
$feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <h1 class="h2">Feedback Management</h1>
            </div>
            
            <?php if (isset($success_msg)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_msg)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_msg; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">All Feedback</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Tour</th>
                                    <th>Rating</th>
                                    <th>Comment</th>
                                    <th>Response</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($feedbacks)): ?>
                                    <tr>
                                        <td colspan="9" class="text-center">No feedback found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($feedbacks as $feedback): ?>
                                        <tr>
                                            <td><?php echo $feedback['feedback_id']; ?></td>
                                            <td><?php echo $feedback['full_name']; ?></td>
                                            <td><?php echo $feedback['tour_title']; ?></td>
                                            <td>
                                                <?php for ($i = 0; $i < $feedback['rating']; $i++): ?>
                                                    <i class="fas fa-star text-warning"></i>
                                                <?php endfor; ?>
                                                <?php for ($i = $feedback['rating']; $i < 5; $i++): ?>
                                                    <i class="far fa-star text-warning"></i>
                                                <?php endfor; ?>
                                            </td>
                                            <td><?php echo substr($feedback['comment'], 0, 100) . (strlen($feedback['comment']) > 100 ? '...' : ''); ?></td>
                                            <td>
                                                <?php if (!empty($feedback['admin_response'])): ?>
                                                    <?php echo substr($feedback['admin_response'], 0, 100) . (strlen($feedback['admin_response']) > 100 ? '...' : ''); ?>
                                                <?php else: ?>
                                                    <span class="text-muted">No response yet</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($feedback['created_at'])); ?></td>
                                            <td>
                                                <?php if ($feedback['is_published']): ?>
                                                    <span class="badge bg-success">Published</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Hidden</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $feedback['feedback_id']; ?>">
                                                    <i class="fas fa-reply"></i> Respond
                                                </button>
                                                <?php if ($feedback['is_published']): ?>
                                                    <a href="feedback.php?id=<?php echo $feedback['feedback_id']; ?>&action=unpublish" class="btn btn-sm btn-warning">
                                                        <i class="fas fa-eye-slash"></i> Hide
                                                    </a>
                                                <?php else: ?>
                                                    <a href="feedback.php?id=<?php echo $feedback['feedback_id']; ?>&action=publish" class="btn btn-sm btn-success">
                                                        <i class="fas fa-eye"></i> Publish
                                                    </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        
                                        <!-- Response Modal -->
                                        <div class="modal fade" id="responseModal<?php echo $feedback['feedback_id']; ?>" tabindex="-1" aria-labelledby="responseModalLabel<?php echo $feedback['feedback_id']; ?>" aria-hidden="true">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="responseModalLabel<?php echo $feedback['feedback_id']; ?>">Respond to Feedback</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <h6>Customer: <?php echo $feedback['full_name']; ?></h6>
                                                            <h6>Tour: <?php echo $feedback['tour_title']; ?></h6>
                                                            <h6>Rating: 
                                                                <?php for ($i = 0; $i < $feedback['rating']; $i++): ?>
                                                                    <i class="fas fa-star text-warning"></i>
                                                                <?php endfor; ?>
                                                                <?php for ($i = $feedback['rating']; $i < 5; $i++): ?>
                                                                    <i class="far fa-star text-warning"></i>
                                                                <?php endfor; ?>
                                                            </h6>
                                                            <div class="card mb-3">
                                                                <div class="card-header">
                                                                    <strong>Customer Comment</strong>
                                                                </div>
                                                                <div class="card-body">
                                                                    <?php echo nl2br(htmlspecialchars($feedback['comment'])); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                                            <input type="hidden" name="feedback_id" value="<?php echo $feedback['feedback_id']; ?>">
                                                            <div class="mb-3">
                                                                <label for="admin_response<?php echo $feedback['feedback_id']; ?>" class="form-label">Your Response</label>
                                                                <textarea name="admin_response" id="admin_response<?php echo $feedback['feedback_id']; ?>" class="form-control" rows="5" required><?php echo $feedback['admin_response']; ?></textarea>
                                                            </div>
                                                            <div class="text-end">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                                <button type="submit" class="btn btn-primary">Save Response</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php
// Include admin footer
include_once 'includes/footer.php';
?>
