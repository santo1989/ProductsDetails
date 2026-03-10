    </main>

    <!-- Footer -->
    <footer class="bg-dark text-white mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5><img src="<?php echo isset($base_url) ? $base_url : ''; ?>assets/logo/logo.png" alt="Tosrifa Industries Ltd" class="brand-logo me-2">Tosrifa Industries Ltd</h5>
                    <p>Your trusted source for quality garment products. Displaying company products with detailed specifications.</p>
                </div>
                <div class="col-md-3">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>index.php" class="text-white text-decoration-none">Products</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>admin/dashboard.php" class="text-white text-decoration-none">Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo isset($base_url) ? $base_url : ''; ?>admin/index.php" class="text-white text-decoration-none">Login</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <p>
                        <strong>Tosrifa Industries Ltd</strong><br>
                        Corporate &amp; Finance Office<br>
                        Holding No 4/2 A, Plot 49 &amp; 57 135<br>
                        Gopalpur Munnu Nagar, Tongi,<br>
                        Gazipur Bangladesh<br>
                        <i class="bi bi-telephone"></i> Phone: 8802224410051, 02224410052, 02224410053, 02224410054<br>
                        FAX: 880-2-9817743
                    </p>
                </div>
            </div>
            <hr class="bg-white">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Tosrifa Industries Ltd. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JS -->
    <script src="<?php echo isset($base_url) ? $base_url : ''; ?>assets/js/script.js"></script>
    </body>

    </html>