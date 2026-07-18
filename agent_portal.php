<?php
require_once 'includes/config.php';
require_role('agent');

$success_count = 0;
$error_count = 0;
$messages = [];

// Handle Bulk Import
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
                        $stmt = $pdo->prepare("INSERT INTO shops (owner_id, name, description, locality, address, city, district, pincode, type, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$owner_id, $shop_name, $description, $locality, $address, $city, $district, $pincode, $type, $category_name]);
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

// Handle Single Shop Manual Creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_single_shop'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    $shop_name = trim($_POST['shop_name'] ?? '');
    $owner_username = trim($_POST['owner_username'] ?? '');
    $owner_email = trim($_POST['owner_email'] ?? '');
    $owner_password = trim($_POST['owner_password'] ?? '');
    $owner_mobile = trim($_POST['owner_mobile'] ?? '');

    $description = trim($_POST['description'] ?? '');
    $locality = trim($_POST['locality'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $type = trim($_POST['type'] ?? 'free_listing');
    $category_id = (int)($_POST['category_id'] ?? 0);

    if (empty($shop_name) || empty($owner_username) || empty($owner_email) || empty($owner_password)) {
        $messages[] = ['type' => 'danger', 'text' => 'Shop Name, Owner Username, Email, and Password are required.'];
    } else {
        try {
            $db_in_transaction = $pdo->inTransaction();
            if (!$db_in_transaction) {
                $pdo->beginTransaction();
            }

            // 1. Find or create Owner User
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$owner_username]);
            $owner_id = $stmt->fetchColumn();

            if (!$owner_id) {
                // Check email uniqueness
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$owner_email]);
                if ($stmt->fetchColumn()) {
                    throw new Exception("User creation failed: Email '{$owner_email}' already exists.");
                }

                // Create owner user
                $hashed_pass = password_hash($owner_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, role, password, mobile) VALUES (?, ?, 'shop_owner', ?, ?)");
                $stmt->execute([$owner_username, $owner_email, $hashed_pass, $owner_mobile]);
                $owner_id = $pdo->lastInsertId();

                // Add to MLM hierarchy
                $stmt = $pdo->prepare("INSERT INTO mlm_hierarchy (user_id, parent_id, level_in_tree) VALUES (?, NULL, 1)");
                $stmt->execute([$owner_id]);
            }

            // 2. Fetch category name
            $category_name = "";
            if ($category_id > 0) {
                $stmt = $pdo->prepare("SELECT name FROM shop_categories WHERE id = ?");
                $stmt->execute([$category_id]);
                $category_name = $stmt->fetchColumn() ?: "";
            }

            // 3. Create Shop
            $stmt = $pdo->prepare("INSERT INTO shops (owner_id, name, description, locality, address, city, district, pincode, type, category) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$owner_id, $shop_name, $description, $locality, $address, $city, $district, $pincode, $type, $category_name]);
            $shop_id = $pdo->lastInsertId();

            // 4. Map Category
            if ($category_id > 0) {
                $stmt = $pdo->prepare("INSERT INTO shop_category_map (shop_id, category_id) VALUES (?, ?)");
                $stmt->execute([$shop_id, $category_id]);
            }

            if (!$db_in_transaction && $pdo->inTransaction()) {
                $pdo->commit();
            }
            $messages[] = ['type' => 'success', 'text' => "Shop '{$shop_name}' has been created successfully for owner '{$owner_username}'!"];
        } catch (Exception $e) {
            if (!$db_in_transaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $messages[] = ['type' => 'danger', 'text' => "Failed to add shop: " . $e->getMessage()];
        }
    }
}

// Handle Add Place / Locality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_locality'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    $loc_name = trim($_POST['loc_name'] ?? '');
    $latitude = !empty($_POST['latitude']) ? (float)$_POST['latitude'] : 0.0;
    $longitude = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : 0.0;

    if (empty($loc_name)) {
        $messages[] = ['type' => 'danger', 'text' => 'Locality name is required.'];
    } else {
        try {
            // Check if locality name already exists
            $stmt = $pdo->prepare("SELECT id FROM localities WHERE LOWER(name) = LOWER(?)");
            $stmt->execute([$loc_name]);
            if ($stmt->fetch()) {
                $messages[] = ['type' => 'warning', 'text' => "Locality '{$loc_name}' already exists."];
            } else {
                $stmt = $pdo->prepare("INSERT INTO localities (name, latitude, longitude) VALUES (?, ?, ?)");
                $stmt->execute([$loc_name, $latitude, $longitude]);
                $messages[] = ['type' => 'success', 'text' => "Locality '{$loc_name}' has been added successfully!"];
            }
        } catch (PDOException $e) {
            $messages[] = ['type' => 'danger', 'text' => "Database error: " . $e->getMessage()];
        }
    }
}

