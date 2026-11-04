<?php
require_once 'includes/config.php';
require_once 'includes/seo_helper.php';

$search = $_GET['search'] ?? '';
$place = $_GET['place'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT s.*, u.username as owner_name FROM shops s JOIN users u ON s.owner_id = u.id WHERE 1=1";
$params = [];
if ($place) {
    $query .= " AND s.locality = ?";
    $params[] = $place;
}
if ($category) {
    $query .= " AND s.category = ?";
    $params[] = $category;
}
if ($search) {
    $query .= " AND (s.name LIKE ? OR s.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$query .= " ORDER BY (s.type = 'privilege' OR s.type = 'classic' OR s.type = 'paid') DESC, s.name ASC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$shops = $stmt->fetchAll();

$localities = $pdo->query("SELECT name FROM localities ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
$categories = $pdo->query("SELECT name FROM shop_categories ORDER BY name ASC")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Local Business Guide | Powernet Digimart</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
:root{
  --deep:#0b5d38; --deeper:#073f26; --lime:#b8de7e; --lime-d:#9ccb5c;
  --red:#dc2a1f; --orange:#e67a1a; --blue:#667bb5; --taupe:#75645e;
  --ink:#1c1c1c; --white:#fff; --muted:#5b6470; --leaf:#39b54a;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Poppins',system-ui,sans-serif;color:var(--ink);background:#fbfbfb;line-height:1.5}
a{text-decoration:none;color:inherit}
img{display:block;max-width:100%}
:focus-visible{outline:3px solid var(--orange);outline-offset:2px}
.wrap{max-width:1240px;margin:0 auto;padding:0 20px}

/* Header */
.top{background:var(--deep);color:#fff}
.top .wrap{display:flex;align-items:center;justify-content:space-between;height:84px}
.brand{display:flex;flex-direction:column;line-height:1}
.brand small{font-size:12px;font-weight:500;letter-spacing:.3px}
.brand b{font-size:34px;font-weight:700;letter-spacing:-.5px;display:flex;align-items:center}
.brand b span.m{color:var(--leaf);position:relative}
.brand b span.m i{position:absolute;left:50%;top:-9px;transform:translateX(-50%);font-size:13px;color:#fff}
.auth{display:flex;gap:26px;font-weight:600;font-size:15px;align-items:center}
.auth a:hover{color:var(--lime)}
.auth .reg{background:var(--lime);color:var(--deeper);padding:7px 18px;border-radius:999px}
.auth .reg:hover{color:var(--deeper);background:#fff}

/* Nav */
.menu{background:var(--lime);position:sticky;top:0;z-index:50;box-shadow:0 2px 0 rgba(0,0,0,.06)}
.menu .wrap{display:flex;align-items:center;justify-content:space-between}
.menu ul{display:flex;list-style:none;gap:6px}
.menu li a{display:block;padding:13px 16px;font-weight:600;font-size:15px;color:var(--deeper);border-bottom:3px solid transparent}
.menu li a:hover,.menu li a.on{border-color:var(--deeper);background:rgba(255,255,255,.3)}
.burger{display:none;background:none;border:0;font-size:22px;color:var(--deeper);padding:10px;cursor:pointer}

/* Hero / Filter Bar */
.guide-hero{background:linear-gradient(135deg, var(--deep), var(--deeper));color:#fff;padding:40px 0 35px}
.guide-hero h1{font-size:32px;font-weight:700;margin-bottom:8px}
.guide-hero p{font-size:15px;opacity:.9;max-width:650px}

.search-box{margin-top:24px;display:flex;gap:10px;flex-wrap:wrap}
.search-box input{flex:1;min-width:240px;padding:12px 16px;border:0;border-radius:6px;font-family:inherit;font-size:14px}
.search-box select{padding:12px 16px;border:0;border-radius:6px;font-family:inherit;font-size:14px;background:#fff;color:var(--ink)}
.search-box button{background:var(--leaf);color:#fff;font-family:inherit;font-weight:700;padding:12px 24px;border:0;border-radius:6px;cursor:pointer;display:inline-flex;align-items:center;gap:8px}
.search-box button:hover{background:var(--lime-d);color:var(--deeper)}

/* Locality Badges */
.pills-bar{background:#fff;padding:14px 0;border-bottom:1px solid #e5e7eb}
.pills-scroll{display:flex;gap:8px;overflow-x:auto;padding-bottom:4px;scrollbar-width:thin}
.pill{padding:6px 14px;border-radius:999px;font-size:13px;font-weight:600;white-space:nowrap;background:#f1f3f5;color:var(--muted);border:1px solid #e2e8f0;transition:all .2s}
.pill:hover{background:var(--lime);color:var(--deeper);border-color:var(--lime-d)}
.pill.active{background:var(--deep);color:#fff;border-color:var(--deep)}

/* Shop Cards Grid */
.guide-content{padding:40px 0 60px}
.guide-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px}

.shop-card{background:#fff;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;box-shadow:0 2px 6px rgba(0,0,0,.04);display:flex;flex-direction:column;transition:transform .2s, box-shadow .2s}
.shop-card:hover{transform:translateY(-3px);box-shadow:0 8px 18px rgba(0,0,0,.08)}

.shop-cover{height:120px;background:linear-gradient(135deg, var(--deep), var(--leaf));position:relative;overflow:hidden}
.shop-cover img{width:100%;height:100%;object-fit:cover;opacity:.4}

.shop-body{padding:18px;display:flex;flex-direction:column;flex:1;position:relative}
.shop-head{display:flex;align-items:flex-start;gap:14px;margin-top:-36px;margin-bottom:14px}
.shop-logo{width:64px;height:64px;border-radius:8px;background:#fff;padding:4px;border:2px solid #fff;box-shadow:0 4px 10px rgba(0,0,0,.12);flex-shrink:0;overflow:hidden}
.shop-logo img{width:100%;height:100%;object-fit:contain}
.shop-title{flex:1;padding-top:20px}
.shop-title h3{font-size:18px;font-weight:700;line-height:1.2;color:var(--ink);margin-bottom:4px}
.shop-badge{display:inline-block;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;text-transform:uppercase;margin-top:2px}
.badge-privilege{background:#fef3c7;color:#92400e;border:1px solid #fde68a}
.badge-classic{background:#e0e7ff;color:#3730a3;border:1px solid #c7d2fe}
.badge-free{background:#f3f4f6;color:#4b5563;border:1px solid #e5e7eb}

.shop-desc{font-size:13.5px;color:var(--muted);line-height:1.5;margin-bottom:16px;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;flex:1}

.shop-meta{display:flex;align-items:center;justify-content:space-between;padding-top:14px;border-top:1px solid #f1f3f5;font-size:13px;color:var(--muted)}
.shop-meta .loc{display:inline-flex;align-items:center;gap:6px}
.shop-meta .loc i{color:var(--red)}

.shop-btn{background:var(--deep);color:#fff;font-weight:600;font-size:13px;padding:6px 14px;border-radius:4px;transition:background .2s}
.shop-btn:hover{background:var(--deeper);color:#fff}

.empty-state{text-align:center;padding:60px 20px;grid-column:1 / -1;background:#fff;border-radius:8px;border:1px solid #e5e7eb}
.empty-state i{font-size:48px;color:#d1d5db;margin-bottom:16px}
.empty-state h3{font-size:20px;font-weight:700;color:var(--ink);margin-bottom:6px}
.empty-state p{font-size:14px;color:var(--muted);margin-bottom:20px}
.empty-state a{background:var(--deep);color:#fff;padding:10px 20px;border-radius:6px;font-weight:600;font-size:14px}

/* Footer */
footer{background:#1d1b1a;color:#e8e8e8;text-align:center;padding:26px 20px;font-size:14px}
footer b{color:#fff}
footer .soc{margin-top:8px;display:flex;justify-content:center;gap:18px;font-size:16px}
footer .soc a:hover{color:var(--lime)}

/* Responsive */
@media(max-width:1000px){
  .guide-grid{grid-template-columns:repeat(2,1fr)}
}
@media(max-width:780px){
  .burger{display:block}
  .menu ul{display:none;position:absolute;top:100%;left:0;right:0;background:var(--lime);flex-direction:column;padding:6px 20px 12px;box-shadow:0 8px 14px rgba(0,0,0,.15)}
  .menu ul.open{display:flex}
  .brand b{font-size:28px}
  .auth{gap:14px}
  .guide-hero h1{font-size:26px}
}
@media(max-width:560px){
  .guide-grid{grid-template-columns:1fr}
  .search-box{flex-direction:column}
  .search-box button{width:100%;justify-content:center}
}
</style>
</head>
<body>

<!-- Top Header Branding -->
<header class="top">
  <div class="wrap">
    <a href="index.php" class="brand" aria-label="Powernet Digimart home">
      <small>Powernet</small>
      <b>Digi<span class="m">m<i class="fas fa-bag-shopping"></i></span>art</b>
    </a>
    <nav class="auth" aria-label="Account">
      <?php if(is_logged_in()): ?>
        <a href="<?php echo ($_SESSION['role'] === 'admin') ? 'admin/index.php' : (($_SESSION['role'] === 'agent') ? 'agent_portal.php' : (($_SESSION['role'] === 'shop_owner') ? 'shop_portal.php' : 'dashboard.php')); ?>" class="reg">
          <i class="fas fa-user-circle me-1"></i> <?php echo e($_SESSION['username']); ?>
        </a>
        <a href="logout.php">Logout</a>
      <?php else: ?>
        <a href="register.php">Sign Up</a>
        <a href="login.php">Login</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<!-- Primary Navigation -->
<div class="menu">
  <div class="wrap">
    <ul id="mainMenu">
      <li><a href="index.php">Home</a></li>
      <li><a href="localguide.php" class="on">Localguide</a></li>
      <li><a href="shopping.php">Shopping</a></li>
      <li><a href="royalty-store.php">Royalty Store</a></li>
      <li><a href="about.php">About Us</a></li>
    </ul>
    <button class="burger" id="burger" aria-label="Menu" aria-expanded="false"><i class="fas fa-bars"></i></button>
  </div>
</div>

<main>
  <!-- Hero / Search Section -->
  <section class="guide-hero">
    <div class="wrap">
      <h1>Local Business &amp; Shop Guide</h1>
      <p>Discover verified local shops, stores, and neighborhood merchants across your region.</p>

      <form action="localguide.php" method="GET" class="search-box">
        <?php if($place): ?><input type="hidden" name="place" value="<?php echo e($place); ?>"><?php endif; ?>
        <input type="text" name="search" placeholder="Search by shop name, service, keywords..." value="<?php echo e($search); ?>">
        <select name="category">
          <option value="">All Categories</option>
          <?php foreach($categories as $cat): ?>
            <option value="<?php echo e($cat); ?>" <?php if($category === $cat) echo 'selected'; ?>><?php echo e($cat); ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit"><i class="fas fa-magnifying-glass"></i> Search</button>
      </form>
    </div>
  </section>

  <!-- Locality Filter Pills -->
  <div class="pills-bar">
    <div class="wrap">
      <div class="pills-scroll">
        <a href="localguide.php<?php echo $search ? '?search='.urlencode($search) : ''; ?>" class="pill <?php echo empty($place) ? 'active' : ''; ?>">All Localities</a>
        <?php foreach($localities as $loc): ?>
          <a href="localguide.php?place=<?php echo urlencode($loc); ?><?php echo $search ? '&search='.urlencode($search) : ''; ?><?php echo $category ? '&category='.urlencode($category) : ''; ?>" class="pill <?php echo ($place === $loc) ? 'active' : ''; ?>">
            <?php echo e($loc); ?>
          </a>
        <?php endforeach; ?>
      </div>
    </div>
  </div>

  <!-- Shop Listings Grid -->
  <div class="guide-content">
    <div class="wrap">
      <?php if($search || $place || $category): ?>
        <div style="margin-bottom: 20px; font-size: 14px; color: var(--muted); display:flex; align-items:center; gap: 10px; flex-wrap:wrap;">
          <span>Showing results for:</span>
          <?php if($place): ?><strong style="background:#eaf6d9;color:var(--deep);padding:2px 8px;border-radius:4px;"><?php echo e($place); ?></strong><?php endif; ?>
          <?php if($category): ?><strong style="background:#eaf6d9;color:var(--deep);padding:2px 8px;border-radius:4px;"><?php echo e($category); ?></strong><?php endif; ?>
          <?php if($search): ?><strong style="background:#eaf6d9;color:var(--deep);padding:2px 8px;border-radius:4px;">"<?php echo e($search); ?>"</strong><?php endif; ?>
          <a href="localguide.php" style="color:var(--red);text-decoration:underline;font-size:13px;margin-left:auto;">Clear All Filters</a>
        </div>
      <?php endif; ?>

      <div class="guide-grid">
        <?php if(empty($shops)): ?>
          <div class="empty-state">
            <i class="fas fa-store-slash"></i>
            <h3>No Local Businesses Found</h3>
            <p>We couldn't find any listings matching your search criteria in this locality.</p>
            <a href="localguide.php">Browse All Listings</a>
          </div>
        <?php else: foreach($shops as $shop): ?>
          <article class="shop-card">
            <div class="shop-cover">
              <?php if(!empty($shop['wallpaper'])): ?>
                <img src="<?php echo BASE_URL . '/' . e($shop['wallpaper']); ?>" alt="<?php echo e($shop['name']); ?> cover">
              <?php endif; ?>
            </div>
            <div class="shop-body">
              <div class="shop-head">
                <div class="shop-logo">
                  <img src="<?php echo !empty($shop['logo']) ? BASE_URL . '/' . e($shop['logo']) : 'https://picsum.photos/seed/' . $shop['id'] . '/100/100'; ?>" alt="<?php echo e($shop['name']); ?> logo">
                </div>
                <div class="shop-title">
                  <h3><?php echo e($shop['name']); ?></h3>
                  <?php
                    $badgeClass = ($shop['type'] === 'privilege') ? 'badge-privilege' : (($shop['type'] === 'classic') ? 'badge-classic' : 'badge-free');
                  ?>
                  <span class="shop-badge <?php echo $badgeClass; ?>"><?php echo e(strtoupper($shop['type'])); ?></span>
                </div>
              </div>

              <p class="shop-desc"><?php echo e($shop['description'] ?: 'Leading local merchant offering quality goods and services in the community.'); ?></p>

              <div class="shop-meta">
                <span class="loc"><i class="fas fa-location-dot"></i> <?php echo e($shop['locality'] ?: 'All Localities'); ?></span>
                <a href="shop_detail.php?id=<?php echo $shop['id']; ?>" class="shop-btn">View Shop</a>
              </div>
            </div>
          </article>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</main>

<!-- Footer matching front layout -->
<footer>
  <div>Powered By : <b>Power Net Associats</b></div>
  <div class="soc">
    <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
    <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
    <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
    <a href="#" aria-label="YouTube"><i class="fab fa-youtube"></i></a>
  </div>
</footer>

<script>
document.getElementById('burger').addEventListener('click',function(){
  var m=document.getElementById('mainMenu'),o=m.classList.toggle('open');
  this.setAttribute('aria-expanded',o);
});
</script>
</body>
</html>
