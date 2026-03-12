<?php
session_start();
require_once 'includes/db.php';

$page_title = 'Products Gallery';

// Fetch all products from database
$sql = "SELECT * FROM products ORDER BY Created_At DESC";
$result = $conn->query($sql);

$autoRefreshMeta = ['last_update' => 0, 'total' => 0, 'scope' => 'all'];
$metaResult = $conn->query("SELECT COALESCE(UNIX_TIMESTAMP(MAX(COALESCE(Updated_At, Created_At))), 0) AS last_update, COUNT(*) AS total FROM products");
if ($metaResult && $metaRow = $metaResult->fetch_assoc()) {
    $autoRefreshMeta['last_update'] = (int) ($metaRow['last_update'] ?? 0);
    $autoRefreshMeta['total'] = (int) ($metaRow['total'] ?? 0);
    $metaResult->free();
}
?>

<?php include 'includes/header.php'; ?>

<div class="container my-5">
    <div data-product-autorefresh="1" data-interval="20" data-scope="<?php echo htmlspecialchars($autoRefreshMeta['scope']); ?>" data-last-update="<?php echo (int) $autoRefreshMeta['last_update']; ?>" data-total="<?php echo (int) $autoRefreshMeta['total']; ?>"></div>
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-4">
                <i class="bi bi-grid-3x3-gap heading-circle-icon"></i> Products Gallery
            </h1>
            <p class="lead">Browse our complete collection of quality garment products</p>
        </div>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            <?php while ($product = $result->fetch_assoc()): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm product-card">
                        <?php
                        $main_image = !empty($product['Main_Image']) ? get_optimized_image_for_display($product['Main_Image']) : 'assets/images/placeholder.svg';
                        ?>
                        <img src="<?php echo htmlspecialchars($main_image); ?>"
                            class="card-img-top"
                            loading="lazy"
                            decoding="async"
                            alt="<?php echo htmlspecialchars($product['Product_Name']); ?>"
                            onerror="this.onerror=null;this.src='assets/images/placeholder.svg';"
                            style="height: 250px; object-fit: cover;">

                        <?php if (!empty($product['Tag'])): ?>
                            <div class="position-absolute top-0 end-0 m-2">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($product['Tag']); ?></span>
                            </div>
                        <?php endif; ?>

                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($product['Product_Name']); ?></h5>

                            <p class="card-text">
                                <?php if (!empty($product['Category'])): ?>
                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($product['Category']); ?></span>
                                <?php endif; ?>
                                <?php if (!empty($product['Color'])): ?>
                                    <span class="badge bg-info text-dark"><?php echo htmlspecialchars($product['Color']); ?></span>
                                <?php endif; ?>
                            </p>

                            <p class="card-text text-truncate" style="max-height: 48px;">
                                <?php echo htmlspecialchars(substr($product['Description'], 0, 100)); ?>...
                            </p>

                            <?php if ($product['Price'] > 0): ?>
                                <p class="card-text">
                                    <strong class="text-primary fs-5">$<?php echo number_format($product['Price'], 2); ?></strong>
                                </p>
                            <?php endif; ?>
                        </div>

                        <div class="card-footer bg-transparent">
                            <a href="details.php?slug=<?php echo urlencode($product['Product_URL']); ?>"
                                class="btn btn-primary w-100">
                                <i class="bi bi-eye"></i> View Details
                            </a>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle"></i> No products available at the moment. Please check back later.
        </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
include 'includes/footer.php';
?>