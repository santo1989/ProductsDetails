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

$emailColumnCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'Email'");
if ($emailColumnCheck && $emailColumnCheck->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN Email VARCHAR(191) NULL UNIQUE AFTER Username");
}
if ($emailColumnCheck instanceof mysqli_result) {
    $emailColumnCheck->free();
}

/**
 * Helper function to sanitize input
 */
function sanitize_input($data)
{
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Helper function to generate slug from text
 */
function generate_slug($text)
{
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

/**
 * Helper function to check if user is logged in
 */
function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

/**
 * Helper function to check if user is admin
 */
function is_admin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Helper function to redirect
 */
function redirect($url)
{
    header("Location: $url");
    exit();
}

function convert_image_to_webp($source_path, $destination_path, $mime_type, $quality = 82)
{
    if (!function_exists('imagewebp')) {
        return false;
    }

    $source_image = null;

    if ($mime_type === 'image/jpeg') {
        if (!function_exists('imagecreatefromjpeg')) {
            return false;
        }
        $source_image = @imagecreatefromjpeg($source_path);
    } elseif ($mime_type === 'image/png') {
        if (!function_exists('imagecreatefrompng')) {
            return false;
        }
        $source_image = @imagecreatefrompng($source_path);
        if ($source_image) {
            imagepalettetotruecolor($source_image);
            imagealphablending($source_image, true);
            imagesavealpha($source_image, true);
        }
    } elseif ($mime_type === 'image/webp') {
        if (!function_exists('imagecreatefromwebp')) {
            return false;
        }
        $source_image = @imagecreatefromwebp($source_path);
    }

    if (!$source_image) {
        return false;
    }

    $saved = imagewebp($source_image, $destination_path, $quality);
    imagedestroy($source_image);

    return $saved;
}

function create_thumbnail_webp($source_path, $thumbnail_path, $max_width = 480, $quality = 72)
{
    if (!function_exists('imagewebp') || !function_exists('getimagesize')) {
        return false;
    }

    $image_info = @getimagesize($source_path);
    if (!$image_info || empty($image_info['mime']) || empty($image_info[0]) || empty($image_info[1])) {
        return false;
    }

    $mime_type = $image_info['mime'];
    $width = (int)$image_info[0];
    $height = (int)$image_info[1];

    if ($mime_type === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
        $source_image = @imagecreatefromjpeg($source_path);
    } elseif ($mime_type === 'image/png' && function_exists('imagecreatefrompng')) {
        $source_image = @imagecreatefrompng($source_path);
    } elseif ($mime_type === 'image/webp' && function_exists('imagecreatefromwebp')) {
        $source_image = @imagecreatefromwebp($source_path);
    } else {
        return false;
    }

    if (!$source_image) {
        return false;
    }

    if ($width <= $max_width) {
        $target_width = $width;
        $target_height = $height;
    } else {
        $target_width = $max_width;
        $target_height = (int)round(($height / $width) * $target_width);
    }

    $thumbnail_image = imagecreatetruecolor($target_width, $target_height);
    if (!$thumbnail_image) {
        imagedestroy($source_image);
        return false;
    }

    imagealphablending($thumbnail_image, false);
    imagesavealpha($thumbnail_image, true);
    $transparent = imagecolorallocatealpha($thumbnail_image, 0, 0, 0, 127);
    imagefill($thumbnail_image, 0, 0, $transparent);

    imagecopyresampled($thumbnail_image, $source_image, 0, 0, 0, 0, $target_width, $target_height, $width, $height);
    $saved = imagewebp($thumbnail_image, $thumbnail_path, $quality);

    imagedestroy($source_image);
    imagedestroy($thumbnail_image);

    return $saved;
}

function get_thumbnail_path_from_relative_path($relative_path)
{
    $extension = pathinfo($relative_path, PATHINFO_EXTENSION);
    if ($extension === '') {
        return $relative_path . '_thumb.webp';
    }

    return substr($relative_path, 0, -strlen($extension) - 1) . '_thumb.webp';
}

function get_optimized_image_for_display($image_path)
{
    if (empty($image_path) || preg_match('/^https?:\/\//i', $image_path)) {
        return $image_path;
    }

    $thumb_relative_path = get_thumbnail_path_from_relative_path($image_path);
    $thumb_absolute_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $thumb_relative_path);

    if (file_exists($thumb_absolute_path)) {
        return $thumb_relative_path;
    }

    return $image_path;
}

function delete_image_and_thumbnail($relative_path, $base_prefix = '')
{
    if (empty($relative_path) || preg_match('/^https?:\/\//i', $relative_path)) {
        return;
    }

    $original_path = $base_prefix . $relative_path;
    if (file_exists($original_path)) {
        @unlink($original_path);
    }

    $thumb_relative_path = get_thumbnail_path_from_relative_path($relative_path);
    $thumb_path = $base_prefix . $thumb_relative_path;
    if (file_exists($thumb_path)) {
        @unlink($thumb_path);
    }
}

function regenerate_thumbnail_for_relative_path($relative_path, $overwrite = true)
{
    if (empty($relative_path) || preg_match('/^https?:\/\//i', $relative_path)) {
        return false;
    }

    $absolute_original_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative_path);
    if (!file_exists($absolute_original_path)) {
        return false;
    }

    $thumb_relative_path = get_thumbnail_path_from_relative_path($relative_path);
    $absolute_thumb_path = dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $thumb_relative_path);

    if (!$overwrite && file_exists($absolute_thumb_path)) {
        return true;
    }

    $thumb_directory = dirname($absolute_thumb_path);
    if (!is_dir($thumb_directory)) {
        mkdir($thumb_directory, 0755, true);
    }

    return create_thumbnail_webp($absolute_original_path, $absolute_thumb_path, 480, 72);
}

