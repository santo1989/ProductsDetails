<?php
session_start();
require_once 'includes/db.php';

// Get product slug from URL
$slug = isset($_GET['slug']) ? sanitize_input($_GET['slug']) : '';

if (empty($slug)) {
    $_SESSION['error_message'] = 'Product not found.';
    redirect('index.php');
}

// Fetch product by slug
$stmt = $conn->prepare("SELECT * FROM products WHERE Product_URL = ?");
$stmt->bind_param("s", $slug);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error_message'] = 'Product not found.';
    $stmt->close();
    $conn->close();
    redirect('index.php');
}

$product = $result->fetch_assoc();
$page_title = $product['Product_Name'];

// Collect all images
$images = [];
if (!empty($product['Main_Image'])) $images[] = $product['Main_Image'];
if (!empty($product['Image1'])) $images[] = $product['Image1'];
if (!empty($product['Image2'])) $images[] = $product['Image2'];
if (!empty($product['Image3'])) $images[] = $product['Image3'];
if (!empty($product['Image4'])) $images[] = $product['Image4'];

// If no images, use placeholder
if (empty($images)) {
    $images[] = 'assets/images/placeholder.svg';
}

$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Products</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($product['Product_Name']); ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Images -->
        <div class="col-md-6">
            <div class="product-gallery">
                <!-- Main Image Display -->
                <div class="main-image-container mb-3">
                    <div id="productCarousel" class="carousel slide" data-bs-ride="carousel">
                        <div class="carousel-inner">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                         class="d-block w-100 main-product-image" 
                                         onerror="this.onerror=null;this.src='assets/images/placeholder.svg';"
                                         alt="<?php echo htmlspecialchars($product['Product_Name']); ?>">
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if (count($images) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Thumbnail Images -->
                <?php if (count($images) > 1): ?>
                    <div class="thumbnail-container">
                        <div class="row g-2">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="col">
                                    <img src="<?php echo htmlspecialchars($image); ?>" 
                                         class="img-thumbnail thumbnail-image <?php echo $index === 0 ? 'active' : ''; ?>" 
                                         alt="Thumbnail <?php echo $index + 1; ?>"
                                         onerror="this.onerror=null;this.src='assets/images/placeholder.svg';"
                                         data-bs-target="#productCarousel" 
                                         data-bs-slide-to="<?php echo $index; ?>"
                                         style="cursor: pointer; height: 80px; object-fit: cover;">
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Details -->
        <div class="col-md-6">
            <div class="product-details">
                <h1 class="mb-3"><?php echo htmlspecialchars($product['Product_Name']); ?></h1>
                
                <?php if (!empty($product['Tag'])): ?>
                    <div class="mb-3">
                        <span class="badge bg-primary fs-6"><?php echo htmlspecialchars($product['Tag']); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($product['Price'] > 0): ?>
                    <h2 class="text-primary mb-4">$<?php echo number_format($product['Price'], 2); ?></h2>
                <?php endif; ?>

                <div class="product-info">
                    <?php if (!empty($product['Description'])): ?>
                        <div class="mb-4">
                            <h5><i class="bi bi-card-text"></i> Description</h5>
                            <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['Description'])); ?></p>
                        </div>
                    <?php endif; ?>

                    <table class="table table-bordered">
                        <tbody>
                            <?php if (!empty($product['Category'])): ?>
                                <tr>
                                    <th width="35%"><i class="bi bi-tag"></i> Category</th>
                                    <td><?php echo htmlspecialchars($product['Category']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['Size'])): ?>
                                <tr>
                                    <th><i class="bi bi-rulers"></i> Size</th>
                                    <td><?php echo htmlspecialchars($product['Size']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['Color'])): ?>
                                <tr>
                                    <th><i class="bi bi-palette"></i> Color</th>
                                    <td><?php echo htmlspecialchars($product['Color']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['Fabrication'])): ?>
                                <tr>
                                    <th><i class="bi bi-scissors"></i> Fabrication</th>
                                    <td><?php echo htmlspecialchars($product['Fabrication']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['Construction'])): ?>
                                <tr>
                                    <th><i class="bi bi-gear"></i> Construction</th>
                                    <td><?php echo htmlspecialchars($product['Construction']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['GSM'])): ?>
                                <tr>
                                    <th><i class="bi bi-speedometer"></i> GSM</th>
                                    <td><?php echo htmlspecialchars($product['GSM']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['Finishes'])): ?>
                                <tr>
                                    <th><i class="bi bi-stars"></i> Finishes</th>
                                    <td><?php echo htmlspecialchars($product['Finishes']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['Buyer'])): ?>
                                <tr>
                                    <th><i class="bi bi-person"></i> Buyer</th>
                                    <td><?php echo htmlspecialchars($product['Buyer']); ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if (!empty($product['Style'])): ?>
                                <tr>
                                    <th><i class="bi bi-brush"></i> Style</th>
                                    <td><?php echo htmlspecialchars($product['Style']); ?></td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if (!empty($product['Tags'])): ?>
                        <div class="mb-3">
                            <h6><i class="bi bi-tags"></i> Tags</h6>
                            <?php 
                            $tags = explode(',', $product['Tags']);
                            foreach ($tags as $tag): 
                            ?>
                                <span class="badge bg-secondary me-1"><?php echo htmlspecialchars(trim($tag)); ?></span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="mt-4">
                        <a href="index.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Products
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
$conn->close();
include 'includes/footer.php'; 
?>
