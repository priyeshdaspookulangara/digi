    <footer class="mt-5 py-5 glass-card rounded-0 border-start-0 border-end-0 border-bottom-0">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h3 class="neon-text mb-4">NEXGEN</h3>
                    <p class="opacity-75">Revolutionizing the marketplace through integrated MLM ecosystems. Quality meets opportunity in our tech-driven platform.</p>
                    <div class="social-links d-flex gap-3 mt-4">
                        <a href="#" class="glass-btn-circle"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="glass-btn-circle"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="glass-btn-circle"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="glass-btn-circle"><i class="fab fa-whatsapp"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="mb-4 fw-bold">Marketplace</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#">Categories</a></li>
                        <li><a href="#">Featured Products</a></li>
                        <li><a href="#">Latest Deals</a></li>
                        <li><a href="#">Brands</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h5 class="mb-4 fw-bold">Company</h5>
                    <ul class="list-unstyled footer-links">
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Contact Support</a></li>
                        <li><a href="#">Privacy Policy</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 col-md-6">
                    <h5 class="mb-4 fw-bold">Newsletter</h5>
                    <p class="small opacity-75">Stay updated with the latest products and MLM milestones.</p>
                    <div class="input-group mb-3 glass-input-group">
                        <input type="email" class="form-control" placeholder="Email Address">
                        <button class="btn neon-button-sm" type="button">Subscribe</button>
                    </div>
                </div>
            </div>
            <hr class="neon-line my-5">
            <div class="text-center opacity-50 small">
                <p>&copy; <?php echo date('Y'); ?> NexGen Integrated Marketplace & MLM. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Animations / Logic -->
    <script src="<?php echo BASE_URL; ?>/assets/js/main.js"></script>

    <script>
    $(document).ready(function() {
        const csrfToken = '<?php echo get_csrf_token(); ?>';

        // Load initial cart count
        $.post('<?php echo BASE_URL; ?>/cart_handler.php', {action: 'get_count', csrf_token: csrfToken}, function(response) {
            if (response.success) {
                $('.cart-icon .badge').text(response.total);
            }
        });

        // Add to Cart
        $(document).on('click', '.add-to-cart-btn', function(e) {
            e.preventDefault();
            const productId = $(this).data('id');
            $.post('<?php echo BASE_URL; ?>/cart_handler.php', {action: 'add', product_id: productId, quantity: 1, csrf_token: csrfToken}, function(response) {
                if (response.success) {
                    $('.cart-icon .badge').text(response.total);
                    alert('Product added to cart!');
                } else {
                    alert(response.message);
                }
            });
        });

        // Wishlist Toggle
        $(document).on('click', '.wishlist-btn', function(e) {
            e.preventDefault();
            const productId = $(this).data('id');
            const btn = $(this);
            $.post('<?php echo BASE_URL; ?>/wishlist_handler.php', {action: 'toggle', product_id: productId, csrf_token: csrfToken}, function(response) {
                if (response.success) {
                    if (response.status === 'added') {
                        btn.find('i').removeClass('far').addClass('fas text-danger');
                    } else {
                        btn.find('i').removeClass('fas text-danger').addClass('far');
                    }
                } else {
                    alert(response.message);
                }
            });
        });
    });
    </script>
</body>
</html>
