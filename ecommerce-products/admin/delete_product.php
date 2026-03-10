<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to delete products.';
    redirect('dashboard.php');
}

// Get product ID
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($product_id === 0) {
    $_SESSION['error_message'] = 'Invalid product ID.';
    redirect('dashboard.php');
}

// Fetch product to get image paths
$stmt = $conn->prepare("SELECT Main_Image, Image1, Image2, Image3, Image4 FROM products WHERE ID = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Product not found.';
    $stmt->close();
    $conn->close();
    redirect('dashboard.php');
}

$product = $result->fetch_assoc();
$stmt->close();

// Delete product images from server
$images = [$product['Main_Image'], $product['Image1'], $product['Image2'], $product['Image3'], $product['Image4']];
foreach ($images as $image) {
    if (!empty($image) && file_exists('../' . $image)) {
        unlink('../' . $image);
    }
}

// Delete product from database
$stmt = $conn->prepare("DELETE FROM products WHERE ID = ?");
$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = 'Product deleted successfully.';
} else {
    $_SESSION['error_message'] = 'Failed to delete product.';
}

$stmt->close();
$conn->close();
redirect('dashboard.php');
