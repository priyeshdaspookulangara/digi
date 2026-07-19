<?php
require_once 'includes/config.php';
require_role('agent');

$success_count = 0;
$error_count = 0;
$messages = [];

// Handle Bulk CSV/Text Import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_import'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    $csv_data_string = '';

    // Handle File Upload
    if (isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
        $csv_data_string = file_get_contents($_FILES['csv_file']['tmp_name']);
    } elseif (!empty($_POST['csv_text'])) {
        // Handle Pasted Text
        $csv_data_string = $_POST['csv_text'];
    }

    if (!empty($csv_data_string)) {
        // Parse CSV text/file
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $csv_data_string);
        rewind($stream);

        $headers = fgetcsv($stream);
        if ($headers) {
            $headers = array_map(function($h) { return trim(strtolower($h)); }, $headers);

            // Check for required headers
            $required_headers = ['shop_name', 'owner_username'];
            $missing = array_diff($required_headers, $headers);

            if (!empty($missing)) {
                $messages[] = ['type' => 'danger', 'text' => 'CSV is missing required headers: ' . implode(', ', $missing)];
            } else {
                try {
                    $db_in_transaction = $pdo->inTransaction();
                    if (!$db_in_transaction) {
                        $pdo->beginTransaction();
                    }

                    while (($row = fgetcsv($stream)) !== FALSE) {
                        if (count($row) === 0 || (count($row) === 1 && empty($row[0]))) {
                            continue; // skip empty rows
                        }
                        if (count($row) < count($headers)) {
                            $row = array_pad($row, count($headers), '');
                        }
                        $data = array_combine($headers, array_slice($row, 0, count($headers)));

                        // Extract parameters
                        $shop_name = trim($data['shop_name'] ?? '');
                        $owner_username = trim($data['owner_username'] ?? '');

                        if (empty($shop_name) || empty($owner_username)) {
                            $error_count++;
                            $messages[] = ['type' => 'warning', 'text' => "Skipped row: Missing 'shop_name' or 'owner_username'."];
                            continue;
                        }

                        $description = trim($data['description'] ?? '');
                        $locality = trim($data['locality'] ?? '');
                        $address = trim($data['address'] ?? '');
                        $city = trim($data['city'] ?? '');
                        $district = trim($data['district'] ?? '');
                        $pincode = trim($data['pincode'] ?? '');
                        $type = strtolower(trim($data['type'] ?? 'free_listing'));
                        if (!in_array($type, ['privilege', 'classic', 'free_listing'])) {
                            $type = 'free_listing';
                        }

                        $owner_email = trim($data['owner_email'] ?? '');
                        if (empty($owner_email)) {
                            $owner_email = $owner_username . '@example.com';
                        }
                        $owner_password = trim($data['owner_password'] ?? '');
                        if (empty($owner_password)) {
                            $owner_password = 'Pass123!';
                        }
                        $owner_mobile = trim($data['owner_mobile'] ?? '');
                        $category_name = trim($data['category'] ?? '');

                        // 1. Find or create User
                        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
                        $stmt->execute([$owner_username]);
                        $owner_id = $stmt->fetchColumn();

                        if (!$owner_id) {
                            // Check email uniqueness to prevent SQL violation
                            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                            $stmt->execute([$owner_email]);
                            if ($stmt->fetchColumn()) {
                                $error_count++;
                                $messages[] = ['type' => 'danger', 'text' => "User creation failed for '{$owner_username}': Email '{$owner_email}' already exists."];
                                continue;
                            }

                            // Create user with role 'shop_owner'
                            $hashed_pass = password_hash($owner_password, PASSWORD_DEFAULT);
                            $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password, mobile) VALUES (?, ?, 'shop_owner', ?, ?)");
                            $stmt->execute([$owner_username, $owner_email, $hashed_pass, $owner_mobile]);
                            $owner_id = $pdo->lastInsertId();

                            // Add to MLM hierarchy
                            $stmt = $pdo->prepare("INSERT INTO mlm_hierarchy (user_id, parent_id, level_in_tree) VALUES (?, NULL, 1)");
                            $stmt->execute([$owner_id]);
                        }

                        // 2. Find or create Category
                        $category_id = null;
                        if (!empty($category_name)) {
                            $stmt = $pdo->prepare("SELECT id FROM shop_categories WHERE name = ?");
                            $stmt->execute([$category_name]);
                            $category_id = $stmt->fetchColumn();

                            if (!$category_id) {
                                $stmt = $pdo->prepare("INSERT INTO shop_categories (name) VALUES (?)");
                                $stmt->execute([$category_name]);
                                $category_id = $pdo->lastInsertId();
                            }
                        }

                        // 3. Create Shop
                        $stmt = $pdo->prepare("INSERT INTO shops (owner_id, name, description, locality, address, city, district, pincode, type, category, added_by_agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$owner_id, $shop_name, $description, $locality, $address, $city, $district, $pincode, $type, $category_name, $_SESSION['user_id']]);
                        $shop_id = $pdo->lastInsertId();

                        // 4. Map Category
                        if ($category_id) {
                            $stmt = $pdo->prepare("INSERT INTO shop_category_map (shop_id, category_id) VALUES (?, ?)");
                            $stmt->execute([$shop_id, $category_id]);
                        }

                        $success_count++;
                    }

                    if (!$db_in_transaction && $pdo->inTransaction()) {
                        $pdo->commit();
                    }
                    $messages[] = ['type' => 'success', 'text' => "Import completed! Successfully added {$success_count} shops. Errors/Warnings: {$error_count}."];
                } catch (Exception $e) {
                    if (!$db_in_transaction && $pdo->inTransaction()) {
                        $pdo->rollBack();
                    }
                    $messages[] = ['type' => 'danger', 'text' => "Transaction rolled back due to error: " . $e->getMessage()];
                }
            }
        } else {
            $messages[] = ['type' => 'danger', 'text' => 'Failed to parse CSV headers.'];
        }
        fclose($stream);
    } else {
        $messages[] = ['type' => 'warning', 'text' => 'Please upload a CSV file or paste CSV text.'];
    }
}

