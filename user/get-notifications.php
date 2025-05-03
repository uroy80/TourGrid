<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include necessary files
require_once '../config/database.php';
require_once '../config/session.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!Session::isLoggedIn()) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

try {
    // Get user ID from session
    $user_id = Session::get('user_id');
    
    if (!$user_id) {
        echo json_encode(['error' => 'User ID not found in session']);
        exit;
    }
    
    // Create database connection
    $database = new Database();
    $db = $database->getConnection();
    
    // Prepare query to get notifications for the user
    $query = "SELECT notification_id, title, message, type, is_read, created_at 
              FROM notifications 
              WHERE user_id = :user_id 
              ORDER BY created_at DESC 
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    // Fetch all notifications
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return notifications as JSON
    echo json_encode($notifications);
    
} catch (Exception $e) {
    // Return error message
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
