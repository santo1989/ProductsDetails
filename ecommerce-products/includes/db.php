<?php
/**
 * Database Connection File
 * 
 * Uses MySQLi for database connections
 * Update these credentials based on your environment
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Update with your password
define('DB_NAME', 'products_db');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");

/**
 * Helper function to sanitize input
 */
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Helper function to generate slug from text
 */
function generate_slug($text) {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Helper function to check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Helper function to check if user is admin
 */
function is_admin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Helper function to redirect
 */
function redirect($url) {
    header("Location: $url");
    exit();
}
?>
