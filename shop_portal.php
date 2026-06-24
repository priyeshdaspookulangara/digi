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
                            <label class="form-label small fw-bold">Category</label>
                            <select name="category" class="form-select">
                                <?php
                                $all_categories = $pdo->query("SELECT name FROM shop_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
                                foreach($all_categories as $cat): ?>
                                    <option value="<?php echo e($cat); ?>" <?php echo $shop['category'] === $cat ? 'selected' : ''; ?>><?php echo e($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
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
                    <div class="mt-4 border-top pt-3 text-end">
                        <button type="submit" class="btn btn-primary px-4 fw-bold">Update Branding</button>
                    </div>
                </form>
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
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Product Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. CyberPulse Smartwatch" required>
                        </div>
                        <div class="col-md-<?php echo $shop['type'] === 'privilege' ? '2' : '4'; ?>">
                            <label class="form-label small fw-bold">Price (₹)</label>
                            <input type="number" name="price" class="form-control" placeholder="0.00" required>
                        </div>
                        <?php if($shop['type'] === 'privilege'): ?>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">Discount Tag</label>
                            <input type="text" name="discount_entry" class="form-control" placeholder="10% OFF">
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Product Description</label>
                        <textarea name="description" class="form-control" rows="3" required></textarea>
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

<?php require_once 'includes/admin_layout_footer.php'; ?>