// Handle Single Shop Addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_single_shop'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    $owner_username = trim($_POST['owner_username'] ?? '');
    $owner_email = trim($_POST['owner_email'] ?? '');
    $owner_password = trim($_POST['owner_password'] ?? '');
    $owner_mobile = trim($_POST['owner_mobile'] ?? '');

    $shop_name = trim($_POST['name'] ?? '');
    $type = trim($_POST['type'] ?? 'free_listing');
    if (!in_array($type, ['privilege', 'classic', 'free_listing'])) {
        $type = 'free_listing';
    }
    $locality = trim($_POST['locality'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $description = trim($_POST['description'] ?? '');

    $selected_categories = $_POST['categories'] ?? [];
    $selected_tags = $_POST['tags'] ?? [];

    $meta_keywords = trim($_POST['meta_keywords'] ?? '');
    $og_title = trim($_POST['og_title'] ?? '');
    $og_description = trim($_POST['og_description'] ?? '');
    $social_links = trim($_POST['social_links'] ?? '');

    if (empty($shop_name) || empty($owner_username)) {
        $messages[] = ['type' => 'danger', 'text' => "Shop Name and Owner Username are required fields."];
    } else {
        try {
            $pdo->beginTransaction();

            // 1. Find or create Owner User
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$owner_username]);
            $owner_id = $stmt->fetchColumn();

            if (!$owner_id) {
                if (empty($owner_email)) {
                    $owner_email = $owner_username . '@example.com';
                }
                // Check email uniqueness to prevent unique constraint violation
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$owner_email]);
                if ($stmt->fetchColumn()) {
                    throw new Exception("User creation failed. Email '{$owner_email}' already exists.");
                }

                if (empty($owner_password)) {
                    $owner_password = 'Pass123!';
                }
                $hashed_pass = password_hash($owner_password, PASSWORD_DEFAULT);

                // Create owner
                $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password, mobile) VALUES (?, ?, 'shop_owner', ?, ?)");
                $stmt->execute([$owner_username, $owner_email, $hashed_pass, $owner_mobile]);
                $owner_id = $pdo->lastInsertId();

                // Add to MLM hierarchy
                $stmt = $pdo->prepare("INSERT INTO mlm_hierarchy (user_id, parent_id, level_in_tree) VALUES (?, NULL, 1)");
                $stmt->execute([$owner_id]);
            }

            // 2. Handle File Uploads
            $upload_dir = 'uploads/shops/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $logo_path = '';
            if (isset($_FILES['shop_logo']) && $_FILES['shop_logo']['error'] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['shop_logo']['tmp_name']);
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($mime, $allowed)) {
                    $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
                    $filename = uniqid('logo_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['shop_logo']['tmp_name'], $upload_dir . $filename)) {
                        $logo_path = $upload_dir . $filename;
                    }
                }
            }

            $wallpaper_path = '';
            if (isset($_FILES['shop_wallpaper']) && $_FILES['shop_wallpaper']['error'] === UPLOAD_ERR_OK) {
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($_FILES['shop_wallpaper']['tmp_name']);
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                if (in_array($mime, $allowed)) {
                    $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
                    $filename = uniqid('wp_') . '.' . $ext;
                    if (move_uploaded_file($_FILES['shop_wallpaper']['tmp_name'], $upload_dir . $filename)) {
                        $wallpaper_path = $upload_dir . $filename;
                    }
                }
            }

            // 3. Category for backward compatibility (first checked)
            $category_name = '';
            if (!empty($selected_categories)) {
                $stmt = $pdo->prepare("SELECT name FROM shop_categories WHERE id = ?");
                $stmt->execute([$selected_categories[0]]);
                $category_name = $stmt->fetchColumn() ?: '';
            }

            // Business Rules for Tiers
            if ($type === 'free_listing') {
                if (count($selected_categories) > 2) {
                    $selected_categories = array_slice($selected_categories, 0, 2);
                }
                $selected_tags = [];
                $meta_keywords = $og_title = $og_description = $social_links = '';
                $wallpaper_path = ''; // No wallpaper for free listing
            }

            // 4. Create Shop
            $stmt = $pdo->prepare("INSERT INTO shops (owner_id, name, description, logo, wallpaper, meta_keywords, og_title, og_description, locality, category, type, social_links, address, city, district, pincode, added_by_agent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $owner_id, $shop_name, $description, $logo_path, $wallpaper_path,
                $meta_keywords, $og_title, $og_description, $locality, $category_name,
                $type, $social_links, $address, $city, $district, $pincode, $_SESSION['user_id']
            ]);
            $shop_id = $pdo->lastInsertId();

            // 5. Map Categories
            if (!empty($selected_categories)) {
                $stmt = $pdo->prepare("INSERT INTO shop_category_map (shop_id, category_id) VALUES (?, ?)");
                foreach ($selected_categories as $cat_id) {
                    $stmt->execute([$shop_id, $cat_id]);
                }
            }

            // 6. Map Tags
            if (!empty($selected_tags) && $type !== 'free_listing') {
                $stmt = $pdo->prepare("INSERT INTO shop_tag_map (shop_id, tag_id) VALUES (?, ?)");
                foreach ($selected_tags as $tag_id) {
                    $stmt->execute([$shop_id, $tag_id]);
                }
            }

            $pdo->commit();
            $messages[] = ['type' => 'success', 'text' => "Shop '{$shop_name}' created successfully for owner '{$owner_username}'!"];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $messages[] = ['type' => 'danger', 'text' => "Failed to create shop: " . $e->getMessage()];
        }
    }
}

