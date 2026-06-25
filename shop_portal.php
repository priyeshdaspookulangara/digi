<?php
require_once 'includes/config.php';
require_role('shop_owner');
require_once 'includes/mlm_logic.php';

// Fetch active shop for current user
$stmt = $pdo->prepare("SELECT * FROM shops WHERE owner_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$shop = $stmt->fetch();

if (!$shop) {
    $stmt = $pdo->prepare("INSERT INTO shops (owner_id, name) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $_SESSION['username'] . "'s Shop"]);
    $shop_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("SELECT * FROM shops WHERE id = ?");
    $stmt->execute([$shop_id]);
    $shop = $stmt->fetch();
}

// Handle Offer Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_offer'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }
    $stmt = $pdo->prepare("INSERT INTO offers (shop_id, title, description, discount_percent) VALUES (?, ?, ?, ?)");
    $stmt->execute([$shop['id'], $_POST['offer_title'], $_POST['offer_desc'], $_POST['discount']]);
}

$csrf_token = get_csrf_token();
$pageTitle = "Shop Portal | " . $shop['name'];
require_once 'includes/admin_layout_header.php';
?>

<div class="page__heading d-flex align-items-center">
    <div class="flex">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="#">Dashboard</a></li>
                <li class="breadcrumb-item active" aria-current="page">Shop Management</li>
            </ol>
        </nav>
        <h1 class="m-0"><?php echo e($shop['name']); ?> <small class="text-muted fw-light" style="font-size: 1rem;">(<?php echo e(ucfirst($shop['type'])); ?> Tier)</small></h1>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Settings & Products -->
    <div class="col-lg-8">
        <!-- Shop Configuration Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="material-icons align-middle me-2 text-primary">store</i>Identity & Branding</h5>
            </div>
            <div class="card-body">
                <form action="process_shop.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <input type="hidden" name="existing_logo" value="<?php echo e($shop['logo']); ?>">
                    <input type="hidden" name="existing_wallpaper" value="<?php echo e($shop['wallpaper']); ?>">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Shop Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo e($shop['name']); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Categories (Multi-select)</label>
                            <div class="border p-2 rounded" style="max-height: 150px; overflow-y: auto; background: #fff;">
                                <?php
                                $current_cats = $pdo->prepare("SELECT category_id FROM shop_category_map WHERE shop_id = ?");
                                $current_cats->execute([$shop['id']]);
                                $selected_cats = $current_cats->fetchAll(PDO::FETCH_COLUMN);

                                $all_categories = $pdo->query("SELECT id, name FROM shop_categories ORDER BY name ASC")->fetchAll();
                                foreach($all_categories as $cat): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categories[]" value="<?php echo $cat['id']; ?>" id="cat_<?php echo $cat['id']; ?>" <?php echo in_array($cat['id'], $selected_cats) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small" for="cat_<?php echo $cat['id']; ?>"><?php echo e($cat['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Attributes / Tags</label>
                            <div class="border p-2 rounded" style="max-height: 150px; overflow-y: auto; background: #fff;">
                                <?php
                                $current_tags = $pdo->prepare("SELECT tag_id FROM shop_tag_map WHERE shop_id = ?");
                                $current_tags->execute([$shop['id']]);
                                $selected_tags = $current_tags->fetchAll(PDO::FETCH_COLUMN);

                                $all_tags = $pdo->query("SELECT id, name FROM shop_tags ORDER BY name ASC")->fetchAll();
                                foreach($all_tags as $tag): ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="tags[]" value="<?php echo $tag['id']; ?>" id="tag_<?php echo $tag['id']; ?>" <?php echo in_array($tag['id'], $selected_tags) ? 'checked' : ''; ?>>
                                        <label class="form-check-label small" for="tag_<?php echo $tag['id']; ?>"><?php echo e($tag['name']); ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Locality</label>
                            <select name="locality" class="form-select">
                                <?php
                                $localities = $pdo->query("SELECT name FROM localities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
                                foreach($localities as $loc): ?>
                                    <option value="<?php echo e($loc); ?>" <?php echo $shop['locality'] === $loc ? 'selected' : ''; ?>><?php echo e($loc); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-<?php echo $shop['type'] === 'free_listing' ? '12' : '6'; ?>">
                            <label class="form-label small fw-bold">Shop Logo</label>
                            <input type="file" name="shop_logo" class="form-control">
                        </div>
                        <?php if($shop['type'] !== 'free_listing'): ?>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Wallpaper / Cover</label>
                            <input type="file" name="shop_wallpaper" class="form-control">
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control" rows="2"><?php echo e($shop['description']); ?></textarea>
                        </div>
                    </div>

                    <!-- SEO & Marketing Section -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold mb-3"><i class="material-icons align-middle me-1 text-secondary">search</i>SEO & Social Marketing</h6>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Keywords (Comma separated)</label>
                                <input type="text" name="meta_keywords" class="form-control form-control-sm" value="<?php echo e($shop['meta_keywords']); ?>" placeholder="shopping, electronics, local deals...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">OG Title (Social Share)</label>
                                <input type="text" name="og_title" class="form-control form-control-sm" value="<?php echo e($shop['og_title']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">OG Description</label>
                                <input type="text" name="og_description" class="form-control form-control-sm" value="<?php echo e($shop['og_description']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 border-top pt-3 text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Update Shop Info</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Product Addition Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="material-icons align-middle me-2 text-primary">inventory_2</i>Your Products</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Product</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $products = $pdo->prepare("SELECT * FROM products WHERE shop_id = ? ORDER BY created_at DESC");
                            $products->execute([$shop['id']]);
                            $prod_list = $products->fetchAll();
                            if (empty($prod_list)):
                            ?>
                                <tr><td colspan="4" class="text-center py-4 text-muted">No products added yet.</td></tr>
                            <?php else: foreach($prod_list as $p): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo $p['image'] ?: 'https://via.placeholder.com/40'; ?>" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;">
                                            <div>
                                                <div class="fw-bold small"><?php echo e($p['name']); ?></div>
                                                <div class="text-muted" style="font-size: 0.7rem;">ID: #<?php echo $p['id']; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="fw-bold">₹<?php echo number_format($p['price'], 2); ?></span></td>
                                    <td>
                                        <?php if($p['is_featured']): ?>
                                            <span class="badge bg-soft-primary text-primary border border-primary border-opacity-25" style="font-size: 0.65rem;">FEATURED</span>
                                        <?php else: ?>
                                            <span class="badge bg-soft-secondary text-muted border" style="font-size: 0.65rem;">STANDARD</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-outline-secondary border-0"><i class="material-icons" style="font-size: 1.2rem;">edit</i></button>
                                        <button class="btn btn-sm btn-outline-danger border-0"><i class="material-icons" style="font-size: 1.2rem;">delete</i></button>
                                    </td>
                                </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Product Addition Card -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="material-icons align-middle me-2 text-primary">add_circle</i>Quick Add Product</h5>
            </div>
            <div class="card-body">
                <form action="process_product.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="row g-3 mb-3">
                        <div class="col-md-7">
                            <label class="form-label small fw-bold">Product Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. CyberPulse Smartwatch" required>
                        </div>
                        <div class="col-md-<?php echo $shop['type'] === 'privilege' ? '2' : '3'; ?>">
                            <label class="form-label small fw-bold">Price (₹)</label>
                            <input type="number" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                        <?php if($shop['type'] === 'privilege'): ?>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Discount Tag</label>
                            <input type="text" name="discount_entry" class="form-control" placeholder="10% OFF">
                        </div>
                        <?php endif; ?>
                        <div class="col-md-2 d-flex align-items-end pb-1">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="featProduct">
                                <label class="form-check-label small fw-bold" for="featProduct">Featured</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Product Description</label>
                        <textarea name="description" class="form-control" rows="2" required></textarea>
                    </div>

                    <!-- Product SEO -->
                    <div class="p-3 bg-light rounded mb-3">
                        <h6 class="small fw-bold mb-2 text-muted uppercase">SEO & Social Meta (Optional)</h6>
                        <div class="row g-2">
                            <div class="col-md-4">
                                <input type="text" name="meta_keywords" class="form-control form-control-sm" placeholder="Keywords">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="og_title" class="form-control form-control-sm" placeholder="Social Title">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="og_description" class="form-control form-control-sm" placeholder="Social Desc">
                            </div>
                        </div>
                    </div>
                    <?php if($shop['type'] !== 'free_listing'): ?>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Product Image</label>
                            <input type="file" name="product_image" class="form-control">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Product Video</label>
                            <input type="file" name="product_video" class="form-control">
                        </div>
                    </div>
                    <?php endif; ?>
                    <div class="mt-4 border-top pt-3 text-end">
                        <button type="submit" class="btn btn-indigo px-5 fw-bold" style="background-color: #6366f1; color: white;">SAVE PRODUCT</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Right Column: Calendar & Enquiries -->
    <div class="col-lg-4">
        <!-- Active Offers Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title m-0 fw-bold"><i class="material-icons align-middle me-2 text-primary">local_offer</i>Active Offers</h5>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#offerModal">ADD</button>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php
                    $offers = $pdo->prepare("SELECT * FROM offers WHERE shop_id = ? AND is_active = 1");
                    $offers->execute([$shop['id']]);
                    $offer_list = $offers->fetchAll();
                    if (empty($offer_list)): ?>
                        <div class="list-group-item text-center py-4 text-muted small">No active offers.</div>
                    <?php else: foreach($offer_list as $o): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-bold small"><?php echo e($o['title']); ?></div>
                                <div class="text-success fw-bold small"><?php echo $o['discount_percent']; ?>% OFF</div>
                            </div>
                            <button class="btn btn-sm text-danger border-0"><i class="material-icons" style="font-size: 1.1rem;">delete</i></button>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>

        <!-- Calendar Card -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold"><i class="material-icons align-middle me-2 text-primary">event_note</i>Enquiry Calendar</h5>
            </div>
            <div class="card-body p-2">
                <div id="calendar" style="font-size: 0.8rem;"></div>
            </div>
        </div>

        <!-- Appointment/Enquiry List -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title m-0 fw-bold" id="appointment-list-title">Daily Enquiries</h5>
            </div>
            <div class="list-group list-group-flush" id="appointment-list-container">
                <div class="list-group-item text-center py-4 text-muted small">Select a date to view enquiries.</div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const calendarEl = document.getElementById('calendar');
    const listContainer = document.getElementById('appointment-list-container');
    const listTitle = document.getElementById('appointment-list-title');

    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        height: 'auto',
        headerToolbar: {
            left: 'prev,next',
            center: 'title',
            right: ''
        },
        dateClick: function(info) {
            fetchEnquiries(info.dateStr);
        },
        events: 'api.php?action=get_enquiry_dates&shop_id=<?php echo $shop['id']; ?>'
    });
    calendar.render();

    function fetchEnquiries(dateStr) {
        listTitle.innerText = `Enquiries for ${dateStr}`;
        listContainer.innerHTML = '<div class="list-group-item text-center py-3">Loading...</div>';

        fetch(`api.php?action=get_enquiries_for_date&shop_id=<?php echo $shop['id']; ?>&date=${dateStr}`)
            .then(res => res.json())
            .then(data => {
                listContainer.innerHTML = '';
                if (data.length > 0) {
                    data.forEach(enq => {
                        listContainer.innerHTML += `
                            <div class="list-group-item border-0 border-bottom">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <strong class="text-dark small">${enq.customer_name}</strong>
                                    <span class="badge bg-light text-primary border" style="font-size: 0.6rem;">${enq.product_name || 'General'}</span>
                                </div>
                                <p class="mb-1 text-muted" style="font-size: 0.75rem;">"${enq.message}"</p>
                                <div class="text-end">
                                    <button class="btn btn-sm btn-link p-0 text-primary fw-bold" style="font-size: 0.7rem;">REPLY</button>
                                </div>
                            </div>`;
                    });
                } else {
                    listContainer.innerHTML = '<div class="list-group-item text-center py-4 text-muted small">No enquiries on this day.</div>';
                }
            });
    }

    // Load today by default
    fetchEnquiries(new Date().toISOString().split('T')[0]);
});
</script>

<!-- Offer Modal -->
<div class="modal fade" id="offerModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Offer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Offer Title</label>
                        <input type="text" name="offer_title" class="form-control" placeholder="e.g. Festival Special" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="offer_desc" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Discount (%)</label>
                        <input type="number" name="discount" class="form-control" min="1" max="100" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_offer" class="btn btn-primary">Publish Offer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/admin_layout_footer.php'; ?>
