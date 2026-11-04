<?php
$pageTitle = "Royalty Store | Powernet Digimart";
require_once 'includes/header.php';
?>

<div class="container my-5">
    <div class="card p-5 shadow-sm border-0 mx-auto text-center" style="max-width: 800px; border-radius: 16px;">
        <div class="mb-4">
            <span class="badge bg-warning text-dark px-3 py-2 text-uppercase fw-bold"><i class="fas fa-crown me-1"></i> Exclusive Member Perks</span>
        </div>
        <h1 class="fw-bold mb-3">Royalty Store</h1>
        <p class="text-muted lead mb-4">Exclusive merchandise and premium deals reserved for our network partners and royalty club members.</p>

        <div class="row g-4 my-3 text-start">
            <div class="col-md-6">
                <div class="border p-4 rounded h-100">
                    <h5 class="fw-bold text-success"><i class="fas fa-gem me-2"></i>Royalty Chamber</h5>
                    <p class="small text-muted mb-0">Accumulate royalty points from referral milestones and shop purchases to unlock high-value partner items and cash rewards.</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="border p-4 rounded h-100">
                    <h5 class="fw-bold text-success"><i class="fas fa-tags me-2"></i>Privilege Discounts</h5>
                    <p class="small text-muted mb-0">Access up to 60% off across select merchant partner stores located throughout our local network hubs.</p>
                </div>
            </div>
        </div>

        <div class="mt-4">
            <a href="shopping.php" class="btn btn-success px-4 py-2 fw-bold">Explore Marketplace</a>
            <a href="register.php" class="btn btn-outline-secondary px-4 py-2 ms-2">Join Network</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