// Fetch all registered shops added by current agent (restricted from others)
$stmt = $pdo->prepare("SELECT s.*, u.username as owner_name FROM shops s JOIN users u ON s.owner_id = u.id WHERE s.added_by_agent_id = ? ORDER BY s.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$shops = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM shop_categories ORDER BY name ASC")->fetchAll();
$tags = $pdo->query("SELECT * FROM shop_tags ORDER BY name ASC")->fetchAll();
$localities = $pdo->query("SELECT name FROM localities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$csrf_token = get_csrf_token();
$pageTitle = "Agent Portal | NexGen Marketplace";
require_once 'includes/admin_layout_header.php';
?>

<div class="page__heading d-flex align-items-center mb-4">
    <div class="flex-grow-1">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Agent Portal</li>
            </ol>
        </nav>
        <h1 class="m-0 text-dark">Agent Portal <small class="text-muted fw-light" style="font-size: 1rem;">(Shop Registrar)</small></h1>
    </div>
</div>

<?php foreach ($messages as $msg): ?>
    <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo e($msg['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach; ?>

<div class="row g-4">
    <!-- Registration Options Column -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold text-dark"><i class="material-icons align-middle me-2 text-primary">post_add</i>Register Shops</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="importTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-secondary" id="single-tab" data-bs-toggle="tab" data-bs-target="#single-import" type="button" role="tab" aria-controls="single-import" aria-selected="true">
                            <i class="material-icons align-middle me-1">storefront</i> Add Single Shop
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-secondary" id="file-tab" data-bs-toggle="tab" data-bs-target="#file-import" type="button" role="tab" aria-controls="file-import" aria-selected="false">
                            <i class="material-icons align-middle me-1">upload_file</i> Upload CSV File
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold text-secondary" id="paste-tab" data-bs-toggle="tab" data-bs-target="#paste-import" type="button" role="tab" aria-controls="paste-import" aria-selected="false">
                            <i class="material-icons align-middle me-1">content_paste</i> Paste CSV Text
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="importTabContent">
                    <!-- Add Single Shop Tab -->
                    <div class="tab-pane fade show active" id="single-import" role="tabpanel" aria-labelledby="single-tab">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <!-- Merchant Owner Info -->
                            <div class="p-3 bg-light rounded mb-4 border">
                                <h6 class="fw-bold mb-3 text-indigo d-flex align-items-center">
                                    <i class="material-icons me-2">person</i> MERCHANT ACCOUNT CREDENTIALS
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Owner Username *</label>
                                        <input type="text" name="owner_username" class="form-control" placeholder="e.g. johndoe" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Owner Email</label>
                                        <input type="email" name="owner_email" class="form-control" placeholder="e.g. john@example.com">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Owner Password</label>
                                        <input type="password" name="owner_password" class="form-control" placeholder="Default is Pass123!">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Owner Mobile</label>
                                        <input type="text" name="owner_mobile" class="form-control" placeholder="e.g. 9876543210">
                                    </div>
                                </div>
                            </div>

                            <!-- Shop Info -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Shop Name *</label>
                                    <input type="text" name="name" class="form-control" placeholder="e.g. CyberPulse Store" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Shop Tier / Type *</label>
                                    <select name="type" id="shop_tier" class="form-select" required>
                                        <option value="free_listing" selected>Free Listing</option>
                                        <option value="classic">Classic</option>
                                        <option value="privilege">Privilege</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Categories</label>
                                    <div class="border p-2 rounded bg-white" style="max-height: 150px; overflow-y: auto;">
                                        <?php foreach ($categories as $cat): ?>
                                            <div class="form-check">
                                                <input class="form-check-input category-checkbox-single" type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" id="single_cat_<?php echo $cat['id']; ?>">
                                                <label class="form-check-label small" for="single_cat_<?php echo $cat['id']; ?>"><?php echo e($cat['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted d-block mt-1 tier-limit-warning" style="font-size: 0.75rem;">Free listings are limited to 2 categories.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Attributes / Tags</label>
                                    <div class="border p-2 rounded bg-white" id="tags_container" style="max-height: 150px; overflow-y: auto;">
                                        <?php foreach ($tags as $tag): ?>
                                            <div class="form-check">
                                                <input class="form-check-input tag-checkbox-single" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="single_tag_<?php echo $tag['id']; ?>">
                                                <label class="form-check-label small" for="single_tag_<?php echo $tag['id']; ?>"><?php echo e($tag['name']); ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <small class="text-muted d-block mt-1 tag-limit-warning text-danger d-none" style="font-size: 0.75rem;">Tags are locked for Free Listing tier.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Locality / Area</label>
                                    <select name="locality" class="form-select">
                                        <option value="">Select Locality</option>
                                        <?php foreach ($localities as $loc): ?>
                                            <option value="<?php echo e($loc); ?>"><?php echo e($loc); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">City</label>
                                    <input type="text" name="city" class="form-control" placeholder="e.g. Chalakudy">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">District</label>
                                    <input type="text" name="district" class="form-control" placeholder="e.g. Thrissur">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Pincode</label>
                                    <input type="text" name="pincode" class="form-control" placeholder="e.g. 680307">
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Street Address</label>
                                    <input type="text" name="address" class="form-control" placeholder="e.g. 123 Main Street, Near Central Bank">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Shop Logo</label>
                                    <input type="file" name="shop_logo" class="form-control">
                                </div>

                                <div class="col-md-6" id="wallpaper_input_container">
                                    <label class="form-label small fw-bold text-muted">Wallpaper / Cover</label>
                                    <input type="file" name="shop_wallpaper" class="form-control" id="shop_wallpaper_input">
                                    <small class="text-muted d-block mt-1 wallpaper-warning text-danger d-none" style="font-size: 0.75rem;">Wallpaper is locked for Free Listing tier.</small>
                                </div>

                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="Write a short description about the shop..."></textarea>
                                </div>
                            </div>

                            <!-- SEO & Marketing (Optional / Paid Features) -->
                            <div class="mt-4 pt-3 border-top" id="seo_section">
                                <h6 class="fw-bold mb-3 text-secondary d-flex align-items-center">
                                    <i class="material-icons me-2">search</i> SEO & SOCIAL MARKETING <span class="badge bg-warning text-dark ms-2 small d-none" id="seo_paid_badge" style="font-size:0.65rem;">PAID FEATURE</span>
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Keywords (Comma separated)</label>
                                        <input type="text" name="meta_keywords" class="form-control" placeholder="shopping, electronics, local deals...">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">OG Title (Social Share)</label>
                                        <input type="text" name="og_title" class="form-control" placeholder="e.g. Welcome to CyberPulse">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">OG Description</label>
                                        <input type="text" name="og_description" class="form-control" placeholder="e.g. Best local deals in town">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label small fw-bold text-muted">Social Links (JSON or comma-separated)</label>
                                        <input type="text" name="social_links" class="form-control" placeholder="Facebook, Instagram, WhatsApp...">
                                    </div>
                                </div>
                            </div>

                            <div class="text-end border-top pt-3 mt-4">
                                <button type="submit" name="add_single_shop" class="btn btn-primary px-5 fw-bold" style="background-color: #6366f1; border-color: #6366f1;">
                                    <i class="material-icons align-middle me-1">add_business</i> REGISTER SHOP
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- File Upload Tab -->
                    <div class="tab-pane fade" id="file-import" role="tabpanel" aria-labelledby="file-tab">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="mb-4">
                                <label for="csv_file" class="form-label small fw-bold text-muted">Select CSV File</label>
                                <input type="file" class="form-control" id="csv_file" name="csv_file" accept=".csv">
                                <small class="text-muted d-block mt-2">Upload a CSV file containing shop and owner information.</small>
                            </div>
                            <div class="text-end border-top pt-3 mt-4">
                                <button type="submit" name="bulk_import" class="btn btn-primary px-5 fw-bold" style="background-color: #6366f1; border-color: #6366f1;">
                                    <i class="material-icons align-middle me-1">sync_alt</i> PROCESS BULK IMPORT
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Paste CSV Tab -->
                    <div class="tab-pane fade" id="paste-import" role="tabpanel" aria-labelledby="paste-tab">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <div class="mb-4">
                                <label for="csv_text" class="form-label small fw-bold text-muted">Paste CSV Data</label>
                                <textarea class="form-control" id="csv_text" name="csv_text" rows="8" placeholder="shop_name,owner_username,description,locality,address,city,district,pincode,type,owner_email,owner_password,owner_mobile,category&#10;My Shop,johndoe,A great shop,Chalakudy,Main St,Chalakudy,Thrissur,680307,classic,john@example.com,pass123,9876543210,Electronics"></textarea>
                                <small class="text-muted d-block mt-2">Paste comma-separated rows with column headers on the first line.</small>
                            </div>
                            <div class="text-end border-top pt-3 mt-4">
                                <button type="submit" name="bulk_import" class="btn btn-primary px-5 fw-bold" style="background-color: #6366f1; border-color: #6366f1;">
                                    <i class="material-icons align-middle me-1">sync_alt</i> PROCESS BULK IMPORT
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold text-dark"><i class="material-icons align-middle me-2 text-secondary">info</i>CSV Format Guide</h5>
            </div>
            <div class="card-body">
                <p class="text-muted">For a successful import, please ensure your CSV structure matches the layout below. The first row must contain the header column names.</p>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm small">
                        <thead class="bg-light">
                            <tr>
                                <th>Column Header</th>
                                <th>Required</th>
                                <th>Description / Value Range</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><code>shop_name</code></td>
                                <td><span class="badge bg-danger">Yes</span></td>
                                <td>The display name of the shop.</td>
                            </tr>
                            <tr>
                                <td><code>owner_username</code></td>
                                <td><span class="badge bg-danger">Yes</span></td>
                                <td>Username of the merchant. If the user does not exist, they will be registered automatically with consistent MLM nodes.</td>
                            </tr>
                            <tr>
                                <td><code>description</code></td>
                                <td>No</td>
                                <td>A short description or bio of the shop.</td>
                            </tr>
                            <tr>
                                <td><code>locality</code></td>
                                <td>No</td>
                                <td>Locality / Area of operation (e.g. Chalakudy, Guruvayur, Irinjalakuda).</td>
                            </tr>
                            <tr>
                                <td><code>address</code></td>
                                <td>No</td>
                                <td>Street address / suite details.</td>
                            </tr>
                            <tr>
                                <td><code>city</code></td>
                                <td>No</td>
                                <td>City name.</td>
                            </tr>
                            <tr>
                                <td><code>district</code></td>
                                <td>No</td>
                                <td>District name (e.g. Thrissur).</td>
                            </tr>
                            <tr>
                                <td><code>pincode</code></td>
                                <td>No</td>
                                <td>Postal Pincode number.</td>
                            </tr>
                            <tr>
                                <td><code>type</code></td>
                                <td>No</td>
                                <td>Shop tier: <code>privilege</code>, <code>classic</code>, or <code>free_listing</code> (defaults to <code>free_listing</code>).</td>
                            </tr>
                            <tr>
                                <td><code>owner_email</code></td>
                                <td>No</td>
                                <td>Email for new users (defaults to <code>owner_username@example.com</code>).</td>
                            </tr>
                            <tr>
                                <td><code>owner_password</code></td>
                                <td>No</td>
                                <td>Password for new users (defaults to <code>Pass123!</code>).</td>
                            </tr>
                            <tr>
                                <td><code>owner_mobile</code></td>
                                <td>No</td>
                                <td>Contact mobile number.</td>
                            </tr>
                            <tr>
                                <td><code>category</code></td>
                                <td>No</td>
                                <td>Shop category name (automatically mapped or created if new).</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="bg-light p-3 rounded border mt-3">
                    <h6 class="fw-bold mb-2 text-dark small d-flex justify-content-between align-items-center">
                        <span>Copyable Raw Template Header:</span>
                        <button class="btn btn-sm btn-outline-secondary py-0 px-2 small" onclick="copyTemplate()">Copy</button>
                    </h6>
                    <pre class="m-0 small text-dark" id="template-text">shop_name,owner_username,description,locality,address,city,district,pincode,type,owner_email,owner_password,owner_mobile,category</pre>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Shops Column -->
    <div class="col-lg-5">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold text-dark"><i class="material-icons align-middle me-2 text-primary">storefront</i>My Registered Shops (<?php echo count($shops); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 650px; overflow-y: auto;">
                    <?php if (empty($shops)): ?>
                        <div class="p-4 text-center text-muted small">No shops registered by you yet.</div>
                    <?php else: foreach ($shops as $s): ?>
                        <div class="list-group-item py-3 px-4 d-flex align-items-center justify-content-between border-0 border-bottom">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2">
                                    <h6 class="m-0 fw-bold text-dark"><?php echo e($s['name']); ?></h6>
                                    <span class="badge rounded-pill bg-light text-dark border small" style="font-size: 0.65rem;">
                                        <?php echo e(strtoupper(str_replace('_', ' ', $s['type']))); ?>
                                    </span>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="material-icons align-middle text-secondary" style="font-size: 1rem;">person</i> Owner: <strong><?php echo e($s['owner_name']); ?></strong>
                                </div>
                                <div class="text-muted small mt-1">
                                    <i class="material-icons align-middle text-secondary" style="font-size: 1rem;">place</i> Locality: <?php echo e($s['locality'] ?: 'N/A'); ?> | Category: <?php echo e($s['category'] ?: 'Uncategorized'); ?>
                                </div>
                            </div>
                            <div class="text-end ms-2">
                                <a href="shop_detail.php?id=<?php echo $s['id']; ?>" class="btn btn-sm btn-outline-primary" target="_blank">View</a>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function copyTemplate() {
    const text = document.getElementById('template-text').innerText;
    navigator.clipboard.writeText(text).then(() => {
        alert('Template headers copied to clipboard!');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const shopTier = document.getElementById('shop_tier');
    const categoryCheckboxesSingle = document.querySelectorAll('.category-checkbox-single');
    const tagCheckboxesSingle = document.querySelectorAll('.tag-checkbox-single');
    const seoSection = document.getElementById('seo_section');
    const seoPaidBadge = document.getElementById('seo_paid_badge');
    const wallpaperInputContainer = document.getElementById('wallpaper_input_container');
    const shopWallpaperInput = document.getElementById('shop_wallpaper_input');
    const wallpaperWarning = document.querySelector('.wallpaper-warning');
    const tagLimitWarning = document.querySelector('.tag-limit-warning');

    function applyTierRestrictions() {
        const tier = shopTier.value;
        if (tier === 'free_listing') {
            // Free Listing tier restrictions
            tagLimitWarning.classList.remove('d-none');
            tagCheckboxesSingle.forEach(cb => {
                cb.checked = false;
                cb.disabled = true;
            });

            wallpaperWarning.classList.remove('d-none');
            shopWallpaperInput.disabled = true;
            shopWallpaperInput.value = '';

            seoSection.style.opacity = '0.3';
            seoSection.style.pointerEvents = 'none';
            seoPaidBadge.classList.remove('d-none');
        } else {
            // Paid tier (Classic / Privilege)
            tagLimitWarning.classList.add('d-none');
            tagCheckboxesSingle.forEach(cb => {
                cb.disabled = false;
            });

            wallpaperWarning.classList.add('d-none');
            shopWallpaperInput.disabled = false;

            seoSection.style.opacity = '1';
            seoSection.style.pointerEvents = 'auto';
            seoPaidBadge.classList.add('d-none');
        }
    }

    shopTier.addEventListener('change', applyTierRestrictions);
    applyTierRestrictions();

    // Max 2 categories for free listing
    categoryCheckboxesSingle.forEach(cb => {
        cb.addEventListener('change', function() {
            const tier = shopTier.value;
            if (tier === 'free_listing') {
                const checkedCount = document.querySelectorAll('.category-checkbox-single:checked').length;
                if (checkedCount > 2) {
                    this.checked = false;
                    alert('Free listings are limited to 2 categories.');
                }
            }
        });
    });
});
</script>

<?php require_once 'includes/admin_layout_footer.php'; ?>
