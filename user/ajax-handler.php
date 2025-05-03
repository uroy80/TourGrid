<?php
require_once '../config/database.php';
require_once '../config/session.php';

// Start session
Session::start();

// Check if user is logged in
if (!Session::isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
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
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Only allow admins to access this page
if (!$is_admin) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Permission denied']);
    exit;
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'create_directory':
            $path = $_POST['path'] ?? '';
            if (empty($path)) {
                echo json_encode(['success' => false, 'message' => 'No path provided']);
                exit;
            }

            if (file_exists($path)) {
                echo json_encode(['success' => true, 'message' => 'Directory already exists']);
                exit;
            }

            if (mkdir($path, 0777, true)) {
                echo json_encode(['success' => true, 'message' => 'Directory created successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create directory']);
            }
            break;

        case 'fix_permissions':
            $path = $_POST['path'] ?? '';
            if (empty($path)) {
                echo json_encode(['success' => false, 'message' => 'No path provided']);
                exit;
            }

            if (!file_exists($path)) {
                echo json_encode(['success' => false, 'message' => 'Directory does not exist']);
                exit;
            }

            if (chmod($path, 0777)) {
                echo json_encode(['success' => true, 'message' => 'Permissions fixed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to fix permissions']);
            }
            break;

        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
