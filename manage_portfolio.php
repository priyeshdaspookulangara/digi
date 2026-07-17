<?php
require_once 'includes/config.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Check if user already has a portfolio
$stmt = $pdo->prepare("SELECT * FROM portfolios WHERE user_id = ?");
$stmt->execute([$user_id]);
$portfolio = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        die("CSRF token validation failed.");
    }

    $title = trim($_POST['title'] ?? '');
    $professional_name = trim($_POST['professional_name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $experience_years = (int)($_POST['experience_years'] ?? 0);
    $skills = trim($_POST['skills'] ?? '');
    $services = trim($_POST['services'] ?? '');
    $contact_email = trim($_POST['contact_email'] ?? '');
    $contact_phone = trim($_POST['contact_phone'] ?? '');
    $locality = trim($_POST['locality'] ?? '');

    if (empty($title) || empty($professional_name) || !$category_id) {
        $error = "Title, Professional Name, and Category are required.";
    } else {
        // Handle Image Upload
        $image_path = $portfolio['image'] ?? '';
        if (isset($_FILES['portfolio_image']) && $_FILES['portfolio_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/portfolios/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($_FILES['portfolio_image']['tmp_name']);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (in_array($mime, $allowed)) {
                $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
                $filename = uniqid('port_') . '.' . $ext;
                if (move_uploaded_file($_FILES['portfolio_image']['tmp_name'], $upload_dir . $filename)) {
                    $image_path = $upload_dir . $filename;
                }
            } else {
                $error = "Invalid image type. Only JPG, PNG, and WEBP are allowed.";
            }
        }

        if (empty($error)) {
            try {
                if ($portfolio) {
                    // Update existing portfolio
                    $stmt = $pdo->prepare("
                        UPDATE portfolios
                        SET title = ?, professional_name = ?, category_id = ?, description = ?,
                            experience_years = ?, skills = ?, services = ?, contact_email = ?,
                            contact_phone = ?, locality = ?, image = ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([
                        $title, $professional_name, $category_id, $description,
                        $experience_years, $skills, $services, $contact_email,
                        $contact_phone, $locality, $image_path, $user_id
                    ]);
                    $success = "Portfolio updated successfully!";
                } else {
                    // Insert new portfolio
                    $stmt = $pdo->prepare("
                        INSERT INTO portfolios
                        (user_id, title, professional_name, category_id, description, experience_years, skills, services, contact_email, contact_phone, locality, image)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $user_id, $title, $professional_name, $category_id, $description,
                        $experience_years, $skills, $services, $contact_email,
                        $contact_phone, $locality, $image_path
                    ]);
                    $success = "Portfolio created successfully!";
                }

                // Refresh portfolio data
                $stmt = $pdo->prepare("SELECT * FROM portfolios WHERE user_id = ?");
                $stmt->execute([$user_id]);
                $portfolio = $stmt->fetch();

            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch categories for select input
$categories = $pdo->query("SELECT * FROM professional_categories ORDER BY name ASC")->fetchAll();
// Fetch localites for select input
$localities = $pdo->query("SELECT name FROM localities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);

$csrf_token = get_csrf_token();
$pageTitle = "Manage My Portfolio | NexGen Marketplace";
include 'includes/header.php';
?>

<div class="container my-5">
    <div class="glass-card p-5 mx-auto" style="max-width: 800px;">
        <div class="d-flex align-items-center mb-4 border-bottom pb-3">
            <div class="p-3 bg-light rounded-circle me-3 text-primary">
                <i class="fas fa-id-card fa-2x"></i>
            </div>
            <div>
                <h2 class="text-dark fw-bold m-0">Professional Portfolio</h2>
                <p class="text-muted small m-0">Showcase your skills, experience, and services to potential customers on the marketplace.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 small mb-4">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo e($error); ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success border-0 small mb-4">
                <i class="fas fa-check-circle me-2"></i><?php echo e($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

            <div class="row g-4">
                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Professional / Business Name</label>
                    <input type="text" name="professional_name" class="form-control" value="<?php echo e($portfolio['professional_name'] ?? $_SESSION['username']); ?>" placeholder="e.g. John Doe, Tech Solutions" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Professional Title</label>
                    <input type="text" name="title" class="form-control" value="<?php echo e($portfolio['title'] ?? ''); ?>" placeholder="e.g. Master Electrician, UI/UX Designer" required>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Category</label>
                    <select name="category_id" class="form-select" required>
                        <option value="">-- Select Category --</option>
                        <?php foreach($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($portfolio['category_id']) && $portfolio['category_id'] == $cat['id']) ? 'selected' : ''; ?>><?php echo e($cat['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Years of Experience</label>
                    <input type="number" name="experience_years" class="form-control" value="<?php echo e($portfolio['experience_years'] ?? ''); ?>" placeholder="e.g. 5" min="0">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Locality / Base Location</label>
                    <select name="locality" class="form-select">
                        <option value="">-- Select Locality --</option>
                        <?php foreach($localities as $loc): ?>
                            <option value="<?php echo e($loc); ?>" <?php echo (isset($portfolio['locality']) && $portfolio['locality'] === $loc) ? 'selected' : ''; ?>><?php echo e($loc); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Profile / Cover Image</label>
                    <input type="file" name="portfolio_image" class="form-control" accept="image/*">
                    <?php if(!empty($portfolio['image'])): ?>
                        <div class="mt-2 text-muted small">
                            Current Image: <a href="<?php echo e($portfolio['image']); ?>" target="_blank" class="text-primary">View Current File</a>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Contact Email</label>
                    <input type="email" name="contact_email" class="form-control" value="<?php echo e($portfolio['contact_email'] ?? ''); ?>" placeholder="e.g. john@example.com">
                </div>

                <div class="col-md-6">
                    <label class="form-label small fw-bold text-muted">Contact Phone / Whatsapp</label>
                    <input type="text" name="contact_phone" class="form-control" value="<?php echo e($portfolio['contact_phone'] ?? ''); ?>" placeholder="e.g. +91 98765 43210">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-muted">Skills / Expertise (Comma separated)</label>
                    <input type="text" name="skills" class="form-control" value="<?php echo e($portfolio['skills'] ?? ''); ?>" placeholder="e.g. Wiring, Troubleshooting, Inverter Setup">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-muted">Services Offered (Comma separated)</label>
                    <input type="text" name="services" class="form-control" value="<?php echo e($portfolio['services'] ?? ''); ?>" placeholder="e.g. Home Electrical Repairs, Commercial Wiring, Panel Upgrades">
                </div>

                <div class="col-12">
                    <label class="form-label small fw-bold text-muted">About Me / Bio</label>
                    <textarea name="description" class="form-control" rows="5" placeholder="Describe your experience, values, and outstanding projects..."><?php echo e($portfolio['description'] ?? ''); ?></textarea>
                </div>
            </div>

            <div class="mt-5 border-top pt-3 d-flex justify-content-between align-items-center">
                <?php if($portfolio): ?>
                    <a href="portfolio_detail.php?id=<?php echo $portfolio['id']; ?>" class="btn btn-outline-secondary fw-bold" target="_blank">
                        <i class="fas fa-external-link-alt me-1"></i> View Live Portfolio
                    </a>
                <?php else: ?>
                    <div></div>
                <?php endif; ?>
                <button type="submit" class="btn btn-primary px-5 fw-bold" style="background-color: #6366f1; border-color: #6366f1;">
                    <i class="fas fa-save me-1"></i> SAVE PORTFOLIO
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
