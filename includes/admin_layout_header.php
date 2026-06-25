<?php
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo $pageTitle ?? 'Dashboard | NexGen Marketplace'; ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet">

    <!-- Custom Admin Style -->
    <style>
        :root {
            --sidebar-width: 260px;
            --primary-indigo: #6366f1;
            --sidebar-bg: #ffffff;
            --header-bg: #111827;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
            font-size: 0.9rem;
        }

        /* Layout Structure */
        #wrapper {
            display: flex;
            min-height: 100vh;
        }

        #sidebar {
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            border-right: 1px solid #e5e7eb;
            transition: all 0.3s;
            z-index: 1001;
        }

        #content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        /* Header */
        .navbar-main {
            background-color: var(--header-bg) !important;
            height: 64px;
            padding: 0 1.5rem;
        }

        .navbar-brand {
            font-weight: 800;
            letter-spacing: 1px;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Sidebar Menu */
        .sidebar-heading {
            padding: 1.5rem 1.5rem 0.5rem;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 0.05em;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu-item {
            padding: 0.25rem 1rem;
        }

        .sidebar-menu-button {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            color: #4b5563;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s;
            font-weight: 500;
        }

        .sidebar-menu-button:hover {
            background-color: #f3f4f6;
            color: var(--primary-indigo);
        }

        .sidebar-menu-item.active .sidebar-menu-button {
            background-color: #eef2ff;
            color: var(--primary-indigo);
        }

        .sidebar-menu-icon {
            margin-right: 12px;
            font-size: 20px;
        }

        /* Page Content */
        .page__container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
            width: 100%;
        }

        .page__heading {
            margin-bottom: 2rem;
        }

        .breadcrumb {
            background: transparent;
            padding: 0;
            margin-bottom: 0.5rem;
        }

        .breadcrumb-item a {
            color: var(--primary-indigo);
            text-decoration: none;
        }

        .card {
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            background: #fff;
        }

        /* Account Section in Sidebar */
        .sidebar-account {
            padding: 1.5rem;
            border-bottom: 1px solid #e5e7eb;
        }

        .avatar {
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6b7280;
        }
    </style>
</head>
<body>

<div id="wrapper">
    <!-- Sidebar -->
    <div id="sidebar">
        <div class="sidebar-account d-flex align-items-center">
            <div class="avatar me-3">
                <i class="material-icons">account_circle</i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold text-dark"><?php echo e($_SESSION['username'] ?? 'User'); ?></div>
                <small class="text-muted text-uppercase" style="font-size: 0.65rem;"><?php echo e($_SESSION['role'] ?? 'Staff'); ?></small>
            </div>
        </div>

        <div class="sidebar-heading">Menu</div>
        <ul class="sidebar-menu">
            <?php if(($_SESSION['role'] ?? '') === 'admin'): ?>
            <li class="sidebar-menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'admin/index.php') !== false ? 'active' : ''; ?>">
                <a class="sidebar-menu-button" href="<?php echo BASE_URL; ?>/admin/index.php">
                    <i class="sidebar-menu-icon material-icons">dashboard</i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'admin/manage_shops.php') !== false ? 'active' : ''; ?>">
                <a class="sidebar-menu-button" href="<?php echo BASE_URL; ?>/admin/manage_shops.php">
                    <i class="sidebar-menu-icon material-icons">store</i>
                    <span>Shops Control</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'admin/manage_categories.php') !== false ? 'active' : ''; ?>">
                <a class="sidebar-menu-button" href="<?php echo BASE_URL; ?>/admin/manage_categories.php">
                    <i class="sidebar-menu-icon material-icons">category</i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'admin/manage_tags.php') !== false ? 'active' : ''; ?>">
                <a class="sidebar-menu-button" href="<?php echo BASE_URL; ?>/admin/manage_tags.php">
                    <i class="sidebar-menu-icon material-icons">sell</i>
                    <span>Tags/Attributes</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'admin/marketing.php') !== false ? 'active' : ''; ?>">
                <a class="sidebar-menu-button" href="<?php echo BASE_URL; ?>/admin/marketing.php">
                    <i class="sidebar-menu-icon material-icons">campaign</i>
                    <span>Marketing</span>
                </a>
            </li>
            <?php else: ?>
            <li class="sidebar-menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'shop_portal.php') !== false ? 'active' : ''; ?>">
                <a class="sidebar-menu-button" href="<?php echo BASE_URL; ?>/shop_portal.php">
                    <i class="sidebar-menu-icon material-icons">dashboard</i>
                    <span>Shop Dashboard</span>
                </a>
            </li>
            <li class="sidebar-menu-item <?php echo strpos($_SERVER['PHP_SELF'], 'manage_products.php') !== false ? 'active' : ''; ?>">
                <a class="sidebar-menu-button" href="#">
                    <i class="sidebar-menu-icon material-icons">inventory_2</i>
                    <span>My Products</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a class="sidebar-menu-button" href="#">
                    <i class="sidebar-menu-icon material-icons">event_note</i>
                    <span>Enquiries</span>
                </a>
            </li>
            <?php endif; ?>
            <li class="sidebar-menu-item">
                <a class="sidebar-menu-button" href="<?php echo BASE_URL; ?>/index.php">
                    <i class="sidebar-menu-icon material-icons">shopping_bag</i>
                    <span>View Marketplace</span>
                </a>
            </li>
            <li class="sidebar-menu-item">
                <a class="sidebar-menu-button text-danger" href="<?php echo BASE_URL; ?>/logout.php">
                    <i class="sidebar-menu-icon material-icons">logout</i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div id="content">
        <!-- Header -->
        <nav class="navbar navbar-expand navbar-dark bg-dark navbar-main shadow-sm">
            <div class="container-fluid">
                <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
                    <i class="material-icons text-primary">local_hospital</i>
                    <span>NEXGEN <small class="text-muted fw-light" style="font-size: 0.6rem;">ADMIN</small></span>
                </a>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown">
                            <i class="material-icons me-2">account_circle</i>
                            <span><?php echo e($_SESSION['username'] ?? 'Admin'); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </nav>

        <div class="page__container">
