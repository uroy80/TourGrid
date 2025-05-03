<?php
class Session {
    // Start or resume a session
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Get session variable
    public static function get($key) {
        return isset($_SESSION[$key]) ? $_SESSION[$key] : null;
    }
    
    // Set session variable
    public static function set($key, $value) {
        $_SESSION[$key] = $value;
    }
    
    // Unset session variable
    public static function unset($key) {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    
    // Destroy session
    public static function destroy() {
        session_unset();
        session_destroy();
    }
    
    // Check if user is admin
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
    }
}
?>