// Fetch all registered shops
$shops = $pdo->query("SELECT s.*, u.username as owner_name FROM shops s JOIN users u ON s.owner_id = u.id ORDER BY s.created_at DESC")->fetchAll();
$categories = $pdo->query("SELECT * FROM shop_categories ORDER BY name ASC")->fetchAll();
$localities = $pdo->query("SELECT * FROM localities ORDER BY name ASC")->fetchAll();

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
        <h1 class="m-0 text-dark">Agent Portal <small class="text-muted fw-light" style="font-size: 1rem;">(Add & Manage Shops)</small></h1>
    </div>
</div>

<?php foreach ($messages as $msg): ?>
    <div class="alert alert-<?php echo $msg['type']; ?> alert-dismissible fade show" role="alert">
        <?php echo e($msg['text']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach; ?>

<div class="row g-4">
    <!-- Import Options Column -->
    <div class="col-lg-7">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold text-dark"><i class="material-icons align-middle me-2 text-primary">add_business</i>Add or Import Shops</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs mb-4" id="importTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold text-secondary" id="single-tab" data-bs-toggle="tab" data-bs-target="#single-import" type="button" role="tab" aria-controls="single-import" aria-selected="true">
                            <i class="material-icons align-middle me-1">add_business</i> Add Single Shop
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
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                            <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">1. Shop Owner Credentials</h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Owner Username</label>
                                    <input type="text" name="owner_username" class="form-control" placeholder="e.g. johndoe" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Owner Email</label>
                                    <input type="email" name="owner_email" class="form-control" placeholder="e.g. john@example.com" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Owner Password</label>
                                    <input type="password" name="owner_password" class="form-control" placeholder="e.g. Pass123!" value="Pass123!" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Owner Mobile</label>
                                    <input type="text" name="owner_mobile" class="form-control" placeholder="e.g. 9876543210">
                                </div>
                            </div>

                            <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">2. Shop Information</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Shop Name</label>
                                    <input type="text" name="shop_name" class="form-control" placeholder="e.g. Cyber Tech Hub" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Shop Type</label>
                                    <select name="type" class="form-select" required>
                                        <option value="free_listing">Free Listing</option>
                                        <option value="classic">Classic</option>
                                        <option value="privilege">Privilege</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Primary Category</label>
                                    <select name="category_id" class="form-select">
                                        <option value="">-- Select Category --</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo e($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold text-muted">Locality</label>
                                    <select name="locality" class="form-select">
                                        <option value="">-- Select Locality --</option>
                                        <?php foreach ($localities as $loc): ?>
                                            <option value="<?php echo e($loc['name']); ?>"><?php echo e($loc['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">City</label>
                                    <input type="text" name="city" class="form-control" placeholder="City">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">District</label>
                                    <input type="text" name="district" class="form-control" placeholder="District">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Pincode</label>
                                    <input type="text" name="pincode" class="form-control" placeholder="Pincode">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Street Address</label>
                                    <input type="text" name="address" class="form-control" placeholder="e.g. 123 Main St, Near Central Square">
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold text-muted">Description</label>
                                    <textarea name="description" class="form-control" rows="3" placeholder="A short description of the shop..."></textarea>
                                </div>
                            </div>

                            <div class="text-end mt-4 border-top pt-3">
                                <button type="submit" name="add_single_shop" class="btn btn-primary px-5 fw-bold" style="background-color: #6366f1; border-color: #6366f1;">
                                    <i class="material-icons align-middle me-1">add_business</i> CREATE SINGLE SHOP
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

    <!-- Right Side Column -->
    <div class="col-lg-5">
        <!-- Add Locality Form -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold text-dark"><i class="material-icons align-middle me-2 text-primary">add_location_alt</i>Add New Place / Locality</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Locality Name</label>
                        <input type="text" name="loc_name" class="form-control" placeholder="e.g. Ernakulam Town" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Latitude (Optional)</label>
                            <input type="number" step="0.000001" name="latitude" class="form-control" placeholder="e.g. 10.52">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold text-muted">Longitude (Optional)</label>
                            <input type="number" step="0.000001" name="longitude" class="form-control" placeholder="e.g. 76.21">
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="submit" name="add_locality" class="btn btn-outline-primary fw-bold w-100">
                            <i class="material-icons align-middle me-1">add_location</i> ADD LOCALITY
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Live Registered Shops -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold text-dark"><i class="material-icons align-middle me-2 text-primary">storefront</i>Live Registered Shops (<?php echo count($shops); ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" style="max-height: 450px; overflow-y: auto;">
                    <?php if (empty($shops)): ?>
                        <div class="p-4 text-center text-muted small">No shops registered on the platform yet.</div>
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
</script>

<?php require_once 'includes/admin_layout_footer.php'; ?>
