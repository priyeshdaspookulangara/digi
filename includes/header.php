<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/seo_helper.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'NexGen Marketplace | Premium MLM Ecosystem'; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- Google Fonts: Inter & Montserrat -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Montserrat:wght@700;800&display=swap" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom Glassmorphism CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">

    <?php
    if (isset($seoTags)) {
        echo renderMetaTags($seoTags['title'], $seoTags['description'], $seoTags['image'], $seoTags['url'], $seoTags['keywords']);
    } else {
        echo renderMetaTags("NexGen Marketplace", "Modern MLM-based high-tech marketplace.", "", BASE_URL, "mlm, e-commerce, tech");
    }
    ?>
</head>
<body>
    <!-- Top Utility Bar -->
    <div class="top-bar py-2 d-none d-lg-block">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="location-selector glass-card py-1 px-3" style="border-radius: 50px;">
                <i class="fas fa-map-marker-alt me-2 neon-mint"></i>
                <span>Deliver to: <strong>Thrissur, KL</strong></span>
            </div>
            <div class="top-links d-flex gap-4">
                <a href="#"><i class="fas fa-headset me-1 neon-mint"></i> Support</a>
                <a href="#"><i class="fas fa-truck me-1 neon-lime"></i> Track Order</a>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top main-nav">
        <div class="container">
            <a class="navbar-brand neon-text" href="<?php echo BASE_URL; ?>/index.php">NEXGEN</a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="mainNavbar">
                <form class="mx-auto d-flex search-container my-2 my-lg-0" action="<?php echo BASE_URL; ?>/index.php" method="GET">
                    <input class="form-control search-input" type="search" name="search" placeholder="Search for products, brands and more" aria-label="Search" value="<?php echo e($_GET['search'] ?? ''); ?>">
                    <button class="btn search-btn" type="submit"><i class="fas fa-search"></i></button>
                </form>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/index.php"><i class="fas fa-store me-1 opacity-50"></i> Shop</a>
                    </li>
                    <?php if (is_logged_in()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/shop_portal.php"><i class="fas fa-briefcase me-1"></i> Merchant</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i> <?php echo e($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end glass-card">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/checkout.php">MLM Dashboard</a></li>
                                <?php if($_SESSION['role'] === 'admin'): ?>
                                    <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/index.php">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item ms-lg-3">
                            <a class="btn neon-button-sm" href="<?php echo BASE_URL; ?>/register.php">Join MLM</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item ms-lg-3">
                        <a class="nav-link cart-icon position-relative" href="#">
                            <i class="fas fa-shopping-cart opacity-75"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