function save_uploaded_product_image($file, $upload_dir, $field, $max_file_size, &$error)
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        $error = "Invalid upload for $field.";
        return false;
    }

    if ($file['size'] > $max_file_size) {
        $error = "File size for $field exceeds 5MB limit.";
        return false;
    }

    $image_info = @getimagesize($file['tmp_name']);
    if (!$image_info || empty($image_info['mime'])) {
        $error = "Invalid image file for $field.";
        return false;
    }

    $mime_type = $image_info['mime'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime_type, $allowed_types, true)) {
        $error = "Invalid file type for $field. Only JPG, PNG and WEBP are allowed.";
        return false;
    }

    $can_optimize = function_exists('imagewebp') && (
        ($mime_type === 'image/jpeg' && function_exists('imagecreatefromjpeg')) ||
        ($mime_type === 'image/png' && function_exists('imagecreatefrompng')) ||
        ($mime_type === 'image/webp' && function_exists('imagecreatefromwebp'))
    );

    if ($can_optimize) {
        $filename = uniqid($field . '_') . '.webp';
        $filepath = $upload_dir . $filename;

        if (!convert_image_to_webp($file['tmp_name'], $filepath, $mime_type, 82)) {
            $error = "Failed to optimize image for $field.";
            return false;
        }

        $thumb_filename = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.webp';
        $thumb_filepath = $upload_dir . $thumb_filename;
        create_thumbnail_webp($filepath, $thumb_filepath, 480, 72);

        return 'uploads/' . $filename;
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }
    if (!in_array($extension, ['jpg', 'png', 'webp'], true)) {
        $extension = 'jpg';
    }

    $filename = uniqid($field . '_') . '.' . $extension;
    $filepath = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        $error = "Failed to upload $field.";
        return false;
    }

    $thumb_filename = pathinfo($filename, PATHINFO_FILENAME) . '_thumb.webp';
    $thumb_filepath = $upload_dir . $thumb_filename;
    create_thumbnail_webp($filepath, $thumb_filepath, 480, 72);

    return 'uploads/' . $filename;
}
