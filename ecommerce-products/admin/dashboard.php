<?php
session_start();
require_once '../includes/db.php';

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['error_message'] = 'Please login to access the dashboard.';
    redirect('index.php');
}

$base_url = '../';
$page_title = 'Dashboard';

// Fetch products - admins see all, users see only their own
if (is_admin()) {
    $sql = "SELECT p.*, u.Username as Creator 
            FROM products p 
            LEFT JOIN users u ON p.Created_By = u.ID 
            ORDER BY p.Created_At DESC";
    $result = $conn->query($sql);
} else {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT p.*, u.Username as Creator 
                            FROM products p 
                            LEFT JOIN users u ON p.Created_By = u.ID 
                            WHERE p.Created_By = ? 
                            ORDER BY p.Created_At DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
}
?>

<?php include '../includes/header.php'; ?>

<div class="container my-5">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="display-5">
                <i class="bi bi-speedometer2"></i> Dashboard
            </h1>
            <p class="lead">Manage your products</p>
        </div>
        <div class="col-md-4 text-end">
            <?php if (is_admin()): ?>
                <form method="POST" action="reset_demo_images.php" class="d-inline">
                    <button type="submit" class="btn btn-outline-primary me-2" onclick="return confirm('Reset demo product images to Unsplash URLs?');">
                        <i class="bi bi-arrow-repeat"></i> Reset Demo Images
                    </button>
                </form>
            <?php endif; ?>
            <a href="create_product.php" class="btn btn-success btn-lg">
                <i class="bi bi-plus-circle"></i> Add New Product
            </a>
        </div>
    </div>

    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="bi bi-list"></i> Products List
                <?php if (!is_admin()): ?>
                    <span class="badge bg-warning text-dark">My Products</span>
                <?php else: ?>
                    <span class="badge bg-light text-dark">All Products</span>
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Product Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <?php if (is_admin()): ?>
                                    <th>Created By</th>
                                <?php endif; ?>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $product['ID']; ?></td>
                                    <td>
                                        <?php 
                                        $thumb = !empty($product['Main_Image']) ? $product['Main_Image'] : '../assets/images/placeholder.svg';
                                        ?>
                                        <img src="<?php echo htmlspecialchars($thumb); ?>" 
                                             alt="<?php echo htmlspecialchars($product['Product_Name']); ?>"
                                            onerror="this.onerror=null;this.src='../assets/images/placeholder.svg';"
                                             style="width: 50px; height: 50px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($product['Product_Name']); ?></strong>
                                        <?php if (!empty($product['Tag'])): ?>
                                            <br><span class="badge bg-info text-dark"><?php echo htmlspecialchars($product['Tag']); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['Category']); ?></td>
                                    <td>
                                        <?php if ($product['Price'] > 0): ?>
                                            <strong>$<?php echo number_format($product['Price'], 2); ?></strong>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <?php if (is_admin()): ?>
                                        <td><?php echo htmlspecialchars($product['Creator'] ?? 'Unknown'); ?></td>
                                    <?php endif; ?>
                                    <td><?php echo date('M d, Y', strtotime($product['Created_At'])); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="../details.php?slug=<?php echo urlencode($product['Product_URL']); ?>" 
                                               class="btn btn-sm btn-info" 
                                               title="View" 
                                               target="_blank">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <a href="edit_product.php?id=<?php echo $product['ID']; ?>" 
                                               class="btn btn-sm btn-warning" 
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <?php if (is_admin()): ?>
                                                <a href="delete_product.php?id=<?php echo $product['ID']; ?>" 
                                                   class="btn btn-sm btn-danger" 
                                                   title="Delete"
                                                   onclick="return confirm('Are you sure you want to delete this product?');">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="bi bi-info-circle"></i> No products found. 
                    <a href="create_product.php">Create your first product</a>.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php 
if (isset($stmt)) $stmt->close();
$conn->close();
include '../includes/footer.php'; 
?>
