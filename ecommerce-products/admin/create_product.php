<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error_message'] = 'Please login to create products.';
    redirect('index.php');
}

$base_url = '../';
$page_title = 'Create Product';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_name = sanitize_input($_POST['product_name']);
    $category = sanitize_input($_POST['category']);
    $size = sanitize_input($_POST['size']);
    $description = sanitize_input($_POST['description']);
    $fabrication = sanitize_input($_POST['fabrication']);
    $construction = sanitize_input($_POST['construction']);
    $gsm = sanitize_input($_POST['gsm']);
    $finishes = sanitize_input($_POST['finishes']);
    $color = sanitize_input($_POST['color']);
    $buyer = sanitize_input($_POST['buyer']);
    $style = sanitize_input($_POST['style']);
    $tags = sanitize_input($_POST['tags']);
    $tag = sanitize_input($_POST['tag']);
    $price = floatval($_POST['price']);
    $product_url = generate_slug($product_name);
    $created_by = $_SESSION['user_id'];

    // Validate required fields
    if (empty($product_name)) {
        $error = 'Product name is required.';
    } else {
        // Check if product URL already exists
        $stmt = $conn->prepare("SELECT ID FROM products WHERE Product_URL = ?");
        $stmt->bind_param("s", $product_url);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $product_url .= '-' . time(); // Make it unique
        }
        $stmt->close();

        // Handle image uploads
        $upload_dir = '../uploads/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
        $max_file_size = 5 * 1024 * 1024; // 5MB

        $image_paths = [];
        $image_fields = ['main_image', 'image1', 'image2', 'image3', 'image4'];

        foreach ($image_fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                
                // Validate file type
                if (!in_array($file['type'], $allowed_types)) {
                    $error = "Invalid file type for $field. Only JPG and PNG are allowed.";
                    break;
                }
                
                // Validate file size
                if ($file['size'] > $max_file_size) {
                    $error = "File size for $field exceeds 5MB limit.";
                    break;
                }
                
                // Generate unique filename
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = uniqid($field . '_') . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    $image_paths[$field] = 'uploads/' . $filename;
                } else {
                    $error = "Failed to upload $field.";
                    break;
                }
            } else {
                $image_paths[$field] = '';
            }
        }

        // Insert product if no errors
        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO products (Product_Name, Category, Size, Description, Fabrication, 
                                    Construction, GSM, Finishes, Color, Buyer, Style, Tags, Tag, Price, 
                                    Main_Image, Image1, Image2, Image3, Image4, Product_URL, Created_By) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->bind_param("sssssssssssssdssssssi", 
                $product_name, $category, $size, $description, $fabrication,
                $construction, $gsm, $finishes, $color, $buyer, $style, $tags, $tag, $price,
                $image_paths['main_image'], $image_paths['image1'], $image_paths['image2'], 
                $image_paths['image3'], $image_paths['image4'], $product_url, $created_by
            );
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Product created successfully!';
                $stmt->close();
                $conn->close();
                redirect('dashboard.php');
            } else {
                $error = 'Failed to create product. Please try again.';
            }
            
            $stmt->close();
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-5">
                <i class="bi bi-plus-circle"></i> Create New Product
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active">Create Product</li>
                </ol>
            </nav>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow">
        <div class="card-body p-4">
            <form method="POST" action="" enctype="multipart/form-data">
                <div class="row">
                    <!-- Basic Information -->
                    <div class="col-md-12 mb-4">
                        <h4 class="border-bottom pb-2"><i class="bi bi-info-circle"></i> Basic Information</h4>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="product_name" class="form-label">Product Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="product_name" name="product_name" required>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="category" class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" name="category" placeholder="e.g., Apparel">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="size" class="form-label">Size</label>
                        <input type="text" class="form-control" id="size" name="size" placeholder="e.g., S, M, L, XL">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="text" class="form-control" id="color" name="color" placeholder="e.g., Blue">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="price" class="form-label">Price ($)</label>
                        <input type="number" step="0.01" class="form-control" id="price" name="price" value="0">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="tag" class="form-label">Tag</label>
                        <input type="text" class="form-control" id="tag" name="tag" placeholder="e.g., New Arrival, Bestseller">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="4"></textarea>
                    </div>

                    <!-- Technical Specifications -->
                    <div class="col-md-12 mb-4 mt-3">
                        <h4 class="border-bottom pb-2"><i class="bi bi-gear"></i> Technical Specifications</h4>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="fabrication" class="form-label">Fabrication</label>
                        <input type="text" class="form-control" id="fabrication" name="fabrication" placeholder="e.g., 100% Cotton">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="construction" class="form-label">Construction</label>
                        <input type="text" class="form-control" id="construction" name="construction" placeholder="e.g., Single Jersey">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="gsm" class="form-label">GSM</label>
                        <input type="text" class="form-control" id="gsm" name="gsm" placeholder="e.g., 180">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="finishes" class="form-label">Finishes</label>
                        <input type="text" class="form-control" id="finishes" name="finishes" placeholder="e.g., Bio-washed">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="buyer" class="form-label">Buyer</label>
                        <input type="text" class="form-control" id="buyer" name="buyer" placeholder="e.g., H&M">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="style" class="form-label">Style</label>
                        <input type="text" class="form-control" id="style" name="style" placeholder="e.g., Casual">
                    </div>

                    <div class="col-md-12 mb-3">
                        <label for="tags" class="form-label">Tags (comma-separated)</label>
                        <input type="text" class="form-control" id="tags" name="tags" placeholder="e.g., cotton, t-shirt, casual">
                    </div>

                    <!-- Product Images -->
                    <div class="col-md-12 mb-4 mt-3">
                        <h4 class="border-bottom pb-2"><i class="bi bi-images"></i> Product Images</h4>
                        <p class="text-muted small">Allowed formats: JPG, PNG (Max 5MB per image)</p>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="main_image" class="form-label">Main Image</label>
                        <input type="file" class="form-control" id="main_image" name="main_image" accept="image/jpeg,image/jpg,image/png">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="image1" class="form-label">Additional Image 1</label>
                        <input type="file" class="form-control" id="image1" name="image1" accept="image/jpeg,image/jpg,image/png">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="image2" class="form-label">Additional Image 2</label>
                        <input type="file" class="form-control" id="image2" name="image2" accept="image/jpeg,image/jpg,image/png">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="image3" class="form-label">Additional Image 3</label>
                        <input type="file" class="form-control" id="image3" name="image3" accept="image/jpeg,image/jpg,image/png">
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="image4" class="form-label">Additional Image 4</label>
                        <input type="file" class="form-control" id="image4" name="image4" accept="image/jpeg,image/jpg,image/png">
                    </div>
                </div>

                <!-- Submit Buttons -->
                <hr class="my-4">
                <div class="d-flex justify-content-between">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Cancel
                    </a>
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="bi bi-check-circle"></i> Create Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php 
$conn->close();
include '../includes/footer.php'; 
?>
